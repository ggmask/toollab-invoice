<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title><?= e($invoice['invoice_number']) ?></title>
  <style>
    @page { margin: 25px 30px; size: a4 portrait; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.4; margin: 0; }
    .header-table { width: 100%; border-bottom: 2px solid #4F46E5; padding-bottom: 12px; margin-bottom: 14px; }
    .title { font-size: 20px; font-weight: bold; color: #4F46E5; text-align: right; }
    .meta-table { width: 100%; margin-bottom: 14px; }
    .meta-cell { width: 50%; vertical-align: top; }
    .badge-box { background: #f3f4f6; border-radius: 4px; padding: 6px 10px; margin-bottom: 14px; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .items-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: left; }
    .items-table td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
    .text-right { text-align: right; }
    .totals-table { width: 100%; }
    .totals-box { width: 260px; float: right; border-collapse: collapse; }
    .totals-box td { padding: 3px 6px; }
    .total-grand { border-top: 1.5px solid #111827; font-weight: bold; font-size: 12px; color: #4F46E5; }
    .footer-table { width: 100%; margin-top: 30px; }
  </style>
</head>
<body>
  <table class="header-table">
    <tr>
      <td style="width: 55%; vertical-align: top;">
        <strong style="font-size: 14px; color: #111827;"><?= e($invoice['supplier_name']) ?></strong><br>
        <?= nl2br(e($invoice['supplier_address'])) ?><br>
        <?= e($invoice['supplier_city']) ?>, <?= e($invoice['supplier_state']) ?> - <?= e($invoice['supplier_pincode']) ?><br>
        <strong>GSTIN:</strong> <?= e($invoice['supplier_gstin']) ?> &bull; <strong>State Code:</strong> <?= e($invoice['supplier_state_code']) ?><br>
        <strong>Phone:</strong> <?= e($invoice['supplier_phone']) ?> &bull; <strong>Email:</strong> <?= e($invoice['supplier_email']) ?>
      </td>
      <td style="width: 45%; vertical-align: top; text-align: right;">
        <div class="title">TAX INVOICE</div>
        <div style="font-size: 12px; font-weight: bold; margin-top: 2px;"># <?= e($invoice['invoice_number']) ?></div>
        <div style="margin-top: 4px;">
          <strong>Date:</strong> <?= format_date($invoice['issue_date']) ?><br>
          <strong>Due Date:</strong> <?= format_date($invoice['due_date']) ?><br>
          <strong>Place of Supply:</strong> <?= e($invoice['place_of_supply_state_code']) ?> - <?= e($invoice['place_of_supply_state']) ?><br>
          <?php if (!empty($invoice['po_number'])): ?>
            <strong>PO #:</strong> <?= e($invoice['po_number']) ?><br>
          <?php endif; ?>
        </div>
      </td>
    </tr>
  </table>

  <table class="meta-table">
    <tr>
      <td class="meta-cell">
        <strong style="color: #4F46E5; font-size: 10px; text-transform: uppercase;">Billed To (Customer):</strong><br>
        <strong style="font-size: 11px;"><?= e($invoice['customer_name']) ?></strong><br>
        <?= nl2br(e($invoice['customer_billing_address'])) ?><br>
        <?= e($invoice['customer_billing_city']) ?>, <?= e($invoice['customer_billing_state']) ?> - <?= e($invoice['customer_billing_pincode']) ?><br>
        <strong>GSTIN:</strong> <?= !empty($invoice['customer_gstin']) ? e($invoice['customer_gstin']) : 'Unregistered' ?><br>
        <strong>Phone:</strong> <?= e($invoice['customer_phone'] ?: '-') ?>
      </td>
      <td class="meta-cell" style="padding-left: 15px;">
        <?php if (!empty($invoice['customer_shipping_address'])): ?>
          <strong style="color: #4F46E5; font-size: 10px; text-transform: uppercase;">Shipped To:</strong><br>
          <?= nl2br(e($invoice['customer_shipping_address'])) ?>
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <table class="items-table">
    <thead>
      <tr>
        <th style="width: 4%;">#</th>
        <th style="width: 38%;">Item & Description</th>
        <th style="width: 10%;">HSN/SAC</th>
        <th class="text-right" style="width: 8%;">Qty</th>
        <th class="text-right" style="width: 10%;">Rate</th>
        <th class="text-right" style="width: 8%;">Disc</th>
        <th class="text-right" style="width: 11%;">Taxable</th>
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
              <div style="font-size: 9px; color: #4b5563;"><?= nl2br(e($it['description'])) ?></div>
            <?php endif; ?>
          </td>
          <td><?= e($it['hsn_sac'] ?: '-') ?></td>
          <td class="text-right"><?= (float)$it['quantity'] ?> <?= e($it['unit']) ?></td>
          <td class="text-right"><?= number_format($it['unit_price'], 2) ?></td>
          <td class="text-right"><?= (float)$it['discount_amount'] > 0 ? number_format($it['discount_amount'], 2) : '-' ?></td>
          <td class="text-right"><?= number_format($it['taxable_value'], 2) ?></td>
          <td class="text-right" style="font-weight: bold;"><?= number_format($it['line_total'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div>
    <table class="totals-box">
      <tr>
        <td>Taxable Amount:</td>
        <td class="text-right"><?= format_currency($invoice['taxable_value']) ?></td>
      </tr>
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
      <?php if ($invoice['additional_charges'] > 0): ?>
        <tr>
          <td><?= e($invoice['additional_charges_label'] ?: 'Additional Charges') ?>:</td>
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
        <td>Total Amount:</td>
        <td class="text-right"><?= format_currency($invoice['total_amount']) ?></td>
      </tr>
      <tr>
        <td>Paid Amount:</td>
        <td class="text-right" style="color: #047857; font-weight: bold;"><?= format_currency($invoice['paid_amount']) ?></td>
      </tr>
      <tr style="font-weight: bold;">
        <td>Balance Due:</td>
        <td class="text-right" style="color: #b91c1c;"><?= format_currency($invoice['balance_due']) ?></td>
      </tr>
    </table>

    <div style="margin-right: 280px;">
      <strong>Amount in Words:</strong><br>
      <?= \App\Support\NumberToWords::convertIndianCurrency($invoice['total_amount']) ?><br><br>

      <?php $bank = json_decode($invoice['bank_details'] ?? '', true); ?>
      <?php if (!empty($bank['bank_name'])): ?>
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px 10px; font-size: 9px;">
          <strong>Bank Remittance Details:</strong><br>
          Bank: <?= e($bank['bank_name']) ?> &bull; Branch: <?= e($bank['branch']) ?><br>
          A/C: <?= e($bank['account_no']) ?> &bull; IFSC: <?= e($bank['ifsc']) ?><br>
          <?php if (!empty($bank['upi_id'])): ?>UPI ID: <?= e($bank['upi_id']) ?><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <table class="footer-table">
    <tr>
      <td style="width: 60%; vertical-align: top; font-size: 9px;">
        <?php if (!empty($invoice['terms'])): ?>
          <strong>Terms & Conditions:</strong><br>
          <?= nl2br(e($invoice['terms'])) ?>
        <?php endif; ?>
      </td>
      <td style="width: 40%; text-align: center; vertical-align: top;">
        <div style="font-weight: bold; margin-bottom: 40px;">For <?= e($invoice['supplier_name']) ?></div>
        <div style="border-top: 1px solid #111827; padding-top: 3px; font-weight: bold; font-size: 10px;">
          <?= e($invoice['signature_title'] ?: 'Authorized Signatory') ?>
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
