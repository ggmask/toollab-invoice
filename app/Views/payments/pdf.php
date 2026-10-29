<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>Payment Receipt - <?= e($payment['payment_number']) ?></title>
  <style>
    @page { margin: 25px 30px; size: a4 portrait; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.4; margin: 0; }
    .header-table { width: 100%; border-bottom: 2px solid #4F46E5; padding-bottom: 12px; margin-bottom: 14px; }
    .title { font-size: 18px; font-weight: bold; color: #4F46E5; text-align: right; }
    .meta-table { width: 100%; margin-bottom: 14px; }
    .meta-cell { width: 50%; vertical-align: top; }
    .card-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .items-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: left; }
    .items-table td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
    .text-right { text-align: right; }
    .footer-table { width: 100%; margin-top: 35px; }
  </style>
</head>
<body>
  <table class="header-table">
    <tr>
      <td style="width: 55%; vertical-align: top;">
        <strong style="font-size: 13px; color: #111827;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></strong><br>
        <?= e($business['address_line1']) ?><br>
        <?= e($business['city']) ?>, <?= e($business['state']) ?> - <?= e($business['pincode']) ?><br>
        <strong>GSTIN:</strong> <?= e($business['gstin'] ?: 'Unregistered') ?><br>
        <strong>Phone:</strong> <?= e($business['phone']) ?> &bull; <strong>Email:</strong> <?= e($business['email']) ?>
      </td>
      <td style="width: 45%; vertical-align: top; text-align: right;">
        <div class="title">PAYMENT RECEIPT</div>
        <div style="font-size: 12px; font-weight: bold; margin-top: 2px;"># <?= e($payment['payment_number']) ?></div>
        <div style="margin-top: 4px;">
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
          <strong style="color: #6b7280; font-size: 9px; text-transform: uppercase;">Received From</strong><br>
          <strong style="font-size: 11px; color: #111827;"><?= e($customer['name']) ?></strong><br>
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
        <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 12px; text-align: center;">
          <div style="color: #4F46E5; font-size: 9px; font-weight: bold; text-transform: uppercase;">Total Received Amount</div>
          <div style="font-size: 20px; font-weight: bold; color: #1e1b4b; margin: 3px 0;">
            <?= format_currency($payment['amount']) ?>
          </div>
          <div style="font-size: 9px; color: #4338ca; font-style: italic;">
            <?= e(App\Support\NumberToWords::toWords((float)$payment['amount'])) ?> Only
          </div>
        </div>
      </td>
    </tr>
  </table>

  <div style="font-weight: bold; font-size: 10px; text-transform: uppercase; color: #4b5563; margin-bottom: 6px;">
    Invoice Allocations
  </div>

  <table class="items-table">
    <thead>
      <tr>
        <th>Invoice #</th>
        <th>Date</th>
        <th class="text-right">Total Invoice</th>
        <th class="text-right">Allocated Amount</th>
        <th class="text-right">Remaining Balance</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($allocations)): ?>
        <tr>
          <td colspan="5" style="text-align: center; color: #6b7280; font-style: italic; padding: 12px;">
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
    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px; margin-top: 10px;">
      <strong style="font-size: 9px; text-transform: uppercase; color: #6b7280;">Notes:</strong><br>
      <?= nl2br(e($payment['notes'])) ?>
    </div>
  <?php endif; ?>

  <table class="footer-table">
    <tr>
      <td style="width: 60%; vertical-align: bottom; font-size: 8px; color: #9ca3af;">
        This is a computer-generated voucher.<br>
        Transaction Ref: <?= e($payment['uuid']) ?>
      </td>
      <td style="width: 40%; text-align: center; vertical-align: bottom;">
        <div style="height: 40px; border-bottom: 1px dashed #9ca3af;"></div>
        <div style="margin-top: 6px; font-weight: bold; font-size: 9px;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></div>
        <div style="font-size: 8px; color: #6b7280;"><?= e($business['signature_title'] ?: 'Authorized Signatory') ?></div>
      </td>
    </tr>
  </table>
</body>
</html>
