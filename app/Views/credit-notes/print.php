<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Credit Note - <?= e($creditNote['credit_note_number']) ?></title>
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
    .btn-print { background: #b91c1c; color: #fff; border: 1px solid #b91c1c; }
    .btn-back { background: #fff; color: #374151; border: 1px solid #d1d5db; }
    .header-table { width: 100%; margin-bottom: 16px; border-bottom: 2px solid #b91c1c; padding-bottom: 12px; }
    .logo { max-height: 52px; max-width: 180px; margin-bottom: 6px; }
    .title { font-size: 22px; font-weight: bold; color: #b91c1c; text-align: right; }
    .subtitle { font-size: 11px; font-style: italic; color: #6b7280; text-align: right; margin-top: 2px; }
    .meta-table { width: 100%; margin-bottom: 16px; }
    .meta-box { width: 48%; vertical-align: top; }
    .table-bordered { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .table-bordered th { background: #fef2f2; border: 1px solid #fecaca; padding: 7px 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
    .table-bordered td { border: 1px solid #e5e7eb; padding: 7px 8px; vertical-align: top; }
    .text-right { text-align: right; }
    .totals-right { width: 330px; float: right; border-collapse: collapse; }
    .totals-right td { padding: 4px 6px; }
    .totals-right .total-row { border-top: 2px solid #111827; font-weight: bold; font-size: 14px; color: #b91c1c; }
    .footer-table { width: 100%; margin-top: 36px; }

    @media print {
      body { background: #fff; padding: 0; }
      .print-actions { display: none; }
      .invoice-box { border: none; padding: 0; box-shadow: none; max-width: 100%; }
      thead { display: table-header-group; }
      tr { page-break-inside: avoid; }
    }
  </style>
</head>
<body>
  <div class="print-actions">
    <button class="btn btn-back" onclick="window.close()">Close</button>
    <button class="btn btn-print" onclick="window.print()">Print Credit Note</button>
  </div>

  <div class="invoice-box">
    <table class="header-table">
      <tr>
        <td style="width: 55%; vertical-align: top;">
          <?php if (!empty($business['logo_url'])): ?>
            <img src="<?= asset($business['logo_url']) ?>" alt="Logo" class="logo"><br>
          <?php endif; ?>
          <strong style="font-size: 16px; color: #111827;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></strong><br>
          <?= e($business['address_line1']) ?><br>
          <?= e($business['city']) ?>, <?= e($business['state']) ?> - <?= e($business['pincode']) ?><br>
          <strong>GSTIN:</strong> <?= e($business['gstin'] ?: 'Unregistered') ?> &bull; <strong>State Code:</strong> <?= e($business['state_code']) ?><br>
          <strong>Phone:</strong> <?= e($business['phone']) ?> &bull; <strong>Email:</strong> <?= e($business['email']) ?>
        </td>
        <td style="width: 45%; text-align: right; vertical-align: top;">
          <div class="title">CREDIT NOTE</div>
          <div class="subtitle">Issued under Section 34 of the CGST Act, 2017 & Rule 53</div>
          <div style="font-size: 14px; font-weight: bold; margin-top: 3px;"># <?= e($creditNote['credit_note_number']) ?></div>
          <div style="margin-top: 5px; line-height: 1.5; font-size: 11px;">
            <strong>Credit Note Date:</strong> <?= format_date($creditNote['credit_note_date']) ?><br>
            <strong>Original Tax Invoice:</strong> #<?= e($creditNote['original_invoice_number']) ?><br>
            <?php if (!empty($creditNote['original_invoice_date'])): ?>
              <strong>Invoice Date:</strong> <?= format_date($creditNote['original_invoice_date']) ?><br>
            <?php endif; ?>
            <strong>Reason:</strong> <?= e(ucwords(str_replace('_', ' ', $creditNote['reason']))) ?><br>
          </div>
        </td>
      </tr>
    </table>

    <table class="meta-table">
      <tr>
        <td class="meta-box">
          <strong style="color: #b91c1c; font-size: 11px; text-transform: uppercase;">Details of Receiver (Customer):</strong><br>
          <strong style="font-size: 13px;"><?= e($customer['name']) ?></strong><br>
          <?= nl2br(e($customer['billing_address'])) ?><br>
          <?= e($customer['billing_city']) ?>, <?= e($customer['billing_state']) ?> - <?= e($customer['billing_pincode']) ?><br>
          <strong>GSTIN:</strong> <?= !empty($customer['gstin']) ? e($customer['gstin']) : 'Unregistered' ?><br>
          <strong>Place of Supply:</strong> <?= e($customer['billing_state_code']) ?> - <?= e($customer['billing_state']) ?>
        </td>
        <td class="meta-box" style="padding-left: 20px;">
          <strong style="color: #b91c1c; font-size: 11px; text-transform: uppercase;">Adjustment Particulars:</strong><br>
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
          <th style="width: 36%;">Item Description</th>
          <th style="width: 10%;">HSN/SAC</th>
          <th class="text-right" style="width: 8%;">Qty</th>
          <th class="text-right" style="width: 12%;">Rate</th>
          <th class="text-right" style="width: 12%;">Taxable</th>
          <th class="text-right" style="width: 6%;">GST%</th>
          <th class="text-right" style="width: 12%;">Credit Total</th>
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
            <td class="text-right"><?= number_format($it['taxable_value'], 2) ?></td>
            <td class="text-right"><?= (float)$it['gst_rate'] ?>%</td>
            <td class="text-right" style="font-weight: bold;"><?= number_format($it['line_total'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div style="overflow: hidden; margin-bottom: 20px;">
      <table class="totals-right">
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
        <tr class="total-row">
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

      <div style="margin-right: 350px; font-size: 11px;">
        <div style="font-weight: bold; margin-bottom: 4px;">Amount in Words:</div>
        <div style="font-style: italic;"><?= \App\Support\NumberToWords::convertIndianCurrency($creditNote['total_amount']) ?></div>
      </div>
    </div>

    <table class="footer-table">
      <tr>
        <td style="width: 60%; vertical-align: top; font-size: 11px; padding-right: 20px;">
          <?php if (!empty($creditNote['notes'])): ?>
            <strong style="text-transform: uppercase; color: #374151;">Notes:</strong><br>
            <?= nl2br(e($creditNote['notes'])) ?>
          <?php endif; ?>
        </td>
        <td style="width: 40%; text-align: center; vertical-align: top;">
          <div style="font-weight: bold; margin-bottom: 50px;">For <?= e($business['trade_name'] ?: $business['legal_name']) ?></div>
          <div style="border-top: 1px solid #111827; padding-top: 4px; font-weight: bold;">
            <?= e($business['signature_title'] ?: 'Authorized Signatory') ?>
          </div>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>

