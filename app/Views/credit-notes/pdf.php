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
  <title>Credit Note - <?= e($creditNote['credit_note_number']) ?></title>
  <style>
    @page { margin: 22px 26px 36px 26px; size: a4 portrait; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1f2937; line-height: 1.35; margin: 0; }
    .header-table { width: 100%; border-bottom: 2px solid #b91c1c; padding-bottom: 10px; margin-bottom: 12px; }
    .title { font-size: 18pt; font-weight: bold; color: #b91c1c; text-align: right; }
    .subtitle { font-size: 7.5pt; font-style: italic; color: #4b5563; text-align: right; margin-top: 1px; }
    .meta-table { width: 100%; margin-bottom: 12px; }
    .meta-cell { width: 50%; vertical-align: top; }
    .table-bordered { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .table-bordered th { background: #fef2f2; border: 1px solid #fecaca; padding: 5px 6px; font-size: 7.5pt; text-transform: uppercase; text-align: left; }
    .table-bordered td { border: 1px solid #e5e7eb; padding: 5px 6px; vertical-align: top; font-size: 8pt; }
    .text-right { text-align: right; }
    .totals-box { width: 280px; float: right; border-collapse: collapse; font-size: 8pt; }
    .totals-box td { padding: 3px 5px; }
    .total-grand { border-top: 1.5px solid #111827; font-weight: bold; font-size: 10pt; color: #b91c1c; }
    .footer-table { width: 100%; margin-top: 24px; font-size: 8pt; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .avoid-break { page-break-inside: avoid; }
  </style>
</head>
<body>
  <table class="header-table">
    <tr>
      <td style="width: 55%; vertical-align: top;">
        <?php if ($logoBase64): ?>
          <img src="<?= $logoBase64 ?>" style="max-height: 48px; max-width: 170px; margin-bottom: 5px;" alt="Logo"><br>
        <?php endif; ?>
        <strong style="font-size: 12pt; color: #111827;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></strong><br>
        <?= e($business['address_line1']) ?><br>
        <?= e($business['city']) ?>, <?= e($business['state']) ?> - <?= e($business['pincode']) ?><br>
        <strong>GSTIN:</strong> <?= e($business['gstin'] ?: 'Unregistered') ?> &bull; <strong>State Code:</strong> <?= e($business['state_code']) ?><br>
        <strong>Phone:</strong> <?= e($business['phone']) ?> &bull; <strong>Email:</strong> <?= e($business['email']) ?>
      </td>
      <td style="width: 45%; vertical-align: top; text-align: right;">
        <div class="title">CREDIT NOTE</div>
        <div class="subtitle">Issued under Section 34 of the CGST Act, 2017 & Rule 53</div>
        <div style="font-size: 11pt; font-weight: bold; margin-top: 3px;"># <?= e($creditNote['credit_note_number']) ?></div>
        <div style="margin-top: 4px; font-size: 8pt; line-height: 1.4;">
          <strong>Credit Note Date:</strong> <?= format_date($creditNote['credit_note_date']) ?><br>
          <strong>Original Tax Invoice:</strong> #<?= e($creditNote['original_invoice_number']) ?><br>
          <?php if (!empty($creditNote['original_invoice_date'])): ?>
            <strong>Original Invoice Date:</strong> <?= format_date($creditNote['original_invoice_date']) ?><br>
          <?php endif; ?>
          <strong>Reason:</strong> <?= e(ucwords(str_replace('_', ' ', $creditNote['reason']))) ?><br>
        </div>
      </td>
    </tr>
  </table>

  <table class="meta-table">
    <tr>
      <td class="meta-cell">
        <strong style="color: #b91c1c; font-size: 8pt; text-transform: uppercase;">Details of Receiver (Customer):</strong><br>
        <strong style="font-size: 9.5pt; color: #111827;"><?= e($customer['name']) ?></strong><br>
        <?= nl2br(e($customer['billing_address'])) ?><br>
        <?= e($customer['billing_city']) ?>, <?= e($customer['billing_state']) ?> - <?= e($customer['billing_pincode']) ?><br>
        <strong>GSTIN:</strong> <?= !empty($customer['gstin']) ? e($customer['gstin']) : 'Unregistered' ?><br>
        <strong>Place of Supply:</strong> <?= e($customer['billing_state_code']) ?> - <?= e($customer['billing_state']) ?>
      </td>
      <td class="meta-cell" style="padding-left: 10px;">
        <strong style="color: #b91c1c; font-size: 8pt; text-transform: uppercase;">Adjustment Particulars:</strong><br>
        Supply Type: <?= ucfirst(str_replace('_', ' ', $creditNote['supply_type'])) ?><br>
        <?php if (!empty($creditNote['reason_description'])): ?>
          Specific Remarks: <?= e($creditNote['reason_description']) ?><br>
        <?php endif; ?>
        Status: <strong><?= strtoupper($creditNote['status']) ?></strong>
      </td>
    </tr>
  </table>

  <table class="table-bordered">
    <thead>
      <tr>
        <th style="width: 4%;">#</th>
        <th style="width: 34%;">Item Description</th>
        <th style="width: 10%;">HSN/SAC</th>
        <th class="text-right" style="width: 8%;">Qty</th>
        <th class="text-right" style="width: 11%;">Rate</th>
        <th class="text-right" style="width: 11%;">Taxable</th>
        <th class="text-right" style="width: 8%;">GST %</th>
        <th class="text-right" style="width: 14%;">Credit Total</th>
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
          <td class="text-right"><?= number_format($it['taxable_value'], 2) ?></td>
          <td class="text-right"><?= (float)$it['gst_rate'] ?>%</td>
          <td class="text-right" style="font-weight: bold;"><?= number_format($it['line_total'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- GST Analysis Table -->
  <div style="margin-top: 6px; margin-bottom: 10px;" class="avoid-break">
    <div style="font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #374151; margin-bottom: 3px;">
      GST Tax Credit Breakdown
    </div>
    <table class="table-bordered" style="margin-bottom: 0;">
      <thead>
        <tr>
          <th style="width: 16%;">HSN / SAC</th>
          <th class="text-right" style="width: 16%;">Taxable Value</th>
          <?php if ($creditNote['supply_type'] === 'inter_state'): ?>
            <th class="text-right" style="width: 16%;">IGST Rate</th>
            <th class="text-right" style="width: 24%;">IGST Amount</th>
          <?php else: ?>
            <th class="text-right" style="width: 10%;">CGST%</th>
            <th class="text-right" style="width: 14%;">CGST Amt</th>
            <th class="text-right" style="width: 10%;">SGST%</th>
            <th class="text-right" style="width: 14%;">SGST Amt</th>
          <?php endif; ?>
          <th class="text-right" style="width: 20%;">Total Tax Credit</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($hsnBreakdown as $row): ?>
          <tr>
            <td><?= e($row['hsn_sac']) ?></td>
            <td class="text-right"><?= format_currency($row['taxable_value']) ?></td>
            <?php if ($creditNote['supply_type'] === 'inter_state'): ?>
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
        <tr style="background: #fef2f2; font-weight: bold;">
          <td>Total</td>
          <td class="text-right"><?= format_currency($totalTaxable) ?></td>
          <?php if ($creditNote['supply_type'] === 'inter_state'): ?>
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

  <div class="avoid-break" style="overflow: hidden; margin-top: 8px;">
    <table class="totals-box">
      <tr>
        <td>Taxable Credit:</td>
        <td class="text-right"><?= format_currency($creditNote['taxable_value']) ?></td>
      </tr>
      <?php if ($creditNote['cgst_amount'] > 0): ?>
        <tr>
          <td>CGST Credit:</td>
          <td class="text-right"><?= format_currency($creditNote['cgst_amount']) ?></td>
        </tr>
      <?php endif; ?>
      <?php if ($creditNote['sgst_amount'] > 0): ?>
        <tr>
          <td>SGST / UTGST Credit:</td>
          <td class="text-right"><?= format_currency($creditNote['sgst_amount']) ?></td>
        </tr>
      <?php endif; ?>
      <?php if ($creditNote['igst_amount'] > 0): ?>
        <tr>
          <td>IGST Credit:</td>
          <td class="text-right"><?= format_currency($creditNote['igst_amount']) ?></td>
        </tr>
      <?php endif; ?>
      <?php if ($creditNote['rounding_adjustment'] != 0): ?>
        <tr>
          <td>Round Off:</td>
          <td class="text-right"><?= ($creditNote['rounding_adjustment'] > 0 ? '+' : '') . format_currency($creditNote['rounding_adjustment']) ?></td>
        </tr>
      <?php endif; ?>
      <tr class="total-grand">
        <td>Total Credit Value:</td>
        <td class="text-right"><?= format_currency($creditNote['total_amount']) ?></td>
      </tr>
      <tr>
        <td>Applied to Invoice:</td>
        <td class="text-right" style="color: #047857; font-weight: bold;"><?= format_currency($creditNote['applied_amount']) ?></td>
      </tr>
      <tr style="font-weight: bold;">
        <td>Unapplied Credit:</td>
        <td class="text-right" style="color: #b45309;"><?= format_currency($creditNote['unapplied_amount']) ?></td>
      </tr>
    </table>

    <div style="margin-right: 300px;">
      <strong style="font-size: 8pt;">Amount in Words:</strong><br>
      <span style="font-size: 8pt; font-style: italic;"><?= \App\Support\NumberToWords::convertIndianCurrency($creditNote['total_amount']) ?></span>
    </div>
  </div>

  <table class="footer-table avoid-break">
    <tr>
      <td style="width: 60%; vertical-align: top; font-size: 7.5pt; padding-right: 15px;">
        <?php if (!empty($creditNote['notes'])): ?>
          <strong style="text-transform: uppercase; color: #374151;">Notes:</strong><br>
          <?= nl2br(e($creditNote['notes'])) ?>
        <?php endif; ?>
      </td>
      <td style="width: 40%; text-align: center; vertical-align: top;">
        <div style="font-weight: bold; margin-bottom: 40px; font-size: 8.5pt;">For <?= e($business['trade_name'] ?: $business['legal_name']) ?></div>
        <div style="border-top: 1px solid #111827; padding-top: 3px; font-weight: bold; font-size: 8.5pt;">
          <?= e($business['signature_title'] ?: 'Authorized Signatory') ?>
        </div>
      </td>
    </tr>
  </table>
</body>
</html>

