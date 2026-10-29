<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title><?= e($estimate['estimate_number']) ?></title>
  <style>
    @page { margin: 25px 30px; size: a4 portrait; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.4; margin: 0; }
    .header-table { width: 100%; border-bottom: 2px solid #4F46E5; padding-bottom: 12px; margin-bottom: 14px; }
    .title { font-size: 20px; font-weight: bold; color: #4F46E5; text-align: right; }
    .meta-table { width: 100%; margin-bottom: 14px; }
    .meta-cell { width: 50%; vertical-align: top; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .items-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: left; }
    .items-table td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
    .text-right { text-align: right; }
    .totals-box { width: 260px; float: right; border-collapse: collapse; }
    .totals-box td { padding: 3px 6px; }
    .total-grand { border-top: 1.5px solid #111827; font-weight: bold; font-size: 12px; color: #4F46E5; }
  </style>
</head>
<body>
  <table class="header-table">
    <tr>
      <td style="width: 55%; vertical-align: top;">
        <strong style="font-size: 14px; color: #111827;"><?= e($estimate['supplier_name']) ?></strong><br>
        <?= nl2br(e($estimate['supplier_address'])) ?><br>
        <?= e($estimate['supplier_city']) ?>, <?= e($estimate['supplier_state']) ?> - <?= e($estimate['supplier_pincode']) ?><br>
        <strong>GSTIN:</strong> <?= e($estimate['supplier_gstin']) ?>
      </td>
      <td style="width: 45%; vertical-align: top; text-align: right;">
        <div class="title">ESTIMATE / QUOTE</div>
        <div style="font-size: 12px; font-weight: bold; margin-top: 2px;"># <?= e($estimate['estimate_number']) ?></div>
        <div style="margin-top: 4px;">
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
        <strong style="color: #4F46E5; font-size: 10px; text-transform: uppercase;">Quotation For:</strong><br>
        <strong style="font-size: 11px;"><?= e($estimate['customer_name']) ?></strong><br>
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
        <th style="width: 42%;">Item & Description</th>
        <th style="width: 10%;">HSN/SAC</th>
        <th class="text-right" style="width: 8%;">Qty</th>
        <th class="text-right" style="width: 12%;">Rate</th>
        <th class="text-right" style="width: 10%;">Discount</th>
        <th class="text-right" style="width: 14%;">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $idx => $it): ?>
        <tr>
          <td><?= $idx + 1 ?></td>
          <td>
            <strong><?= e($it['name']) ?></strong>
            <?php if (!empty($it['description'])): ?>
              <div style="font-size: 9px; color: #4b5563;"><?= nl2br(e($it['description'])) ?></div>
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

  <div>
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

    <div style="margin-right: 280px;">
      <strong>Amount in Words:</strong><br>
      <?= \App\Support\NumberToWords::convertIndianCurrency($estimate['total_amount']) ?>
    </div>
  </div>
</body>
</html>
