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

class CustomerController
{
    public function index(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $search = trim($request->get('search', ''));
        $gstStatus = trim($request->get('gst_status', ''));
        $status = $request->get('status', 'active');
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = ["c.business_id = ?"];
        $params = [$businessId];

        if ($status === 'archived') {
            $where[] = "c.is_archived = 1";
        } elseif ($status === 'active') {
            $where[] = "c.is_archived = 0";
        }

        if ($gstStatus !== '') {
            $where[] = "c.gst_status = ?";
            $params[] = $gstStatus;
        }

        if ($search !== '') {
            $where[] = "(c.name LIKE ? OR c.contact_person LIKE ? OR c.gstin LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
            $searchTerm = "%{$search}%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }

        $whereSql = implode(' AND ', $where);

        // Count total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c WHERE {$whereSql}");
        $countStmt->execute($params);
        $totalCustomers = (int)$countStmt->fetchColumn();

        // Fetch with invoiced and outstanding aggregated metrics
        $sql = "
            SELECT c.*,
                   COALESCE(inv.total_invoiced, 0) as total_invoiced,
                   COALESCE(inv.total_outstanding, 0) as total_outstanding
            FROM customers c
            LEFT JOIN (
                SELECT customer_id,
                       SUM(CASE WHEN status IN ('issued', 'partially_paid', 'paid') THEN total_amount ELSE 0 END) as total_invoiced,
                       SUM(CASE WHEN status IN ('issued', 'partially_paid') THEN balance_due ELSE 0 END) as total_outstanding
                FROM invoices
                WHERE business_id = ?
                GROUP BY customer_id
            ) inv ON inv.customer_id = c.id
            WHERE {$whereSql}
            ORDER BY c.name ASC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $fetchParams = array_merge([$businessId], $params);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($fetchParams);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($totalCustomers / $perPage);

        return new Response(View::render('customers.index', [
            'title' => 'Customers',
            'customers' => $customers,
            'totalCustomers' => $totalCustomers,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'gstStatus' => $gstStatus,
            'status' => $status,
        ]));
    }

    public function create(Request $request): Response
    {
        $states = Config::get('constants.gst_states', []);
        $paymentTerms = Config::get('constants.payment_terms', []);

        return new Response(View::render('customers.create', [
            'title' => 'Add New Customer',
            'states' => $states,
            'paymentTerms' => $paymentTerms,
        ]));
    }

    public function store(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $name = trim($request->post('name', ''));
        if (empty($name)) {
            Session::flash('error', 'Customer name is required.');
            return Response::redirect(url('/customers/create'));
        }

        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $gstin = strtoupper(trim($request->post('gstin', '')));
        $pan = strtoupper(trim($request->post('pan', '')));
        if (empty($pan) && strlen($gstin) === 15) {
            $pan = substr($gstin, 2, 10);
        }

        $billingStateCode = trim($request->post('billing_state_code', ''));
        $states = Config::get('constants.gst_states', []);
        $billingState = $states[$billingStateCode] ?? trim($request->post('billing_state', ''));

        $shippingStateCode = trim($request->post('shipping_state_code', ''));
        $shippingState = $states[$shippingStateCode] ?? trim($request->post('shipping_state', ''));

        $stmt = $pdo->prepare("
            INSERT INTO customers (
                business_id, uuid, name, contact_person, customer_type, email, phone,
                gst_status, gstin, pan,
                billing_address, billing_city, billing_state, billing_state_code, billing_pincode, billing_country,
                shipping_address, shipping_city, shipping_state, shipping_state_code, shipping_pincode, shipping_country,
                payment_terms, notes, is_archived, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?, 'India',
                ?, ?, ?, ?, ?, 'India',
                ?, ?, 0, NOW(), NOW()
            )
        ");

        $stmt->execute([
            $businessId,
            $uuid,
            $name,
            trim($request->post('contact_person', '')),
            $request->post('customer_type', 'business'),
            trim($request->post('email', '')),
            trim($request->post('phone', '')),
            $request->post('gst_status', 'registered_regular'),
            $gstin ?: null,
            $pan ?: null,
            trim($request->post('billing_address', '')),
            trim($request->post('billing_city', '')),
            $billingState,
            $billingStateCode ?: null,
            trim($request->post('billing_pincode', '')),
            trim($request->post('shipping_address', '')) ?: trim($request->post('billing_address', '')),
            trim($request->post('shipping_city', '')) ?: trim($request->post('billing_city', '')),
            $shippingState ?: $billingState,
            $shippingStateCode ?: $billingStateCode,
            trim($request->post('shipping_pincode', '')) ?: trim($request->post('billing_pincode', '')),
            trim($request->post('payment_terms', 'Net 15')),
            trim($request->post('notes', '')),
        ]);

        $customerId = (int)$pdo->lastInsertId();
        AuditService::log('customer.created', 'customer', $customerId, ['name' => $name]);

        Session::flash('success', "Customer '{$name}' created successfully.");

        if ($request->isAjax()) {
            return Response::json(['success' => true, 'id' => $customerId, 'name' => $name]);
        }

        return Response::redirect(url('/customers/' . $customerId));
    }

    public function show(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            Session::flash('error', 'Customer not found.');
            return Response::redirect(url('/customers'));
        }

        // Summary metrics
        $sumStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN status IN ('issued', 'partially_paid', 'paid') THEN total_amount ELSE 0 END), 0) as total_invoiced,
                COALESCE(SUM(CASE WHEN status IN ('issued', 'partially_paid', 'paid') THEN paid_amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status IN ('issued', 'partially_paid') THEN balance_due ELSE 0 END), 0) as outstanding_balance
            FROM invoices
            WHERE customer_id = ? AND business_id = ?
        ");
        $sumStmt->execute([$id, $businessId]);
        $metrics = $sumStmt->fetch(PDO::FETCH_ASSOC);

        // Invoices
        $invStmt = $pdo->prepare("
            SELECT * FROM invoices 
            WHERE customer_id = ? AND business_id = ? 
            ORDER BY issue_date DESC, id DESC 
            LIMIT 15
        ");
        $invStmt->execute([$id, $businessId]);
        $invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

        // Estimates
        $estStmt = $pdo->prepare("
            SELECT * FROM estimates 
            WHERE customer_id = ? AND business_id = ? 
            ORDER BY estimate_date DESC, id DESC 
            LIMIT 15
        ");
        $estStmt->execute([$id, $businessId]);
        $estimates = $estStmt->fetchAll(PDO::FETCH_ASSOC);

        // Payments
        $payStmt = $pdo->prepare("
            SELECT * FROM payments 
            WHERE customer_id = ? AND business_id = ? 
            ORDER BY payment_date DESC, id DESC 
            LIMIT 15
        ");
        $payStmt->execute([$id, $businessId]);
        $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        return new Response(View::render('customers.show', [
            'title' => $customer['name'],
            'customer' => $customer,
            'metrics' => $metrics,
            'invoices' => $invoices,
            'estimates' => $estimates,
            'payments' => $payments,
        ]));
    }

    public function edit(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            Session::flash('error', 'Customer not found.');
            return Response::redirect(url('/customers'));
        }

        $states = Config::get('constants.gst_states', []);
        $paymentTerms = Config::get('constants.payment_terms', []);

        return new Response(View::render('customers.edit', [
            'title' => 'Edit ' . $customer['name'],
            'customer' => $customer,
            'states' => $states,
            'paymentTerms' => $paymentTerms,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        if (!$stmt->fetch()) {
            Session::flash('error', 'Customer not found.');
            return Response::redirect(url('/customers'));
        }

        $name = trim($request->post('name', ''));
        if (empty($name)) {
            Session::flash('error', 'Customer name is required.');
            return Response::redirect(url('/customers/' . $id . '/edit'));
        }

        $gstin = strtoupper(trim($request->post('gstin', '')));
        $pan = strtoupper(trim($request->post('pan', '')));
        if (empty($pan) && strlen($gstin) === 15) {
            $pan = substr($gstin, 2, 10);
        }

        $billingStateCode = trim($request->post('billing_state_code', ''));
        $states = Config::get('constants.gst_states', []);
        $billingState = $states[$billingStateCode] ?? trim($request->post('billing_state', ''));

        $shippingStateCode = trim($request->post('shipping_state_code', ''));
        $shippingState = $states[$shippingStateCode] ?? trim($request->post('shipping_state', ''));

        $upStmt = $pdo->prepare("
            UPDATE customers SET
                name = ?, contact_person = ?, customer_type = ?, email = ?, phone = ?,
                gst_status = ?, gstin = ?, pan = ?,
                billing_address = ?, billing_city = ?, billing_state = ?, billing_state_code = ?, billing_pincode = ?,
                shipping_address = ?, shipping_city = ?, shipping_state = ?, shipping_state_code = ?, shipping_pincode = ?,
                payment_terms = ?, notes = ?, updated_at = NOW()
            WHERE id = ? AND business_id = ?
        ");

        $upStmt->execute([
            $name,
            trim($request->post('contact_person', '')),
            $request->post('customer_type', 'business'),
            trim($request->post('email', '')),
            trim($request->post('phone', '')),
            $request->post('gst_status', 'registered_regular'),
            $gstin ?: null,
            $pan ?: null,
            trim($request->post('billing_address', '')),
            trim($request->post('billing_city', '')),
            $billingState,
            $billingStateCode ?: null,
            trim($request->post('billing_pincode', '')),
            trim($request->post('shipping_address', '')),
            trim($request->post('shipping_city', '')),
            $shippingState,
            $shippingStateCode ?: null,
            trim($request->post('shipping_pincode', '')),
            trim($request->post('payment_terms', 'Net 15')),
            trim($request->post('notes', '')),
            $id,
            $businessId,
        ]);

        AuditService::log('customer.updated', 'customer', (int)$id, ['name' => $name]);
        Session::flash('success', "Customer '{$name}' updated successfully.");

        return Response::redirect(url('/customers/' . $id));
    }

    public function archive(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT id, name, is_archived FROM customers WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            $newStatus = $customer['is_archived'] ? 0 : 1;
            $up = $pdo->prepare("UPDATE customers SET is_archived = ?, updated_at = NOW() WHERE id = ? AND business_id = ?");
            $up->execute([$newStatus, $id, $businessId]);

            $action = $newStatus ? 'archived' : 'restored';
            AuditService::log("customer.{$action}", 'customer', (int)$id, ['name' => $customer['name']]);
            Session::flash('success', "Customer '{$customer['name']}' {$action} successfully.");
        }

        return Response::redirect(url('/customers/' . $id));
    }
}
