<?php
// Compute items totals and HSN breakdown for transparent GST analysis
$itemsTaxableSum = 0.0;
$itemsCgstSum = 0.0;
$itemsSgstSum = 0.0;
$itemsIgstSum = 0.0;
$itemsGrossSum = 0.0;
$hsnBreakdown = [];

foreach ($items as $it) {
    $itemsTaxableSum += (float)$it['taxable_value'];
    $itemsCgstSum += (float)$it['cgst_amount'];
    $itemsSgstSum += (float)$it['sgst_amount'];
    $itemsIgstSum += (float)$it['igst_amount'];
    $itemsGrossSum += (float)$it['line_total'];

    $hsn = !empty($it['hsn_sac']) ? trim($it['hsn_sac']) : 'OTHER';
    $rateKey = (string)(float)$it['gst_rate'];
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
}

// Include taxable shipping/freight charges in HSN breakdown if present
if ((float)$invoice['additional_charges'] > 0) {
    $addTaxable = (float)$invoice['additional_charges'];
    $addCgst = max(0.0, (float)$invoice['cgst_amount'] - $itemsCgstSum);
    $addSgst = max(0.0, (float)$invoice['sgst_amount'] - $itemsSgstSum);
    $addIgst = max(0.0, (float)$invoice['igst_amount'] - $itemsIgstSum);
    $addTax = $addCgst + $addSgst + $addIgst;

    if ($addTax > 0) {
        $freightRate = round(($addTax / $addTaxable) * 100, 1);
        $groupKey = '9965_' . $freightRate;
        $hsnBreakdown[$groupKey] = [
            'hsn_sac' => '9965 (Freight)',
            'gst_rate' => $freightRate,
            'taxable_value' => $addTaxable,
            'cgst_rate' => round($freightRate / 2, 2),
            'cgst_amount' => $addCgst,
            'sgst_rate' => round($freightRate / 2, 2),
            'sgst_amount' => $addSgst,
            'igst_rate' => $freightRate,
            'igst_amount' => $addIgst,
            'total_tax' => $addTax,
        ];
    }
}
?>
<div class="page-header">
  <div>
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
      <h1 class="page-title"><?= e($invoice['invoice_number']) ?></h1>
      <span class="badge badge-<?= e($invoice['status']) ?>" style="font-size: 13px; padding: 4px 10px;">
        <?= e(str_replace('_', ' ', $invoice['status'])) ?>
      </span>
      <?php if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'void' && strtotime($invoice['due_date']) < time()): ?>
        <span class="badge badge-overdue" style="font-size: 13px; padding: 4px 10px;">Overdue</span>
      <?php endif; ?>
    </div>
    <div class="page-description">
      <?= e(ucfirst(str_replace('_', ' ', $invoice['document_type']))) ?> &bull; Created on <?= format_date($invoice['created_at']) ?>
    </div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/invoices/' . $invoice['id'] . '/download') ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
      Download PDF
    </a>

    <a href="<?= url('/invoices/' . $invoice['id'] . '/print') ?>" target="_blank" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
      </svg>
      Print
    </a>

    <?php if ($invoice['status'] === 'draft'): ?>
      <a href="<?= url('/invoices/' . $invoice['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">
        Edit Draft
      </a>
      <form action="<?= url('/invoices/' . $invoice['id'] . '/issue') ?>" method="POST" style="margin: 0;" onsubmit="return confirm('Officially issue this invoice? An official tax invoice number will be assigned.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary btn-sm">
          Issue Invoice
        </button>
      </form>
      <form action="<?= url('/invoices/' . $invoice['id'] . '/delete') ?>" method="POST" style="margin: 0;" onsubmit="return confirm('Permanently delete this draft invoice? This action cannot be undone.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger);">
          Delete Draft
        </button>
      </form>
    <?php endif; ?>

    <?php if (in_array($invoice['status'], ['issued', 'partially_paid'])): ?>
      <a href="<?= url('/payments/create?customer_id=' . $invoice['customer_id'] . '&invoice_id=' . $invoice['id']) ?>" class="btn btn-primary btn-sm">
        Record Payment
      </a>
    <?php endif; ?>

    <?php if (in_array($invoice['status'], ['issued', 'partially_paid', 'paid'])): ?>
      <a href="<?= url('/credit-notes/create?invoice_id=' . $invoice['id']) ?>" class="btn btn-secondary btn-sm" title="Issue Credit Note under Section 34">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m0 0l-6-6m6 6H3"/>
        </svg>
        Issue Credit Note
      </a>
    <?php endif; ?>

    <form action="<?= url('/invoices/' . $invoice['id'] . '/duplicate') ?>" method="POST" style="margin: 0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary btn-sm" title="Duplicate into a new draft">
        Duplicate
      </button>
    </form>

    <?php if ($invoice['status'] !== 'void' && $invoice['status'] !== 'draft' && $invoice['paid_amount'] == 0): ?>
      <button type="button" class="btn btn-secondary btn-sm" style="color: var(--danger);" onclick="openVoidModal()">
        Void
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if ($invoice['status'] === 'void'): ?>
  <div class="alert alert-error">
    <div>
      <strong>This invoice has been voided.</strong>
      <div style="font-size: 12px; margin-top: 2px;">
        Reason: <?= e($invoice['void_reason']) ?> &bull; Voided on <?= format_date($invoice['voided_at']) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Invoice Document Details Box -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-body">
    <!-- Header: Supplier & Customer Info Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--slate-200); margin-bottom: 24px;">
      <!-- Supplier Details -->
      <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--slate-500); margin-bottom: 6px;">
          Billed By (Supplier)
        </div>
        <div style="font-size: 16px; font-weight: 700; color: var(--slate-900);">
          <?= e($invoice['supplier_legal_name'] ?? $invoice['supplier_name']) ?>
        </div>
        <?php if (!empty($invoice['supplier_trade_name']) && $invoice['supplier_trade_name'] !== $invoice['supplier_name']): ?>
          <div style="font-size: 13px; color: var(--slate-600);"><?= e($invoice['supplier_trade_name']) ?></div>
        <?php endif; ?>
        <div style="font-size: 13px; color: var(--slate-600); margin-top: 4px; line-height: 1.5;">
          <?= nl2br(e($invoice['supplier_address'])) ?><br>
          <?= e($invoice['supplier_city']) ?>, <?= e($invoice['supplier_state']) ?> - <?= e($invoice['supplier_pincode']) ?>
        </div>
        <?php if (!empty($invoice['supplier_gstin'])): ?>
          <div style="font-size: 13px; font-weight: 600; color: var(--slate-800); margin-top: 6px;">
            GSTIN: <?= e($invoice['supplier_gstin']) ?> (State Code: <?= e($invoice['supplier_state_code']) ?>)
          </div>
        <?php endif; ?>
      </div>

      <!-- Customer Details -->
      <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--slate-500); margin-bottom: 6px;">
          Billed To (Customer)
        </div>
        <div style="font-size: 16px; font-weight: 700; color: var(--slate-900);">
          <?= e($invoice['customer_name']) ?>
        </div>
        <div style="font-size: 13px; color: var(--slate-600); margin-top: 4px; line-height: 1.5;">
          <?= nl2br(e($invoice['customer_billing_address'])) ?><br>
          <?= e($invoice['customer_billing_city']) ?>, <?= e($invoice['customer_billing_state']) ?> - <?= e($invoice['customer_billing_pincode']) ?>
        </div>
        <div style="font-size: 13px; font-weight: 600; color: var(--slate-800); margin-top: 6px;">
          GSTIN: <?= !empty($invoice['customer_gstin']) ? e($invoice['customer_gstin']) : 'Unregistered Consumer' ?>
        </div>
        <?php if (!empty($invoice['customer_phone'])): ?>
          <div style="font-size: 12px; color: var(--slate-500);">Phone: <?= e($invoice['customer_phone']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Meta Details Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; background: var(--slate-50); padding: 14px 18px; border-radius: 8px; margin-bottom: 24px;">
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">Issue Date</div>
        <div style="font-size: 13px; font-weight: 600;"><?= format_date($invoice['issue_date']) ?></div>
      </div>
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">Due Date</div>
        <div style="font-size: 13px; font-weight: 600;"><?= format_date($invoice['due_date']) ?></div>
      </div>
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">Place of Supply</div>
        <div style="font-size: 13px; font-weight: 600;"><?= e($invoice['place_of_supply_state_code']) ?> - <?= e($invoice['place_of_supply_state']) ?></div>
      </div>
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">Supply Type</div>
        <div style="font-size: 13px; font-weight: 600;">
          <?= $invoice['supply_type'] === 'intra_state' ? 'Intra-State (CGST + SGST)' : 'Inter-State (IGST)' ?>
        </div>
      </div>
      <?php if (!empty($invoice['po_number'])): ?>
        <div>
          <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">PO #</div>
          <div style="font-size: 13px; font-weight: 600;"><?= e($invoice['po_number']) ?></div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Line Items Table -->
    <div class="table-responsive" style="margin-bottom: 24px;">
      <table class="table" style="font-size: 13px;">
        <thead>
          <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 27%;">Item Description</th>
            <th style="width: 9%;">HSN/SAC</th>
            <th class="text-right" style="width: 7%;">Qty</th>
            <th class="text-right" style="width: 10%;">Rate</th>
            <th class="text-right" style="width: 8%;">Discount</th>
            <th class="text-right" style="width: 10%;">Taxable</th>
            <th class="text-center" style="width: 7%;">GST%</th>
            <?php if ($invoice['supply_type'] === 'inter_state'): ?>
              <th class="text-right" style="width: 9%;">IGST</th>
            <?php else: ?>
              <th class="text-right" style="width: 8%;">CGST</th>
              <th class="text-right" style="width: 8%;">SGST</th>
            <?php endif; ?>
            <th class="text-right" style="width: 10%;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $idx => $it): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td>
                <div style="font-weight: 600; color: var(--slate-900);"><?= e($it['name']) ?></div>
                <?php if (!empty($it['description'])): ?>
                  <div style="font-size: 11px; color: var(--slate-500); margin-top: 2px;"><?= nl2br(e($it['description'])) ?></div>
                <?php endif; ?>
              </td>
              <td style="font-family: monospace; font-size: 12px;"><?= e($it['hsn_sac'] ?: '-') ?></td>
              <td class="text-right"><?= (float)$it['quantity'] ?> <?= e($it['unit']) ?></td>
              <td class="text-right"><?= format_currency($it['unit_price']) ?></td>
              <td class="text-right">
                <?= (float)$it['discount_amount'] > 0 ? format_currency($it['discount_amount']) : '-' ?>
              </td>
              <td class="text-right" style="font-weight: 500;"><?= format_currency($it['taxable_value']) ?></td>
              <td class="text-center">
                <span class="badge" style="background: var(--slate-100); color: var(--slate-700); font-size: 11px;">
                  <?= (float)$it['gst_rate'] ?>%
                </span>
              </td>
              <?php if ($invoice['supply_type'] === 'inter_state'): ?>
                <td class="text-right" style="font-size: 12px; color: var(--slate-600);"><?= format_currency($it['igst_amount']) ?></td>
              <?php else: ?>
                <td class="text-right" style="font-size: 12px; color: var(--slate-600);"><?= format_currency($it['cgst_amount']) ?></td>
                <td class="text-right" style="font-size: 12px; color: var(--slate-600);"><?= format_currency($it['sgst_amount']) ?></td>
              <?php endif; ?>
              <td class="text-right" style="font-weight: 600; color: var(--slate-900);"><?= format_currency($it['line_total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background: var(--slate-50); font-weight: 700; border-top: 2px solid var(--slate-200);">
            <td colspan="6" style="text-align: right; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--slate-600);">Items Total:</td>
            <td class="text-right"><?= format_currency($itemsTaxableSum) ?></td>
            <td></td>
            <?php if ($invoice['supply_type'] === 'inter_state'): ?>
              <td class="text-right"><?= format_currency($itemsIgstSum) ?></td>
            <?php else: ?>
              <td class="text-right"><?= format_currency($itemsCgstSum) ?></td>
              <td class="text-right"><?= format_currency($itemsSgstSum) ?></td>
            <?php endif; ?>
            <td class="text-right" style="color: var(--primary);"><?= format_currency($itemsGrossSum) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Totals Summary & Financial Notes -->
    <div style="display: grid; grid-template-columns: 1fr 390px; gap: 32px; align-items: start;">
      <div>
        <!-- Bank Details for Payment -->
        <?php if (!empty($bankDetails['bank_name'])): ?>
          <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: 8px; padding: 14px 18px; margin-bottom: 16px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500); margin-bottom: 6px;">
              Bank Remittance Details
            </div>
            <div style="font-size: 13px; line-height: 1.6;">
              <strong>Bank:</strong> <?= e($bankDetails['bank_name']) ?> &bull; <strong>Branch:</strong> <?= e($bankDetails['branch']) ?><br>
              <strong>A/C No:</strong> <?= e($bankDetails['account_no']) ?> &bull; <strong>IFSC:</strong> <?= e($bankDetails['ifsc']) ?><br>
              <?php if (!empty($bankDetails['upi_id'])): ?>
                <strong>UPI ID:</strong> <?= e($bankDetails['upi_id']) ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- GST Tax Analysis Table by HSN/SAC -->
        <?php if (!empty($hsnBreakdown)): ?>
          <div style="background: white; border: 1px solid var(--slate-200); border-radius: 8px; padding: 14px 16px; margin-bottom: 16px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--slate-500); margin-bottom: 8px;">
              GST Tax Breakdown by HSN / SAC
            </div>
            <div class="table-responsive">
              <table class="table" style="font-size: 12px; margin-bottom: 0;">
                <thead>
                  <tr style="background: var(--slate-50);">
                    <th>HSN / SAC</th>
                    <th class="text-right">Taxable</th>
                    <th class="text-center">Rate</th>
                    <?php if ($invoice['supply_type'] === 'inter_state'): ?>
                      <th class="text-right">IGST</th>
                    <?php else: ?>
                      <th class="text-right">CGST</th>
                      <th class="text-right">SGST</th>
                    <?php endif; ?>
                    <th class="text-right">Total Tax</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($hsnBreakdown as $h): ?>
                    <tr>
                      <td style="font-family: monospace; font-weight: 600; color: var(--slate-800);"><?= e($h['hsn_sac']) ?></td>
                      <td class="text-right"><?= format_currency($h['taxable_value']) ?></td>
                      <td class="text-center"><?= (float)$h['gst_rate'] ?>%</td>
                      <?php if ($invoice['supply_type'] === 'inter_state'): ?>
                        <td class="text-right"><?= format_currency($h['igst_amount']) ?></td>
                      <?php else: ?>
                        <td class="text-right"><?= format_currency($h['cgst_amount']) ?></td>
                        <td class="text-right"><?= format_currency($h['sgst_amount']) ?></td>
                      <?php endif; ?>
                      <td class="text-right" style="font-weight: 600;"><?= format_currency($h['total_tax']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($invoice['notes'])): ?>
          <div style="font-size: 12px; color: var(--slate-600); margin-bottom: 12px;">
            <strong>Notes:</strong> <?= nl2br(e($invoice['notes'])) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($invoice['terms'])): ?>
          <div style="font-size: 12px; color: var(--slate-500); line-height: 1.5;">
            <strong>Terms & Conditions:</strong><br>
            <?= nl2br(e($invoice['terms'])) ?>
          </div>
        <?php endif; ?>

        <div style="margin-top: 18px; font-size: 12px; font-style: italic; color: var(--slate-500);">
          Amount in Words: <strong><?= \App\Support\NumberToWords::convertIndianCurrency($invoice['total_amount']) ?></strong>
        </div>
      </div>

      <!-- Financial Totals Box -->
      <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: 8px; padding: 18px;">
        <div class="totals-row">
          <span>Gross Subtotal:</span>
          <span style="font-weight: 500;"><?= format_currency($invoice['subtotal_gross']) ?></span>
        </div>

        <?php if ($invoice['line_discounts_total'] > 0): ?>
          <div class="totals-row">
            <span>Item Discounts:</span>
            <span style="color: var(--danger);">- <?= format_currency($invoice['line_discounts_total']) ?></span>
          </div>
        <?php endif; ?>

        <?php if ($invoice['invoice_discount_amount'] > 0): ?>
          <div class="totals-row">
            <span>Invoice Discount:</span>
            <span style="color: var(--danger);">- <?= format_currency($invoice['invoice_discount_amount']) ?></span>
          </div>
        <?php endif; ?>

        <?php if ($invoice['additional_charges'] > 0): ?>
          <div class="totals-row">
            <span><?= e($invoice['additional_charges_label'] ?: 'Shipping / Handling') ?>:</span>
            <span>+ <?= format_currency($invoice['additional_charges']) ?></span>
          </div>
        <?php endif; ?>

        <div class="totals-row" style="border-top: 1px dashed var(--slate-200); padding-top: 8px; font-weight: 600;">
          <span>Taxable Turnover:</span>
          <span><?= format_currency($invoice['taxable_value']) ?></span>
        </div>

        <?php if ($invoice['supply_type'] === 'inter_state'): ?>
          <div class="totals-row">
            <span>IGST (Integrated Tax):</span>
            <span style="font-weight: 600;"><?= format_currency($invoice['igst_amount']) ?></span>
          </div>
        <?php else: ?>
          <div class="totals-row">
            <span>CGST (Central Tax):</span>
            <span style="font-weight: 600;"><?= format_currency($invoice['cgst_amount']) ?></span>
          </div>
          <div class="totals-row">
            <span>SGST / UTGST (State Tax):</span>
            <span style="font-weight: 600;"><?= format_currency($invoice['sgst_amount']) ?></span>
          </div>
        <?php endif; ?>

        <?php if (!empty($invoice['is_reverse_charge'])): ?>
          <div style="font-size: 11px; color: var(--danger); margin: 4px 0 6px; font-weight: 500;">
            * Under Reverse Charge (RCM): Tax is payable by recipient directly to Government.
          </div>
        <?php endif; ?>

        <div class="totals-row" style="border-top: 1px solid var(--slate-200); padding-top: 4px; font-size: 13px; color: var(--slate-700);">
          <span>Total GST:</span>
          <span style="font-weight: 600;"><?= format_currency($invoice['tax_total']) ?></span>
        </div>

        <?php if ($invoice['rounding_adjustment'] != 0): ?>
          <div class="totals-row">
            <span>Round Off:</span>
            <span><?= ($invoice['rounding_adjustment'] > 0 ? '+' : '') . format_currency($invoice['rounding_adjustment']) ?></span>
          </div>
        <?php endif; ?>

        <div class="totals-row final" style="font-size: 16px; margin-top: 8px;">
          <span>Total Amount:</span>
          <span style="color: var(--primary);"><?= format_currency($invoice['total_amount']) ?></span>
        </div>

        <?php 
          $appliedCredit = 0.0;
          if (!empty($creditNotes)) {
              foreach ($creditNotes as $cn) {
                  if ($cn['status'] !== 'void') {
                      $appliedCredit += (float)($cn['applied_amount'] ?? $cn['total_amount']);
                  }
              }
          }
        ?>
        <?php if ($appliedCredit > 0): ?>
          <div class="totals-row" style="color: var(--danger); font-size: 13px; margin-top: 4px;">
            <span>Credit Notes Applied:</span>
            <span style="font-weight: 600;">-<?= format_currency($appliedCredit) ?></span>
          </div>
        <?php endif; ?>

        <div class="totals-row" style="margin-top: 10px; border-top: 1px solid var(--slate-200); padding-top: 8px;">
          <span>Paid Amount:</span>
          <span style="color: var(--success); font-weight: 600;"><?= format_currency($invoice['paid_amount']) ?></span>
        </div>

        <div class="totals-row" style="font-size: 14px; font-weight: 700; color: <?= $invoice['balance_due'] > 0 ? 'var(--danger)' : 'var(--slate-600)' ?>;">
          <span>Balance Due:</span>
          <span><?= format_currency($invoice['balance_due']) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Payment History Against This Invoice -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">Payment & Allocation History</h2>
    <?php if ($invoice['balance_due'] > 0 && in_array($invoice['status'], ['issued', 'partially_paid'])): ?>
      <a href="<?= url('/payments/create?customer_id=' . $invoice['customer_id'] . '&invoice_id=' . $invoice['id']) ?>" class="btn btn-primary btn-sm">
        + Record Payment
      </a>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Receipt #</th>
          <th>Payment Date</th>
          <th>Method</th>
          <th>Reference #</th>
          <th class="text-right">Allocated Amount</th>
          <th class="text-center">Receipt Status</th>
          <th class="text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr>
            <td colspan="7" class="empty-state">
              No payments have been recorded against this invoice yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td>
                <a href="<?= url('/payments/' . $p['payment_id']) ?>" style="font-weight: 600;">
                  <?= e($p['payment_number']) ?>
                </a>
              </td>
              <td><?= format_date($p['payment_date']) ?></td>
              <td><?= strtoupper(str_replace('_', ' ', $p['payment_method'])) ?></td>
              <td><?= e($p['reference_number'] ?: '-') ?></td>
              <td class="text-right" style="font-weight: 600; color: var(--success);">
                <?= format_currency($p['allocated_amount']) ?>
              </td>
              <td class="text-center">
                <span class="badge badge-<?= e($p['payment_status']) ?>">
                  <?= ucfirst($p['payment_status']) ?>
                </span>
              </td>
              <td class="text-right">
                <a href="<?= url('/payments/' . $p['payment_id']) ?>" class="btn btn-secondary btn-sm">
                  View Receipt
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Credit Notes Issued Against This Invoice (Section 34) -->
<div class="card" style="margin-top: 24px;">
  <div class="card-header">
    <div>
      <h2 class="card-title">Credit Notes (CGST Section 34)</h2>
      <div style="font-size: 12px; color: var(--slate-500); margin-top: 2px;">
        Sales returns, rate adjustments, and post-sale credits issued against this invoice.
      </div>
    </div>
    <?php if (in_array($invoice['status'], ['issued', 'partially_paid', 'paid'])): ?>
      <a href="<?= url('/credit-notes/create?invoice_id=' . $invoice['id']) ?>" class="btn btn-secondary btn-sm">
        + Issue Credit Note
      </a>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Credit Note #</th>
          <th>Date</th>
          <th>Reason</th>
          <th class="text-right">Credit Value</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($creditNotes)): ?>
          <tr>
            <td colspan="6" class="empty-state">
              No credit notes have been issued against this invoice.
              <?php if (in_array($invoice['status'], ['issued', 'partially_paid', 'paid'])): ?>
                <div style="margin-top: 8px;">
                  <a href="<?= url('/credit-notes/create?invoice_id=' . $invoice['id']) ?>" class="btn btn-secondary btn-sm">
                    Issue Credit Note
                  </a>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($creditNotes as $cn): ?>
            <tr>
              <td>
                <a href="<?= url('/credit-notes/' . $cn['id']) ?>" style="font-weight: 600;">
                  <?= e($cn['credit_note_number']) ?>
                </a>
              </td>
              <td><?= format_date($cn['credit_note_date']) ?></td>
              <td>
                <?= e(ucwords(str_replace('_', ' ', $cn['reason'] ?? 'Sales Return'))) ?>
                <?php if (!empty($cn['reason_description'])): ?>
                  <div style="font-size: 11px; color: var(--slate-500);"><?= e($cn['reason_description']) ?></div>
                <?php endif; ?>
              </td>
              <td class="text-right" style="font-weight: 600; color: var(--danger);">
                -<?= format_currency($cn['total_amount']) ?>
              </td>
              <td class="text-center">
                <?php
                  $badgeClass = match($cn['status']) {
                    'applied' => 'badge-success',
                    'issued' => 'badge-info',
                    'void' => 'badge-danger',
                    default => 'badge-secondary',
                  };
                ?>
                <span class="badge <?= $badgeClass ?>">
                  <?= strtoupper($cn['status']) ?>
                </span>
              </td>
              <td class="text-right">
                <div style="display: inline-flex; gap: 6px;">
                  <a href="<?= url('/credit-notes/' . $cn['id']) ?>" class="btn btn-secondary btn-sm">
                    View
                  </a>
                  <a href="<?= url('/credit-notes/' . $cn['id'] . '/print') ?>" target="_blank" class="btn btn-secondary btn-sm" title="Print">
                    Print
                  </a>
                  <a href="<?= url('/credit-notes/' . $cn['id'] . '/download') ?>" class="btn btn-secondary btn-sm" title="Download PDF">
                    PDF
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Void Confirmation Modal -->
<div id="void-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
  <div class="card" style="width: 440px; margin: 0; padding: 24px; box-shadow: var(--shadow-lg);">
    <h3 style="color: var(--danger); margin-bottom: 8px;">Void Invoice <?= e($invoice['invoice_number']) ?></h3>
    <p style="font-size: 13px; color: var(--slate-600); margin-bottom: 16px;">
      Voiding formally cancels this issued invoice. The document number will be permanently preserved for audit records.
    </p>

    <form action="<?= url('/invoices/' . $invoice['id'] . '/void') ?>" method="POST">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label" for="void_reason">Reason for Voiding <span style="color: var(--danger);">*</span></label>
        <textarea name="void_reason" id="void_reason" class="form-control" rows="3" placeholder="e.g. Order cancelled by customer / Billed with wrong GSTIN" required></textarea>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeVoidModal()">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm">Confirm Void</button>
      </div>
    </form>
  </div>
</div>

<script>
function openVoidModal() {
  document.getElementById('void-modal').style.display = 'flex';
}
function closeVoidModal() {
  document.getElementById('void-modal').style.display = 'none';
}
</script>
