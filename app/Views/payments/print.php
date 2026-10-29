<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Receipt - <?= e($payment['payment_number']) ?></title>
  <link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      color: #111827;
      background: #f3f4f6;
      margin: 0;
      padding: 20px;
    }
    .receipt-box {
      max-width: 800px;
      margin: auto;
      padding: 30px;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 4px;
    }
    .print-actions {
      max-width: 800px;
      margin: 0 auto 20px auto;
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
      font-weight: bold;
    }
    .btn-print { background: #4F46E5; color: #fff; border: 1px solid #4F46E5; }
    .btn-back { background: #fff; color: #374151; border: 1px solid #d1d5db; }

    .header-table { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #4F46E5; padding-bottom: 15px; }
    .logo { max-height: 45px; }
    .title { font-size: 20px; font-weight: bold; color: #4F46E5; text-align: right; }
    
    .meta-table { width: 100%; margin-bottom: 20px; }
    .meta-box { width: 48%; vertical-align: top; }
    
    .details-box {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 4px;
      padding: 12px 16px;
      margin-bottom: 20px;
    }
    
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .items-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
    .items-table td { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
    .text-right { text-align: right; }

    .footer-table { width: 100%; margin-top: 40px; }

    @media print {
      body { background: #fff; padding: 0; }
      .print-actions { display: none; }
      .receipt-box { border: none; padding: 0; }
    }
  </style>
</head>
<body>

  <div class="print-actions">
    <a href="javascript:window.history.back()" class="btn btn-back">← Back</a>
    <button onclick="window.print()" class="btn btn-print">Print Voucher</button>
  </div>

  <div class="receipt-box">
    <table class="header-table">
      <tr>
        <td style="width: 50%; vertical-align: top;">
          <img src="<?= asset('logo.png') ?>" alt="ToolLab Logo" class="logo"><br>
          <strong style="font-size: 14px;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></strong><br>
          <?= e($business['address_line1']) ?><br>
          <?= e($business['city']) ?>, <?= e($business['state']) ?> - <?= e($business['pincode']) ?><br>
          <strong>GSTIN:</strong> <?= e($business['gstin'] ?: 'Unregistered') ?><br>
          <strong>Phone:</strong> <?= e($business['phone']) ?> &bull; <strong>Email:</strong> <?= e($business['email']) ?>
        </td>
        <td style="width: 50%; vertical-align: top; text-align: right;">
          <div class="title">PAYMENT RECEIPT</div>
          <div style="font-size: 14px; font-weight: bold; margin-top: 4px;"># <?= e($payment['payment_number']) ?></div>
          <div style="margin-top: 6px;">
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
        <td class="meta-box" style="padding-right: 15px;">
          <div style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 12px; border-radius: 4px;">
            <strong style="color: #4b5563; font-size: 11px; text-transform: uppercase;">Received From</strong><br>
            <strong style="font-size: 13px; color: #111827;"><?= e($customer['name']) ?></strong><br>
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
        <td class="meta-box" style="padding-left: 15px;">
          <div style="background: #eef2ff; border: 1px solid #c7d2fe; padding: 16px; border-radius: 4px; text-align: center;">
            <div style="color: #4F46E5; font-size: 11px; font-weight: bold; text-transform: uppercase;">Total Amount Received</div>
            <div style="font-size: 24px; font-weight: bold; color: #1e1b4b; margin: 4px 0;">
              <?= format_currency($payment['amount']) ?>
            </div>
            <div style="font-size: 11px; color: #4338ca; font-style: italic;">
              <?= e(App\Support\NumberToWords::toWords((float)$payment['amount'])) ?> Only
            </div>
          </div>
        </td>
      </tr>
    </table>

    <h4 style="margin: 20px 0 8px 0; text-transform: uppercase; font-size: 11px; color: #4b5563;">Invoice Allocations</h4>
    <table class="items-table">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Invoice Date</th>
          <th class="text-right">Total Invoice (₹)</th>
          <th class="text-right">Allocated (₹)</th>
          <th class="text-right">Current Balance (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($allocations)): ?>
          <tr>
            <td colspan="5" style="text-align: center; color: #6b7280; font-style: italic;">
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
      <div style="margin-top: 15px; font-size: 11px; color: #4b5563;">
        <strong>Notes:</strong> <?= nl2br(e($payment['notes'])) ?>
      </div>
    <?php endif; ?>

    <table class="footer-table">
      <tr>
        <td style="width: 60%; vertical-align: bottom; font-size: 10px; color: #9ca3af;">
          Computer-generated receipt voucher.<br>
          Record ID: <?= e($payment['uuid']) ?>
        </td>
        <td style="width: 40%; text-align: center; vertical-align: bottom;">
          <div style="height: 50px; border-bottom: 1px dashed #d1d5db;"></div>
          <div style="margin-top: 8px; font-weight: bold;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></div>
          <div style="font-size: 10px; color: #6b7280;"><?= e($business['signature_title'] ?: 'Authorized Signatory') ?></div>
        </td>
      </tr>
    </table>
  </div>

</body>
</html>
