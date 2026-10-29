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

class ProductController
{
    public function index(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $search = trim($request->get('search', ''));
        $itemType = trim($request->get('item_type', ''));
        $status = $request->get('status', 'active');
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = ["business_id = ?"];
        $params = [$businessId];

        if ($status === 'archived') {
            $where[] = "is_archived = 1";
        } elseif ($status === 'active') {
            $where[] = "is_archived = 0";
        }

        if ($itemType !== '') {
            $where[] = "item_type = ?";
            $params[] = $itemType;
        }

        if ($search !== '') {
            $where[] = "(name LIKE ? OR sku LIKE ? OR hsn_sac LIKE ? OR description LIKE ?)";
            $term = "%{$search}%";
            array_push($params, $term, $term, $term, $term);
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE {$whereSql}");
        $countStmt->execute($params);
        $totalProducts = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT * FROM products 
            WHERE {$whereSql}
            ORDER BY name ASC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($totalProducts / $perPage);

        return new Response(View::render('products.index', [
            'title' => 'Products & Services',
            'products' => $products,
            'totalProducts' => $totalProducts,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'itemType' => $itemType,
            'status' => $status,
        ]));
    }

    public function create(Request $request): Response
    {
        $units = Config::get('constants.units', []);
        $gstRates = Config::get('constants.gst_rates', []);

        return new Response(View::render('products.create', [
            'title' => 'Add New Item',
            'units' => $units,
            'gstRates' => $gstRates,
        ]));
    }

    public function store(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $name = trim($request->post('name', ''));
        if (empty($name)) {
            Session::flash('error', 'Item name is required.');
            return Response::redirect(url('/products/create'));
        }

        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $price = max(0.0, (float)$request->post('selling_price', 0));
        $gstRate = max(0.0, (float)$request->post('gst_rate', 18));
        $sku = trim($request->post('sku', ''));

        $stmt = $pdo->prepare("
            INSERT INTO products (
                business_id, uuid, name, sku, item_type, hsn_sac, unit,
                selling_price, gst_rate, tax_category, description, is_archived,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 0,
                NOW(), NOW()
            )
        ");

        $stmt->execute([
            $businessId,
            $uuid,
            $name,
            $sku ?: null,
            $request->post('item_type', 'goods'),
            trim($request->post('hsn_sac', '')),
            trim($request->post('unit', 'PCS')),
            $price,
            $gstRate,
            $request->post('tax_category', 'taxable'),
            trim($request->post('description', '')),
        ]);

        $productId = (int)$pdo->lastInsertId();
        AuditService::log('product.created', 'product', $productId, ['name' => $name, 'price' => $price]);

        Session::flash('success', "Item '{$name}' created successfully.");

        if ($request->isAjax()) {
            return Response::json(['success' => true, 'id' => $productId, 'name' => $name]);
        }

        return Response::redirect(url('/products'));
    }

    public function edit(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            Session::flash('error', 'Product not found.');
            return Response::redirect(url('/products'));
        }

        $units = Config::get('constants.units', []);
        $gstRates = Config::get('constants.gst_rates', []);

        return new Response(View::render('products.edit', [
            'title' => 'Edit ' . $product['name'],
            'product' => $product,
            'units' => $units,
            'gstRates' => $gstRates,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        if (!$stmt->fetch()) {
            Session::flash('error', 'Product not found.');
            return Response::redirect(url('/products'));
        }

        $name = trim($request->post('name', ''));
        if (empty($name)) {
            Session::flash('error', 'Item name is required.');
            return Response::redirect(url('/products/' . $id . '/edit'));
        }

        $price = max(0.0, (float)$request->post('selling_price', 0));
        $gstRate = max(0.0, (float)$request->post('gst_rate', 18));
        $sku = trim($request->post('sku', ''));

        $upStmt = $pdo->prepare("
            UPDATE products SET
                name = ?, sku = ?, item_type = ?, hsn_sac = ?, unit = ?,
                selling_price = ?, gst_rate = ?, tax_category = ?, description = ?,
                updated_at = NOW()
            WHERE id = ? AND business_id = ?
        ");

        $upStmt->execute([
            $name,
            $sku ?: null,
            $request->post('item_type', 'goods'),
            trim($request->post('hsn_sac', '')),
            trim($request->post('unit', 'PCS')),
            $price,
            $gstRate,
            $request->post('tax_category', 'taxable'),
            trim($request->post('description', '')),
            $id,
            $businessId,
        ]);

        AuditService::log('product.updated', 'product', (int)$id, ['name' => $name]);
        Session::flash('success', "Item '{$name}' updated successfully.");

        return Response::redirect(url('/products'));
    }

    public function archive(Request $request, string $id): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT id, name, is_archived FROM products WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $newStatus = $product['is_archived'] ? 0 : 1;
            $up = $pdo->prepare("UPDATE products SET is_archived = ?, updated_at = NOW() WHERE id = ? AND business_id = ?");
            $up->execute([$newStatus, $id, $businessId]);

            $action = $newStatus ? 'archived' : 'restored';
            AuditService::log("product.{$action}", 'product', (int)$id, ['name' => $product['name']]);
            Session::flash('success', "Item '{$product['name']}' {$action} successfully.");
        }

        return Response::redirect(url('/products'));
    }

    public function apiSearch(Request $request): Response
    {
        $business = Session::get('business');
        $businessId = (int)$business['id'];
        $pdo = Database::pdo();

        $query = trim($request->get('q', ''));
        $stmt = $pdo->prepare("
            SELECT id, name, sku, hsn_sac, unit, selling_price, gst_rate, tax_category, description, item_type
            FROM products
            WHERE business_id = ? AND is_archived = 0 AND (name LIKE ? OR sku LIKE ? OR hsn_sac LIKE ?)
            ORDER BY name ASC
            LIMIT 20
        ");
        $term = "%{$query}%";
        $stmt->execute([$businessId, $term, $term, $term]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::json($products);
    }
}
