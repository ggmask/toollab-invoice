<div class="page-header">
  <div>
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
      <h1 class="page-title"><?= e($estimate['estimate_number']) ?></h1>
      <span class="badge badge-<?= e($estimate['status']) ?>" style="font-size: 13px; padding: 4px 10px;">
        <?= e(str_replace('_', ' ', $estimate['status'])) ?>
      </span>
    </div>
    <div class="page-description">
      Quotation Date: <?= format_date($estimate['estimate_date']) ?> &bull; Valid Until: <?= format_date($estimate['valid_until']) ?>
    </div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/estimates/' . $estimate['id'] . '/download') ?>" class="btn btn-secondary btn-sm">
      Download PDF
    </a>
    <a href="<?= url('/estimates/' . $estimate['id'] . '/print') ?>" target="_blank" class="btn btn-secondary btn-sm">
      Print
    </a>

    <?php if ($estimate['status'] !== 'converted'): ?>
      <form action="<?= url('/estimates/' . $estimate['id'] . '/convert') ?>" method="POST" style="margin: 0;" onsubmit="return confirm('Convert this estimate into a draft invoice? Agreed line items and prices will be transferred.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary btn-sm">
          Convert to Invoice &rarr;
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($linkedInvoice)): ?>
  <div class="alert alert-success">
    <div>
      This estimate was converted into invoice 
      <a href="<?= url('/invoices/' . $linkedInvoice['id']) ?>" style="font-weight: 700; text-decoration: underline;">
        <?= e($linkedInvoice['invoice_number']) ?>
      </a> (Total: <?= format_currency($linkedInvoice['total_amount']) ?>).
    </div>
  </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 24px;">
  <div class="card-body">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--slate-200); margin-bottom: 24px;">
      <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500); margin-bottom: 6px;">
          From
        </div>
        <div style="font-size: 16px; font-weight: 700; color: var(--slate-900);">
          <?= e($estimate['supplier_name']) ?>
        </div>
        <div style="font-size: 13px; color: var(--slate-600); margin-top: 4px; line-height: 1.5;">
          <?= nl2br(e($estimate['supplier_address'])) ?><br>
          <?= e($estimate['supplier_city']) ?>, <?= e($estimate['supplier_state']) ?> - <?= e($estimate['supplier_pincode']) ?>
        </div>
        <?php if (!empty($estimate['supplier_gstin'])): ?>
          <div style="font-size: 13px; font-weight: 600; color: var(--slate-800); margin-top: 6px;">
            GSTIN: <?= e($estimate['supplier_gstin']) ?>
          </div>
        <?php endif; ?>
      </div>

      <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500); margin-bottom: 6px;">
          Quotation For (Customer)
        </div>
        <div style="font-size: 16px; font-weight: 700; color: var(--slate-900);">
          <?= e($estimate['customer_name']) ?>
        </div>
        <div style="font-size: 13px; color: var(--slate-600); margin-top: 4px; line-height: 1.5;">
          <?= nl2br(e($estimate['customer_billing_address'])) ?><br>
          <?= e($estimate['customer_billing_city']) ?>, <?= e($estimate['customer_billing_state']) ?> - <?= e($estimate['customer_billing_pincode']) ?>
        </div>
        <div style="font-size: 13px; font-weight: 600; color: var(--slate-800); margin-top: 6px;">
          GSTIN: <?= !empty($estimate['customer_gstin']) ? e($estimate['customer_gstin']) : 'Unregistered' ?>
        </div>
      </div>
    </div>

    <!-- Status updater if not converted -->
    <?php if ($estimate['status'] !== 'converted'): ?>
      <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 24px; padding: 12px 16px; background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: 8px;">
        <span style="font-size: 12px; font-weight: 600; color: var(--slate-700);">Update Status:</span>
        <form action="<?= url('/estimates/' . $estimate['id'] . '/status') ?>" method="POST" style="display: inline; margin: 0;">
          <?= csrf_field() ?>
          <input type="hidden" name="status" value="sent">
          <button type="submit" class="btn btn-secondary btn-sm" <?= $estimate['status'] === 'sent' ? 'disabled' : '' ?>>Mark Sent</button>
        </form>
        <form action="<?= url('/estimates/' . $estimate['id'] . '/status') ?>" method="POST" style="display: inline; margin: 0;">
          <?= csrf_field() ?>
          <input type="hidden" name="status" value="accepted">
          <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--success);" <?= $estimate['status'] === 'accepted' ? 'disabled' : '' ?>>Mark Accepted</button>
        </form>
        <form action="<?= url('/estimates/' . $estimate['id'] . '/status') ?>" method="POST" style="display: inline; margin: 0;">
          <?= csrf_field() ?>
          <input type="hidden" name="status" value="rejected">
          <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger);" <?= $estimate['status'] === 'rejected' ? 'disabled' : '' ?>>Mark Rejected</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="table-responsive" style="margin-bottom: 24px;">
      <table class="table" style="font-size: 13px;">
        <thead>
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 38%;">Item Description</th>
            <th style="width: 10%;">HSN/SAC</th>
            <th class="text-right" style="width: 8%;">Qty</th>
            <th class="text-right" style="width: 12%;">Rate</th>
            <th class="text-right" style="width: 10%;">Discount</th>
            <th class="text-right" style="width: 12%;">Taxable</th>
            <th class="text-right" style="width: 11%;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $idx => $it): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td>
                <div style="font-weight: 600; color: var(--slate-900);"><?= e($it['name']) ?></div>
                <?php if (!empty($it['description'])): ?>
                  <div style="font-size: 12px; color: var(--slate-500); margin-top: 2px;"><?= nl2br(e($it['description'])) ?></div>
                <?php endif; ?>
              </td>
              <td><?= e($it['hsn_sac'] ?: '-') ?></td>
              <td class="text-right"><?= (float)$it['quantity'] ?> <?= e($it['unit']) ?></td>
              <td class="text-right"><?= format_currency($it['unit_price']) ?></td>
              <td class="text-right"><?= (float)$it['discount_amount'] > 0 ? format_currency($it['discount_amount']) : '-' ?></td>
              <td class="text-right" style="font-weight: 500;"><?= format_currency($it['taxable_value']) ?></td>
              <td class="text-right" style="font-weight: 600; color: var(--slate-900);"><?= format_currency($it['line_total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 380px; gap: 32px; align-items: start;">
      <div>
        <?php if (!empty($estimate['notes'])): ?>
          <div style="font-size: 12px; color: var(--slate-600); margin-bottom: 12px;">
            <strong>Notes:</strong> <?= nl2br(e($estimate['notes'])) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($estimate['terms'])): ?>
          <div style="font-size: 12px; color: var(--slate-500); line-height: 1.5;">
            <strong>Terms:</strong><br>
            <?= nl2br(e($estimate['terms'])) ?>
          </div>
        <?php endif; ?>

        <div style="margin-top: 18px; font-size: 12px; font-style: italic; color: var(--slate-500);">
          Estimated Amount in Words: <strong><?= \App\Support\NumberToWords::convertIndianCurrency($estimate['total_amount']) ?></strong>
        </div>
      </div>

      <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: 8px; padding: 18px;">
        <div class="totals-row">
          <span>Gross Subtotal:</span>
          <span><?= format_currency($estimate['subtotal_gross']) ?></span>
        </div>
        <?php if ($estimate['line_discounts_total'] > 0): ?>
          <div class="totals-row">
            <span>Item Discounts:</span>
            <span style="color: var(--danger);">- <?= format_currency($estimate['line_discounts_total']) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($estimate['estimate_discount_amount'] > 0): ?>
          <div class="totals-row">
            <span>Estimate Discount:</span>
            <span style="color: var(--danger);">- <?= format_currency($estimate['estimate_discount_amount']) ?></span>
          </div>
        <?php endif; ?>
        <div class="totals-row" style="border-top: 1px dashed var(--slate-200); padding-top: 6px; font-weight: 600;">
          <span>Taxable Amount:</span>
          <span><?= format_currency($estimate['taxable_value']) ?></span>
        </div>
        <div class="totals-row">
          <span>GST Taxes:</span>
          <span><?= format_currency($estimate['tax_total']) ?></span>
        </div>
        <?php if ($estimate['additional_charges'] > 0): ?>
          <div class="totals-row">
            <span>Additional Charges:</span>
            <span><?= format_currency($estimate['additional_charges']) ?></span>
          </div>
        <?php endif; ?>
        <div class="totals-row final" style="font-size: 16px;">
          <span>Estimated Total:</span>
          <span style="color: var(--primary);"><?= format_currency($estimate['total_amount']) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>
