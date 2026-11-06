<?php
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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $docTitle ?> - <?= e($invoice['invoice_number']) ?></title>
  <link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      font-size: 12px;
      color: #111827;
      background: #f3f4f6;
      margin: 0;
      padding: 20px;
    }
    .invoice-box {
      max-width: 840px;
      margin: auto;
      padding: 30px;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 4px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .print-actions {
      max-width: 840px;
      margin: 0 auto 16px auto;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .btn {
      padding: 8px 16px;
      font-size: 13px;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      font-weight: 600;
    }
    .btn-print { background: #4F46E5; color: #fff; border: 1px solid #4F46E5; }
    .btn-back { background: #fff; color: #374151; border: 1px solid #d1d5db; }
    .header-table { width: 100%; margin-bottom: 16px; border-bottom: 2px solid #4F46E5; padding-bottom: 12px; }
    .logo { max-height: 52px; max-width: 180px; margin-bottom: 6px; }
    .title { font-size: 22px; font-weight: bold; color: #4F46E5; text-align: right; }
    .subtitle { font-size: 11px; font-style: italic; color: #6b7280; text-align: right; margin-top: 2px; }
    .meta-table { width: 100%; margin-bottom: 16px; }
    .meta-box { width: 48%; vertical-align: top; }
    .table-bordered { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .table-bordered th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 7px 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
    .table-bordered td { border: 1px solid #e5e7eb; padding: 7px 8px; vertical-align: top; }
    .text-right { text-align: right; }
    .totals-right { width: 330px; float: right; border-collapse: collapse; }
    .totals-right td { padding: 4px 6px; }
    .totals-right .total-row { border-top: 2px solid #111827; font-weight: bold; font-size: 14px; color: #4F46E5; }
    .footer-table { width: 100%; margin-top: 36px; }
    .alert-banner { background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; padding: 7px 10px; font-size: 11px; color: #991b1b; margin-bottom: 14px; }

    @media print {
      body { background: #fff; padding: 0; }
      .print-actions { display: none; }
      .invoice-box { border: none; padding: 0; box-shadow: none; max-width: 100%; }
      thead { display: table-header-group; }
      tr { page-break-inside: avoid; }
      .avoid-break { page-break-inside: avoid; }
    }
  </style>
</head>
<body>
  <div class="print-actions">
    <button class="btn btn-back" onclick="window.close()">Close</button>
    <button class="btn btn-print" onclick="window.print()">Print Invoice</button>
  </div>

  <div class="invoice-box">
    <table class="header-table">
      <tr>
        <td style="width: 55%; vertical-align: top;">
          <?php if (!empty($business['logo_url'])): ?>
            <img src="<?= asset($business['logo_url']) ?>" alt="Logo" class="logo"><br>
          <?php endif; ?>
          <strong style="font-size: 16px; color: #111827;"><?= e($invoice['supplier_name']) ?></strong><br>
          <?php if (!empty($invoice['supplier_trade_name']) && $invoice['supplier_trade_name'] !== $invoice['supplier_name']): ?>
            <span style="font-size: 11px; color: #4b5563;">Trade Name: <?= e($invoice['supplier_trade_name']) ?></span><br>
          <?php endif; ?>
          <?= nl2br(e($invoice['supplier_address'])) ?><br>
          <?= e($invoice['supplier_city']) ?>, <?= e($invoice['supplier_state']) ?> - <?= e($invoice['supplier_pincode']) ?><br>
          <strong>GSTIN:</strong> <?= e($invoice['supplier_gstin']) ?> &bull; <strong>State Code:</strong> <?= e($invoice['supplier_state_code']) ?><br>
          <strong>Phone:</strong> <?= e($invoice['supplier_phone']) ?> &bull; <strong>Email:</strong> <?= e($invoice['supplier_email']) ?>
        </td>
        <td style="width: 45%; text-align: right; vertical-align: top;">
          <div class="title"><?= $docTitle ?></div>
          <?php if ($docSubtitle): ?>
            <div class="subtitle"><?= $docSubtitle ?></div>
          <?php endif; ?>
          <div style="font-size: 14px; font-weight: bold; margin-top: 3px;"># <?= e($invoice['invoice_number']) ?></div>
          <div style="margin-top: 5px; line-height: 1.5; font-size: 11px;">
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
        <td class="meta-box">
          <strong style="color: #4F46E5; font-size: 11px; text-transform: uppercase;">Details of Receiver (Billed To):</strong><br>
          <strong style="font-size: 13px;"><?= e($invoice['customer_name']) ?></strong><br>
          <?= nl2br(e($invoice['customer_billing_address'])) ?><br>
          <?= e($invoice['customer_billing_city']) ?>, <?= e($invoice['customer_billing_state']) ?> - <?= e($invoice['customer_billing_pincode']) ?><br>
          <strong>GSTIN:</strong> <?= !empty($invoice['customer_gstin']) ? e($invoice['customer_gstin']) : 'Unregistered' ?><br>
          <strong>State Code:</strong> <?= e($invoice['customer_billing_state_code'] ?: $invoice['place_of_supply_state_code']) ?><br>
          <strong>Phone:</strong> <?= e($invoice['customer_phone'] ?: '-') ?> &bull; <strong>Email:</strong> <?= e($invoice['customer_email'] ?: '-') ?>
        </td>
        <td class="meta-box" style="padding-left: 20px;">
          <?php if (!empty($invoice['customer_shipping_address'])): ?>
            <strong style="color: #4F46E5; font-size: 11px; text-transform: uppercase;">Details of Consignee (Shipped To):</strong><br>
            <?= nl2br(e($invoice['customer_shipping_address'])) ?>
          <?php else: ?>
            <strong style="color: #4F46E5; font-size: 11px; text-transform: uppercase;">Supply Details:</strong><br>
            Payment Terms: <?= e($invoice['payment_terms'] ?: 'Net 15') ?><br>
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
                <div style="font-size: 11px; color: #4b5563;"><?= nl2br(e($it['description'])) ?></div>
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
      <div style="margin-top: 10px; margin-bottom: 16px;" class="avoid-break">
        <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: #374151; margin-bottom: 4px;">
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

    <div class="avoid-break" style="overflow: hidden; margin-bottom: 20px;">
      <table class="totals-right">
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
        <tr class="total-row">
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

      <div style="margin-right: 350px; font-size: 11px;">
        <div style="font-weight: bold; margin-bottom: 4px;">Amount in Words:</div>
        <div style="font-style: italic;"><?= \App\Support\NumberToWords::convertIndianCurrency($invoice['total_amount']) ?></div>

        <?php $bank = json_decode($invoice['bank_details'] ?? '', true); ?>
        <?php if (!empty($bank['bank_name'])): ?>
          <div style="margin-top: 14px; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; line-height: 1.5;">
            <strong style="text-transform: uppercase; color: #374151;">Bank Remittance Details:</strong><br>
            Bank Name: <strong><?= e($bank['bank_name']) ?></strong> &bull; Branch: <?= e($bank['branch']) ?><br>
            Account No: <strong><?= e($bank['account_no']) ?></strong> &bull; IFSC: <strong><?= e($bank['ifsc']) ?></strong><br>
            <?php if (!empty($bank['upi_id'])): ?>UPI ID: <strong><?= e($bank['upi_id']) ?></strong><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <table class="footer-table avoid-break">
      <tr>
        <td style="width: 60%; vertical-align: top; font-size: 11px; padding-right: 20px;">
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
          <div style="font-weight: bold; margin-bottom: 50px;">For <?= e($invoice['supplier_name']) ?></div>
          <div style="border-top: 1px solid #111827; padding-top: 4px; font-weight: bold;">
            <?= e($invoice['signature_title'] ?: 'Authorized Signatory') ?>
          </div>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
