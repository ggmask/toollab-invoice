<?php

// Database Seeder for ToolLab Invoice
// Run: php database/seed.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Support/helpers.php';

use App\Services\DocumentNumberService;
use App\Services\InvoiceCalculationService;
use App\Services\TaxCalculationService;
use App\Support\Config;
use App\Support\Database;
use App\Support\FinancialYear;

Config::loadEnv(__DIR__ . '/../.env');

$pdo = Database::pdo();
echo "Starting Database Seeding...\n";

// Disable foreign keys for clean re-seeding if needed
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE audit_logs;");
$pdo->exec("TRUNCATE TABLE payment_allocations;");
$pdo->exec("TRUNCATE TABLE payments;");
$pdo->exec("TRUNCATE TABLE recurring_occurrences;");
$pdo->exec("TRUNCATE TABLE recurring_profiles;");
$pdo->exec("TRUNCATE TABLE estimate_items;");
$pdo->exec("TRUNCATE TABLE estimates;");
$pdo->exec("TRUNCATE TABLE invoice_items;");
$pdo->exec("TRUNCATE TABLE invoices;");
$pdo->exec("TRUNCATE TABLE document_sequences;");
$pdo->exec("TRUNCATE TABLE products;");
$pdo->exec("TRUNCATE TABLE customers;");
$pdo->exec("TRUNCATE TABLE tax_rates;");
$pdo->exec("TRUNCATE TABLE business_members;");
$pdo->exec("TRUNCATE TABLE businesses;");
$pdo->exec("TRUNCATE TABLE users;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

// 1. Create Default User
$passwordHash = password_hash('password123', PASSWORD_DEFAULT);
$uStmt = $pdo->prepare("
    INSERT INTO users (name, email, password_hash, phone, status, created_at, updated_at)
    VALUES ('Admin User', 'admin@toollab.in', ?, '+91 98765 43210', 'active', NOW(), NOW())
");
$uStmt->execute([$passwordHash]);
$userId = (int)$pdo->lastInsertId();
echo " + User created: admin@toollab.in / password123 (ID: {$userId})\n";

// 2. Create Business Workspace
$bizUuid = 'e82b7cf1-6922-441f-827c-31c38e9a1001';
$bStmt = $pdo->prepare("
    INSERT INTO businesses (
        uuid, legal_name, trade_name, business_type, gst_status, gstin, pan,
        address_line1, address_line2, city, state, state_code, pincode, country,
        phone, email, website, currency, currency_symbol,
        invoice_prefix, estimate_prefix, payment_prefix, number_padding,
        default_payment_terms, default_due_days, default_notes, default_terms,
        bank_name, bank_account_no, bank_ifsc, bank_branch, bank_upi_id, signature_title,
        created_at, updated_at
    ) VALUES (
        ?, 'ToolLab Technologies Private Limited', 'ToolLab', 'Private Limited Company', 'registered_regular', '27AAACT1234A1Z5', 'AAACT1234A',
        'Plot 42, Tech Park Avenue', 'Andheri East', 'Mumbai', 'Maharashtra', '27', '400069', 'India',
        '+91 22 2847 9000', 'billing@toollab.in', 'https://toollab.in', 'INR', '₹',
        'INV', 'EST', 'PAY', 4,
        'Net 15', 15,
        'Thank you for partnering with ToolLab! Please make payment by the due date.',
        '1. Goods/services once delivered cannot be returned.\n2. Interest @ 18% p.a. will be charged on overdue payments.\n3. Subject to Mumbai Jurisdiction.',
        'HDFC Bank Ltd', '50200012345678', 'HDFC0001234', 'Andheri East Branch', 'toollab@okhdfcbank', 'Authorized Signatory',
        NOW(), NOW()
    )
");
$bStmt->execute([$bizUuid]);
$businessId = (int)$pdo->lastInsertId();
echo " + Business created: ToolLab Technologies Pvt Ltd (ID: {$businessId})\n";

// Link Membership
$pdo->prepare("INSERT INTO business_members (business_id, user_id, role, is_active, created_at) VALUES (?, ?, 'owner', 1, NOW())")
    ->execute([$businessId, $userId]);

// 3. Tax Rates
$rates = [
    ['GST 0% (Nil / Exempt)', 0.00, 'exempt'],
    ['GST 5%', 5.00, 'taxable'],
    ['GST 12%', 12.00, 'taxable'],
    ['GST 18%', 18.00, 'taxable'],
    ['GST 28%', 28.00, 'taxable'],
];
$trStmt = $pdo->prepare("INSERT INTO tax_rates (business_id, name, rate, tax_category, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
foreach ($rates as $r) {
    $trStmt->execute([$businessId, $r[0], $r[1], $r[2]]);
}
echo " + Default tax rates created.\n";

// 4. Customers
$customersData = [
    [
        'name' => 'Acme Retail Solutions LLP',
        'contact_person' => 'Rajesh Sharma',
        'customer_type' => 'business',
        'email' => 'rajesh@acmeretail.in',
        'phone' => '+91 98200 11223',
        'gst_status' => 'registered_regular',
        'gstin' => '27AABCA5678B1Z2',
        'billing_address' => 'Shop 14, High Street Mall, Senapati Bapat Marg',
        'billing_city' => 'Mumbai',
        'billing_state' => 'Maharashtra',
        'billing_state_code' => '27',
        'billing_pincode' => '400013',
    ],
    [
        'name' => 'Delhi Digital Ventures Pvt Ltd',
        'contact_person' => 'Neha Gupta',
        'customer_type' => 'business',
        'email' => 'accounts@delhidigital.com',
        'phone' => '+91 98111 22334',
        'gst_status' => 'registered_regular',
        'gstin' => '07AABCD9988C1Z4', // Interstate Delhi
        'billing_address' => 'B-4, Connaught Place, Inner Circle',
        'billing_city' => 'New Delhi',
        'billing_state' => 'Delhi',
        'billing_state_code' => '07',
        'billing_pincode' => '110001',
    ],
    [
        'name' => 'Bangalore Cloud Systems',
        'contact_person' => 'Karthik Raman',
        'customer_type' => 'business',
        'email' => 'karthik@bcsystems.io',
        'phone' => '+91 99000 44556',
        'gst_status' => 'registered_regular',
        'gstin' => '29AAFCB7766D1Z8', // Interstate Karnataka
        'billing_address' => '102, Indiranagar 100ft Road',
        'billing_city' => 'Bengaluru',
        'billing_state' => 'Karnataka',
        'billing_state_code' => '29',
        'billing_pincode' => '560038',
    ],
    [
        'name' => 'Pooja Verma (Freelance Designer)',
        'contact_person' => 'Pooja Verma',
        'customer_type' => 'individual',
        'email' => 'pooja.verma@gmail.com',
        'phone' => '+91 97110 99887',
        'gst_status' => 'unregistered',
        'gstin' => null,
        'billing_address' => 'Flat 502, Green Meadows, Kothrud',
        'billing_city' => 'Pune',
        'billing_state' => 'Maharashtra',
        'billing_state_code' => '27',
        'billing_pincode' => '411038',
    ],
];

$cIns = $pdo->prepare("
    INSERT INTO customers (
        business_id, uuid, name, contact_person, customer_type, email, phone,
        gst_status, gstin, billing_address, billing_city, billing_state, billing_state_code, billing_pincode,
        shipping_address, shipping_city, shipping_state, shipping_state_code, shipping_pincode,
        payment_terms, is_archived, created_at, updated_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        'Net 15', 0, NOW(), NOW()
    )
");

$customerIds = [];
foreach ($customersData as $c) {
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $cIns->execute([
        $businessId, $uuid, $c['name'], $c['contact_person'], $c['customer_type'], $c['email'], $c['phone'],
        $c['gst_status'], $c['gstin'], $c['billing_address'], $c['billing_city'], $c['billing_state'], $c['billing_state_code'], $c['billing_pincode'],
        $c['billing_address'], $c['billing_city'], $c['billing_state'], $c['billing_state_code'], $c['billing_pincode'],
    ]);
    $customerIds[] = (int)$pdo->lastInsertId();
}
echo " + Seeded " . count($customerIds) . " customers.\n";

// 5. Products & Services
$productsData = [
    [
        'name' => 'Custom Web Application Development',
        'sku' => 'SRV-WEB-01',
        'item_type' => 'services',
        'hsn_sac' => '998314',
        'unit' => 'HRS',
        'selling_price' => 1500.00,
        'gst_rate' => 18.00,
        'description' => 'Full-stack PHP/MySQL responsive web development and API integration.',
    ],
    [
        'name' => 'Cloud Hosting & Maintenance (Monthly)',
        'sku' => 'SRV-HOST-MTH',
        'item_type' => 'services',
        'hsn_sac' => '998315',
        'unit' => 'MTH',
        'selling_price' => 4500.00,
        'gst_rate' => 18.00,
        'description' => 'Managed cloud hosting, daily automated database backups, security patches, and 99.9% uptime monitoring.',
    ],
    [
        'name' => 'High-Speed Thermal POS Printer',
        'sku' => 'HW-PRN-80MM',
        'item_type' => 'goods',
        'hsn_sac' => '844332',
        'unit' => 'PCS',
        'selling_price' => 6800.00,
        'gst_rate' => 18.00,
        'description' => '80mm USB + Ethernet thermal receipt printer with auto-cutter for retail billing.',
    ],
    [
        'name' => 'Premium Thermal Receipt Paper Rolls (Pack of 50)',
        'sku' => 'STN-ROLL-50',
        'item_type' => 'goods',
        'hsn_sac' => '481141',
        'unit' => 'BOX',
        'selling_price' => 1250.00,
        'gst_rate' => 12.00,
        'description' => 'BPA-free high sensitivity 79mm x 50m thermal paper rolls.',
    ],
    [
        'name' => 'Wireless 2D QR Barcode Scanner',
        'sku' => 'HW-SCN-2D',
        'item_type' => 'goods',
        'hsn_sac' => '847160',
        'unit' => 'PCS',
        'selling_price' => 3200.00,
        'gst_rate' => 18.00,
        'description' => 'Handheld wireless Bluetooth & 2.4GHz QR code scanner with cradle.',
    ],
];

$pIns = $pdo->prepare("
    INSERT INTO products (
        business_id, uuid, name, sku, item_type, hsn_sac, unit,
        selling_price, gst_rate, tax_category, description, is_archived,
        created_at, updated_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, 'taxable', ?, 0,
        NOW(), NOW()
    )
");

$productIds = [];
foreach ($productsData as $p) {
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $pIns->execute([
        $businessId, $uuid, $p['name'], $p['sku'], $p['item_type'], $p['hsn_sac'], $p['unit'],
        $p['selling_price'], $p['gst_rate'], $p['description']
    ]);
    $productIds[] = (int)$pdo->lastInsertId();
}
echo " + Seeded " . count($productIds) . " catalog items.\n";

// 6. Invoices: Let's create realistic invoices across statuses (Paid, Partially Paid, Issued, Draft)
$fy = FinancialYear::current();

// Invoice 1: Paid (Intra-state Maharashtra)
$inv1Items = [
    [
        'product_id' => $productIds[0],
        'name' => 'Custom Web Application Development',
        'description' => 'Phase 1: Architecture, database setup and invoicing engine (40 hours)',
        'hsn_sac' => '998314',
        'item_type' => 'services',
        'quantity' => 40,
        'unit' => 'HRS',
        'unit_price' => 1500.00,
        'discount_type' => 'percentage',
        'discount_value' => 5.0, // 5% discount
        'gst_rate' => 18.00,
    ],
    [
        'product_id' => $productIds[1],
        'name' => 'Cloud Hosting & Maintenance (Monthly)',
        'description' => 'Hosting for October 2026',
        'hsn_sac' => '998315',
        'item_type' => 'services',
        'quantity' => 1,
        'unit' => 'MTH',
        'unit_price' => 4500.00,
        'discount_type' => 'flat',
        'discount_value' => 0.0,
        'gst_rate' => 18.00,
    ]
];
$calc1 = InvoiceCalculationService::calculate($inv1Items, 'intra_state', 'flat', 0, 0);
$num1 = DocumentNumberService::generateNextNumber($businessId, 'invoice', 'INV', 4, $fy);

$insInv = $pdo->prepare("
    INSERT INTO invoices (
        business_id, customer_id, uuid, invoice_number, financial_year,
        document_type, status, issue_date, due_date, po_number, payment_terms,
        place_of_supply_state, place_of_supply_state_code, supply_type, is_reverse_charge,
        supplier_name, supplier_trade_name, supplier_gstin, supplier_address, supplier_city, supplier_state, supplier_state_code, supplier_pincode, supplier_phone, supplier_email,
        customer_name, customer_gstin, customer_billing_address, customer_billing_city, customer_billing_state, customer_billing_state_code, customer_billing_pincode, customer_shipping_address, customer_email, customer_phone,
        subtotal_gross, line_discounts_total, invoice_discount_type, invoice_discount_value, invoice_discount_amount,
        taxable_value, cgst_amount, sgst_amount, igst_amount, cess_amount, tax_total,
        additional_charges, rounding_adjustment, total_amount, paid_amount, balance_due,
        notes, terms, bank_details, signature_title, issued_at, issued_by_user_id, created_at, updated_at
    ) VALUES (
        ?, ?, ?, ?, ?,
        'tax_invoice', ?, ?, ?, ?, 'Net 15',
        ?, ?, ?, 0,
        'ToolLab Technologies Private Limited', 'ToolLab', '27AAACT1234A1Z5', 'Plot 42, Tech Park Avenue, Andheri East', 'Mumbai', 'Maharashtra', '27', '400069', '+91 22 2847 9000', 'billing@toollab.in',
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        'Thank you for your business!', '1. Payment terms: Net 15 days.', ?, 'Authorized Signatory', ?, ?, NOW(), NOW()
    )
");

$bankJson = json_encode(['bank_name' => 'HDFC Bank Ltd', 'account_no' => '50200012345678', 'ifsc' => 'HDFC0001234', 'branch' => 'Andheri East Branch', 'upi_id' => 'toollab@okhdfcbank']);

$uuid1 = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$insInv->execute([
    $businessId, $customerIds[0], $uuid1, $num1, $fy,
    'paid', '2026-10-05', '2026-10-20', 'PO-2026-089',
    'Maharashtra', '27', 'intra_state',
    $customersData[0]['name'], $customersData[0]['gstin'], $customersData[0]['billing_address'], $customersData[0]['billing_city'], $customersData[0]['billing_state'], $customersData[0]['billing_state_code'], $customersData[0]['billing_pincode'], $customersData[0]['billing_address'], $customersData[0]['email'], $customersData[0]['phone'],
    $calc1['subtotal_gross'], $calc1['line_discounts_total'], $calc1['invoice_discount_type'], $calc1['invoice_discount_value'], $calc1['invoice_discount_amount'],
    $calc1['taxable_value'], $calc1['cgst_amount'], $calc1['sgst_amount'], $calc1['igst_amount'], $calc1['cess_amount'], $calc1['tax_total'],
    $calc1['additional_charges'], $calc1['rounding_adjustment'], $calc1['total_amount'], $calc1['total_amount'], 0.00,
    $bankJson, '2026-10-05 10:00:00', $userId
]);
$inv1Id = (int)$pdo->lastInsertId();

$insItem = $pdo->prepare("
    INSERT INTO invoice_items (
        invoice_id, product_id, item_order, name, description, hsn_sac, item_type,
        quantity, unit, unit_price, gross_amount,
        discount_type, discount_value, discount_amount, taxable_value,
        tax_category, gst_rate, cgst_rate, cgst_amount, sgst_rate, sgst_amount,
        igst_rate, igst_amount, cess_rate, cess_amount, total_tax, line_total
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($calc1['items'] as $it) {
    $insItem->execute([
        $inv1Id, $it['product_id'], $it['item_order'], $it['name'], $it['description'], $it['hsn_sac'], $it['item_type'],
        $it['quantity'], $it['unit'], $it['unit_price'], $it['gross_amount'],
        $it['discount_type'], $it['discount_value'], $it['discount_amount'], $it['taxable_value'],
        $it['tax_category'], $it['gst_rate'], $it['cgst_rate'], $it['cgst_amount'], $it['sgst_rate'], $it['sgst_amount'],
        $it['igst_rate'], $it['igst_amount'], $it['cess_rate'], $it['cess_amount'], $it['total_tax'], $it['line_total']
    ]);
}

// Payment record for Inv 1
$payNum1 = DocumentNumberService::generateNextNumber($businessId, 'payment', 'PAY', 4, $fy);
$pUuid1 = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$pdo->prepare("
    INSERT INTO payments (
        business_id, customer_id, uuid, payment_number, payment_date,
        amount, allocated_amount, unallocated_amount, payment_method,
        reference_number, bank_name, status, created_by_user_id, created_at, updated_at
    ) VALUES (?, ?, ?, ?, '2026-10-12', ?, ?, 0.00, 'bank_transfer', 'NEFT-HDFC-998822', 'HDFC Bank', 'posted', ?, NOW(), NOW())
")->execute([$businessId, $customerIds[0], $pUuid1, $payNum1, $calc1['total_amount'], $calc1['total_amount'], $userId]);
$pay1Id = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO payment_allocations (payment_id, invoice_id, amount, created_at) VALUES (?, ?, ?, NOW())")
    ->execute([$pay1Id, $inv1Id, $calc1['total_amount']]);

echo " + Seeded Invoice 1 (Paid): {$num1} - Total: ₹ " . number_format($calc1['total_amount'], 2) . "\n";

// Invoice 2: Partially Paid (Inter-state Delhi with IGST 18% & 12%)
$inv2Items = [
    [
        'product_id' => $productIds[2],
        'name' => 'High-Speed Thermal POS Printer',
        'description' => 'Model TP-800 with auto cutter (5 units for Delhi retail outlet)',
        'hsn_sac' => '844332',
        'item_type' => 'goods',
        'quantity' => 5,
        'unit' => 'PCS',
        'unit_price' => 6800.00,
        'discount_type' => 'flat',
        'discount_value' => 1000.0,
        'gst_rate' => 18.00,
    ],
    [
        'product_id' => $productIds[3],
        'name' => 'Premium Thermal Receipt Paper Rolls (Pack of 50)',
        'description' => '80mm rolls',
        'hsn_sac' => '481141',
        'item_type' => 'goods',
        'quantity' => 10,
        'unit' => 'BOX',
        'unit_price' => 1250.00,
        'discount_type' => 'percentage',
        'discount_value' => 0.0,
        'gst_rate' => 12.00,
    ]
];
$calc2 = InvoiceCalculationService::calculate($inv2Items, 'inter_state', 'flat', 500, 350); // Shipping 350, flat disc 500
$num2 = DocumentNumberService::generateNextNumber($businessId, 'invoice', 'INV', 4, $fy);
$uuid2 = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

$paid2 = 25000.00;
$bal2 = round($calc2['total_amount'] - $paid2, 2);

$insInv->execute([
    $businessId, $customerIds[1], $uuid2, $num2, $fy,
    'partially_paid', '2026-10-18', '2026-11-02', 'DEL-PO-441',
    'Delhi', '07', 'inter_state',
    $customersData[1]['name'], $customersData[1]['gstin'], $customersData[1]['billing_address'], $customersData[1]['billing_city'], $customersData[1]['billing_state'], $customersData[1]['billing_state_code'], $customersData[1]['billing_pincode'], $customersData[1]['billing_address'], $customersData[1]['email'], $customersData[1]['phone'],
    $calc2['subtotal_gross'], $calc2['line_discounts_total'], $calc2['invoice_discount_type'], $calc2['invoice_discount_value'], $calc2['invoice_discount_amount'],
    $calc2['taxable_value'], $calc2['cgst_amount'], $calc2['sgst_amount'], $calc2['igst_amount'], $calc2['cess_amount'], $calc2['tax_total'],
    $calc2['additional_charges'], $calc2['rounding_adjustment'], $calc2['total_amount'], $paid2, $bal2,
    $bankJson, '2026-10-18 14:30:00', $userId
]);
$inv2Id = (int)$pdo->lastInsertId();

foreach ($calc2['items'] as $it) {
    $insItem->execute([
        $inv2Id, $it['product_id'], $it['item_order'], $it['name'], $it['description'], $it['hsn_sac'], $it['item_type'],
        $it['quantity'], $it['unit'], $it['unit_price'], $it['gross_amount'],
        $it['discount_type'], $it['discount_value'], $it['discount_amount'], $it['taxable_value'],
        $it['tax_category'], $it['gst_rate'], $it['cgst_rate'], $it['cgst_amount'], $it['sgst_rate'], $it['sgst_amount'],
        $it['igst_rate'], $it['igst_amount'], $it['cess_rate'], $it['cess_amount'], $it['total_tax'], $it['line_total']
    ]);
}

// Payment record for Inv 2 (Partial 25,000)
$payNum2 = DocumentNumberService::generateNextNumber($businessId, 'payment', 'PAY', 4, $fy);
$pUuid2 = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$pdo->prepare("
    INSERT INTO payments (
        business_id, customer_id, uuid, payment_number, payment_date,
        amount, allocated_amount, unallocated_amount, payment_method,
        reference_number, bank_name, status, created_by_user_id, created_at, updated_at
    ) VALUES (?, ?, ?, ?, '2026-10-22', 25000.00, 25000.00, 0.00, 'upi', 'UPI/2290118833/HDFC', 'Google Pay', 'posted', ?, NOW(), NOW())
")->execute([$businessId, $customerIds[1], $pUuid2, $payNum2, $userId]);
$pay2Id = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO payment_allocations (payment_id, invoice_id, amount, created_at) VALUES (?, ?, 25000.00, NOW())")
    ->execute([$pay2Id, $inv2Id]);

echo " + Seeded Invoice 2 (Partially Paid): {$num2} - Total: ₹ " . number_format($calc2['total_amount'], 2) . " (Balance: ₹ " . number_format($bal2, 2) . ")\n";

// Invoice 3: Issued Unpaid (Interstate Karnataka)
$inv3Items = [
    [
        'product_id' => $productIds[4],
        'name' => 'Wireless 2D QR Barcode Scanner',
        'description' => 'Batch of 6 scanners for warehouse',
        'hsn_sac' => '847160',
        'item_type' => 'goods',
        'quantity' => 6,
        'unit' => 'PCS',
        'unit_price' => 3200.00,
        'discount_type' => 'percentage',
        'discount_value' => 5.0,
        'gst_rate' => 18.00,
    ]
];
$calc3 = InvoiceCalculationService::calculate($inv3Items, 'inter_state', 'flat', 0, 200); // 200 shipping
$num3 = DocumentNumberService::generateNextNumber($businessId, 'invoice', 'INV', 4, $fy);
$uuid3 = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

$insInv->execute([
    $businessId, $customerIds[2], $uuid3, $num3, $fy,
    'issued', '2026-10-25', '2026-11-09', 'BCS-PO-990',
    'Karnataka', '29', 'inter_state',
    $customersData[2]['name'], $customersData[2]['gstin'], $customersData[2]['billing_address'], $customersData[2]['billing_city'], $customersData[2]['billing_state'], $customersData[2]['billing_state_code'], $customersData[2]['billing_pincode'], $customersData[2]['billing_address'], $customersData[2]['email'], $customersData[2]['phone'],
    $calc3['subtotal_gross'], $calc3['line_discounts_total'], $calc3['invoice_discount_type'], $calc3['invoice_discount_value'], $calc3['invoice_discount_amount'],
    $calc3['taxable_value'], $calc3['cgst_amount'], $calc3['sgst_amount'], $calc3['igst_amount'], $calc3['cess_amount'], $calc3['tax_total'],
    $calc3['additional_charges'], $calc3['rounding_adjustment'], $calc3['total_amount'], 0.00, $calc3['total_amount'],
    $bankJson, '2026-10-25 16:00:00', $userId
]);
$inv3Id = (int)$pdo->lastInsertId();
foreach ($calc3['items'] as $it) {
    $insItem->execute([
        $inv3Id, $it['product_id'], $it['item_order'], $it['name'], $it['description'], $it['hsn_sac'], $it['item_type'],
        $it['quantity'], $it['unit'], $it['unit_price'], $it['gross_amount'],
        $it['discount_type'], $it['discount_value'], $it['discount_amount'], $it['taxable_value'],
        $it['tax_category'], $it['gst_rate'], $it['cgst_rate'], $it['cgst_amount'], $it['sgst_rate'], $it['sgst_amount'],
        $it['igst_rate'], $it['igst_amount'], $it['cess_rate'], $it['cess_amount'], $it['total_tax'], $it['line_total']
    ]);
}
echo " + Seeded Invoice 3 (Issued Unpaid): {$num3} - Total: ₹ " . number_format($calc3['total_amount'], 2) . "\n";

// 7. Estimate
$estNum1 = DocumentNumberService::generateNextNumber($businessId, 'estimate', 'EST', 4, $fy);
$estItems = [
    [
        'product_id' => $productIds[0],
        'name' => 'Custom Web Application Development',
        'description' => 'Mobile App API and Merchant Portal Extension',
        'hsn_sac' => '998314',
        'item_type' => 'services',
        'quantity' => 60,
        'unit' => 'HRS',
        'unit_price' => 1500.00,
        'discount_type' => 'percentage',
        'discount_value' => 10.0,
        'gst_rate' => 18.00,
    ]
];
$calcEst = InvoiceCalculationService::calculate($estItems, 'intra_state');
$estUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

$pdo->prepare("
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
        'sent', '2026-10-26', '2026-11-25',
        'Maharashtra', '27', 'intra_state',
        'ToolLab Technologies Private Limited', 'ToolLab', '27AAACT1234A1Z5', 'Plot 42, Tech Park Avenue, Andheri East', 'Mumbai', 'Maharashtra', '27', '400069', '+91 22 2847 9000', 'billing@toollab.in',
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        'Quotation valid for 30 calendar days.', '1. 50% advance on sign-off.\n2. Milestone delivery.', NOW(), NOW()
    )
")->execute([
    $businessId, $customerIds[0], $estUuid, $estNum1, $fy,
    $customersData[0]['name'], $customersData[0]['gstin'], $customersData[0]['billing_address'], $customersData[0]['billing_city'], $customersData[0]['billing_state'], $customersData[0]['billing_state_code'], $customersData[0]['billing_pincode'], $customersData[0]['email'], $customersData[0]['phone'],
    $calcEst['subtotal_gross'], $calcEst['line_discounts_total'], $calcEst['invoice_discount_type'], $calcEst['invoice_discount_value'], $calcEst['invoice_discount_amount'],
    $calcEst['taxable_value'], $calcEst['cgst_amount'], $calcEst['sgst_amount'], $calcEst['igst_amount'], $calcEst['cess_amount'], $calcEst['tax_total'],
    $calcEst['additional_charges'], $calcEst['rounding_adjustment'], $calcEst['total_amount'],
]);
$estId = (int)$pdo->lastInsertId();

$insEstItem = $pdo->prepare("
    INSERT INTO estimate_items (
        estimate_id, product_id, item_order, name, description, hsn_sac, item_type,
        quantity, unit, unit_price, gross_amount,
        discount_type, discount_value, discount_amount, taxable_value,
        tax_category, gst_rate, cgst_rate, cgst_amount, sgst_rate, sgst_amount,
        igst_rate, igst_amount, cess_rate, cess_amount, total_tax, line_total
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($calcEst['items'] as $it) {
    $insEstItem->execute([
        $estId, $it['product_id'], $it['item_order'], $it['name'], $it['description'], $it['hsn_sac'], $it['item_type'],
        $it['quantity'], $it['unit'], $it['unit_price'], $it['gross_amount'],
        $it['discount_type'], $it['discount_value'], $it['discount_amount'], $it['taxable_value'],
        $it['tax_category'], $it['gst_rate'], $it['cgst_rate'], $it['cgst_amount'], $it['sgst_rate'], $it['sgst_amount'],
        $it['igst_rate'], $it['igst_amount'], $it['cess_rate'], $it['cess_amount'], $it['total_tax'], $it['line_total']
    ]);
}
echo " + Seeded Estimate: {$estNum1} - Total: ₹ " . number_format($calcEst['total_amount'], 2) . "\n";

// 8. Recurring Invoice Profile
$recUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$recItems = [
    [
        'product_id' => $productIds[1],
        'name' => 'Cloud Hosting & Maintenance (Monthly)',
        'description' => 'Monthly dedicated VPS, daily backups and SLA support',
        'hsn_sac' => '998315',
        'item_type' => 'services',
        'quantity' => 1,
        'unit' => 'MTH',
        'unit_price' => 4500.00,
        'discount_type' => 'flat',
        'discount_value' => 0.0,
        'gst_rate' => 18.00,
    ]
];
$pdo->prepare("
    INSERT INTO recurring_profiles (
        business_id, customer_id, uuid, profile_name, frequency,
        start_date, end_date, next_issue_date, due_days, generation_mode,
        status, items_json, po_number, notes, terms, created_at, updated_at
    ) VALUES (
        ?, ?, ?, 'Acme Monthly Retainer', 'monthly',
        '2026-11-01', '2027-10-31', '2026-11-01', 15, 'auto',
        'active', ?, 'RECUR-PO-01', 'Monthly recurring retainer invoice', 'Standard billing terms apply.', NOW(), NOW()
    )
")->execute([$businessId, $customerIds[0], $recUuid, json_encode($recItems, JSON_UNESCAPED_UNICODE)]);
echo " + Seeded Recurring Profile: Acme Monthly Retainer\n";

echo "Database Seeding Completed Successfully!\n";
