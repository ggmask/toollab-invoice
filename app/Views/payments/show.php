<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <a href="<?= url('/payments') ?>" style="color: var(--slate-500);">&larr; Payments</a> / <?= e($payment['payment_number']) ?>
    </div>
    <div style="display: flex; align-items: center; gap: 12px;">
      <h1 class="page-title"><?= e($payment['payment_number']) ?></h1>
      <span class="badge badge-<?= ($payment['status'] === 'posted') ? 'posted' : 'reversed' ?>">
        <?= e(ucfirst($payment['status'])) ?> Receipt
      </span>
    </div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/payments/' . $payment['id'] . '/print') ?>" target="_blank" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
      </svg>
      Print Voucher
    </a>
    <a href="<?= url('/payments/' . $payment['id'] . '/pdf') ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
      </svg>
      Download PDF
    </a>
    <?php if ($payment['status'] === 'posted'): ?>
      <button type="button" onclick="document.getElementById('reverseModal').classList.remove('hidden')" class="btn btn-danger btn-sm">
        Reverse Receipt
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if ($payment['status'] === 'reversed'): ?>
  <div style="background: var(--danger-light); border: 1px solid #FECACA; border-radius: var(--radius); padding: 14px 18px; margin-bottom: 24px; color: #991B1B;">
    <div style="font-weight: 700; font-size: 14px;">This payment receipt was reversed on <?= format_date($payment['reversed_at']) ?></div>
    <div style="font-size: 13px; margin-top: 4px;"><strong>Reason:</strong> <?= e($payment['reversal_reason']) ?></div>
    <div style="font-size: 12px; margin-top: 4px; color: #B91C1C;">All invoice balances linked to this receipt have been restored.</div>
  </div>
<?php endif; ?>

<!-- Voucher Document Box -->
<div class="card" style="max-width: 860px; margin: 0 auto 30px auto; padding: 32px; background: var(--white); box-shadow: var(--shadow);">
  <!-- Header -->
  <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--slate-200); padding-bottom: 20px; margin-bottom: 24px;">
    <div style="display: flex; gap: 16px; align-items: center;">
      <img src="<?= asset('logo.png') ?>" alt="ToolLab Logo" style="height: 48px; width: auto; object-fit: contain;">
      <div>
        <h2 style="font-size: 18px; font-weight: 700; color: var(--slate-900); margin: 0;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></h2>
        <div style="font-size: 12px; color: var(--slate-500); margin-top: 2px;"><?= e($business['address_line1']) ?>, <?= e($business['city']) ?>, <?= e($business['state']) ?> - <?= e($business['pincode']) ?></div>
        <div style="font-size: 12px; color: var(--slate-500);">GSTIN: <span style="font-family: monospace; font-weight: 600;"><?= e($business['gstin'] ?: 'Unregistered') ?></span></div>
      </div>
    </div>

    <div style="text-align: right;">
      <span style="display: inline-block; padding: 4px 10px; background: var(--primary-light); color: var(--primary); font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 4px; letter-spacing: 0.05em;">Payment Receipt Voucher</span>
      <div style="font-size: 18px; font-weight: 700; font-family: monospace; color: var(--slate-900); margin-top: 6px;"><?= e($payment['payment_number']) ?></div>
      <div style="font-size: 12px; color: var(--slate-500); margin-top: 2px;">Receipt Date: <strong style="color: var(--slate-800);"><?= format_date($payment['payment_date']) ?></strong></div>
    </div>
  </div>

  <!-- Receipt Metadata Grid -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius); padding: 18px; margin-bottom: 24px;">
    <div>
      <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400); display: block;">Received From</span>
      <h3 style="font-size: 15px; font-weight: 700; color: var(--slate-900); margin: 4px 0 2px 0;">
        <a href="<?= url('/customers/' . $payment['customer_id']) ?>" style="color: var(--slate-900); text-decoration: none;">
          <?= e($payment['customer_name']) ?>
        </a>
      </h3>
      <?php if (!empty($payment['customer_gstin'])): ?>
        <div style="font-size: 12px; color: var(--slate-600);">GSTIN: <span style="font-family: monospace; font-weight: 600;"><?= e($payment['customer_gstin']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($payment['billing_address'])): ?>
        <div style="font-size: 12px; color: var(--slate-500); margin-top: 2px;"><?= e($payment['billing_address']) ?>, <?= e($payment['billing_city']) ?>, <?= e($payment['billing_state']) ?> - <?= e($payment['billing_pincode']) ?></div>
      <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
      <div>
        <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400); display: block;">Payment Mode</span>
        <span style="font-size: 13px; font-weight: 600; color: var(--slate-800); display: block; margin-top: 2px;">
          <?= e(strtoupper(str_replace('_', ' ', $payment['payment_method']))) ?>
        </span>
      </div>
      <div>
        <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400); display: block;">Reference / UTR</span>
        <span style="font-size: 13px; font-family: monospace; color: var(--slate-800); display: block; margin-top: 2px;">
          <?= e($payment['reference_number'] ?: '—') ?>
        </span>
      </div>
      <div>
        <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400); display: block;">Bank Account</span>
        <span style="font-size: 13px; color: var(--slate-800); display: block; margin-top: 2px;">
          <?= e($payment['bank_name'] ?: '—') ?>
        </span>
      </div>
      <div>
        <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400); display: block;">Amount Received</span>
        <span style="font-size: 17px; font-weight: 700; color: var(--success); display: block; margin-top: 2px;">
          <?= format_currency($payment['amount']) ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Amount in Words -->
  <div style="border-left: 3px solid var(--primary); padding-left: 14px; margin-bottom: 24px;">
    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400); display: block;">Amount in Words</span>
    <div style="font-size: 13px; font-weight: 500; color: var(--slate-800); font-style: italic; margin-top: 2px;">
      <?= e(App\Support\NumberToWords::toWords((float)$payment['amount'])) ?>
    </div>
  </div>

  <!-- Allocations Table -->
  <div style="margin-bottom: 24px;">
    <h3 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--slate-600); margin-bottom: 8px;">Invoice Allocations</h3>
    <div class="table-responsive" style="border: 1px solid var(--slate-200); border-radius: var(--radius);">
      <table class="table">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Issue Date</th>
            <th class="text-right">Total Invoice (₹)</th>
            <th class="text-right">Allocated Amount (₹)</th>
            <th class="text-right">Current Remaining (₹)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($allocations)): ?>
            <tr>
              <td colspan="5" style="text-align: center; color: var(--slate-500); font-style: italic; padding: 20px;">
                No direct invoice allocations. Retained as unallocated advance credit.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($allocations as $a): ?>
              <tr>
                <td style="font-weight: 600;">
                  <a href="<?= url('/invoices/' . $a['invoice_id']) ?>" style="color: var(--primary);">
                    <?= e($a['invoice_number']) ?>
                  </a>
                </td>
                <td style="font-size: 12px; color: var(--slate-500);"><?= format_date($a['issue_date']) ?></td>
                <td class="text-right"><?= format_currency($a['total_amount']) ?></td>
                <td class="text-right" style="font-weight: 700; color: var(--success);"><?= format_currency($a['amount']) ?></td>
                <td class="text-right" style="color: var(--slate-600);"><?= format_currency($a['current_balance']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot style="background: var(--slate-50); font-weight: 600; border-top: 1px solid var(--slate-200);">
          <tr>
            <td colspan="3" class="text-right">Total Allocated:</td>
            <td class="text-right" style="color: var(--success); font-weight: 700;"><?= format_currency($payment['allocated_amount']) ?></td>
            <td></td>
          </tr>
          <?php if ((float)$payment['unallocated_amount'] > 0): ?>
            <tr>
              <td colspan="3" class="text-right" style="color: var(--warning);">Unallocated Advance Credit:</td>
              <td class="text-right" style="color: var(--warning); font-weight: 700;"><?= format_currency($payment['unallocated_amount']) ?></td>
              <td></td>
            </tr>
          <?php endif; ?>
        </tfoot>
      </table>
    </div>
  </div>

  <?php if (!empty($payment['notes'])): ?>
    <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius); padding: 12px 16px; margin-bottom: 24px;">
      <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400); display: block;">Notes</span>
      <div style="font-size: 13px; color: var(--slate-700); margin-top: 4px;"><?= nl2br(e($payment['notes'])) ?></div>
    </div>
  <?php endif; ?>

  <!-- Signature Footer -->
  <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 32px; border-top: 1px solid var(--slate-200);">
    <div style="font-size: 11px; color: var(--slate-400);">
      <div>Computer-generated official receipt voucher.</div>
      <div>Transaction UUID: <?= e($payment['uuid']) ?></div>
    </div>

    <div style="text-align: center; width: 200px;">
      <div style="height: 48px; border-bottom: 1px dashed var(--slate-300);"></div>
      <div style="font-size: 13px; font-weight: 700; color: var(--slate-800); margin-top: 8px;"><?= e($business['trade_name'] ?: $business['legal_name']) ?></div>
      <div style="font-size: 11px; color: var(--slate-500);"><?= e($business['signature_title'] ?: 'Authorized Signatory') ?></div>
    </div>
  </div>
</div>

<!-- Reversal Modal -->
<?php if ($payment['status'] === 'posted'): ?>
  <div id="reverseModal" class="hidden modal-backdrop">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title" style="color: var(--danger);">Reverse Payment Receipt</h3>
        <button type="button" onclick="document.getElementById('reverseModal').classList.add('hidden')" class="modal-close">&times;</button>
      </div>

      <form method="POST" action="<?= url('/payments/' . $payment['id'] . '/reverse') ?>">
        <?= csrf_field() ?>
        <div class="modal-body">
          <p style="font-size: 13px; color: var(--slate-600); margin-bottom: 16px;">
            This action will reverse the receipt, restore balances on all allocated invoices, and record an audit log.
          </p>

          <div class="form-group">
            <label class="form-label" for="reversal_reason">Reason for Reversal <span style="color: var(--danger);">*</span></label>
            <textarea name="reversal_reason" id="reversal_reason" rows="3" class="form-control" required placeholder="e.g. Cheque bounced, entered in error..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" onclick="document.getElementById('reverseModal').classList.add('hidden')" class="btn btn-secondary">
            Cancel
          </button>
          <button type="submit" class="btn btn-danger">
            Confirm Reversal
          </button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>
