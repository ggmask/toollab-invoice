<?php

namespace App\Controllers;

use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\InvoiceCalculationService;
use App\Services\TaxCalculationService;
use App\Support\Config;
use App\Support\Database;
use App\Support\FinancialYear;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;
use PDO;

class RecurringInvoiceController
{
    public function index(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $status = $request->get('status', '');
        $where = ["r.business_id = ?"];
        $params = [$businessId];

        if ($status !== '') {
            $where[] = "r.status = ?";
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT r.*, c.name as customer_name, inv.invoice_number as last_invoice_number
            FROM recurring_profiles r
            JOIN customers c ON c.id = r.customer_id
            LEFT JOIN invoices inv ON inv.id = r.last_generated_invoice_id
            WHERE {$whereSql}
            ORDER BY r.next_issue_date ASC, r.id DESC
        ");
        $stmt->execute($params);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('recurring.index', [
            'title' => 'Recurring Invoices',
            'profiles' => $profiles,
            'status' => $status,
        ]));
    }

    public function create(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE business_id = ? AND is_archived = 0 ORDER BY name ASC");
        $cStmt->execute([$businessId]);
        $customers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        $pStmt = $pdo->prepare("SELECT * FROM products WHERE business_id = ? AND is_archived = 0 ORDER BY name ASC");
        $pStmt->execute([$businessId]);
        $products = $pStmt->fetchAll(PDO::FETCH_ASSOC);

        $frequencies = Config::get('constants.frequencies', []);
        $units = Config::get('constants.units', []);
        $gstRates = Config::get('constants.gst_rates', []);

        return new Response(View::render('recurring.create', [
            'title' => 'Create Recurring Profile',
            'business' => $business,
            'customers' => $customers,
            'products' => $products,
            'frequencies' => $frequencies,
            'units' => $units,
            'gstRates' => $gstRates,
            'today' => date('Y-m-d'),
        ]));
    }

    public function store(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $profileName = trim($request->post('profile_name', ''));
        $customerId = (int)$request->post('customer_id', 0);
        $frequency = $request->post('frequency', 'monthly');
        $startDate = $request->post('start_date', date('Y-m-d'));
        $endDate = $request->post('end_date') ?: null;
        $dueDays = max(0, (int)$request->post('due_days', 15));
        $generationMode = $request->post('generation_mode', 'manual');

        if (empty($profileName) || $customerId <= 0) {
            Session::flash('error', 'Profile name and customer are required.');
            return Response::redirect(url('/recurring/create'));
        }

        $rawItems = $request->post('items', []);
        if (empty($rawItems)) {
            Session::flash('error', 'At least one line item is required.');
            return Response::redirect(url('/recurring/create'));
        }

        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stmt = $pdo->prepare("
            INSERT INTO recurring_profiles (
                business_id, customer_id, uuid, profile_name, frequency,
                start_date, end_date, next_issue_date, due_days, generation_mode,
                status, items_json, po_number, notes, terms, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                'active', ?, ?, ?, ?, NOW(), NOW()
            )
        ");

        $stmt->execute([
            $businessId,
            $customerId,
            $uuid,
            $profileName,
            $frequency,
            $startDate,
            $endDate,
            $startDate,
            $dueDays,
            $generationMode,
            json_encode($rawItems, JSON_UNESCAPED_UNICODE),
            trim($request->post('po_number', '')),
            trim($request->post('notes', '')),
            trim($request->post('terms', '')),
        ]);

        $profileId = (int)$pdo->lastInsertId();
        AuditService::log('recurring.created', 'recurring_profile', $profileId, ['profile_name' => $profileName]);

        Session::flash('success', 'Recurring invoice profile created successfully.');
        return Response::redirect(url('/recurring/' . $profileId));
    }

    public function show(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT r.*, c.name as customer_name
            FROM recurring_profiles r
            JOIN customers c ON c.id = r.customer_id
            WHERE r.id = ? AND r.business_id = ?
        ");
        $stmt->execute([$id, $businessId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            Session::flash('error', 'Recurring profile not found.');
            return Response::redirect(url('/recurring'));
        }

        $items = json_decode($profile['items_json'], true) ?: [];

        // Generated occurrences history
        $occStmt = $pdo->prepare("
            SELECT ro.*, inv.invoice_number, inv.total_amount, inv.status as invoice_status
            FROM recurring_occurrences ro
            LEFT JOIN invoices inv ON inv.id = ro.invoice_id
            WHERE ro.recurring_profile_id = ?
            ORDER BY ro.scheduled_date DESC
        ");
        $occStmt->execute([$id]);
        $occurrences = $occStmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('recurring.show', [
            'title' => 'Recurring: ' . $profile['profile_name'],
            'profile' => $profile,
            'items' => $items,
            'occurrences' => $occurrences,
            'business' => $business,
        ]));
    }

    public function toggleStatus(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT id, status FROM recurring_profiles WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            $newStatus = ($profile['status'] === 'active') ? 'paused' : 'active';
            $up = $pdo->prepare("UPDATE recurring_profiles SET status = ?, updated_at = NOW() WHERE id = ?");
            $up->execute([$newStatus, $id]);

            AuditService::log('recurring.status_changed', 'recurring_profile', (int)$id, ['status' => $newStatus]);
            Session::flash('success', "Recurring schedule is now {$newStatus}.");
        }

        return Response::redirect(url('/recurring/' . $id));
    }

    public function generateNow(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $user = Session::get('user');
        $userId = (int)$user['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT r.*, c.name as customer_name, c.billing_state_code
            FROM recurring_profiles r
            JOIN customers c ON c.id = r.customer_id
            WHERE r.id = ? AND r.business_id = ?
        ");
        $stmt->execute([$id, $businessId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            Session::flash('error', 'Recurring profile not found.');
            return Response::redirect(url('/recurring'));
        }

        $scheduledDate = $profile['next_issue_date'];

        // Check idempotency in recurring_occurrences
        $checkStmt = $pdo->prepare("
            SELECT id, invoice_id FROM recurring_occurrences 
            WHERE recurring_profile_id = ? AND scheduled_date = ?
        ");
        $checkStmt->execute([$id, $scheduledDate]);
        if ($checkStmt->fetch()) {
            Session::flash('error', "An invoice has already been generated for scheduled date {$scheduledDate}.");
            return Response::redirect(url('/recurring/' . $id));
        }

        try {
            $newInvoiceId = Database::transaction(function (PDO $pdo) use ($profile, $business, $userId, $scheduledDate) {
                // Fetch customer fresh details
                $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
                $cStmt->execute([$profile['customer_id']]);
                $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

                $items = json_decode($profile['items_json'], true) ?: [];
                $supplierStateCode = $business['state_code'] ?? '';
                $posCode = $customer['billing_state_code'] ?? '';
                $supplyType = TaxCalculationService::determineSupplyType($supplierStateCode, $posCode);

                $calc = InvoiceCalculationService::calculate($items, $supplyType);
                $fy = FinancialYear::forDate($scheduledDate);

                $prefix = $business['invoice_prefix'] ?? 'INV';
                $padding = (int)($business['number_padding'] ?? 4);
                $invoiceNumber = DocumentNumberService::generateNextNumber($profile['business_id'], 'invoice', $prefix, $padding, $fy);

                $dueDate = date('Y-m-d', strtotime($scheduledDate . ' +' . $profile['due_days'] . ' days'));

                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                // Insert invoice
                $ins = $pdo->prepare("
                    INSERT INTO invoices (
                        business_id, customer_id, uuid, invoice_number, financial_year,
                        document_type, status, issue_date, due_date, po_number,
                        place_of_supply_state, place_of_supply_state_code, supply_type,
                        supplier_name, supplier_trade_name, supplier_gstin, supplier_address, supplier_city, supplier_state, supplier_state_code, supplier_pincode, supplier_phone, supplier_email,
                        customer_name, customer_gstin, customer_billing_address, customer_billing_city, customer_billing_state, customer_billing_state_code, customer_billing_pincode, customer_shipping_address, customer_email, customer_phone,
                        subtotal_gross, line_discounts_total, invoice_discount_type, invoice_discount_value, invoice_discount_amount,
                        taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, tax_total,
                        additional_charges, rounding_adjustment, total_amount, paid_amount, balance_due,
                        notes, terms, bank_details, signature_title,
                        issued_at, issued_by_user_id, created_from_recurring_id, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        'tax_invoice', 'issued', ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, 0.00, ?,
                        ?, ?, ?, ?,
                        NOW(), ?, ?, NOW(), NOW()
                    )
                ");

                $ins->execute([
                    $profile['business_id'], $customer['id'], $uuid, $invoiceNumber, $fy,
                    $scheduledDate, $dueDate, $profile['po_number'],
                    $customer['billing_state'], $customer['billing_state_code'], $supplyType,
                    $business['legal_name'], $business['trade_name'] ?? $business['legal_name'], $business['gstin'],
                    trim(($business['address_line1'] ?? '') . ' ' . ($business['address_line2'] ?? '')),
                    $business['city'], $business['state'], $business['state_code'], $business['pincode'], $business['phone'], $business['email'],
                    $customer['name'], $customer['gstin'], $customer['billing_address'], $customer['billing_city'], $customer['billing_state'], $customer['billing_state_code'], $customer['billing_pincode'], $customer['shipping_address'] ?: $customer['billing_address'], $customer['email'], $customer['phone'],
                    $calc['subtotal_gross'], $calc['line_discounts_total'], $calc['invoice_discount_type'], $calc['invoice_discount_value'], $calc['invoice_discount_amount'],
                    $calc['taxable_value'], $calc['cgst_amount'], $calc['sgst_amount'], $calc['igst_amount'], $calc['cess_amount'], $calc['tax_total'],
                    $calc['additional_charges'], $calc['rounding_adjustment'], $calc['total_amount'], $calc['total_amount'],
                    $profile['notes'] ?: $business['default_notes'],
                    $profile['terms'] ?: $business['default_terms'],
                    json_encode([
                        'bank_name' => $business['bank_name'] ?? '',
                        'account_no' => $business['bank_account_no'] ?? '',
                        'ifsc' => $business['bank_ifsc'] ?? '',
                        'branch' => $business['bank_branch'] ?? '',
                        'upi_id' => $business['bank_upi_id'] ?? '',
                    ]),
                    $business['signature_title'] ?? 'Authorized Signatory',
                    $userId,
                    $profile['id']
                ]);

                $invId = (int)$pdo->lastInsertId();

                // Insert items
                $itemIns = $pdo->prepare("
                    INSERT INTO invoice_items (
                        invoice_id, product_id, item_order, name, description, hsn_sac, item_type,
                        quantity, unit, unit_price, gross_amount,
                        discount_type, discount_value, discount_amount, taxable_value,
                        tax_category, gst_rate, cgst_rate, cgst_amount, sgst_rate, sgst_amount,
                        igst_rate, igst_amount, cess_rate, cess_amount, total_tax, line_total
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?
                    )
                ");

                foreach ($calc['items'] as $item) {
                    $itemIns->execute([
                        $invId, $item['product_id'], $item['item_order'], $item['name'], $item['description'], $item['hsn_sac'], $item['item_type'],
                        $item['quantity'], $item['unit'], $item['unit_price'], $item['gross_amount'],
                        $item['discount_type'], $item['discount_value'], $item['discount_amount'], $item['taxable_value'],
                        $item['tax_category'], $item['gst_rate'], $item['cgst_rate'], $item['cgst_amount'], $item['sgst_rate'], $item['sgst_amount'],
                        $item['igst_rate'], $item['igst_amount'], $item['cess_rate'], $item['cess_amount'], $item['total_tax'], $item['line_total'],
                    ]);
                }

                // Record occurrence (idempotency)
                $occIns = $pdo->prepare("
                    INSERT INTO recurring_occurrences (recurring_profile_id, scheduled_date, invoice_id, status, created_at)
                    VALUES (?, ?, ?, 'success', NOW())
                ");
                $occIns->execute([$profile['id'], $scheduledDate, $invId]);

                // Calculate next issue date
                $nextDate = self::calculateNextDate($scheduledDate, $profile['frequency']);
                $newProfileStatus = ($profile['end_date'] && $nextDate > $profile['end_date']) ? 'completed' : 'active';

                $profUp = $pdo->prepare("
                    UPDATE recurring_profiles 
                    SET next_issue_date = ?, last_generated_at = NOW(), last_generated_invoice_id = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $profUp->execute([$nextDate, $invId, $newProfileStatus, $profile['id']]);

                AuditService::log('recurring.invoice_generated', 'recurring_profile', (int)$profile['id'], [
                    'invoice_id' => $invId,
                    'invoice_number' => $invoiceNumber
                ]);

                return $invId;
            });

            Session::flash('success', 'Recurring invoice successfully generated!');
            return Response::redirect(url('/invoices/' . $newInvoiceId));
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to generate recurring invoice: ' . $e->getMessage());
            return Response::redirect(url('/recurring/' . $id));
        }
    }

    public static function calculateNextDate(string $currentDate, string $frequency): string
    {
        $time = strtotime($currentDate);
        return match ($frequency) {
            'weekly' => date('Y-m-d', strtotime('+1 week', $time)),
            'biweekly' => date('Y-m-d', strtotime('+2 weeks', $time)),
            'quarterly' => date('Y-m-d', strtotime('+3 months', $time)),
            'half_yearly' => date('Y-m-d', strtotime('+6 months', $time)),
            'yearly' => date('Y-m-d', strtotime('+1 year', $time)),
            default => date('Y-m-d', strtotime('+1 month', $time)), // monthly
        };
    }
}
