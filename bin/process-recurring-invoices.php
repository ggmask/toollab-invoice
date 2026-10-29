<?php

// CLI runner for processing due recurring invoices
// Usage: php bin/process-recurring-invoices.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Support/helpers.php';

use App\Controllers\RecurringInvoiceController;
use App\Services\DocumentNumberService;
use App\Services\InvoiceCalculationService;
use App\Services\TaxCalculationService;
use App\Support\Config;
use App\Support\Database;
use App\Support\FinancialYear;

Config::loadEnv(__DIR__ . '/../.env');

echo "[" . date('Y-m-d H:i:s') . "] Starting recurring invoices processor...\n";

$pdo = Database::pdo();
$today = date('Y-m-d');

// Find active auto-generating profiles due on or before today
$stmt = $pdo->prepare("
    SELECT r.*, b.legal_name, b.trade_name, b.gstin, b.state_code, b.address_line1, b.address_line2, b.city, b.state, b.pincode, b.phone as business_phone, b.email as business_email, b.invoice_prefix, b.number_padding, b.bank_name, b.bank_account_no, b.bank_ifsc, b.bank_branch, b.bank_upi_id, b.signature_title, b.default_notes, b.default_terms,
           c.name as customer_name, c.gstin as customer_gstin, c.billing_address, c.billing_city, c.billing_state, c.billing_state_code, c.billing_pincode, c.shipping_address, c.email as customer_email, c.phone as customer_phone
    FROM recurring_profiles r
    JOIN businesses b ON b.id = r.business_id
    JOIN customers c ON c.id = r.customer_id
    WHERE r.status = 'active'
      AND r.generation_mode = 'auto'
      AND r.next_issue_date <= ?
");
$stmt->execute([$today]);
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($profiles) . " profiles due for processing.\n";

foreach ($profiles as $profile) {
    $scheduledDate = $profile['next_issue_date'];
    echo "Processing profile #{$profile['id']} ({$profile['profile_name']}) for scheduled date {$scheduledDate}...\n";

    // Check idempotency
    $occCheck = $pdo->prepare("SELECT id FROM recurring_occurrences WHERE recurring_profile_id = ? AND scheduled_date = ?");
    $occCheck->execute([$profile['id'], $scheduledDate]);
    if ($occCheck->fetch()) {
        echo " - Already processed for this date. Skipping.\n";
        continue;
    }

    try {
        Database::transaction(function (PDO $pdo) use ($profile, $scheduledDate) {
            $items = json_decode($profile['items_json'], true) ?: [];
            $supplyType = TaxCalculationService::determineSupplyType($profile['state_code'], $profile['billing_state_code']);
            $calc = InvoiceCalculationService::calculate($items, $supplyType);
            $fy = FinancialYear::forDate($scheduledDate);

            $prefix = $profile['invoice_prefix'] ?? 'INV';
            $padding = (int)($profile['number_padding'] ?? 4);
            $invoiceNumber = DocumentNumberService::generateNextNumber($profile['business_id'], 'invoice', $prefix, $padding, $fy);
            $dueDate = date('Y-m-d', strtotime($scheduledDate . ' +' . $profile['due_days'] . ' days'));

            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Insert Invoice
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
                    issued_at, created_from_recurring_id, created_at, updated_at
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
                    NOW(), ?, NOW(), NOW()
                )
            ");

            $ins->execute([
                $profile['business_id'], $profile['customer_id'], $uuid, $invoiceNumber, $fy,
                $scheduledDate, $dueDate, $profile['po_number'],
                $profile['billing_state'], $profile['billing_state_code'], $supplyType,
                $profile['legal_name'], $profile['trade_name'] ?? $profile['legal_name'], $profile['gstin'],
                trim(($profile['address_line1'] ?? '') . ' ' . ($profile['address_line2'] ?? '')),
                $profile['city'], $profile['state'], $profile['state_code'], $profile['pincode'], $profile['business_phone'], $profile['business_email'],
                $profile['customer_name'], $profile['customer_gstin'], $profile['billing_address'], $profile['billing_city'], $profile['billing_state'], $profile['billing_state_code'], $profile['billing_pincode'], $profile['shipping_address'] ?: $profile['billing_address'], $profile['customer_email'], $profile['customer_phone'],
                $calc['subtotal_gross'], $calc['line_discounts_total'], $calc['invoice_discount_type'], $calc['invoice_discount_value'], $calc['invoice_discount_amount'],
                $calc['taxable_value'], $calc['cgst_amount'], $calc['sgst_amount'], $calc['igst_amount'], $calc['cess_amount'], $calc['tax_total'],
                $calc['additional_charges'], $calc['rounding_adjustment'], $calc['total_amount'], $calc['total_amount'],
                $profile['notes'] ?: $profile['default_notes'],
                $profile['terms'] ?: $profile['default_terms'],
                json_encode([
                    'bank_name' => $profile['bank_name'] ?? '',
                    'account_no' => $profile['bank_account_no'] ?? '',
                    'ifsc' => $profile['bank_ifsc'] ?? '',
                    'branch' => $profile['bank_branch'] ?? '',
                    'upi_id' => $profile['bank_upi_id'] ?? '',
                ]),
                $profile['signature_title'] ?? 'Authorized Signatory',
                $profile['id']
            ]);

            $invId = (int)$pdo->lastInsertId();

            // Insert line items
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

            // Log occurrence
            $occ = $pdo->prepare("INSERT INTO recurring_occurrences (recurring_profile_id, scheduled_date, invoice_id, status, created_at) VALUES (?, ?, ?, 'success', NOW())");
            $occ->execute([$profile['id'], $scheduledDate, $invId]);

            // Calculate next date
            $nextDate = RecurringInvoiceController::calculateNextDate($scheduledDate, $profile['frequency']);
            $newStatus = ($profile['end_date'] && $nextDate > $profile['end_date']) ? 'completed' : 'active';

            $profUp = $pdo->prepare("UPDATE recurring_profiles SET next_issue_date = ?, last_generated_at = NOW(), last_generated_invoice_id = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $profUp->execute([$nextDate, $invId, $newStatus, $profile['id']]);

            echo " + Created invoice #{$invoiceNumber} (ID {$invId}). Next run: {$nextDate}\n";
        });
    } catch (\Throwable $e) {
        echo " - ERROR processing profile #{$profile['id']}: " . $e->getMessage() . "\n";
        $occ = $pdo->prepare("INSERT INTO recurring_occurrences (recurring_profile_id, scheduled_date, status, error_message, created_at) VALUES (?, ?, 'failed', ?, NOW())");
        $occ->execute([$profile['id'], $scheduledDate, $e->getMessage()]);
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Finished recurring invoices processor.\n";
