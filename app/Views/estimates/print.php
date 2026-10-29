<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quotation - <?= e($estimate['estimate_number']) ?></title>
  <link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
  <style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111827; background: #f3f4f6; margin: 0; padding: 20px; }
    .invoice-box { max-width: 800px; margin: auto; padding: 30px; background: #fff; border: 1px solid #e5e7eb; border-radius: 4px; }
    .print-actions { max-width: 800px; margin: 0 auto 20px auto; display: flex; justify-content: flex-end; gap: 10px; }
    .btn { padding: 8px 16px; font-size: 13px; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: bold; }
    .btn-print { background: #4F46E5; color: #fff; border: 1px solid #4F46E5; }
    .btn-back { background: #fff; color: #374151; border: 1px solid #d1d5db; }
    .header-table { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #4F46E5; padding-bottom: 15px; }
    .logo { max-height: 50px; }
    .title { font-size: 22px; font-weight: bold; color: #4F46E5; text-align: right; }
    .meta-table { width: 100%; margin-bottom: 20px; }
    .meta-box { width: 48%; vertical-align: top; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .items-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
    .items-table td { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
    .totals-right { width: 320px; float: right; border-collapse: collapse; }
    .totals-right td { padding: 4px 8px; }
    .totals-right .total-row { border-top: 2px solid #111827; font-weight: bold; font-size: 14px; }
    @media print { body { background: #fff; padding: 0; } .print-actions { display: none; } .invoice-box { border: none; padding: 0; max-width: 100%; } }
  </style>
</head>
<body>
  <div class="print-actions">
    <button class="btn btn-back" onclick="window.close()">Close</button>
    <button class="btn btn-print" onclick="window.print()">Print Estimate</button>
  </div>

  <div class="invoice-box">
    <table class="header-table">
      <tr>
        <td style="width: 50%;">
          <img src="<?= asset('logo.png') ?>" alt="ToolLab" class="logo"><br>
          <strong style="font-size: 15px;"><?= e($estimate['supplier_name']) ?></strong><br>
          <?= nl2br(e($estimate['supplier_address'])) ?><br>
          <?= e($estimate['supplier_city']) ?>, <?= e($estimate['supplier_state']) ?> - <?= e($estimate['supplier_pincode']) ?><br>
          <strong>GSTIN:</strong> <?= e($estimate['supplier_gstin']) ?>
        </td>
        <td style="width: 50%; text-align: right; vertical-align: top;">
          <div class="title">ESTIMATE / QUOTATION</div>
          <div style="font-size: 13px; font-weight: bold; margin-top: 4px;"># <?= e($estimate['estimate_number']) ?></div>
          <div style="margin-top: 6px; line-height: 1.5;">
            <strong>Date:</strong> <?= format_date($estimate['estimate_date']) ?><br>
            <strong>Valid Until:</strong> <?= format_date($estimate['valid_until']) ?><br>
            <strong>Place of Supply:</strong> <?= e($estimate['place_of_supply_state_code']) ?> - <?= e($estimate['place_of_supply_state']) ?>
          </div>
        </td>
      </tr>
    </table>

    <table class="meta-table">
      <tr>
        <td class="meta-box">
          <strong style="color: #4F46E5;">Quotation Prepared For:</strong><br>
          <strong><?= e($estimate['customer_name']) ?></strong><br>
          <?= nl2br(e($estimate['customer_billing_address'])) ?><br>
          <?= e($estimate['customer_billing_city']) ?>, <?= e($estimate['customer_billing_state']) ?> - <?= e($estimate['customer_billing_pincode']) ?><br>
          <strong>GSTIN:</strong> <?= !empty($estimate['customer_gstin']) ? e($estimate['customer_gstin']) : 'Unregistered' ?>
        </td>
      </tr>
    </table>

    <table class="items-table">
      <thead>
        <tr>
          <th style="width: 4%;">#</th>
          <th style="width: 40%;">Item & Description</th>
          <th style="width: 10%;">HSN/SAC</th>
          <th style="width: 8%; text-align: right;">Qty</th>
          <th style="width: 12%; text-align: right;">Rate (₹)</th>
          <th style="width: 10%; text-align: right;">Discount</th>
          <th style="width: 12%; text-align: right;">Total (₹)</th>
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
            <td style="text-align: right;"><?= (float)$it['quantity'] ?> <?= e($it['unit']) ?></td>
            <td style="text-align: right;"><?= number_format($it['unit_price'], 2) ?></td>
            <td style="text-align: right;"><?= (float)$it['discount_amount'] > 0 ? number_format($it['discount_amount'], 2) : '-' ?></td>
            <td style="text-align: right; font-weight: bold;"><?= number_format($it['line_total'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div style="overflow: hidden; margin-bottom: 20px;">
      <table class="totals-right">
        <tr>
          <td>Taxable Amount:</td>
          <td style="text-align: right;"><?= format_currency($estimate['taxable_value']) ?></td>
        </tr>
        <tr>
          <td>Estimated Tax:</td>
          <td style="text-align: right;"><?= format_currency($estimate['tax_total']) ?></td>
        </tr>
        <?php if ($estimate['additional_charges'] > 0): ?>
          <tr>
            <td>Additional Charges:</td>
            <td style="text-align: right;"><?= format_currency($estimate['additional_charges']) ?></td>
          </tr>
        <?php endif; ?>
        <tr class="total-row">
          <td>Total Estimated Value:</td>
          <td style="text-align: right; color: #4F46E5;"><?= format_currency($estimate['total_amount']) ?></td>
        </tr>
      </table>

      <div style="margin-right: 340px; font-size: 11px;">
        <div style="font-weight: bold; margin-bottom: 4px;">Estimated Amount in Words:</div>
        <div><?= \App\Support\NumberToWords::convertIndianCurrency($estimate['total_amount']) ?></div>
      </div>
    </div>
  </div>
</body>
</html>
