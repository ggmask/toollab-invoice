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
  <title>Payment Receipt - <?= e($payment['payment_number']) ?></title>
  <style>
    @page { margin: 22px 26px 36px 26px; size: a4 portrait; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1f2937; line-height: 1.35; margin: 0; }
    .header-table { width: 100%; border-bottom: 2px solid #4F46E5; padding-bottom: 10px; margin-bottom: 12px; }
    .title { font-size: 18pt; font-weight: bold; color: #4F46E5; text-align: right; }
    .meta-table { width: 100%; margin-bottom: 12px; }
    .meta-cell { width: 50%; vertical-align: top; }
    .card-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; font-size: 8pt; }
    .table-bordered { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .table-bordered th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 6px; font-size: 7.5pt; text-transform: uppercase; text-align: left; }
    .table-bordered td { border: 1px solid #e5e7eb; padding: 5px 6px; vertical-align: top; font-size: 8pt; }
    .text-right { text-align: right; }
    .footer-table { width: 100%; margin-top: 30px; font-size: 8pt; }
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
        <strong>GSTIN:</strong> <?= e($business['gstin'] ?: 'Unregistered') ?><br>
        <strong>Phone:</strong> <?= e($business['phone']) ?> &bull; <strong>Email:</strong> <?= e($business['email']) ?>
      </td>
      <td style="width: 45%; vertical-align: top; text-align: right;">
        <div class="title">PAYMENT RECEIPT</div>
        <div style="font-size: 11pt; font-weight: bold; margin-top: 3px;"># <?= e($payment['payment_number']) ?></div>
        <div style="margin-top: 4px; font-size: 8pt; line-height: 1.4;">
          <strong>Receipt Date:</strong> <?= format_date($payment['payment_date']) ?><br>
          <strong>Payment Mode:</strong> <?= e(strtoupper(str_replace('_', ' ', $payment['payment_method']))) ?><br>
          <?php if (!empty($payment['reference_number'])): ?>
            <strong>Ref / UTR:</strong> <?= e($payment['reference_number']) ?><br>
          <?php endif; ?>
          <?php if (!empty($payment['bank_name'])): ?>
            <strong>Bank:</strong> <?= e($payment['bank_name']) ?><br>
          <?php endif; ?>
        </div>
      </td>
    </tr>
  </table>

  <table class="meta-table">
    <tr>
      <td class="meta-cell" style="padding-right: 8px;">
        <div class="card-box">
          <strong style="color: #6b7280; font-size: 7.5pt; text-transform: uppercase;">Received From</strong><br>
          <strong style="font-size: 9.5pt; color: #111827;"><?= e($customer['name']) ?></strong><br>
          <?php if (!empty($customer['billing_address'])): ?>
            <?= nl2br(e($customer['billing_address'])) ?><br>
            <?= e($customer['billing_city']) ?>, <?= e($customer['billing_state']) ?> - <?= e($customer['billing_pincode']) ?><br>
          <?php endif; ?>
          <?php if (!empty($customer['gstin'])): ?>
            <strong>GSTIN:</strong> <?= e($customer['gstin']) ?><br>
          <?php endif; ?>
          <?php if (!empty($customer['phone'])): ?>
            <strong>Phone:</strong> <?= e($customer['phone']) ?>
          <?php endif; ?>
        </div>
      </td>
      <td class="meta-cell" style="padding-left: 8px;">
        <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 10px; text-align: center;">
          <div style="color: #4F46E5; font-size: 7.5pt; font-weight: bold; text-transform: uppercase;">Total Received Amount</div>
          <div style="font-size: 16pt; font-weight: bold; color: #1e1b4b; margin: 2px 0;">
            <?= format_currency($payment['amount']) ?>
          </div>
          <div style="font-size: 8pt; color: #4338ca; font-style: italic;">
            <?= \App\Support\NumberToWords::convertIndianCurrency($payment['amount']) ?>
          </div>
        </div>
      </td>
    </tr>
  </table>

  <div style="font-weight: bold; font-size: 8pt; text-transform: uppercase; color: #4b5563; margin-bottom: 5px;">
    Invoice Allocations
  </div>

  <table class="table-bordered">
    <thead>
      <tr>
        <th style="width: 25%;">Invoice #</th>
        <th style="width: 20%;">Date</th>
        <th class="text-right" style="width: 18%;">Total Invoice</th>
        <th class="text-right" style="width: 18%;">Allocated</th>
        <th class="text-right" style="width: 19%;">Remaining Balance</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($allocations)): ?>
        <tr>
          <td colspan="5" style="text-align: center; color: #6b7280; font-style: italic; padding: 10px;">
            No direct invoice allocations. Retained as unallocated advance credit.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($allocations as $a): ?>
          <tr>
            <td><strong><?= e($a['invoice_number']) ?></strong></td>
            <td><?= format_date($a['issue_date']) ?></td>
            <td class="text-right"><?= format_currency($a['total_amount']) ?></td>
            <td class="text-right" style="font-weight: bold; color: #047857;"><?= format_currency($a['amount']) ?></td>
            <td class="text-right"><?= format_currency($a['current_balance']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr style="background: #f9fafb; font-weight: bold;">
        <td colspan="3" class="text-right">Total Allocated:</td>
        <td class="text-right" style="color: #047857;"><?= format_currency($payment['allocated_amount']) ?></td>
        <td></td>
      </tr>
      <?php if ((float)$payment['unallocated_amount'] > 0): ?>
        <tr style="background: #fffbeb; font-weight: bold;">
          <td colspan="3" class="text-right" style="color: #b45309;">Unallocated Balance:</td>
          <td class="text-right" style="color: #b45309;"><?= format_currency($payment['unallocated_amount']) ?></td>
          <td></td>
        </tr>
      <?php endif; ?>
    </tfoot>
  </table>

  <?php if (!empty($payment['notes'])): ?>
    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px 8px; margin-top: 8px; font-size: 7.5pt;">
      <strong style="text-transform: uppercase; color: #6b7280;">Notes:</strong><br>
      <?= nl2br(e($payment['notes'])) ?>
    </div>
  <?php endif; ?>

  <table class="footer-table avoid-break">
    <tr>
      <td style="width: 60%; vertical-align: bottom; font-size: 7pt; color: #9ca3af;">
        Computer-generated receipt voucher.<br>
        Transaction Ref: <?= e($payment['uuid']) ?>
      </td>
      <td style="width: 40%; text-align: center; vertical-align: bottom;">
        <div style="height: 35px; border-bottom: 1px dashed #9ca3af;"></div>
        <div style="margin-top: 5px; font-weight: bold; font-size: 8pt;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></div>
        <div style="font-size: 7.5pt; color: #6b7280;"><?= e($business['signature_title'] ?: 'Authorized Signatory') ?></div>
      </td>
    </tr>
  </table>
</body>
</html>
