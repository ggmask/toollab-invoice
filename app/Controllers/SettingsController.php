<?php

namespace App\Controllers;

use App\Services\AuditService;
use App\Support\Config;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;
use PDO;

class SettingsController
{
    public function business(Request $request): Response
    {
        $business = Session::get('business');
        $states = Config::get('constants.gst_states', []);

        return new Response(View::render('settings.business', [
            'title' => 'Business Profile & Bank Details',
            'business' => $business,
            'states' => $states,
        ]));
    }

    public function updateBusiness(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $legalName = trim($request->post('legal_name', ''));
        if (empty($legalName)) {
            Session::flash('error', 'Legal Business Name is required.');
            return Response::redirect(url('/settings/business'));
        }

        $stateCode = trim($request->post('state_code', ''));
        $states = Config::get('constants.gst_states', []);
        $stateName = $states[$stateCode] ?? trim($request->post('state', ''));

        // Handle logo upload and removal
        $logoUrl = $business['logo_url'] ?? null;

        if ($request->post('remove_logo')) {
            if ($logoUrl && file_exists(__DIR__ . '/../../public/' . $logoUrl)) {
                @unlink(__DIR__ . '/../../public/' . $logoUrl);
            }
            $logoUrl = null;
        }

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $maxBytes = 2 * 1024 * 1024; // 2MB
            if ($file['size'] > $maxBytes) {
                Session::flash('error', 'Logo file size exceeds 2 MB limit.');
                return Response::redirect(url('/settings/business'));
            }

            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg'
            ];

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            if (!isset($allowedMimes[$mime])) {
                Session::flash('error', 'Invalid logo image format. Allowed: PNG, JPG, WebP, SVG.');
                return Response::redirect(url('/settings/business'));
            }

            $ext = $allowedMimes[$mime];
            $filename = 'logo_' . $businessId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../../public/uploads/logos/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                if ($logoUrl && file_exists(__DIR__ . '/../../public/' . $logoUrl)) {
                    @unlink(__DIR__ . '/../../public/' . $logoUrl);
                }
                $logoUrl = 'uploads/logos/' . $filename;
            }
        }

        $up = $pdo->prepare("
            UPDATE businesses SET
                legal_name = ?, trade_name = ?, business_type = ?,
                address_line1 = ?, address_line2 = ?, city = ?, state = ?, state_code = ?, pincode = ?,
                phone = ?, email = ?, website = ?, logo_url = ?,
                bank_name = ?, bank_account_no = ?, bank_ifsc = ?, bank_branch = ?, bank_upi_id = ?,
                signature_title = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $up->execute([
            $legalName,
            trim($request->post('trade_name', '')) ?: $legalName,
            $request->post('business_type', 'Proprietorship'),
            trim($request->post('address_line1', '')),
            trim($request->post('address_line2', '')),
            trim($request->post('city', '')),
            $stateName,
            $stateCode ?: null,
            trim($request->post('pincode', '')),
            trim($request->post('phone', '')),
            trim($request->post('email', '')),
            trim($request->post('website', '')),
            $logoUrl,
            trim($request->post('bank_name', '')),
            trim($request->post('bank_account_no', '')),
            strtoupper(trim($request->post('bank_ifsc', ''))),
            trim($request->post('bank_branch', '')),
            trim($request->post('bank_upi_id', '')),
            trim($request->post('signature_title', 'Authorized Signatory')),
            $businessId
        ]);

        // Refresh session
        $bFreshStmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
        $bFreshStmt->execute([$businessId]);
        $bFresh = $bFreshStmt->fetch(PDO::FETCH_ASSOC);
        $bFresh['role'] = $business['role'] ?? 'owner';
        Session::set('business', $bFresh);

        AuditService::log('settings.business_updated', 'business', $businessId);
        Session::flash('success', 'Business profile and banking settings updated successfully.');

        return Response::redirect(url('/settings/business'));
    }

    public function gst(Request $request): Response
    {
        $business = Session::get('business');
        $states = Config::get('constants.gst_states', []);

        return new Response(View::render('settings.gst', [
            'title' => 'GST & Tax Configuration',
            'business' => $business,
            'states' => $states,
        ]));
    }

    public function updateGst(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $gstStatus = $request->post('gst_status', 'registered_regular');
        $allowedStatuses = ['registered_regular', 'registered_composition', 'unregistered', 'overseas', 'sez'];
        if (!in_array($gstStatus, $allowedStatuses, true)) {
            $gstStatus = ($gstStatus === 'composition') ? 'registered_composition' : 'registered_regular';
        }
        $gstin = strtoupper(trim($request->post('gstin', '')));
        $pan = strtoupper(trim($request->post('pan', '')));

        if ($gstStatus === 'registered_regular' && !empty($gstin) && strlen($gstin) === 15) {
            $stateCode = substr($gstin, 0, 2);
            $pan = substr($gstin, 2, 10);
        } else {
            $stateCode = trim($request->post('state_code', ''));
        }

        $states = Config::get('constants.gst_states', []);
        $stateName = $states[$stateCode] ?? '';

        $up = $pdo->prepare("
            UPDATE businesses SET
                gst_status = ?, gstin = ?, pan = ?, state_code = ?, state = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $up->execute([$gstStatus, $gstin ?: null, $pan ?: null, $stateCode ?: null, $stateName ?: $business['state'], $businessId]);

        $bFreshStmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
        $bFreshStmt->execute([$businessId]);
        $bFresh = $bFreshStmt->fetch(PDO::FETCH_ASSOC);
        $bFresh['role'] = $business['role'] ?? 'owner';
        Session::set('business', $bFresh);

        AuditService::log('settings.gst_updated', 'business', $businessId, ['gstin' => $gstin]);
        Session::flash('success', 'GST settings updated successfully.');

        return Response::redirect(url('/settings/gst'));
    }

    public function invoice(Request $request): Response
    {
        $business = Session::get('business');
        $paymentTerms = Config::get('constants.payment_terms', []);

        return new Response(View::render('settings.invoice', [
            'title' => 'Document & Numbering Settings',
            'business' => $business,
            'paymentTerms' => $paymentTerms,
        ]));
    }

    public function updateInvoice(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $invPrefix = strtoupper(trim($request->post('invoice_prefix', 'INV')));
        $estPrefix = strtoupper(trim($request->post('estimate_prefix', 'EST')));
        $payPrefix = strtoupper(trim($request->post('payment_prefix', 'PAY')));
        $padding = max(2, min(8, (int)$request->post('number_padding', 4)));

        if (strlen($invPrefix) > 6) {
            Session::flash('error', 'Invoice prefix cannot exceed 6 characters to comply with GST Rule 46 (max 16 characters total).');
            return Response::redirect(url('/settings/invoice'));
        }

        $up = $pdo->prepare("
            UPDATE businesses SET
                invoice_prefix = ?, estimate_prefix = ?, payment_prefix = ?, number_padding = ?,
                default_payment_terms = ?, default_due_days = ?,
                default_notes = ?, default_terms = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $up->execute([
            $invPrefix,
            $estPrefix,
            $payPrefix,
            $padding,
            trim($request->post('default_payment_terms', 'Net 15')),
            max(0, (int)$request->post('default_due_days', 15)),
            trim($request->post('default_notes', '')),
            trim($request->post('default_terms', '')),
            $businessId
        ]);

        $bFreshStmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
        $bFreshStmt->execute([$businessId]);
        $bFresh = $bFreshStmt->fetch(PDO::FETCH_ASSOC);
        $bFresh['role'] = $business['role'] ?? 'owner';
        Session::set('business', $bFresh);

        AuditService::log('settings.invoice_updated', 'business', $businessId);
        Session::flash('success', 'Document numbering and default terms updated successfully.');

        return Response::redirect(url('/settings/invoice'));
    }

    public function taxRates(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM tax_rates WHERE business_id = ? ORDER BY rate ASC");
        $stmt->execute([$businessId]);
        $taxRates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('settings.tax-rates', [
            'title' => 'GST Tax Rates',
            'taxRates' => $taxRates,
        ]));
    }

    public function storeTaxRate(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $name = trim($request->post('name', ''));
        $rate = (float)$request->post('rate', 0);
        $taxCategory = $request->post('tax_category', 'taxable');

        if (empty($name)) {
            Session::flash('error', 'Tax rate name is required.');
            return Response::redirect(url('/settings/tax-rates'));
        }

        $ins = $pdo->prepare("
            INSERT INTO tax_rates (business_id, name, rate, tax_category, is_active, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $ins->execute([$businessId, $name, $rate, $taxCategory]);

        AuditService::log('settings.tax_rate_added', 'tax_rate', (int)$pdo->lastInsertId(), ['name' => $name, 'rate' => $rate]);
        Session::flash('success', "Tax rate '{$name}' added.");

        return Response::redirect(url('/settings/tax-rates'));
    }

    public function account(Request $request): Response
    {
        $user = Session::get('user');

        return new Response(View::render('settings.account', [
            'title' => 'My Account & Security',
            'user' => $user,
        ]));
    }

    public function updateAccount(Request $request): Response
    {
        $user = Session::get('user');
        $userId = (int)$user['id'];
        $pdo = Database::pdo();

        $name = trim($request->post('name', ''));
        $currentPassword = (string)$request->post('current_password', '');
        $newPassword = (string)$request->post('new_password', '');

        if (empty($name)) {
            Session::flash('error', 'Name cannot be empty.');
            return Response::redirect(url('/settings/account'));
        }

        // Check if updating password
        if (!empty($newPassword)) {
            $uStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $hash = $uStmt->fetchColumn();

            if (!password_verify($currentPassword, $hash)) {
                Session::flash('error', 'Current password is incorrect.');
                return Response::redirect(url('/settings/account'));
            }

            if (strlen($newPassword) < 6) {
                Session::flash('error', 'New password must be at least 6 characters.');
                return Response::redirect(url('/settings/account'));
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $up = $pdo->prepare("UPDATE users SET name = ?, password_hash = ?, updated_at = NOW() WHERE id = ?");
            $up->execute([$name, $newHash, $userId]);
            Session::flash('success', 'Profile and password updated successfully.');
        } else {
            $up = $pdo->prepare("UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?");
            $up->execute([$name, $userId]);
            Session::flash('success', 'Profile updated successfully.');
        }

        $user['name'] = $name;
        Session::set('user', $user);
        AuditService::log('user.profile_updated', 'user', $userId);

        return Response::redirect(url('/settings/account'));
    }
}
