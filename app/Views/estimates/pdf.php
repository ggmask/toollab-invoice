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
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title><?= e($estimate['estimate_number']) ?></title>
  <style>
    @page { margin: 22px 26px 36px 26px; size: a4 portrait; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1f2937; line-height: 1.35; margin: 0; }
    .header-table { width: 100%; border-bottom: 2px solid #4F46E5; padding-bottom: 10px; margin-bottom: 12px; }
    .title { font-size: 18pt; font-weight: bold; color: #4F46E5; text-align: right; }
    .meta-table { width: 100%; margin-bottom: 12px; }
    .meta-cell { width: 50%; vertical-align: top; }
    .table-bordered { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .table-bordered th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 6px; font-size: 7.5pt; text-transform: uppercase; text-align: left; }
    .table-bordered td { border: 1px solid #e5e7eb; padding: 5px 6px; vertical-align: top; font-size: 8pt; }
    .text-right { text-align: right; }
    .totals-box { width: 280px; float: right; border-collapse: collapse; font-size: 8pt; }
    .totals-box td { padding: 3px 5px; }
    .total-grand { border-top: 1.5px solid #111827; font-weight: bold; font-size: 10pt; color: #4F46E5; }
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
        <strong style="font-size: 12pt; color: #111827;"><?= e($estimate['supplier_name']) ?></strong><br>
        <?= nl2br(e($estimate['supplier_address'])) ?><br>
        <?= e($estimate['supplier_city']) ?>, <?= e($estimate['supplier_state']) ?> - <?= e($estimate['supplier_pincode']) ?><br>
        <strong>GSTIN:</strong> <?= e($estimate['supplier_gstin']) ?> &bull; <strong>Phone:</strong> <?= e($estimate['supplier_phone']) ?>
      </td>
      <td style="width: 45%; vertical-align: top; text-align: right;">
        <div class="title">ESTIMATE / QUOTE</div>
        <div style="font-size: 11pt; font-weight: bold; margin-top: 3px;"># <?= e($estimate['estimate_number']) ?></div>
        <div style="margin-top: 4px; font-size: 8pt; line-height: 1.4;">
          <strong>Date:</strong> <?= format_date($estimate['estimate_date']) ?><br>
          <strong>Valid Until:</strong> <?= format_date($estimate['valid_until']) ?><br>
          <strong>Place of Supply:</strong> <?= e($estimate['place_of_supply_state_code']) ?> - <?= e($estimate['place_of_supply_state']) ?>
        </div>
      </td>
    </tr>
  </table>

  <table class="meta-table">
    <tr>
      <td class="meta-cell">
        <strong style="color: #4F46E5; font-size: 8pt; text-transform: uppercase;">Quotation Prepared For:</strong><br>
        <strong style="font-size: 9.5pt; color: #111827;"><?= e($estimate['customer_name']) ?></strong><br>
        <?= nl2br(e($estimate['customer_billing_address'])) ?><br>
        <?= e($estimate['customer_billing_city']) ?>, <?= e($estimate['customer_billing_state']) ?> - <?= e($estimate['customer_billing_pincode']) ?><br>
        <strong>GSTIN:</strong> <?= !empty($estimate['customer_gstin']) ? e($estimate['customer_gstin']) : 'Unregistered' ?>
      </td>
    </tr>
  </table>

  <table class="table-bordered">
    <thead>
      <tr>
        <th style="width: 4%;">#</th>
        <th style="width: 40%;">Item Description</th>
        <th style="width: 10%;">HSN/SAC</th>
        <th class="text-right" style="width: 8%;">Qty</th>
        <th class="text-right" style="width: 12%;">Rate</th>
        <th class="text-right" style="width: 10%;">Disc</th>
        <th class="text-right" style="width: 16%;">Total</th>
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
          <td class="text-right" style="font-weight: bold;"><?= number_format($it['line_total'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="avoid-break" style="overflow: hidden; margin-top: 8px;">
    <table class="totals-box">
      <tr>
        <td>Taxable Amount:</td>
        <td class="text-right"><?= format_currency($estimate['taxable_value']) ?></td>
      </tr>
      <tr>
        <td>Estimated GST:</td>
        <td class="text-right"><?= format_currency($estimate['tax_total']) ?></td>
      </tr>
      <?php if ($estimate['additional_charges'] > 0): ?>
        <tr>
          <td>Additional Charges:</td>
          <td class="text-right"><?= format_currency($estimate['additional_charges']) ?></td>
        </tr>
      <?php endif; ?>
      <tr class="total-grand">
        <td>Estimated Total:</td>
        <td class="text-right"><?= format_currency($estimate['total_amount']) ?></td>
      </tr>
    </table>

    <div style="margin-right: 300px;">
      <strong style="font-size: 8pt;">Amount in Words:</strong><br>
      <span style="font-size: 8pt; font-style: italic;"><?= \App\Support\NumberToWords::convertIndianCurrency($estimate['total_amount']) ?></span>
    </div>
  </div>
</body>
</html>
