<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <?php if (!empty($creditNote['invoice_id'])): ?>
        <a href="<?= url('/invoices/' . $creditNote['invoice_id']) ?>" style="color: var(--slate-500);">&larr; Back to Invoice #<?= e($creditNote['original_invoice_number']) ?></a>
      <?php else: ?>
        <a href="<?= url('/invoices') ?>" style="color: var(--slate-500);">&larr; Back to Invoices</a>
      <?php endif; ?>
      / <?= e($creditNote['credit_note_number']) ?>
    </div>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h1 class="page-title" style="margin: 0;">Credit Note #<?= e($creditNote['credit_note_number']) ?></h1>
      <?php
        $badgeClass = match($creditNote['status']) {
          'applied' => 'badge-success',
          'issued' => 'badge-info',
          'void' => 'badge-danger',
          default => 'badge-secondary',
        };
      ?>
      <span class="badge <?= $badgeClass ?>"><?= strtoupper($creditNote['status']) ?></span>
    </div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/credit-notes/' . $creditNote['id'] . '/print') ?>" target="_blank" class="btn btn-secondary">
      <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
      </svg>
      Print
    </a>
    <a href="<?= url('/credit-notes/' . $creditNote['id'] . '/download') ?>" class="btn btn-primary">
      <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Download PDF
    </a>
    <?php if ($creditNote['status'] !== 'void'): ?>
      <form method="POST" action="<?= url('/credit-notes/' . $creditNote['id'] . '/void') ?>" style="margin: 0;" onsubmit="return confirm('Are you sure you want to void this Credit Note? Any applied deductions on the original invoice will be reversed.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Void Note</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
  <div>
    <!-- Main Details Card -->
    <div class="card" style="margin-bottom: 20px;">
      <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--slate-200);">
          <div>
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--slate-500); margin-bottom: 4px;">Customer Details</div>
            <strong style="font-size: 15px; color: var(--slate-900);"><?= e($customer['name']) ?></strong><br>
            <?= nl2br(e($customer['billing_address'])) ?><br>
            <?= e($customer['billing_city']) ?>, <?= e($customer['billing_state']) ?> - <?= e($customer['billing_pincode']) ?><br>
            <strong>GSTIN:</strong> <?= !empty($customer['gstin']) ? e($customer['gstin']) : 'Unregistered' ?><br>
            <strong>Phone:</strong> <?= e($customer['phone'] ?: '-') ?>
          </div>

          <div>
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--slate-500); margin-bottom: 4px;">Statutory Reference</div>
            <div style="line-height: 1.6; font-size: 13px;">
              <strong>Credit Note Date:</strong> <?= format_date($creditNote['credit_note_date']) ?><br>
              <strong>Original Tax Invoice:</strong> 
              <?php if (!empty($creditNote['invoice_id'])): ?>
                <a href="<?= url('/invoices/' . $creditNote['invoice_id']) ?>" style="color: var(--primary); font-weight: 700;">
                  #<?= e($creditNote['original_invoice_number']) ?>
                </a>
              <?php else: ?>
                <strong>#<?= e($creditNote['original_invoice_number']) ?></strong>
              <?php endif; ?>
              <br>
              <?php if (!empty($creditNote['original_invoice_date'])): ?>
                <strong>Invoice Date:</strong> <?= format_date($creditNote['original_invoice_date']) ?><br>
              <?php endif; ?>
              <strong>Reason (Sec 34):</strong> <?= e(ucwords(str_replace('_', ' ', $creditNote['reason']))) ?><br>
              <?php if (!empty($creditNote['reason_description'])): ?>
                <strong>Details:</strong> <?= e($creditNote['reason_description']) ?><br>
              <?php endif; ?>
              <strong>Supply Type:</strong> <?= ucfirst(str_replace('_', ' ', $creditNote['supply_type'])) ?>
            </div>
          </div>
        </div>

        <div style="margin-top: 20px;">
          <h4 style="font-size: 13px; text-transform: uppercase; margin-bottom: 12px; color: var(--slate-600);">Credited Line Items</h4>
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Item & Description</th>
                <th>HSN/SAC</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Rate (₹)</th>
                <th class="text-right">Taxable (₹)</th>
                <th class="text-right">GST %</th>
                <th class="text-right">Total (₹)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $idx => $it): ?>
                <tr>
                  <td><?= $idx + 1 ?></td>
                  <td>
                    <strong><?= e($it['name']) ?></strong>
                    <?php if (!empty($it['description'])): ?>
                      <div style="font-size: 11px; color: var(--slate-500);"><?= nl2br(e($it['description'])) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= e($it['hsn_sac'] ?: '-') ?></td>
                  <td class="text-right"><?= (float)$it['quantity'] ?> <?= e($it['unit']) ?></td>
                  <td class="text-right"><?= number_format($it['unit_price'], 2) ?></td>
                  <td class="text-right"><?= number_format($it['taxable_value'], 2) ?></td>
                  <td class="text-right"><?= (float)$it['gst_rate'] ?>%</td>
                  <td class="text-right" style="font-weight: 700;"><?= number_format($it['line_total'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div>
    <!-- Financial Overview Card -->
    <div class="card" style="margin-bottom: 20px;">
      <div class="card-header">
        <h3 class="card-title">Credit Summary</h3>
      </div>
      <div class="card-body">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span style="color: var(--slate-600);">Taxable Value:</span>
          <span style="font-weight: 600;"><?= format_currency($creditNote['taxable_value']) ?></span>
        </div>
        <?php if ($creditNote['cgst_amount'] > 0): ?>
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span style="color: var(--slate-600);">CGST:</span>
            <span><?= format_currency($creditNote['cgst_amount']) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($creditNote['sgst_amount'] > 0): ?>
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span style="color: var(--slate-600);">SGST / UTGST:</span>
            <span><?= format_currency($creditNote['sgst_amount']) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($creditNote['igst_amount'] > 0): ?>
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span style="color: var(--slate-600);">IGST:</span>
            <span><?= format_currency($creditNote['igst_amount']) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($creditNote['rounding_adjustment'] != 0): ?>
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span style="color: var(--slate-600);">Round Off:</span>
            <span><?= ($creditNote['rounding_adjustment'] > 0 ? '+' : '') . format_currency($creditNote['rounding_adjustment']) ?></span>
          </div>
        <?php endif; ?>
        <div style="display: flex; justify-content: space-between; padding-top: 10px; border-top: 2px solid var(--slate-800); font-size: 16px; font-weight: 700; color: #b91c1c; margin-bottom: 16px;">
          <span>Total Credit Amount:</span>
          <span><?= format_currency($creditNote['total_amount']) ?></span>
        </div>

        <div style="background: #f9fafb; border: 1px solid var(--slate-200); border-radius: 4px; padding: 12px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="font-size: 12px; color: var(--slate-600);">Applied to Invoice:</span>
            <span style="font-weight: 700; color: #047857;"><?= format_currency($creditNote['applied_amount']) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="font-size: 12px; color: var(--slate-600);">Unapplied Balance:</span>
            <span style="font-weight: 700; color: #b45309;"><?= format_currency($creditNote['unapplied_amount']) ?></span>
          </div>
        </div>

        <div style="margin-top: 16px; font-size: 11px; color: var(--slate-500);">
          <strong>In Words:</strong><br>
          <em><?= \App\Support\NumberToWords::convertIndianCurrency($creditNote['total_amount']) ?></em>
        </div>
      </div>
    </div>
  </div>
</div>

