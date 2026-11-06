<?php

namespace App\Controllers;

use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\PdfService;
use App\Support\Config;
use App\Support\Database;
use App\Support\FinancialYear;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;
use App\Support\View;
use PDO;

class PaymentController
{
    public function index(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $search = trim($request->get('search', ''));
        $method = trim($request->get('method', ''));
        $status = trim($request->get('status', ''));
        $customerId = $request->get('customer_id', '');
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = ["p.business_id = ?"];
        $params = [$businessId];

        if ($method !== '') {
            $where[] = "p.payment_method = ?";
            $params[] = $method;
        }

        if ($status !== '') {
            $where[] = "p.status = ?";
            $params[] = $status;
        }

        if ($customerId !== '') {
            $where[] = "p.customer_id = ?";
            $params[] = (int)$customerId;
        }

        if ($search !== '') {
            $where[] = "(p.payment_number LIKE ? OR p.reference_number LIKE ? OR c.name LIKE ?)";
            $term = "%{$search}%";
            array_push($params, $term, $term, $term);
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM payments p
            JOIN customers c ON c.id = p.customer_id
            WHERE {$whereSql}
        ");
        $countStmt->execute($params);
        $totalPayments = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT p.*, c.name as customer_name
            FROM payments p
            JOIN customers c ON c.id = p.customer_id
            WHERE {$whereSql}
            ORDER BY p.payment_date DESC, p.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("SELECT id, name FROM customers WHERE business_id = ? ORDER BY name ASC");
        $cStmt->execute([$businessId]);
        $customers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        $paymentMethods = Config::get('constants.payment_methods', []);
        $totalPages = ceil($totalPayments / $perPage);

        return new Response(View::render('payments.index', [
            'title' => 'Payments & Receipts',
            'payments' => $payments,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
            'totalPayments' => $totalPayments,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'method' => $method,
            'status' => $status,
            'customerId' => $customerId,
        ]));
    }

    public function create(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $selectedCustomerId = (int)$request->get('customer_id', 0);
        $selectedInvoiceId = (int)$request->get('invoice_id', 0);

        // Fetch customers who have open invoices
        $cStmt = $pdo->prepare("
            SELECT DISTINCT c.id, c.name,
                   (SELECT COUNT(*) FROM invoices WHERE customer_id = c.id AND business_id = ? AND status IN ('issued', 'partially_paid')) as open_count
            FROM customers c
            WHERE c.business_id = ? AND c.is_archived = 0
            ORDER BY c.name ASC
        ");
        $cStmt->execute([$businessId, $businessId]);
        $customers = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        $invoices = [];
        if ($selectedCustomerId > 0) {
            $invStmt = $pdo->prepare("
                SELECT id, invoice_number, issue_date, due_date, total_amount, paid_amount, balance_due
                FROM invoices
                WHERE business_id = ? AND customer_id = ? AND status IN ('issued', 'partially_paid')
                ORDER BY due_date ASC, id ASC
            ");
            $invStmt->execute([$businessId, $selectedCustomerId]);
            $invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $paymentMethods = Config::get('constants.payment_methods', []);
        $nextReceiptNumber = DocumentNumberService::previewNextNumber(
            $businessId,
            'payment',
            $business['payment_prefix'] ?? 'PAY',
            (int)($business['number_padding'] ?? 4)
        );

        return new Response(View::render('payments.create', [
            'title' => 'Record Payment Receipt',
            'business' => $business,
            'customers' => $customers,
            'invoices' => $invoices,
            'paymentMethods' => $paymentMethods,
            'nextReceiptNumber' => $nextReceiptNumber,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedInvoiceId' => $selectedInvoiceId,
            'today' => date('Y-m-d'),
        ]));
    }

    public function getCustomerInvoices(Request $request, string $customerId): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT id, invoice_number, issue_date, due_date, total_amount, paid_amount, balance_due
            FROM invoices
            WHERE business_id = ? AND customer_id = ? AND status IN ('issued', 'partially_paid')
            ORDER BY due_date ASC, id ASC
        ");
        $stmt->execute([$businessId, $customerId]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::json($invoices);
    }

    public function store(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $user = Session::get('user');
        $userId = (int)$user['id'];
        $pdo = Database::pdo();

        $customerId = (int)$request->post('customer_id', 0);
        $paymentDate = $request->post('payment_date', date('Y-m-d'));
        $amount = max(0.01, (float)$request->post('amount', 0));
        $paymentMethod = $request->post('payment_method', 'upi');
        $referenceNumber = trim($request->post('reference_number', ''));
        $bankName = trim($request->post('bank_name', ''));
        $notes = trim($request->post('notes', ''));

        // Customer verification
        $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $cStmt->execute([$customerId, $businessId]);
        $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            Session::flash('error', 'Please select a valid customer.');
            return Response::redirect(url('/payments/create'));
        }

        // Validate allocations
        $allocationsInput = $request->post('allocations', []); // [invoice_id => amount]
        $totalAllocated = 0.0;
        $validAllocations = [];

        foreach ($allocationsInput as $invId => $allocAmt) {
            $allocAmt = round((float)$allocAmt, 2);
            if ($allocAmt > 0) {
                // Fetch invoice to check balance
                $invStmt = $pdo->prepare("SELECT id, invoice_number, total_amount, paid_amount, balance_due FROM invoices WHERE id = ? AND business_id = ? AND customer_id = ?");
                $invStmt->execute([$invId, $businessId, $customerId]);
                $inv = $invStmt->fetch(PDO::FETCH_ASSOC);

                if (!$inv) {
                    Session::flash('error', 'Invalid invoice selected for allocation.');
                    return Response::redirect(url('/payments/create?customer_id=' . $customerId));
                }

                if ($allocAmt > $inv['balance_due'] + 0.01) {
                    Session::flash('error', "Allocation for {$inv['invoice_number']} exceeds its outstanding balance.");
                    return Response::redirect(url('/payments/create?customer_id=' . $customerId));
                }

                $validAllocations[] = [
                    'invoice_id' => $inv['id'],
                    'invoice_number' => $inv['invoice_number'],
                    'amount' => $allocAmt,
                    'current_paid' => (float)$inv['paid_amount'],
                    'total_amount' => (float)$inv['total_amount'],
                ];
                $totalAllocated += $allocAmt;
            }
        }

        $totalAllocated = round($totalAllocated, 2);
        if ($totalAllocated > $amount + 0.01) {
            Session::flash('error', 'Total allocated amount cannot exceed the received payment amount.');
            return Response::redirect(url('/payments/create?customer_id=' . $customerId));
        }

        $unallocated = round($amount - $totalAllocated, 2);
        $fy = FinancialYear::forDate($paymentDate);

        try {
            $paymentId = Database::transaction(function (PDO $pdo) use (
                $businessId, $customerId, $paymentDate, $amount, $totalAllocated, $unallocated,
                $paymentMethod, $referenceNumber, $bankName, $notes, $userId, $validAllocations,
                $business, $fy
            ) {
                $prefix = $business['payment_prefix'] ?? 'PAY';
                $padding = (int)($business['number_padding'] ?? 4);
                $paymentNumber = DocumentNumberService::generateNextNumber($businessId, 'payment', $prefix, $padding, $fy);

                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                // Insert payment record
                $stmt = $pdo->prepare("
                    INSERT INTO payments (
                        business_id, customer_id, uuid, payment_number, payment_date,
                        amount, allocated_amount, unallocated_amount, payment_method,
                        reference_number, bank_name, status, notes, created_by_user_id,
                        created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, 'posted', ?, ?,
                        NOW(), NOW()
                    )
                ");

                $stmt->execute([
                    $businessId, $customerId, $uuid, $paymentNumber, $paymentDate,
                    $amount, $totalAllocated, $unallocated, $paymentMethod,
                    $referenceNumber ?: null, $bankName ?: null, $notes ?: null, $userId
                ]);

                $payId = (int)$pdo->lastInsertId();

                // Insert allocations & update invoice balances
                $allocStmt = $pdo->prepare("
                    INSERT INTO payment_allocations (payment_id, invoice_id, amount, created_at)
                    VALUES (?, ?, ?, NOW())
                ");

                $invUpStmt = $pdo->prepare("
                    UPDATE invoices 
                    SET paid_amount = ?, balance_due = ?, status = ?, updated_at = NOW()
                    WHERE id = ? AND business_id = ?
                ");

                foreach ($validAllocations as $alloc) {
                    $allocStmt->execute([$payId, $alloc['invoice_id'], $alloc['amount']]);

                    $newPaid = round($alloc['current_paid'] + $alloc['amount'], 2);
                    $newBalance = max(0.0, round($alloc['total_amount'] - $newPaid, 2));
                    $newStatus = ($newBalance <= 0.00) ? 'paid' : 'partially_paid';

                    $invUpStmt->execute([$newPaid, $newBalance, $newStatus, $alloc['invoice_id'], $businessId]);
                }

                AuditService::log('payment.recorded', 'payment', $payId, [
                    'payment_number' => $paymentNumber,
                    'amount' => $amount,
                    'allocated' => $totalAllocated,
                ]);

                return $payId;
            });

            Session::flash('success', 'Payment receipt recorded and allocated successfully!');
            return Response::redirect(url('/payments/' . $paymentId));
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to record payment: ' . $e->getMessage());
            return Response::redirect(url('/payments/create?customer_id=' . $customerId));
        }
    }

    public function show(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT p.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                   c.billing_address, c.billing_city, c.billing_state, c.billing_pincode, c.gstin as customer_gstin
            FROM payments p
            JOIN customers c ON c.id = p.customer_id
            WHERE p.id = ? AND p.business_id = ?
        ");
        $stmt->execute([$id, $businessId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            Session::flash('error', 'Payment receipt not found.');
            return Response::redirect(url('/payments'));
        }

        // Fetch allocations
        $aStmt = $pdo->prepare("
            SELECT pa.*, inv.invoice_number, inv.issue_date, inv.total_amount, inv.balance_due as current_balance
            FROM payment_allocations pa
            JOIN invoices inv ON inv.id = pa.invoice_id
            WHERE pa.payment_id = ?
        ");
        $aStmt->execute([$id]);
        $allocations = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('payments.show', [
            'title' => 'Receipt ' . $payment['payment_number'],
            'payment' => $payment,
            'allocations' => $allocations,
            'business' => $business,
        ]));
    }

    public function reverse(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $user = Session::get('user');
        $userId = (int)$user['id'];
        $pdo = Database::pdo();

        $reason = trim($request->post('reversal_reason', ''));
        if (empty($reason)) {
            Session::flash('error', 'A reason is required to reverse a posted payment.');
            return Response::redirect(url('/payments/' . $id));
        }

        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment || $payment['status'] === 'reversed') {
            Session::flash('error', 'Payment receipt cannot be reversed.');
            return Response::redirect(url('/payments/' . $id));
        }

        $aStmt = $pdo->prepare("
            SELECT pa.*, inv.total_amount, inv.paid_amount
            FROM payment_allocations pa
            JOIN invoices inv ON inv.id = pa.invoice_id
            WHERE pa.payment_id = ?
        ");
        $aStmt->execute([$id]);
        $allocations = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        Database::transaction(function (PDO $pdo) use ($id, $businessId, $userId, $reason, $allocations, $payment) {
            // Mark payment as reversed
            $pUp = $pdo->prepare("
                UPDATE payments 
                SET status = 'reversed', reversal_reason = ?, reversed_at = NOW(), reversed_by_user_id = ?, updated_at = NOW()
                WHERE id = ? AND business_id = ?
            ");
            $pUp->execute([$reason, $userId, $id, $businessId]);

            // Revert each invoice balance
            $invUp = $pdo->prepare("
                UPDATE invoices 
                SET paid_amount = ?, balance_due = ?, status = ?, updated_at = NOW()
                WHERE id = ? AND business_id = ?
            ");

            foreach ($allocations as $alloc) {
                $revertedPaid = max(0.0, round($alloc['paid_amount'] - $alloc['amount'], 2));
                $revertedBalance = min((float)$alloc['total_amount'], round($alloc['total_amount'] - $revertedPaid, 2));
                $newStatus = ($revertedPaid > 0) ? 'partially_paid' : 'issued';

                $invUp->execute([$revertedPaid, $revertedBalance, $newStatus, $alloc['invoice_id'], $businessId]);
            }

            AuditService::log('payment.reversed', 'payment', (int)$id, [
                'payment_number' => $payment['payment_number'],
                'reason' => $reason
            ]);
        });

        Session::flash('success', "Payment receipt {$payment['payment_number']} has been reversed.");
        return Response::redirect(url('/payments/' . $id));
    }

    public function downloadPdf(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT p.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                   c.billing_address, c.billing_city, c.billing_state, c.billing_pincode, c.gstin as customer_gstin
            FROM payments p
            JOIN customers c ON c.id = p.customer_id
            WHERE p.id = ? AND p.business_id = ?
        ");
        $stmt->execute([$id, $businessId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            Session::flash('error', 'Payment not found.');
            return Response::redirect(url('/payments'));
        }

        $aStmt = $pdo->prepare("
            SELECT pa.*, inv.invoice_number, inv.total_amount, inv.balance_due as current_balance
            FROM payment_allocations pa
            JOIN invoices inv ON inv.id = pa.invoice_id
            WHERE pa.payment_id = ?
        ");
        $aStmt->execute([$id]);
        $allocations = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        $customer = [
            'name' => $payment['customer_name'],
            'email' => $payment['customer_email'],
            'phone' => $payment['customer_phone'],
            'billing_address' => $payment['billing_address'],
            'billing_city' => $payment['billing_city'],
            'billing_state' => $payment['billing_state'],
            'billing_pincode' => $payment['billing_pincode'],
            'gstin' => $payment['customer_gstin'],
        ];

        $pdfContent = PdfService::paymentReceiptPdf($payment, $allocations, $business, $customer);
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $payment['payment_number']) . '.pdf';

        return Response::download($pdfContent, $filename, 'application/pdf');
    }

    public function printView(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT p.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                   c.billing_address, c.billing_city, c.billing_state, c.billing_pincode, c.gstin as customer_gstin
            FROM payments p
            JOIN customers c ON c.id = p.customer_id
            WHERE p.id = ? AND p.business_id = ?
        ");
        $stmt->execute([$id, $businessId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            Session::flash('error', 'Payment not found.');
            return Response::redirect(url('/payments'));
        }

        $aStmt = $pdo->prepare("
            SELECT pa.*, inv.invoice_number, inv.total_amount, inv.balance_due as current_balance
            FROM payment_allocations pa
            JOIN invoices inv ON inv.id = pa.invoice_id
            WHERE pa.payment_id = ?
        ");
        $aStmt->execute([$id]);
        $allocations = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        $customer = [
            'name' => $payment['customer_name'],
            'email' => $payment['customer_email'],
            'phone' => $payment['customer_phone'],
            'billing_address' => $payment['billing_address'],
            'billing_city' => $payment['billing_city'],
            'billing_state' => $payment['billing_state'],
            'billing_pincode' => $payment['billing_pincode'],
            'gstin' => $payment['customer_gstin'],
        ];

        return new Response(View::render('payments.print', [
            'payment' => $payment,
            'allocations' => $allocations,
            'business' => $business,
            'customer' => $customer,
        ], null));
    }
}
