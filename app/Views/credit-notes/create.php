<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <?php if (!empty($linkedInvoice)): ?>
        <a href="<?= url('/invoices/' . $linkedInvoice['id']) ?>" style="color: var(--slate-500);">&larr; Back to Invoice #<?= e($linkedInvoice['invoice_number']) ?></a>
      <?php else: ?>
        <a href="<?= url('/invoices') ?>" style="color: var(--slate-500);">&larr; Back to Invoices</a>
      <?php endif; ?>
      / New Credit Note
    </div>
    <h1 class="page-title">Issue Credit Note</h1>
    <div class="page-description">Create an official credit note in compliance with Section 34 of the CGST Act, 2017.</div>
  </div>
</div>

<form method="POST" action="<?= url('/credit-notes') ?>" id="credit-note-form">
  <?= csrf_field() ?>
  <?php if ($linkedInvoice): ?>
    <input type="hidden" name="invoice_id" value="<?= $linkedInvoice['id'] ?>">
  <?php endif; ?>

  <div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
      <h3 class="card-title">Credit Note Details & Reference</h3>
    </div>
    <div class="card-body">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div>
          <label class="form-label required">Customer</label>
          <select name="customer_id" class="form-control" required id="customer-select">
            <option value="">-- Select Customer --</option>
            <?php foreach ($customers as $c): ?>
              <?php $sel = ($linkedInvoice && $linkedInvoice['customer_id'] == $c['id']) ? 'selected' : ''; ?>
              <option value="<?= $c['id'] ?>" <?= $sel ?> data-gstin="<?= e($c['gstin']) ?>" data-state="<?= e($c['billing_state_code']) ?>">
                <?= e($c['name']) ?> (<?= !empty($c['gstin']) ? e($c['gstin']) : 'Unregistered' ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label required">Original Tax Invoice #</label>
          <input type="text" name="original_invoice_number" class="form-control" required 
                 value="<?= e($linkedInvoice ? $linkedInvoice['invoice_number'] : '') ?>" 
                 placeholder="e.g. INV-2026-27-0001">
        </div>

        <div>
          <label class="form-label">Original Invoice Date</label>
          <input type="date" name="original_invoice_date" class="form-control"
                 value="<?= e($linkedInvoice ? $linkedInvoice['issue_date'] : '') ?>">
        </div>

        <div>
          <label class="form-label required">Credit Note Date</label>
          <input type="date" name="credit_note_date" class="form-control" required value="<?= e($today) ?>">
        </div>

        <div>
          <label class="form-label required">Reason for Issuance (CGST Sec 34)</label>
          <select name="reason" class="form-control" required>
            <option value="sales_return">1. Sales Return / Goods Rejected</option>
            <option value="deficiency_in_service">2. Deficiency in Services</option>
            <option value="correction_in_invoice">3. Correction in Invoice / Price Revision</option>
            <option value="post_sale_discount">4. Post-Sale Discount / Volume Rebate</option>
            <option value="change_in_pos">5. Change in Place of Supply</option>
            <option value="other">6. Other Statutory Reasons</option>
          </select>
        </div>

        <div>
          <label class="form-label">Reason Description / Notes</label>
          <input type="text" name="reason_description" class="form-control" placeholder="Specific reason details...">
        </div>
      </div>
    </div>
  </div>

  <!-- Line Items Card -->
  <div class="card" style="margin-bottom: 20px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 class="card-title">Line Items to Credit</h3>
      <button type="button" class="btn btn-secondary btn-sm" id="btn-add-item">+ Add Line Item</button>
    </div>
    <div class="card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="table" id="items-table" style="margin: 0;">
          <thead>
            <tr>
              <th style="width: 32%;">Item Name & Description</th>
              <th style="width: 12%;">HSN/SAC</th>
              <th style="width: 10%; text-align: right;">Qty</th>
              <th style="width: 10%;">Unit</th>
              <th style="width: 14%; text-align: right;">Rate (₹)</th>
              <th style="width: 12%; text-align: right;">GST Rate %</th>
              <th style="width: 10%; text-align: right;">Total (₹)</th>
              <th style="width: 4%;"></th>
            </tr>
          </thead>
          <tbody id="items-tbody">
            <?php if (!empty($invoiceItems)): ?>
              <?php foreach ($invoiceItems as $idx => $it): ?>
                <tr class="item-row">
                  <td>
                    <input type="text" name="items[<?= $idx ?>][name]" value="<?= e($it['name']) ?>" class="form-control form-control-sm" required placeholder="Item name">
                    <input type="text" name="items[<?= $idx ?>][description]" value="<?= e($it['description']) ?>" class="form-control form-control-sm" style="margin-top: 4px; font-size: 11px;" placeholder="Optional description">
                  </td>
                  <td>
                    <input type="text" name="items[<?= $idx ?>][hsn_sac]" value="<?= e($it['hsn_sac']) ?>" class="form-control form-control-sm" placeholder="HSN/SAC">
                  </td>
                  <td>
                    <input type="number" step="any" min="0.001" name="items[<?= $idx ?>][quantity]" value="<?= (float)$it['quantity'] ?>" class="form-control form-control-sm text-right item-qty" required>
                  </td>
                  <td>
                    <input type="text" name="items[<?= $idx ?>][unit]" value="<?= e($it['unit'] ?: 'PCS') ?>" class="form-control form-control-sm" style="width: 70px;">
                  </td>
                  <td>
                    <input type="number" step="any" min="0" name="items[<?= $idx ?>][unit_price]" value="<?= (float)$it['unit_price'] ?>" class="form-control form-control-sm text-right item-rate" required>
                  </td>
                  <td>
                    <select name="items[<?= $idx ?>][gst_rate]" class="form-control form-control-sm text-right item-tax">
                      <?php foreach ($gstRates as $r): ?>
                        <option value="<?= $r ?>" <?= (float)$it['gst_rate'] == (float)$r ? 'selected' : '' ?>><?= $r ?>%</option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="text-right" style="font-weight: 600; vertical-align: middle;">
                    <span class="item-total">₹ 0.00</span>
                  </td>
                  <td style="text-align: center; vertical-align: middle;">
                    <button type="button" class="btn btn-danger btn-sm btn-del-row" style="padding: 2px 6px;">&times;</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr class="item-row">
                <td>
                  <input type="text" name="items[0][name]" class="form-control form-control-sm" required placeholder="Item name">
                  <input type="text" name="items[0][description]" class="form-control form-control-sm" style="margin-top: 4px; font-size: 11px;" placeholder="Optional description">
                </td>
                <td>
                  <input type="text" name="items[0][hsn_sac]" class="form-control form-control-sm" placeholder="HSN/SAC">
                </td>
                <td>
                  <input type="number" step="any" min="0.001" name="items[0][quantity]" value="1" class="form-control form-control-sm text-right item-qty" required>
                </td>
                <td>
                  <input type="text" name="items[0][unit]" value="PCS" class="form-control form-control-sm" style="width: 70px;">
                </td>
                <td>
                  <input type="number" step="any" min="0" name="items[0][unit_price]" value="0" class="form-control form-control-sm text-right item-rate" required>
                </td>
                <td>
                  <select name="items[0][gst_rate]" class="form-control form-control-sm text-right item-tax">
                    <?php foreach ($gstRates as $r): ?>
                      <option value="<?= $r ?>" <?= (float)$r === 18.0 ? 'selected' : '' ?>><?= $r ?>%</option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td class="text-right" style="font-weight: 600; vertical-align: middle;">
                  <span class="item-total">₹ 0.00</span>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <button type="button" class="btn btn-danger btn-sm btn-del-row" style="padding: 2px 6px;">&times;</button>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Summary & Submission -->
  <div style="display: flex; justify-content: flex-end; margin-bottom: 30px;">
    <div class="card" style="width: 360px;">
      <div class="card-body">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span style="color: var(--slate-600);">Taxable Credit:</span>
          <span style="font-weight: 600;" id="sum-taxable">₹ 0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span style="color: var(--slate-600);">Total GST Credit:</span>
          <span style="font-weight: 600;" id="sum-tax">₹ 0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
          <span style="color: var(--slate-600);">Round Off:</span>
          <span id="sum-round">₹ 0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding-top: 10px; border-top: 2px solid var(--slate-800); font-size: 16px; font-weight: 700; color: #b91c1c;">
          <span>Total Credit Value:</span>
          <span id="sum-grand">₹ 0.00</span>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
          <a href="<?= !empty($linkedInvoice) ? url('/invoices/' . $linkedInvoice['id']) : url('/invoices') ?>" class="btn btn-secondary" style="flex: 1; text-align: center;">Cancel</a>
          <button type="submit" class="btn btn-primary" style="flex: 2;">Issue Credit Note</button>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tbody = document.getElementById('items-tbody');
  const btnAdd = document.getElementById('btn-add-item');

  function recalculate() {
    let totTaxable = 0;
    let totTax = 0;

    tbody.querySelectorAll('.item-row').forEach(row => {
      const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
      const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
      const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;

      const taxable = Math.round(qty * rate * 100) / 100;
      const tax = Math.round(taxable * taxRate) / 100;
      const lineTotal = taxable + tax;

      row.querySelector('.item-total').textContent = '₹ ' + lineTotal.toFixed(2);
      totTaxable += taxable;
      totTax += tax;
    });

    const rawTotal = totTaxable + totTax;
    const rounded = Math.round(rawTotal);
    const roundOff = Math.round((rounded - rawTotal) * 100) / 100;

    document.getElementById('sum-taxable').textContent = '₹ ' + totTaxable.toFixed(2);
    document.getElementById('sum-tax').textContent = '₹ ' + totTax.toFixed(2);
    document.getElementById('sum-round').textContent = (roundOff >= 0 ? '+' : '') + '₹ ' + roundOff.toFixed(2);
    document.getElementById('sum-grand').textContent = '₹ ' + rounded.toFixed(2);
  }

  tbody.addEventListener('input', recalculate);
  tbody.addEventListener('change', recalculate);

  tbody.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-del-row')) {
      if (tbody.querySelectorAll('.item-row').length > 1) {
        e.target.closest('.item-row').remove();
        recalculate();
      } else {
        alert('At least one line item is required.');
      }
    }
  });

  btnAdd.addEventListener('click', function() {
    const idx = tbody.querySelectorAll('.item-row').length;
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
      <td>
        <input type="text" name="items[${idx}][name]" class="form-control form-control-sm" required placeholder="Item name">
        <input type="text" name="items[${idx}][description]" class="form-control form-control-sm" style="margin-top: 4px; font-size: 11px;" placeholder="Optional description">
      </td>
      <td>
        <input type="text" name="items[${idx}][hsn_sac]" class="form-control form-control-sm" placeholder="HSN/SAC">
      </td>
      <td>
        <input type="number" step="any" min="0.001" name="items[${idx}][quantity]" value="1" class="form-control form-control-sm text-right item-qty" required>
      </td>
      <td>
        <input type="text" name="items[${idx}][unit]" value="PCS" class="form-control form-control-sm" style="width: 70px;">
      </td>
      <td>
        <input type="number" step="any" min="0" name="items[${idx}][unit_price]" value="0" class="form-control form-control-sm text-right item-rate" required>
      </td>
      <td>
        <select name="items[${idx}][gst_rate]" class="form-control form-control-sm text-right item-tax">
          <?php foreach ($gstRates as $r): ?>
            <option value="<?= $r ?>" <?= (float)$r === 18.0 ? 'selected' : '' ?>><?= $r ?>%</option>
          <?php endforeach; ?>
        </select>
      </td>
      <td class="text-right" style="font-weight: 600; vertical-align: middle;">
        <span class="item-total">₹ 0.00</span>
      </td>
      <td style="text-align: center; vertical-align: middle;">
        <button type="button" class="btn btn-danger btn-sm btn-del-row" style="padding: 2px 6px;">&times;</button>
      </td>
    `;
    tbody.appendChild(tr);
    recalculate();
  });

  recalculate();
});
</script>

