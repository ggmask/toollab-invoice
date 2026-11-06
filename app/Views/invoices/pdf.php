<?php
$logoBase64 = null;
if (!empty($business['logo_url'])) {
    $logoPath = realpath(__DIR__ . '/../../../public') . '/' . ltrim($business['logo_url'], '/');
    if (file_exists($logoPath)) {
        $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'image/png',
        };
        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
    }
}

$isDraft = ($invoice['status'] === 'draft');
$isComposition = (($business['gst_status'] ?? '') === 'registered_composition' || ($invoice['document_type'] ?? '') === 'bill_of_supply');

if ($isDraft) {
    $docTitle = 'DRAFT INVOICE';
    $docSubtitle = 'Not valid for tax credit or accounting claims';
} elseif ($isComposition) {
    $docTitle = 'BILL OF SUPPLY';
    $docSubtitle = 'Composition taxable person, not eligible to collect tax on supplies';
} else {
    $docTitle = 'TAX INVOICE';
    $docSubtitle = null;
}

$hsnBreakdown = [];
$totalTaxable = 0.0;
$totalCgst = 0.0;
$totalSgst = 0.0;
$totalIgst = 0.0;
$totalTax = 0.0;

foreach ($items as $it) {
    $hsn = !empty($it['hsn_sac']) ? trim($it['hsn_sac']) : 'OTHER';
    $rateKey = (string)((float)$it['gst_rate']);
    $groupKey = $hsn . '_' . $rateKey;
    if (!isset($hsnBreakdown[$groupKey])) {
        $hsnBreakdown[$groupKey] = [
            'hsn_sac' => $hsn,
            'gst_rate' => (float)$it['gst_rate'],
            'taxable_value' => 0.0,
            'cgst_rate' => (float)$it['cgst_rate'],
            'cgst_amount' => 0.0,
            'sgst_rate' => (float)$it['sgst_rate'],
            'sgst_amount' => 0.0,
            'igst_rate' => (float)$it['igst_rate'],
            'igst_amount' => 0.0,
            'total_tax' => 0.0,
        ];
    }
    $hsnBreakdown[$groupKey]['taxable_value'] += (float)$it['taxable_value'];
    $hsnBreakdown[$groupKey]['cgst_amount'] += (float)$it['cgst_amount'];
    $hsnBreakdown[$groupKey]['sgst_amount'] += (float)$it['sgst_amount'];
    $hsnBreakdown[$groupKey]['igst_amount'] += (float)$it['igst_amount'];
    $hsnBreakdown[$groupKey]['total_tax'] += (float)$it['total_tax'];

    $totalTaxable += (float)$it['taxable_value'];
    $totalCgst += (float)$it['cgst_amount'];
    $totalSgst += (float)$it['sgst_amount'];
    $totalIgst += (float)$it['igst_amount'];
    $totalTax += (float)$it['total_tax'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title><?= e($invoice['invoice_number']) ?></title>
  <style>
    @page { margin: 22px 26px 36px 26px; size: a4 portrait; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1f2937; line-height: 1.35; margin: 0; }
    .header-table { width: 100%; border-bottom: 2px solid #4F46E5; padding-bottom: 10px; margin-bottom: 12px; }
    .title { font-size: 18pt; font-weight: bold; color: #4F46E5; text-align: right; }
    .subtitle { font-size: 7.5pt; font-style: italic; color: #4b5563; text-align: right; margin-top: 1px; }
    .meta-table { width: 100%; margin-bottom: 12px; }
    .meta-cell { width: 50%; vertical-align: top; }
    .table-bordered { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .table-bordered th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 6px; font-size: 7.5pt; text-transform: uppercase; text-align: left; }
    .table-bordered td { border: 1px solid #e5e7eb; padding: 5px 6px; vertical-align: top; font-size: 8pt; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .totals-box { width: 280px; float: right; border-collapse: collapse; font-size: 8pt; }
    .totals-box td { padding: 3px 5px; }
    .total-grand { border-top: 1.5px solid #111827; font-weight: bold; font-size: 10pt; color: #4F46E5; }
    .footer-table { width: 100%; margin-top: 24px; font-size: 8pt; }
    .avoid-break { page-break-inside: avoid; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .alert-banner { background: #fef2f2; border: 1px solid #fecaca; border-radius: 3px; padding: 5px 8px; font-size: 7.5pt; color: #991b1b; margin-bottom: 10px; }
    .info-banner { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 3px; padding: 5px 8px; font-size: 7.5pt; color: #1e40af; margin-bottom: 10px; }
  </style>
</head>
<body>
  <table class="header-table">
    <tr>
      <td style="width: 55%; vertical-align: top;">
        <?php if ($logoBase64): ?>
          <img src="<?= $logoBase64 ?>" style="max-height: 48px; max-width: 170px; margin-bottom: 5px;" alt="Logo"><br>
        <?php endif; ?>
        <strong style="font-size: 12pt; color: #111827;"><?= e($invoice['supplier_name']) ?></strong><br>
        <?php if (!empty($invoice['supplier_trade_name']) && $invoice['supplier_trade_name'] !== $invoice['supplier_name']): ?>
          <span style="font-size: 8pt; color: #4b5563;">Trade Name: <?= e($invoice['supplier_trade_name']) ?></span><br>
        <?php endif; ?>
        <?= nl2br(e($invoice['supplier_address'])) ?><br>
        <?= e($invoice['supplier_city']) ?>, <?= e($invoice['supplier_state']) ?> - <?= e($invoice['supplier_pincode']) ?><br>
        <strong>GSTIN:</strong> <?= e($invoice['supplier_gstin']) ?> &bull; <strong>State Code:</strong> <?= e($invoice['supplier_state_code']) ?><br>
        <strong>Phone:</strong> <?= e($invoice['supplier_phone']) ?> &bull; <strong>Email:</strong> <?= e($invoice['supplier_email']) ?>
      </td>
      <td style="width: 45%; vertical-align: top; text-align: right;">
        <div class="title"><?= $docTitle ?></div>
        <?php if ($docSubtitle): ?>
          <div class="subtitle"><?= $docSubtitle ?></div>
        <?php endif; ?>
        <div style="font-size: 11pt; font-weight: bold; margin-top: 3px;"># <?= e($invoice['invoice_number']) ?></div>
        <div style="margin-top: 4px; font-size: 8pt; line-height: 1.4;">
          <strong>Date of Issue:</strong> <?= format_date($invoice['issue_date']) ?><br>
          <strong>Due Date:</strong> <?= format_date($invoice['due_date']) ?><br>
          <strong>Place of Supply:</strong> <?= e($invoice['place_of_supply_state_code']) ?> - <?= e($invoice['place_of_supply_state']) ?><br>
          <strong>Reverse Charge (RCM):</strong> <?= !empty($invoice['is_reverse_charge']) ? 'YES' : 'NO' ?><br>
          <?php if (!empty($invoice['po_number'])): ?>
            <strong>PO Number:</strong> <?= e($invoice['po_number']) ?><br>
          <?php endif; ?>
        </div>
      </td>
    </tr>
  </table>

  <?php if (!empty($invoice['is_reverse_charge'])): ?>
    <div class="alert-banner">
      <strong>Reverse Charge Notice:</strong> Tax on this invoice is payable directly by the recipient (Billed To) under Section 9(3)/9(4) of the CGST Act.
    </div>
  <?php endif; ?>

  <table class="meta-table">
    <tr>
      <td class="meta-cell" style="padding-right: 10px;">
        <strong style="color: #4F46E5; font-size: 8pt; text-transform: uppercase;">Details of Receiver (Billed To):</strong><br>
        <strong style="font-size: 9.5pt; color: #111827;"><?= e($invoice['customer_name']) ?></strong><br>
        <?= nl2br(e($invoice['customer_billing_address'])) ?><br>
        <?= e($invoice['customer_billing_city']) ?>, <?= e($invoice['customer_billing_state']) ?> - <?= e($invoice['customer_billing_pincode']) ?><br>
        <strong>GSTIN:</strong> <?= !empty($invoice['customer_gstin']) ? e($invoice['customer_gstin']) : 'Unregistered' ?><br>
        <strong>State Code:</strong> <?= e($invoice['customer_billing_state_code'] ?: $invoice['place_of_supply_state_code']) ?><br>
        <strong>Phone:</strong> <?= e($invoice['customer_phone'] ?: '-') ?> &bull; <strong>Email:</strong> <?= e($invoice['customer_email'] ?: '-') ?>
      </td>
      <td class="meta-cell" style="padding-left: 10px;">
        <?php if (!empty($invoice['customer_shipping_address'])): ?>
          <strong style="color: #4F46E5; font-size: 8pt; text-transform: uppercase;">Details of Consignee (Shipped To):</strong><br>
          <?= nl2br(e($invoice['customer_shipping_address'])) ?>
        <?php else: ?>
          <strong style="color: #4F46E5; font-size: 8pt; text-transform: uppercase;">Payment Terms & Conditions:</strong><br>
          Terms: <?= e($invoice['payment_terms'] ?: 'Net 15') ?><br>
          Supply Type: <?= e(ucfirst(str_replace('_', ' ', $invoice['supply_type'] ?? 'intra_state'))) ?>
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <table class="table-bordered">
    <thead>
      <tr>
        <th style="width: 4%;">#</th>
        <th style="width: 32%;">Item Description</th>
        <th style="width: 10%;">HSN/SAC</th>
        <th class="text-right" style="width: 8%;">Qty</th>
        <th class="text-right" style="width: 10%;">Rate</th>
        <th class="text-right" style="width: 8%;">Disc</th>
        <th class="text-right" style="width: 10%;">Taxable</th>
        <th class="text-right" style="width: 7%;">GST%</th>
        <th class="text-right" style="width: 11%;">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $idx => $it): ?>
        <tr>
          <td><?= $idx + 1 ?></td>
          <td>
            <strong><?= e($it['name']) ?></strong>
            <?php if (!empty($it['description'])): ?>
              <div style="font-size: 7.5pt; color: #4b5563;"><?= nl2br(e($it['description'])) ?></div>
            <?php endif; ?>
          </td>
          <td><?= e($it['hsn_sac'] ?: '-') ?></td>
          <td class="text-right"><?= (float)$it['quantity'] ?> <?= e($it['unit']) ?></td>
          <td class="text-right"><?= number_format($it['unit_price'], 2) ?></td>
          <td class="text-right"><?= (float)$it['discount_amount'] > 0 ? number_format($it['discount_amount'], 2) : '-' ?></td>
          <td class="text-right"><?= number_format($it['taxable_value'], 2) ?></td>
          <td class="text-right"><?= (float)$it['gst_rate'] ?>%</td>
          <td class="text-right" style="font-weight: bold;"><?= number_format($it['line_total'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Statutory GST Summary Table by HSN/SAC -->
  <?php if (!$isComposition && !empty($hsnBreakdown)): ?>
    <div style="margin-top: 6px; margin-bottom: 10px;" class="avoid-break">
      <div style="font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #374151; margin-bottom: 3px;">
        GST Tax Analysis by HSN/SAC
      </div>
      <table class="table-bordered" style="margin-bottom: 0;">
        <thead>
          <tr>
            <th style="width: 16%;">HSN / SAC</th>
            <th class="text-right" style="width: 16%;">Taxable Value</th>
            <?php if ($invoice['supply_type'] === 'inter_state'): ?>
              <th class="text-right" style="width: 16%;">IGST Rate</th>
              <th class="text-right" style="width: 24%;">IGST Amount</th>
            <?php else: ?>
              <th class="text-right" style="width: 10%;">CGST%</th>
              <th class="text-right" style="width: 14%;">CGST Amt</th>
              <th class="text-right" style="width: 10%;">SGST%</th>
              <th class="text-right" style="width: 14%;">SGST Amt</th>
            <?php endif; ?>
            <th class="text-right" style="width: 20%;">Total Tax</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($hsnBreakdown as $row): ?>
            <tr>
              <td><?= e($row['hsn_sac']) ?></td>
              <td class="text-right"><?= format_currency($row['taxable_value']) ?></td>
              <?php if ($invoice['supply_type'] === 'inter_state'): ?>
                <td class="text-right"><?= $row['igst_rate'] ?>%</td>
                <td class="text-right"><?= format_currency($row['igst_amount']) ?></td>
              <?php else: ?>
                <td class="text-right"><?= $row['cgst_rate'] ?>%</td>
                <td class="text-right"><?= format_currency($row['cgst_amount']) ?></td>
                <td class="text-right"><?= $row['sgst_rate'] ?>%</td>
                <td class="text-right"><?= format_currency($row['sgst_amount']) ?></td>
              <?php endif; ?>
              <td class="text-right" style="font-weight: bold;"><?= format_currency($row['total_tax']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background: #f9fafb; font-weight: bold;">
            <td>Total</td>
            <td class="text-right"><?= format_currency($totalTaxable) ?></td>
            <?php if ($invoice['supply_type'] === 'inter_state'): ?>
              <td></td>
              <td class="text-right"><?= format_currency($totalIgst) ?></td>
            <?php else: ?>
              <td></td>
              <td class="text-right"><?= format_currency($totalCgst) ?></td>
              <td></td>
              <td class="text-right"><?= format_currency($totalSgst) ?></td>
            <?php endif; ?>
            <td class="text-right"><?= format_currency($totalTax) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>

  <div class="avoid-break" style="overflow: hidden; margin-top: 8px;">
    <table class="totals-box">
      <tr>
        <td>Subtotal (Taxable Value):</td>
        <td class="text-right"><?= format_currency($invoice['taxable_value']) ?></td>
      </tr>
      <?php if (!$isComposition): ?>
        <?php if ($invoice['cgst_amount'] > 0): ?>
          <tr>
            <td>CGST:</td>
            <td class="text-right"><?= format_currency($invoice['cgst_amount']) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($invoice['sgst_amount'] > 0): ?>
          <tr>
            <td>SGST / UTGST:</td>
            <td class="text-right"><?= format_currency($invoice['sgst_amount']) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($invoice['igst_amount'] > 0): ?>
          <tr>
            <td>IGST:</td>
            <td class="text-right"><?= format_currency($invoice['igst_amount']) ?></td>
          </tr>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($invoice['additional_charges'] > 0): ?>
        <tr>
          <td><?= e($invoice['additional_charges_label'] ?: 'Shipping / Handling') ?>:</td>
          <td class="text-right"><?= format_currency($invoice['additional_charges']) ?></td>
        </tr>
      <?php endif; ?>
      <?php if ($invoice['rounding_adjustment'] != 0): ?>
        <tr>
          <td>Round Off:</td>
          <td class="text-right"><?= ($invoice['rounding_adjustment'] > 0 ? '+' : '') . format_currency($invoice['rounding_adjustment']) ?></td>
        </tr>
      <?php endif; ?>
      <tr class="total-grand">
        <td>Total Invoice Value:</td>
        <td class="text-right"><?= format_currency($invoice['total_amount']) ?></td>
      </tr>
      <tr>
        <td>Amount Received:</td>
        <td class="text-right" style="color: #047857; font-weight: bold;"><?= format_currency($invoice['paid_amount']) ?></td>
      </tr>
      <tr style="font-weight: bold;">
        <td>Balance Due:</td>
        <td class="text-right" style="color: #b91c1c;"><?= format_currency($invoice['balance_due']) ?></td>
      </tr>
    </table>

    <div style="margin-right: 300px;">
      <strong style="font-size: 8pt;">Amount in Words:</strong><br>
      <span style="font-size: 8pt; font-style: italic;"><?= \App\Support\NumberToWords::convertIndianCurrency($invoice['total_amount']) ?></span><br><br>

      <?php $bank = json_decode($invoice['bank_details'] ?? '', true); ?>
      <?php if (!empty($bank['bank_name'])): ?>
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px 8px; font-size: 7.5pt; line-height: 1.4;">
          <strong style="text-transform: uppercase; color: #374151;">Bank Remittance Details:</strong><br>
          Bank: <strong><?= e($bank['bank_name']) ?></strong> &bull; Branch: <?= e($bank['branch']) ?><br>
          Account No: <strong><?= e($bank['account_no']) ?></strong> &bull; IFSC: <strong><?= e($bank['ifsc']) ?></strong><br>
          <?php if (!empty($bank['upi_id'])): ?>UPI ID: <strong><?= e($bank['upi_id']) ?></strong><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <table class="footer-table avoid-break">
    <tr>
      <td style="width: 60%; vertical-align: top; font-size: 7.5pt; padding-right: 15px;">
        <?php if (!empty($invoice['terms'])): ?>
          <strong style="text-transform: uppercase; color: #374151;">Terms & Conditions:</strong><br>
          <?= nl2br(e($invoice['terms'])) ?>
        <?php endif; ?>
        <?php if (!empty($invoice['notes'])): ?>
          <br><br>
          <strong style="text-transform: uppercase; color: #374151;">Notes:</strong><br>
          <?= nl2br(e($invoice['notes'])) ?>
        <?php endif; ?>
      </td>
      <td style="width: 40%; text-align: center; vertical-align: top;">
        <div style="font-weight: bold; margin-bottom: 40px; font-size: 8.5pt;">For <?= e($invoice['supplier_name']) ?></div>
        <div style="border-top: 1px solid #111827; padding-top: 3px; font-weight: bold; font-size: 8.5pt;">
          <?= e($invoice['signature_title'] ?: 'Authorized Signatory') ?>
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
