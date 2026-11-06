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

class InvoiceController
{
    public function index(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $search = trim($request->get('search', ''));
        $status = trim($request->get('status', ''));
        $customerId = $request->get('customer_id', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = ["i.business_id = ?"];
        $params = [$businessId];

        if ($status !== '') {
            if ($status === 'overdue') {
                $where[] = "i.status IN ('issued', 'partially_paid') AND i.due_date < CURDATE()";
            } else {
                $where[] = "i.status = ?";
                $params[] = $status;
            }
        }

        if ($customerId !== '') {
            $where[] = "i.customer_id = ?";
            $params[] = (int)$customerId;
        }

        if ($startDate !== '') {
            $where[] = "i.issue_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate !== '') {
            $where[] = "i.issue_date <= ?";
            $params[] = $endDate;
        }

        if ($search !== '') {
            $where[] = "(i.invoice_number LIKE ? OR i.customer_name LIKE ? OR i.customer_gstin LIKE ? OR i.po_number LIKE ?)";
            $term = "%{$search}%";
            array_push($params, $term, $term, $term, $term);
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM invoices i WHERE {$whereSql}");
        $countStmt->execute($params);
        $totalInvoices = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT i.*, c.name as current_customer_name
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE {$whereSql}
            ORDER BY i.issue_date DESC, i.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Customers list for filter dropdown
        $cStmt = $pdo->prepare("SELECT id, name FROM customers WHERE business_id = ? ORDER BY name ASC");
        $cStmt->execute([$businessId]);
        $customers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($totalInvoices / $perPage);

        return new Response(View::render('invoices.index', [
            'title' => 'Invoices',
            'invoices' => $invoices,
            'customers' => $customers,
            'totalInvoices' => $totalInvoices,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'status' => $status,
            'customerId' => $customerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
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
        $defaultGstRates = Config::get('constants.gst_rates', [0, 5, 12, 18, 28]);

        $trStmt = $pdo->prepare("SELECT rate FROM tax_rates WHERE business_id = ? AND is_active = 1");
        $trStmt->execute([$businessId]);
        $customRates = $trStmt->fetchAll(PDO::FETCH_COLUMN);
        $gstRates = array_unique(array_merge($defaultGstRates, array_map('floatval', $customRates)));
        sort($gstRates);
        $paymentTerms = Config::get('constants.payment_terms', []);

        $nextInvoiceNumber = DocumentNumberService::previewNextNumber(
            $businessId,
            'invoice',
            $business['invoice_prefix'] ?? 'INV',
            (int)($business['number_padding'] ?? 4)
        );

        $selectedCustomerId = $request->get('customer_id');

        return new Response(View::render('invoices.create', [
            'title' => 'Create New Invoice',
            'business' => $business,
            'customers' => $customers,
            'products' => $products,
            'states' => $states,
            'units' => $units,
            'gstRates' => $gstRates,
            'paymentTerms' => $paymentTerms,
            'nextInvoiceNumber' => $nextInvoiceNumber,
            'selectedCustomerId' => $selectedCustomerId,
            'today' => date('Y-m-d'),
            'defaultDueDate' => date('Y-m-d', strtotime('+' . ($business['default_due_days'] ?? 15) . ' days')),
        ]));
    }

    public function store(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $user = Session::get('user');
        $userId = (int)$user['id'];
        $pdo = Database::pdo();

        $customerId = (int)$request->post('customer_id', 0);
        $issueDate = $request->post('issue_date', date('Y-m-d'));
        $dueDate = $request->post('due_date', date('Y-m-d'));
        $submitAction = $request->post('submit_action', 'draft'); // 'draft' or 'issue'

        // Customer verification
        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$customerId, $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            Session::flash('error', 'Please select a valid customer.');
            return Response::redirect(url('/invoices/create'));
        }

        // Raw items from POST
        $rawItems = $request->post('items', []);
        if (empty($rawItems) || !is_array($rawItems)) {
            Session::flash('error', 'At least one line item is required.');
            return Response::redirect(url('/invoices/create'));
        }

        // Place of supply & supply type
        $posCode = trim($request->post('place_of_supply_state_code', '')) ?: $customer['billing_state_code'];
        $states = Config::get('constants.gst_states', []);
        $posState = $states[$posCode] ?? trim($request->post('place_of_supply_state', ''));

        $supplierStateCode = $business['state_code'] ?? '';
        $supplyType = TaxCalculationService::determineSupplyType($supplierStateCode, $posCode);

        // Discounts and additional charges
        $discountType = $request->post('invoice_discount_type', 'flat');
        $discountValue = max(0.0, (float)$request->post('invoice_discount_value', 0));
        $additionalCharges = max(0.0, (float)$request->post('additional_charges', 0));
        $additionalChargesLabel = trim($request->post('additional_charges_label', 'Shipping / Other Charges'));
        $additionalChargesGstRate = max(0.0, (float)$request->post('additional_charges_gst_rate', 0));
        $isReverseCharge = (bool)$request->post('is_reverse_charge', false);

        // Calculation Service
        $calc = InvoiceCalculationService::calculate(
            $rawItems,
            $supplyType,
            $discountType,
            $discountValue,
            $additionalCharges,
            $additionalChargesGstRate,
            $isReverseCharge
        );

        $status = ($submitAction === 'issue') ? 'issued' : 'draft';
        $fy = FinancialYear::forDate($issueDate);

        try {
            $invoiceId = Database::transaction(function (PDO $pdo) use (
                $businessId, $customerId, $customer, $business, $issueDate, $dueDate,
                $request, $posState, $posCode, $supplyType, $calc, $status, $fy, $userId,
                $additionalChargesLabel, $submitAction
            ) {
                // Generate Invoice Number (transaction-safe)
                $prefix = $business['invoice_prefix'] ?? 'INV';
                $padding = (int)($business['number_padding'] ?? 4);

                if ($submitAction === 'issue') {
                    $invoiceNumber = DocumentNumberService::generateNextNumber($businessId, 'invoice', $prefix, $padding, $fy);
                    $issuedAt = date('Y-m-d H:i:s');
                    $issuedBy = $userId;
                } else {
                    $invoiceNumber = DocumentNumberService::generateDraftNumber($businessId, $padding, $fy);
                    $issuedAt = null;
                    $issuedBy = null;
                }

                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                // Insert Invoice Header
                $stmt = $pdo->prepare("
                    INSERT INTO invoices (
                        business_id, customer_id, uuid, invoice_number, financial_year,
                        document_type, status, issue_date, due_date, po_number, payment_terms,
                        place_of_supply_state, place_of_supply_state_code, supply_type, is_reverse_charge,
                        supplier_name, supplier_trade_name, supplier_gstin, supplier_address, supplier_city, supplier_state, supplier_state_code, supplier_pincode, supplier_phone, supplier_email,
                        customer_name, customer_gstin, customer_billing_address, customer_billing_city, customer_billing_state, customer_billing_state_code, customer_billing_pincode, customer_shipping_address, customer_email, customer_phone,
                        subtotal_gross, line_discounts_total, invoice_discount_type, invoice_discount_value, invoice_discount_amount,
                        taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, tax_total,
                        additional_charges, additional_charges_label, rounding_adjustment, total_amount, paid_amount, balance_due,
                        notes, terms, bank_details, signature_title,
                        issued_at, issued_by_user_id, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, 0.00, ?,
                        ?, ?, ?, ?,
                        ?, ?, NOW(), NOW()
                    )
                ");

                $docType = ($business['gst_status'] === 'registered_composition' || $business['gst_status'] === 'unregistered')
                    ? 'bill_of_supply'
                    : 'tax_invoice';

                $stmt->execute([
                    $businessId,
                    $customerId,
                    $uuid,
                    $invoiceNumber,
                    $fy,
                    $docType,
                    $status,
                    $issueDate,
                    $dueDate,
                    trim($request->post('po_number', '')),
                    trim($request->post('payment_terms', 'Net 15')),
                    $posState,
                    $posCode,
                    $supplyType,
                    $request->post('is_reverse_charge') ? 1 : 0,

                    // Supplier Snapshot
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

                    // Customer Snapshot
                    $customer['name'],
                    $customer['gstin'] ?? null,
                    $customer['billing_address'] ?? '',
                    $customer['billing_city'] ?? '',
                    $customer['billing_state'] ?? '',
                    $customer['billing_state_code'] ?? '',
                    $customer['billing_pincode'] ?? '',
                    $customer['shipping_address'] ?? '',
                    $customer['email'] ?? '',
                    $customer['phone'] ?? '',

                    // Financial totals
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
                    $additionalChargesLabel,
                    $calc['rounding_adjustment'],
                    $calc['total_amount'],
                    $calc['total_amount'], // balance_due initially equals total_amount

                    // Notes & bank
                    trim($request->post('notes', '')) ?: $business['default_notes'],
                    trim($request->post('terms', '')) ?: $business['default_terms'],
                    json_encode([
                        'bank_name' => $business['bank_name'] ?? '',
                        'account_no' => $business['bank_account_no'] ?? '',
                        'ifsc' => $business['bank_ifsc'] ?? '',
                        'branch' => $business['bank_branch'] ?? '',
                        'upi_id' => $business['bank_upi_id'] ?? '',
                    ]),
                    $business['signature_title'] ?? 'Authorized Signatory',

                    $issuedAt,
                    $issuedBy,
                ]);

                $invId = (int)$pdo->lastInsertId();

                // Insert Line Items
                $itemStmt = $pdo->prepare("
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
                    $itemStmt->execute([
                        $invId,
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

                AuditService::log('invoice.created', 'invoice', $invId, [
                    'invoice_number' => $invoiceNumber,
                    'status' => $status,
                    'total' => $calc['total_amount'],
                ]);

                return $invId;
            });

            Session::flash('success', $status === 'issued' ? 'Invoice issued successfully!' : 'Invoice saved as draft.');
            return Response::redirect(url('/invoices/' . $invoiceId));
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to save invoice: ' . $e->getMessage());
            return Response::redirect(url('/invoices/create'));
        }
    }

    public function show(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            return Response::redirect(url('/invoices'));
        }

        // Fetch line items
        $iStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch payments allocated to this invoice
        $pStmt = $pdo->prepare("
            SELECT pa.amount as allocated_amount, pa.created_at, p.id as payment_id, p.payment_number, p.payment_date, p.payment_method, p.reference_number, p.status as payment_status
            FROM payment_allocations pa
            JOIN payments p ON p.id = pa.payment_id
            WHERE pa.invoice_id = ?
            ORDER BY pa.created_at DESC
        ");
        $pStmt->execute([$id]);
        $payments = $pStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch credit notes issued against this invoice
        $cnStmt = $pdo->prepare("
            SELECT id, credit_note_number, credit_note_date, total_amount, applied_amount, status, reason, reason_description, created_at
            FROM credit_notes
            WHERE invoice_id = ? AND business_id = ?
            ORDER BY credit_note_date DESC, id DESC
        ");
        $cnStmt->execute([$id, $businessId]);
        $creditNotes = $cnStmt->fetchAll(PDO::FETCH_ASSOC);

        $bankDetails = !empty($invoice['bank_details']) ? json_decode($invoice['bank_details'], true) : [];

        return new Response(View::render('invoices.show', [
            'title' => 'Invoice ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => $items,
            'payments' => $payments,
            'creditNotes' => $creditNotes,
            'bankDetails' => $bankDetails,
            'business' => $business,
        ]));
    }

    public function edit(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            return Response::redirect(url('/invoices'));
        }

        if ($invoice['status'] !== 'draft') {
            Session::flash('error', 'Only draft invoices can be edited directly. Issued documents must be preserved for audit.');
            return Response::redirect(url('/invoices/' . $id));
        }

        $iStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE business_id = ? AND is_archived = 0 ORDER BY name ASC");
        $cStmt->execute([$businessId]);
        $customers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        $pStmt = $pdo->prepare("SELECT * FROM products WHERE business_id = ? AND is_archived = 0 ORDER BY name ASC");
        $pStmt->execute([$businessId]);
        $products = $pStmt->fetchAll(PDO::FETCH_ASSOC);

        $states = Config::get('constants.gst_states', []);
        $units = Config::get('constants.units', []);
        $defaultGstRates = Config::get('constants.gst_rates', [0, 5, 12, 18, 28]);

        $trStmt = $pdo->prepare("SELECT rate FROM tax_rates WHERE business_id = ? AND is_active = 1");
        $trStmt->execute([$businessId]);
        $customRates = $trStmt->fetchAll(PDO::FETCH_COLUMN);
        $gstRates = array_unique(array_merge($defaultGstRates, array_map('floatval', $customRates)));
        sort($gstRates);
        $paymentTerms = Config::get('constants.payment_terms', []);

        return new Response(View::render('invoices.edit', [
            'title' => 'Edit Draft ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => $items,
            'customers' => $customers,
            'products' => $products,
            'states' => $states,
            'units' => $units,
            'gstRates' => $gstRates,
            'paymentTerms' => $paymentTerms,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $user = Session::get('user');
        $userId = (int)$user['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice || $invoice['status'] !== 'draft') {
            Session::flash('error', 'Invoice cannot be edited.');
            return Response::redirect(url('/invoices'));
        }

        $customerId = (int)$request->post('customer_id', $invoice['customer_id']);
        $issueDate = $request->post('issue_date', $invoice['issue_date']);
        $dueDate = $request->post('due_date', $invoice['due_date']);
        $submitAction = $request->post('submit_action', 'draft');

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$customerId, $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

        $rawItems = $request->post('items', []);
        if (empty($rawItems)) {
            Session::flash('error', 'At least one line item is required.');
            return Response::redirect(url('/invoices/' . $id . '/edit'));
        }

        $posCode = trim($request->post('place_of_supply_state_code', '')) ?: $customer['billing_state_code'];
        $states = Config::get('constants.gst_states', []);
        $posState = $states[$posCode] ?? trim($request->post('place_of_supply_state', ''));

        $supplierStateCode = $business['state_code'] ?? '';
        $supplyType = TaxCalculationService::determineSupplyType($supplierStateCode, $posCode);

        $discountType = $request->post('invoice_discount_type', 'flat');
        $discountValue = max(0.0, (float)$request->post('invoice_discount_value', 0));
        $additionalCharges = max(0.0, (float)$request->post('additional_charges', 0));
        $additionalChargesLabel = trim($request->post('additional_charges_label', 'Shipping / Other Charges'));
        $additionalChargesGstRate = max(0.0, (float)$request->post('additional_charges_gst_rate', 0));
        $isReverseCharge = (bool)$request->post('is_reverse_charge', false);

        $calc = InvoiceCalculationService::calculate(
            $rawItems,
            $supplyType,
            $discountType,
            $discountValue,
            $additionalCharges,
            $additionalChargesGstRate,
            $isReverseCharge
        );
        $fy = FinancialYear::forDate($issueDate);

        Database::transaction(function (PDO $pdo) use (
            $id, $businessId, $customerId, $customer, $business, $issueDate, $dueDate,
            $request, $posState, $posCode, $supplyType, $calc, $submitAction, $fy, $userId,
            $additionalChargesLabel, $invoice
        ) {
            $status = ($submitAction === 'issue') ? 'issued' : 'draft';
            $invoiceNumber = $invoice['invoice_number'];
            $issuedAt = $invoice['issued_at'];
            $issuedBy = $invoice['issued_by_user_id'];

            if ($submitAction === 'issue' && $invoice['status'] === 'draft') {
                $prefix = $business['invoice_prefix'] ?? 'INV';
                $padding = (int)($business['number_padding'] ?? 4);
                $invoiceNumber = DocumentNumberService::generateNextNumber($businessId, 'invoice', $prefix, $padding, $fy);
                $issuedAt = date('Y-m-d H:i:s');
                $issuedBy = $userId;
            }

            $uStmt = $pdo->prepare("
                UPDATE invoices SET
                    customer_id = ?, invoice_number = ?, financial_year = ?, status = ?,
                    issue_date = ?, due_date = ?, po_number = ?, payment_terms = ?,
                    place_of_supply_state = ?, place_of_supply_state_code = ?, supply_type = ?, is_reverse_charge = ?,
                    customer_name = ?, customer_gstin = ?, customer_billing_address = ?, customer_billing_city = ?, customer_billing_state = ?, customer_billing_state_code = ?, customer_billing_pincode = ?, customer_shipping_address = ?, customer_email = ?, customer_phone = ?,
                    subtotal_gross = ?, line_discounts_total = ?, invoice_discount_type = ?, invoice_discount_value = ?, invoice_discount_amount = ?,
                    taxable_value = ?, cgst_amount = ?, sgst_amount = ?, igst_amount = ?, cess_amount = ?, tax_total = ?,
                    additional_charges = ?, additional_charges_label = ?, rounding_adjustment = ?, total_amount = ?, balance_due = ?,
                    notes = ?, terms = ?, issued_at = ?, issued_by_user_id = ?, updated_at = NOW()
                WHERE id = ? AND business_id = ?
            ");

            $uStmt->execute([
                $customerId,
                $invoiceNumber,
                $fy,
                $status,
                $issueDate,
                $dueDate,
                trim($request->post('po_number', '')),
                trim($request->post('payment_terms', 'Net 15')),
                $posState,
                $posCode,
                $supplyType,
                $request->post('is_reverse_charge') ? 1 : 0,

                $customer['name'],
                $customer['gstin'] ?? null,
                $customer['billing_address'] ?? '',
                $customer['billing_city'] ?? '',
                $customer['billing_state'] ?? '',
                $customer['billing_state_code'] ?? '',
                $customer['billing_pincode'] ?? '',
                $customer['shipping_address'] ?? '',
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
                $additionalChargesLabel,
                $calc['rounding_adjustment'],
                $calc['total_amount'],
                $calc['total_amount'],

                trim($request->post('notes', '')),
                trim($request->post('terms', '')),
                $issuedAt,
                $issuedBy,
                $id,
                $businessId,
            ]);

            // Replace line items
            $delStmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $delStmt->execute([$id]);

            $itemStmt = $pdo->prepare("
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
                $itemStmt->execute([
                    $id,
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

            AuditService::log('invoice.updated', 'invoice', (int)$id, ['invoice_number' => $invoiceNumber, 'status' => $status]);
        });

        Session::flash('success', 'Invoice updated successfully.');
        return Response::redirect(url('/invoices/' . $id));
    }

    public function issue(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $user = Session::get('user');
        $userId = (int)$user['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice || $invoice['status'] !== 'draft') {
            Session::flash('error', 'Only draft invoices can be issued.');
            return Response::redirect(url('/invoices/' . $id));
        }

        Database::transaction(function (PDO $pdo) use ($id, $businessId, $business, $userId, $invoice) {
            $fy = FinancialYear::forDate($invoice['issue_date']);
            $prefix = $business['invoice_prefix'] ?? 'INV';
            $padding = (int)($business['number_padding'] ?? 4);

            $invoiceNumber = DocumentNumberService::generateNextNumber($businessId, 'invoice', $prefix, $padding, $fy);
            $issuedAt = date('Y-m-d H:i:s');

            $up = $pdo->prepare("
                UPDATE invoices 
                SET invoice_number = ?, status = 'issued', issued_at = ?, issued_by_user_id = ?, updated_at = NOW()
                WHERE id = ? AND business_id = ?
            ");
            $up->execute([$invoiceNumber, $issuedAt, $userId, $id, $businessId]);

            AuditService::log('invoice.issued', 'invoice', (int)$id, ['invoice_number' => $invoiceNumber]);
        });

        Session::flash('success', 'Invoice has been formally issued!');
        return Response::redirect(url('/invoices/' . $id));
    }

    public function void(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $user = Session::get('user');
        $userId = (int)$user['id'];
        $pdo = Database::pdo();

        $reason = trim($request->post('void_reason', ''));
        if (empty($reason)) {
            Session::flash('error', 'A reason is required to void an issued invoice.');
            return Response::redirect(url('/invoices/' . $id));
        }

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            return Response::redirect(url('/invoices'));
        }

        if ($invoice['paid_amount'] > 0) {
            Session::flash('error', 'Cannot void an invoice with active payments. Reverse payments first.');
            return Response::redirect(url('/invoices/' . $id));
        }

        $up = $pdo->prepare("
            UPDATE invoices 
            SET status = 'void', voided_at = NOW(), voided_by_user_id = ?, void_reason = ?, balance_due = 0.00, updated_at = NOW()
            WHERE id = ? AND business_id = ?
        ");
        $up->execute([$userId, $reason, $id, $businessId]);

        AuditService::log('invoice.voided', 'invoice', (int)$id, [
            'invoice_number' => $invoice['invoice_number'],
            'reason' => $reason
        ]);

        Session::flash('success', "Invoice {$invoice['invoice_number']} has been voided.");
        return Response::redirect(url('/invoices/' . $id));
    }

    public function destroy(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            return Response::redirect(url('/invoices'));
        }

        if ($invoice['status'] !== 'draft') {
            Session::flash('error', 'Only draft invoices can be deleted. Issued invoices must be preserved or marked as void.');
            return Response::redirect(url('/invoices/' . $id));
        }

        Database::transaction(function (PDO $pdo) use ($id, $businessId, $invoice) {
            $delItems = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $delItems->execute([$id]);

            $delInv = $pdo->prepare("DELETE FROM invoices WHERE id = ? AND business_id = ?");
            $delInv->execute([$id, $businessId]);

            AuditService::log('invoice.deleted', 'invoice', (int)$id, [
                'invoice_number' => $invoice['invoice_number']
            ]);
        });

        Session::flash('success', "Draft invoice {$invoice['invoice_number']} has been permanently deleted.");
        return Response::redirect(url('/invoices'));
    }

    public function duplicate(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$orig) {
            Session::flash('error', 'Invoice not found.');
            return Response::redirect(url('/invoices'));
        }

        $iStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+' . ($business['default_due_days'] ?? 15) . ' days'));
        $fy = FinancialYear::forDate($today);

        $newInvId = Database::transaction(function (PDO $pdo) use ($businessId, $orig, $items, $today, $dueDate, $fy, $business) {
            $prefix = $business['invoice_prefix'] ?? 'INV';
            $padding = (int)($business['number_padding'] ?? 4);
            $newNumber = DocumentNumberService::generateDraftNumber($businessId, $padding, $fy);

            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $ins = $pdo->prepare("
                INSERT INTO invoices (
                    business_id, customer_id, uuid, invoice_number, financial_year,
                    document_type, status, issue_date, due_date, po_number, payment_terms,
                    place_of_supply_state, place_of_supply_state_code, supply_type, is_reverse_charge,
                    supplier_name, supplier_trade_name, supplier_gstin, supplier_address, supplier_city, supplier_state, supplier_state_code, supplier_pincode, supplier_phone, supplier_email,
                    customer_name, customer_gstin, customer_billing_address, customer_billing_city, customer_billing_state, customer_billing_state_code, customer_billing_pincode, customer_shipping_address, customer_email, customer_phone,
                    subtotal_gross, line_discounts_total, invoice_discount_type, invoice_discount_value, invoice_discount_amount,
                    taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, tax_total,
                    additional_charges, additional_charges_label, rounding_adjustment, total_amount, paid_amount, balance_due,
                    notes, terms, bank_details, signature_title, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, 'draft', ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, 0.00, ?,
                    ?, ?, ?, ?, NOW(), NOW()
                )
            ");

            $ins->execute([
                $businessId, $orig['customer_id'], $uuid, $newNumber, $fy,
                $orig['document_type'], $today, $dueDate, $orig['po_number'], $orig['payment_terms'],
                $orig['place_of_supply_state'], $orig['place_of_supply_state_code'], $orig['supply_type'], $orig['is_reverse_charge'],
                $orig['supplier_name'], $orig['supplier_trade_name'], $orig['supplier_gstin'], $orig['supplier_address'], $orig['supplier_city'], $orig['supplier_state'], $orig['supplier_state_code'], $orig['supplier_pincode'], $orig['supplier_phone'], $orig['supplier_email'],
                $orig['customer_name'], $orig['customer_gstin'], $orig['customer_billing_address'], $orig['customer_billing_city'], $orig['customer_billing_state'], $orig['customer_billing_state_code'], $orig['customer_billing_pincode'], $orig['customer_shipping_address'], $orig['customer_email'], $orig['customer_phone'],
                $orig['subtotal_gross'], $orig['line_discounts_total'], $orig['invoice_discount_type'], $orig['invoice_discount_value'], $orig['invoice_discount_amount'],
                $orig['taxable_value'], $orig['cgst_amount'], $orig['sgst_amount'], $orig['igst_amount'], $orig['cess_amount'], $orig['tax_total'],
                $orig['additional_charges'], $orig['additional_charges_label'], $orig['rounding_adjustment'], $orig['total_amount'], $orig['total_amount'],
                $orig['notes'], $orig['terms'], $orig['bank_details'], $orig['signature_title'],
            ]);

            $newId = (int)$pdo->lastInsertId();

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
                    $newId, $it['product_id'], $it['item_order'], $it['name'], $it['description'], $it['hsn_sac'], $it['item_type'],
                    $it['quantity'], $it['unit'], $it['unit_price'], $it['gross_amount'],
                    $it['discount_type'], $it['discount_value'], $it['discount_amount'], $it['taxable_value'],
                    $it['tax_category'], $it['gst_rate'], $it['cgst_rate'], $it['cgst_amount'], $it['sgst_rate'], $it['sgst_amount'],
                    $it['igst_rate'], $it['igst_amount'], $it['cess_rate'], $it['cess_amount'], $it['total_tax'], $it['line_total'],
                ]);
            }

            AuditService::log('invoice.duplicated', 'invoice', $newId, ['original_invoice_id' => $orig['id']]);
            return $newId;
        });

        Session::flash('success', 'Invoice duplicated into a new draft.');
        return Response::redirect(url('/invoices/' . $newInvId . '/edit'));
    }

    public function downloadPdf(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            return Response::redirect(url('/invoices'));
        }

        $iStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$invoice['customer_id'], $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $pdfContent = PdfService::invoicePdf($invoice, $items, $business, $customer);
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $invoice['invoice_number']) . '.pdf';

        return Response::download($pdfContent, $filename, 'application/pdf');
    }

    public function printView(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            Session::flash('error', 'Invoice not found.');
            return Response::redirect(url('/invoices'));
        }

        $iStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$invoice['customer_id'], $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return new Response(View::render('invoices.print', [
            'invoice' => $invoice,
            'items' => $items,
            'business' => $business,
            'customer' => $customer,
        ], null));
    }
}
