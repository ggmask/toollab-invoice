<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tax Invoice - <?= e($invoice['invoice_number']) ?></title>
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
    .invoice-box {
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
    .logo { max-height: 50px; }
    .title { font-size: 22px; font-weight: bold; color: #4F46E5; text-align: right; }
    
    .meta-table { width: 100%; margin-bottom: 20px; }
    .meta-box { width: 48%; vertical-align: top; }
    
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .items-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
    .items-table td { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
    
    .totals-table { width: 100%; }
    .totals-right { width: 320px; float: right; border-collapse: collapse; }
    .totals-right td { padding: 4px 8px; }
    .totals-right .total-row { border-top: 2px solid #111827; font-weight: bold; font-size: 14px; }
    
    .footer { margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 15px; font-size: 11px; color: #6b7280; }

    @media print {
      body { background: #fff; padding: 0; }
      .print-actions { display: none; }
      .invoice-box { border: none; padding: 0; max-width: 100%; }
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
        <td style="width: 50%;">
          <img src="<?= asset('logo.png') ?>" alt="ToolLab" class="logo"><br>
          <strong style="font-size: 15px;"><?= e($invoice['supplier_name']) ?></strong><br>
          <?= nl2br(e($invoice['supplier_address'])) ?><br>
          <?= e($invoice['supplier_city']) ?>, <?= e($invoice['supplier_state']) ?> - <?= e($invoice['supplier_pincode']) ?><br>
          <strong>GSTIN:</strong> <?= e($invoice['supplier_gstin']) ?> &bull; <strong>State Code:</strong> <?= e($invoice['supplier_state_code']) ?>
        </td>
        <td style="width: 50%; text-align: right; vertical-align: top;">
          <div class="title">TAX INVOICE</div>
          <div style="font-size: 13px; font-weight: bold; margin-top: 4px;"># <?= e($invoice['invoice_number']) ?></div>
          <div style="margin-top: 6px; line-height: 1.5;">
            <strong>Invoice Date:</strong> <?= format_date($invoice['issue_date']) ?><br>
            <strong>Due Date:</strong> <?= format_date($invoice['due_date']) ?><br>
            <strong>Place of Supply:</strong> <?= e($invoice['place_of_supply_state_code']) ?> - <?= e($invoice['place_of_supply_state']) ?><br>
            <?php if (!empty($invoice['po_number'])): ?>
              <strong>PO Number:</strong> <?= e($invoice['po_number']) ?><br>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    </table>

    <table class="meta-table">
      <tr>
        <td class="meta-box">
          <strong style="color: #4F46E5;">Billed To:</strong><br>
          <strong><?= e($invoice['customer_name']) ?></strong><br>
          <?= nl2br(e($invoice['customer_billing_address'])) ?><br>
          <?= e($invoice['customer_billing_city']) ?>, <?= e($invoice['customer_billing_state']) ?> - <?= e($invoice['customer_billing_pincode']) ?><br>
          <strong>GSTIN:</strong> <?= !empty($invoice['customer_gstin']) ? e($invoice['customer_gstin']) : 'Unregistered Consumer' ?>
        </td>
        <td class="meta-box" style="padding-left: 20px;">
          <?php if (!empty($invoice['customer_shipping_address'])): ?>
            <strong style="color: #4F46E5;">Shipped To:</strong><br>
            <?= nl2br(e($invoice['customer_shipping_address'])) ?>
          <?php endif; ?>
        </td>
      </tr>
    </table>

    <table class="items-table">
      <thead>
        <tr>
          <th style="width: 4%;">#</th>
          <th style="width: 36%;">Item & Description</th>
          <th style="width: 10%;">HSN/SAC</th>
          <th style="width: 8%; text-align: right;">Qty</th>
          <th style="width: 12%; text-align: right;">Rate (₹)</th>
          <th style="width: 10%; text-align: right;">Discount</th>
          <th style="width: 10%; text-align: right;">Taxable (₹)</th>
          <th style="width: 10%; text-align: right;">GST Rate</th>
          <th style="width: 10%; text-align: right;">Total (₹)</th>
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
            <td style="text-align: right;"><?= number_format($it['taxable_value'], 2) ?></td>
            <td style="text-align: right;"><?= (float)$it['gst_rate'] ?>%</td>
            <td style="text-align: right; font-weight: bold;"><?= number_format($it['line_total'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div style="overflow: hidden; margin-bottom: 20px;">
      <table class="totals-right">
        <tr>
          <td>Taxable Amount:</td>
          <td style="text-align: right;"><?= format_currency($invoice['taxable_value']) ?></td>
        </tr>
        <?php if ($invoice['cgst_amount'] > 0): ?>
          <tr>
            <td>CGST:</td>
            <td style="text-align: right;"><?= format_currency($invoice['cgst_amount']) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($invoice['sgst_amount'] > 0): ?>
          <tr>
            <td>SGST:</td>
            <td style="text-align: right;"><?= format_currency($invoice['sgst_amount']) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($invoice['igst_amount'] > 0): ?>
          <tr>
            <td>IGST:</td>
            <td style="text-align: right;"><?= format_currency($invoice['igst_amount']) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($invoice['additional_charges'] > 0): ?>
          <tr>
            <td><?= e($invoice['additional_charges_label'] ?: 'Shipping / Handling') ?>:</td>
            <td style="text-align: right;"><?= format_currency($invoice['additional_charges']) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($invoice['rounding_adjustment'] != 0): ?>
          <tr>
            <td>Round Off:</td>
            <td style="text-align: right;"><?= ($invoice['rounding_adjustment'] > 0 ? '+' : '') . format_currency($invoice['rounding_adjustment']) ?></td>
          </tr>
        <?php endif; ?>
        <tr class="total-row">
          <td>Total Invoice Value:</td>
          <td style="text-align: right; color: #4F46E5;"><?= format_currency($invoice['total_amount']) ?></td>
        </tr>
        <tr>
          <td>Amount Paid:</td>
          <td style="text-align: right; color: #047857; font-weight: bold;"><?= format_currency($invoice['paid_amount']) ?></td>
        </tr>
        <tr style="font-weight: bold;">
          <td>Balance Due:</td>
          <td style="text-align: right; color: #b91c1c;"><?= format_currency($invoice['balance_due']) ?></td>
        </tr>
      </table>

      <div style="margin-right: 340px; font-size: 11px;">
        <div style="font-weight: bold; margin-bottom: 4px;">Amount in Words:</div>
        <div><?= \App\Support\NumberToWords::convertIndianCurrency($invoice['total_amount']) ?></div>

        <?php $bank = json_decode($invoice['bank_details'] ?? '', true); ?>
        <?php if (!empty($bank['bank_name'])): ?>
          <div style="margin-top: 15px; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px;">
            <strong>Bank Details for NEFT / RTGS / IMPS:</strong><br>
            Bank Name: <?= e($bank['bank_name']) ?> &bull; Branch: <?= e($bank['branch']) ?><br>
            A/C No: <?= e($bank['account_no']) ?> &bull; IFSC: <?= e($bank['ifsc']) ?><br>
            <?php if (!empty($bank['upi_id'])): ?>UPI ID: <?= e($bank['upi_id']) ?><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <table style="width: 100%; margin-top: 40px;">
      <tr>
        <td style="width: 60%; vertical-align: top; font-size: 11px;">
          <?php if (!empty($invoice['terms'])): ?>
            <strong>Terms & Conditions:</strong><br>
            <?= nl2br(e($invoice['terms'])) ?>
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
