<?php

namespace App\Controllers;

use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\InvoiceCalculationService;
use App\Services\PdfService;
use App\Services\TaxCalculationService;
use App\Support\Config;
use App\Support\Database;
use App\Support\FinancialYear;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;
use PDO;

class EstimateController
{
    public function index(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $search = trim($request->get('search', ''));
        $status = trim($request->get('status', ''));
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = ["e.business_id = ?"];
        $params = [$businessId];

        if ($status !== '') {
            $where[] = "e.status = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = "(e.estimate_number LIKE ? OR e.customer_name LIKE ?)";
            $term = "%{$search}%";
            array_push($params, $term, $term);
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM estimates e WHERE {$whereSql}");
        $countStmt->execute($params);
        $totalEstimates = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT e.*, c.name as current_customer_name
            FROM estimates e
            LEFT JOIN customers c ON c.id = e.customer_id
            WHERE {$whereSql}
            ORDER BY e.estimate_date DESC, e.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($totalEstimates / $perPage);

        return new Response(View::render('estimates.index', [
            'title' => 'Estimates & Quotations',
            'estimates' => $estimates,
            'totalEstimates' => $totalEstimates,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
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

        $states = Config::get('constants.gst_states', []);
        $units = Config::get('constants.units', []);
        $gstRates = Config::get('constants.gst_rates', []);

        $nextEstimateNumber = DocumentNumberService::previewNextNumber(
            $businessId,
            'estimate',
            $business['estimate_prefix'] ?? 'EST',
            (int)($business['number_padding'] ?? 4)
        );

        return new Response(View::render('estimates.create', [
            'title' => 'Create New Estimate',
            'business' => $business,
            'customers' => $customers,
            'products' => $products,
            'states' => $states,
            'units' => $units,
            'gstRates' => $gstRates,
            'nextEstimateNumber' => $nextEstimateNumber,
            'today' => date('Y-m-d'),
            'defaultValidUntil' => date('Y-m-d', strtotime('+30 days')),
        ]));
    }

    public function store(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $customerId = (int)$request->post('customer_id', 0);
        $estimateDate = $request->post('estimate_date', date('Y-m-d'));
        $validUntil = $request->post('valid_until', date('Y-m-d', strtotime('+30 days')));

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$customerId, $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            Session::flash('error', 'Please select a valid customer.');
            return Response::redirect(url('/estimates/create'));
        }

        $rawItems = $request->post('items', []);
        if (empty($rawItems)) {
            Session::flash('error', 'At least one line item is required.');
            return Response::redirect(url('/estimates/create'));
        }

        $posCode = trim($request->post('place_of_supply_state_code', '')) ?: $customer['billing_state_code'];
        $states = Config::get('constants.gst_states', []);
        $posState = $states[$posCode] ?? trim($request->post('place_of_supply_state', ''));

        $supplierStateCode = $business['state_code'] ?? '';
        $supplyType = TaxCalculationService::determineSupplyType($supplierStateCode, $posCode);

        $discountType = $request->post('estimate_discount_type', 'flat');
        $discountValue = max(0.0, (float)$request->post('estimate_discount_value', 0));
        $additionalCharges = max(0.0, (float)$request->post('additional_charges', 0));

        $calc = InvoiceCalculationService::calculate($rawItems, $supplyType, $discountType, $discountValue, $additionalCharges);
        $fy = FinancialYear::forDate($estimateDate);

        try {
            $estimateId = Database::transaction(function (PDO $pdo) use (
                $businessId, $customerId, $customer, $business, $estimateDate, $validUntil,
                $request, $posState, $posCode, $supplyType, $calc, $fy
            ) {
                $prefix = $business['estimate_prefix'] ?? 'EST';
                $padding = (int)($business['number_padding'] ?? 4);
                $estimateNumber = DocumentNumberService::generateNextNumber($businessId, 'estimate', $prefix, $padding, $fy);

                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $stmt = $pdo->prepare("
                    INSERT INTO estimates (
                        business_id, customer_id, uuid, estimate_number, financial_year,
                        status, estimate_date, valid_until,
                        place_of_supply_state, place_of_supply_state_code, supply_type,
                        supplier_name, supplier_trade_name, supplier_gstin, supplier_address, supplier_city, supplier_state, supplier_state_code, supplier_pincode, supplier_phone, supplier_email,
                        customer_name, customer_gstin, customer_billing_address, customer_billing_city, customer_billing_state, customer_billing_state_code, customer_billing_pincode, customer_email, customer_phone,
                        subtotal_gross, line_discounts_total, estimate_discount_type, estimate_discount_value, estimate_discount_amount,
                        taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, tax_total,
                        additional_charges, rounding_adjustment, total_amount,
                        notes, terms, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        'draft', ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, NOW(), NOW()
                    )
                ");

                $stmt->execute([
                    $businessId,
                    $customerId,
                    $uuid,
                    $estimateNumber,
                    $fy,
                    $estimateDate,
                    $validUntil,
                    $posState,
                    $posCode,
                    $supplyType,

                    $business['legal_name'],
                    $business['trade_name'] ?? $business['legal_name'],
                    $business['gstin'] ?? null,
                    trim(($business['address_line1'] ?? '') . ' ' . ($business['address_line2'] ?? '')),
                    $business['city'] ?? '',
                    $business['state'] ?? '',
                    $business['state_code'] ?? '',
                    $business['pincode'] ?? '',
                    $business['phone'] ?? '',
                    $business['email'] ?? '',

                    $customer['name'],
                    $customer['gstin'] ?? null,
                    $customer['billing_address'] ?? '',
                    $customer['billing_city'] ?? '',
                    $customer['billing_state'] ?? '',
                    $customer['billing_state_code'] ?? '',
                    $customer['billing_pincode'] ?? '',
                    $customer['email'] ?? '',
                    $customer['phone'] ?? '',

                    $calc['subtotal_gross'],
                    $calc['line_discounts_total'],
                    $calc['invoice_discount_type'],
                    $calc['invoice_discount_value'],
                    $calc['invoice_discount_amount'],
                    $calc['taxable_value'],
                    $calc['cgst_amount'],
                    $calc['sgst_amount'],
                    $calc['igst_amount'],
                    $calc['cess_amount'],
                    $calc['tax_total'],
                    $calc['additional_charges'],
                    $calc['rounding_adjustment'],
                    $calc['total_amount'],

                    trim($request->post('notes', '')) ?: $business['default_notes'],
                    trim($request->post('terms', '')) ?: $business['default_terms'],
                ]);

                $estId = (int)$pdo->lastInsertId();

                $itemStmt = $pdo->prepare("
                    INSERT INTO estimate_items (
                        estimate_id, product_id, item_order, name, description, hsn_sac, item_type,
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
                    $itemStmt->execute([
                        $estId,
                        $item['product_id'],
                        $item['item_order'],
                        $item['name'],
                        $item['description'],
                        $item['hsn_sac'],
                        $item['item_type'],
                        $item['quantity'],
                        $item['unit'],
                        $item['unit_price'],
                        $item['gross_amount'],
                        $item['discount_type'],
                        $item['discount_value'],
                        $item['discount_amount'],
                        $item['taxable_value'],
                        $item['tax_category'],
                        $item['gst_rate'],
                        $item['cgst_rate'],
                        $item['cgst_amount'],
                        $item['sgst_rate'],
                        $item['sgst_amount'],
                        $item['igst_rate'],
                        $item['igst_amount'],
                        $item['cess_rate'],
                        $item['cess_amount'],
                        $item['total_tax'],
                        $item['line_total'],
                    ]);
                }

                AuditService::log('estimate.created', 'estimate', $estId, ['estimate_number' => $estimateNumber]);
                return $estId;
            });

            Session::flash('success', 'Estimate created successfully.');
            return Response::redirect(url('/estimates/' . $estimateId));
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to create estimate: ' . $e->getMessage());
            return Response::redirect(url('/estimates/create'));
        }
    }

    public function show(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM estimates WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $estimate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$estimate) {
            Session::flash('error', 'Estimate not found.');
            return Response::redirect(url('/estimates'));
        }

        $iStmt = $pdo->prepare("SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if converted
        $linkedInvoice = null;
        if (!empty($estimate['converted_invoice_id'])) {
            $invStmt = $pdo->prepare("SELECT id, invoice_number, status, total_amount FROM invoices WHERE id = ?");
            $invStmt->execute([$estimate['converted_invoice_id']]);
            $linkedInvoice = $invStmt->fetch(PDO::FETCH_ASSOC);
        }

        return new Response(View::render('estimates.show', [
            'title' => 'Estimate ' . $estimate['estimate_number'],
            'estimate' => $estimate,
            'items' => $items,
            'business' => $business,
            'linkedInvoice' => $linkedInvoice,
        ]));
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $newStatus = $request->post('status', '');
        $allowed = ['sent', 'accepted', 'rejected', 'expired'];

        if (!in_array($newStatus, $allowed)) {
            Session::flash('error', 'Invalid status.');
            return Response::redirect(url('/estimates/' . $id));
        }

        $up = $pdo->prepare("UPDATE estimates SET status = ?, updated_at = NOW() WHERE id = ? AND business_id = ? AND status != 'converted'");
        $up->execute([$newStatus, $id, $businessId]);

        AuditService::log('estimate.status_changed', 'estimate', (int)$id, ['status' => $newStatus]);
        Session::flash('success', 'Estimate status updated to ' . ucfirst($newStatus) . '.');

        return Response::redirect(url('/estimates/' . $id));
    }

    public function convertToInvoice(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM estimates WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $estimate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$estimate) {
            Session::flash('error', 'Estimate not found.');
            return Response::redirect(url('/estimates'));
        }

        if ($estimate['status'] === 'converted' || !empty($estimate['converted_invoice_id'])) {
            Session::flash('error', 'This estimate has already been converted to an invoice.');
            return Response::redirect(url('/estimates/' . $id));
        }

        $iStmt = $pdo->prepare("SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        try {
            $invoiceId = Database::transaction(function (PDO $pdo) use ($estimate, $items, $businessId, $business, $id) {
                $today = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime('+' . ($business['default_due_days'] ?? 15) . ' days'));
                $fy = FinancialYear::forDate($today);

                $prefix = $business['invoice_prefix'] ?? 'INV';
                $padding = (int)($business['number_padding'] ?? 4);
                $invoiceNumber = DocumentNumberService::previewNextNumber($businessId, 'invoice', $prefix, $padding, $fy);

                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $ins = $pdo->prepare("
                    INSERT INTO invoices (
                        business_id, customer_id, uuid, invoice_number, financial_year,
                        document_type, status, issue_date, due_date, payment_terms,
                        place_of_supply_state, place_of_supply_state_code, supply_type,
                        supplier_name, supplier_trade_name, supplier_gstin, supplier_address, supplier_city, supplier_state, supplier_state_code, supplier_pincode, supplier_phone, supplier_email,
                        customer_name, customer_gstin, customer_billing_address, customer_billing_city, customer_billing_state, customer_billing_state_code, customer_billing_pincode, customer_shipping_address, customer_email, customer_phone,
                        subtotal_gross, line_discounts_total, invoice_discount_type, invoice_discount_value, invoice_discount_amount,
                        taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, tax_total,
                        additional_charges, rounding_adjustment, total_amount, paid_amount, balance_due,
                        notes, terms, bank_details, signature_title,
                        created_from_estimate_id, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        'tax_invoice', 'draft', ?, ?, 'Net 15',
                        ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, 0.00, ?,
                        ?, ?, ?, ?,
                        ?, NOW(), NOW()
                    )
                ");

                $ins->execute([
                    $businessId, $estimate['customer_id'], $uuid, $invoiceNumber, $fy,
                    $today, $dueDate,
                    $estimate['place_of_supply_state'], $estimate['place_of_supply_state_code'], $estimate['supply_type'],
                    $estimate['supplier_name'], $estimate['supplier_trade_name'], $estimate['supplier_gstin'], $estimate['supplier_address'], $estimate['supplier_city'], $estimate['supplier_state'], $estimate['supplier_state_code'], $estimate['supplier_pincode'], $estimate['supplier_phone'], $estimate['supplier_email'],
                    $estimate['customer_name'], $estimate['customer_gstin'], $estimate['customer_billing_address'], $estimate['customer_billing_city'], $estimate['customer_billing_state'], $estimate['customer_billing_state_code'], $estimate['customer_billing_pincode'], $estimate['customer_billing_address'], $estimate['customer_email'], $estimate['customer_phone'],
                    $estimate['subtotal_gross'], $estimate['line_discounts_total'], $estimate['estimate_discount_type'], $estimate['estimate_discount_value'], $estimate['estimate_discount_amount'],
                    $estimate['taxable_value'], $estimate['cgst_amount'], $estimate['sgst_amount'], $estimate['igst_amount'], $estimate['cess_amount'], $estimate['tax_total'],
                    $estimate['additional_charges'], $estimate['rounding_adjustment'], $estimate['total_amount'], $estimate['total_amount'],
                    $estimate['notes'], $estimate['terms'],
                    json_encode([
                        'bank_name' => $business['bank_name'] ?? '',
                        'account_no' => $business['bank_account_no'] ?? '',
                        'ifsc' => $business['bank_ifsc'] ?? '',
                        'branch' => $business['bank_branch'] ?? '',
                        'upi_id' => $business['bank_upi_id'] ?? '',
                    ]),
                    $business['signature_title'] ?? 'Authorized Signatory',
                    $id
                ]);

                $newInvId = (int)$pdo->lastInsertId();

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

                foreach ($items as $it) {
                    $itemIns->execute([
                        $newInvId, $it['product_id'], $it['item_order'], $it['name'], $it['description'], $it['hsn_sac'], $it['item_type'],
                        $it['quantity'], $it['unit'], $it['unit_price'], $it['gross_amount'],
                        $it['discount_type'], $it['discount_value'], $it['discount_amount'], $it['taxable_value'],
                        $it['tax_category'], $it['gst_rate'], $it['cgst_rate'], $it['cgst_amount'], $it['sgst_rate'], $it['sgst_amount'],
                        $it['igst_rate'], $it['igst_amount'], $it['cess_rate'], $it['cess_amount'], $it['total_tax'], $it['line_total'],
                    ]);
                }

                // Update Estimate status to converted
                $estUp = $pdo->prepare("
                    UPDATE estimates 
                    SET status = 'converted', converted_invoice_id = ?, converted_at = NOW(), updated_at = NOW()
                    WHERE id = ?
                ");
                $estUp->execute([$newInvId, $id]);

                AuditService::log('estimate.converted_to_invoice', 'estimate', (int)$id, [
                    'invoice_id' => $newInvId,
                    'invoice_number' => $invoiceNumber
                ]);

                return $newInvId;
            });

            Session::flash('success', 'Estimate successfully converted into a new draft invoice.');
            return Response::redirect(url('/invoices/' . $invoiceId));
        } catch (\Throwable $e) {
            Session::flash('error', 'Conversion failed: ' . $e->getMessage());
            return Response::redirect(url('/estimates/' . $id));
        }
    }

    public function downloadPdf(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM estimates WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $estimate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$estimate) {
            Session::flash('error', 'Estimate not found.');
            return Response::redirect(url('/estimates'));
        }

        $iStmt = $pdo->prepare("SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $cStmt->execute([$estimate['customer_id']]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $pdfContent = PdfService::estimatePdf($estimate, $items, $business, $customer);
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $estimate['estimate_number']) . '.pdf';

        return Response::download($pdfContent, $filename, 'application/pdf');
    }

    public function printView(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM estimates WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $estimate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$estimate) {
            Session::flash('error', 'Estimate not found.');
            return Response::redirect(url('/estimates'));
        }

        $iStmt = $pdo->prepare("SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $cStmt->execute([$estimate['customer_id']]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return new Response(View::render('estimates.print', [
            'estimate' => $estimate,
            'items' => $items,
            'business' => $business,
            'customer' => $customer,
        ], null));
    }
}
