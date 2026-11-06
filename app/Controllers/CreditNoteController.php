<?php

namespace App\Controllers;

use PDO;
use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use App\Support\Database;
use App\Support\Session;
use App\Support\Config;
use App\Support\FinancialYear;
use App\Services\DocumentNumberService;
use App\Services\TaxCalculationService;
use App\Services\AuditService;
use App\Services\PdfService;

class CreditNoteController
{
    public function index(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $page = max(1, (int)$request->get('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $search = trim($request->get('search', ''));
        $status = trim($request->get('status', ''));
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        $where = ["cn.business_id = ?"];
        $params = [$businessId];

        if ($status !== '') {
            $where[] = "cn.status = ?";
            $params[] = $status;
        }

        if ($startDate !== '') {
            $where[] = "cn.credit_note_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate !== '') {
            $where[] = "cn.credit_note_date <= ?";
            $params[] = $endDate;
        }

        if ($search !== '') {
            $where[] = "(cn.credit_note_number LIKE ? OR c.name LIKE ? OR cn.original_invoice_number LIKE ?)";
            $term = "%{$search}%";
            array_push($params, $term, $term, $term);
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM credit_notes cn
            LEFT JOIN customers c ON c.id = cn.customer_id
            WHERE {$whereSql}
        ");
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT cn.*, c.name as customer_name, c.gstin as customer_gstin
            FROM credit_notes cn
            LEFT JOIN customers c ON c.id = cn.customer_id
            WHERE {$whereSql}
            ORDER BY cn.credit_note_date DESC, cn.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $creditNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($totalRecords / $perPage);

        return new Response(View::render('credit-notes.index', [
            'title' => 'Credit Notes (Section 34)',
            'creditNotes' => $creditNotes,
            'totalRecords' => $totalRecords,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'status' => $status,
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

        $invoiceId = (int)$request->get('invoice_id', 0);
        $linkedInvoice = null;
        $invoiceItems = [];

        if ($invoiceId > 0) {
            $invStmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND business_id = ?");
            $invStmt->execute([$invoiceId, $businessId]);
            $linkedInvoice = $invStmt->fetch(PDO::FETCH_ASSOC);

            if ($linkedInvoice) {
                $itemStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_order ASC");
                $itemStmt->execute([$invoiceId]);
                $invoiceItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $defaultGstRates = Config::get('constants.gst_rates', [0, 5, 12, 18, 28]);
        $trStmt = $pdo->prepare("SELECT rate FROM tax_rates WHERE business_id = ? AND is_active = 1");
        $trStmt->execute([$businessId]);
        $customRates = $trStmt->fetchAll(PDO::FETCH_COLUMN);
        $gstRates = array_unique(array_merge($defaultGstRates, array_map('floatval', $customRates)));
        sort($gstRates);

        $prefix = $business['credit_note_prefix'] ?? 'CN';
        $padding = (int)($business['number_padding'] ?? 4);
        $nextNumber = DocumentNumberService::previewNextNumber($businessId, 'credit_note', $prefix, $padding);

        return new Response(View::render('credit-notes.create', [
            'title' => 'Issue Credit Note (CGST Section 34)',
            'business' => $business,
            'customers' => $customers,
            'linkedInvoice' => $linkedInvoice,
            'invoiceItems' => $invoiceItems,
            'gstRates' => $gstRates,
            'nextNumber' => $nextNumber,
            'today' => date('Y-m-d'),
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
        $invoiceId = !empty($request->post('invoice_id')) ? (int)$request->post('invoice_id') : null;
        $creditNoteDate = trim($request->post('credit_note_date', date('Y-m-d')));
        $origInvNumber = trim($request->post('original_invoice_number', ''));
        $origInvDate = trim($request->post('original_invoice_date', '')) ?: null;
        $reason = trim($request->post('reason', 'sales_return'));
        $reasonDesc = trim($request->post('reason_description', ''));
        $notes = trim($request->post('notes', ''));

        if ($customerId <= 0) {
            Session::flash('error', 'Please select a valid customer.');
            return Response::redirect(url('/credit-notes/create'));
        }

        if (empty($origInvNumber)) {
            Session::flash('error', 'Original Invoice Number is mandatory under CGST Rule 53.');
            return Response::redirect(url('/credit-notes/create'));
        }

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$customerId, $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            Session::flash('error', 'Customer not found.');
            return Response::redirect(url('/credit-notes/create'));
        }

        $rawItems = $request->post('items', []);
        if (empty($rawItems)) {
            Session::flash('error', 'At least one line item is required.');
            return Response::redirect(url('/credit-notes/create'));
        }

        $supplierStateCode = $business['state_code'] ?? '';
        $posCode = $customer['billing_state_code'] ?? '';
        $supplyType = TaxCalculationService::determineSupplyType($supplierStateCode, $posCode);

        // Process line items & taxes
        $items = [];
        $totTaxable = 0.0;
        $totCgst = 0.0;
        $totSgst = 0.0;
        $totIgst = 0.0;
        $totTax = 0.0;
        $totGross = 0.0;

        foreach ($rawItems as $order => $it) {
            $name = trim($it['name'] ?? '');
            if (empty($name)) continue;

            $qty = max(0.001, (float)($it['quantity'] ?? 1));
            $rate = max(0.0, (float)($it['unit_price'] ?? 0));
            $gstRate = max(0.0, (float)($it['gst_rate'] ?? 0));
            $lineTaxable = round($qty * $rate, 2);

            $cgstRate = 0.0;
            $cgstAmt = 0.0;
            $sgstRate = 0.0;
            $sgstAmt = 0.0;
            $igstRate = 0.0;
            $igstAmt = 0.0;

            if ($supplyType === 'inter_state') {
                $igstRate = $gstRate;
                $igstAmt = round(($lineTaxable * $igstRate) / 100, 2);
            } else {
                $cgstRate = $gstRate / 2;
                $sgstRate = $gstRate / 2;
                $cgstAmt = round(($lineTaxable * $cgstRate) / 100, 2);
                $sgstAmt = round(($lineTaxable * $sgstRate) / 100, 2);
            }

            $lineTax = round($cgstAmt + $sgstAmt + $igstAmt, 2);
            $lineTotal = round($lineTaxable + $lineTax, 2);

            $totTaxable += $lineTaxable;
            $totCgst += $cgstAmt;
            $totSgst += $sgstAmt;
            $totIgst += $igstAmt;
            $totTax += $lineTax;
            $totGross += $lineTotal;

            $items[] = [
                'item_order' => $order + 1,
                'name' => $name,
                'description' => trim($it['description'] ?? ''),
                'hsn_sac' => trim($it['hsn_sac'] ?? ''),
                'quantity' => $qty,
                'unit' => trim($it['unit'] ?? 'PCS'),
                'unit_price' => $rate,
                'taxable_value' => $lineTaxable,
                'gst_rate' => $gstRate,
                'cgst_rate' => $cgstRate,
                'cgst_amount' => $cgstAmt,
                'sgst_rate' => $sgstRate,
                'sgst_amount' => $sgstAmt,
                'igst_rate' => $igstRate,
                'igst_amount' => $igstAmt,
                'cess_rate' => 0.0,
                'cess_amount' => 0.0,
                'total_tax' => $lineTax,
                'line_total' => $lineTotal,
            ];
        }

        if (empty($items)) {
            Session::flash('error', 'Valid items are required to create a credit note.');
            return Response::redirect(url('/credit-notes/create'));
        }

        $rawTotal = $totTaxable + $totTax;
        $roundedTotal = round($rawTotal);
        $roundingAdjustment = round($roundedTotal - $rawTotal, 2);

        $fy = FinancialYear::forDate($creditNoteDate);

        try {
            $cnId = Database::transaction(function (PDO $pdo) use (
                $businessId, $customerId, $invoiceId, $creditNoteDate, $origInvNumber, $origInvDate,
                $reason, $reasonDesc, $notes, $supplyType, $totTaxable, $totCgst, $totSgst, $totIgst,
                $totTax, $roundingAdjustment, $roundedTotal, $items, $fy, $userId, $business
            ) {
                $prefix = $business['credit_note_prefix'] ?? 'CN';
                $padding = (int)($business['number_padding'] ?? 4);
                $cnNumber = DocumentNumberService::generateNextNumber($businessId, 'credit_note', $prefix, $padding, $fy);

                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $appliedAmount = 0.00;
                $status = 'issued';

                // If linked to an invoice, auto-apply against balance due
                if ($invoiceId) {
                    $invStmt = $pdo->prepare("SELECT id, total_amount, paid_amount, balance_due FROM invoices WHERE id = ? AND business_id = ? FOR UPDATE");
                    $invStmt->execute([$invoiceId, $businessId]);
                    $inv = $invStmt->fetch(PDO::FETCH_ASSOC);

                    if ($inv && (float)$inv['balance_due'] > 0) {
                        $appliedAmount = min((float)$inv['balance_due'], $roundedTotal);
                        $newBalance = max(0.0, round((float)$inv['balance_due'] - $appliedAmount, 2));
                        $newPaid = round((float)$inv['paid_amount'] + $appliedAmount, 2);
                        $newStatus = ($newBalance <= 0) ? 'paid' : 'partially_paid';

                        $upInv = $pdo->prepare("
                            UPDATE invoices 
                            SET balance_due = ?, paid_amount = ?, status = ?, updated_at = NOW()
                            WHERE id = ? AND business_id = ?
                        ");
                        $upInv->execute([$newBalance, $newPaid, $newStatus, $invoiceId, $businessId]);

                        if ($appliedAmount >= $roundedTotal) {
                            $status = 'applied';
                        }
                    }
                }

                $unappliedAmount = round($roundedTotal - $appliedAmount, 2);

                $ins = $pdo->prepare("
                    INSERT INTO credit_notes (
                        business_id, customer_id, invoice_id, uuid, credit_note_number, financial_year,
                        credit_note_date, original_invoice_number, original_invoice_date,
                        reason, reason_description, status, supply_type,
                        taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, tax_total,
                        rounding_adjustment, total_amount, applied_amount, unapplied_amount,
                        notes, created_by_user_id, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, NOW(), NOW()
                    )
                ");

                $ins->execute([
                    $businessId, $customerId, $invoiceId, $uuid, $cnNumber, $fy,
                    $creditNoteDate, $origInvNumber, $origInvDate,
                    $reason, $reasonDesc, $status, $supplyType,
                    $totTaxable, $totCgst, $totSgst, $totIgst, 0.00, $totTax,
                    $roundingAdjustment, $roundedTotal, $appliedAmount, $unappliedAmount,
                    $notes, $userId
                ]);

                $newCnId = (int)$pdo->lastInsertId();

                $itIns = $pdo->prepare("
                    INSERT INTO credit_note_items (
                        credit_note_id, item_order, name, description, hsn_sac,
                        quantity, unit, unit_price, taxable_value, gst_rate,
                        cgst_rate, cgst_amount, sgst_rate, sgst_amount,
                        igst_rate, igst_amount, cess_rate, cess_amount,
                        total_tax, line_total
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?
                    )
                ");

                foreach ($items as $item) {
                    $itIns->execute([
                        $newCnId, $item['item_order'], $item['name'], $item['description'], $item['hsn_sac'],
                        $item['quantity'], $item['unit'], $item['unit_price'], $item['taxable_value'], $item['gst_rate'],
                        $item['cgst_rate'], $item['cgst_amount'], $item['sgst_rate'], $item['sgst_amount'],
                        $item['igst_rate'], $item['igst_amount'], $item['cess_rate'], $item['cess_amount'],
                        $item['total_tax'], $item['line_total'],
                    ]);
                }

                AuditService::log('credit_note.created', 'credit_note', $newCnId, [
                    'credit_note_number' => $cnNumber,
                    'total_amount' => $roundedTotal,
                    'applied_amount' => $appliedAmount,
                ]);

                return $newCnId;
            });

            Session::flash('success', 'Credit Note issued successfully under CGST Section 34.');
            return Response::redirect(url('/credit-notes/' . $cnId));
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to issue credit note: ' . $e->getMessage());
            return Response::redirect(url('/credit-notes/create'));
        }
    }

    public function show(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM credit_notes WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $creditNote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$creditNote) {
            Session::flash('error', 'Credit Note not found.');
            return Response::redirect(url('/credit-notes'));
        }

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$creditNote['customer_id'], $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

        $iStmt = $pdo->prepare("SELECT * FROM credit_note_items WHERE credit_note_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('credit-notes.show', [
            'title' => 'Credit Note #' . $creditNote['credit_note_number'],
            'creditNote' => $creditNote,
            'customer' => $customer,
            'items' => $items,
            'business' => $business,
        ]));
    }

    public function void(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM credit_notes WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $creditNote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$creditNote || $creditNote['status'] === 'void') {
            Session::flash('error', 'Credit note is already void or not found.');
            return Response::redirect(url('/credit-notes'));
        }

        Database::transaction(function (PDO $pdo) use ($creditNote, $businessId, $id) {
            // If credit note was applied to an invoice, reverse the deduction
            if ((float)$creditNote['applied_amount'] > 0 && !empty($creditNote['invoice_id'])) {
                $upInv = $pdo->prepare("
                    UPDATE invoices 
                    SET balance_due = balance_due + ?, paid_amount = GREATEST(0.00, paid_amount - ?), updated_at = NOW()
                    WHERE id = ? AND business_id = ?
                ");
                $upInv->execute([$creditNote['applied_amount'], $creditNote['applied_amount'], $creditNote['invoice_id'], $businessId]);
            }

            $upCn = $pdo->prepare("
                UPDATE credit_notes 
                SET status = 'void', applied_amount = 0.00, unapplied_amount = 0.00, updated_at = NOW()
                WHERE id = ? AND business_id = ?
            ");
            $upCn->execute([$id, $businessId]);

            AuditService::log('credit_note.voided', 'credit_note', (int)$id, [
                'credit_note_number' => $creditNote['credit_note_number']
            ]);
        });

        Session::flash('success', "Credit Note {$creditNote['credit_note_number']} has been voided.");
        return Response::redirect(url('/credit-notes/' . $id));
    }

    public function downloadPdf(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM credit_notes WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $creditNote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$creditNote) {
            Session::flash('error', 'Credit note not found.');
            return Response::redirect(url('/credit-notes'));
        }

        $iStmt = $pdo->prepare("SELECT * FROM credit_note_items WHERE credit_note_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$creditNote['customer_id'], $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $pdfContent = PdfService::creditNotePdf($creditNote, $items, $business, $customer);
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $creditNote['credit_note_number']) . '.pdf';

        return Response::download($pdfContent, $filename, 'application/pdf');
    }

    public function printView(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM credit_notes WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $creditNote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$creditNote) {
            Session::flash('error', 'Credit note not found.');
            return Response::redirect(url('/credit-notes'));
        }

        $iStmt = $pdo->prepare("SELECT * FROM credit_note_items WHERE credit_note_id = ? ORDER BY item_order ASC");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$creditNote['customer_id'], $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return new Response(View::render('credit-notes.print', [
            'creditNote' => $creditNote,
            'items' => $items,
            'business' => $business,
            'customer' => $customer,
        ], null));
    }
}

