<div class="page-header">
  <div>
    <h1 class="page-title">Create Sales Invoice</h1>
    <div class="page-description">Fill in line items, verify tax calculation, and save as draft or issue immediately.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/invoices') ?>" class="btn btn-secondary btn-sm">
      &larr; Back to Invoices
    </a>
  </div>
</div>

<form action="<?= url('/invoices') ?>" method="POST" id="invoice-form">
  <?= csrf_field() ?>
  <input type="hidden" id="supplier_state_code" value="<?= e($business['state_code'] ?? '27') ?>">

  <!-- Document & Customer Details Grid -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. Document & Customer Information</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <!-- Customer Select -->
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="customer_id">Select Customer <span style="color: var(--danger);">*</span></label>
          <select name="customer_id" id="customer_id" class="form-control" required onchange="onCustomerChange(this)">
            <option value="">-- Choose Customer --</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>" 
                      data-gstin="<?= e($c['gstin']) ?>"
                      data-state="<?= e($c['billing_state']) ?>"
                      data-statecode="<?= e($c['billing_state_code']) ?>"
                      data-address="<?= e($c['billing_address']) ?>"
                      <?= $selectedCustomerId == $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?> <?= !empty($c['gstin']) ? '(' . e($c['gstin']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div style="font-size: 11px; margin-top: 4px;">
            <a href="<?= url('/customers/create') ?>" target="_blank">+ Add New Customer</a>
          </div>
        </div>

        <!-- Invoice Date -->
        <div class="form-group">
          <label class="form-label" for="issue_date">Invoice Date <span style="color: var(--danger);">*</span></label>
          <input type="date" name="issue_date" id="issue_date" class="form-control" value="<?= $today ?>" required>
        </div>

        <!-- Due Date -->
        <div class="form-group">
          <label class="form-label" for="due_date">Due Date <span style="color: var(--danger);">*</span></label>
          <input type="date" name="due_date" id="due_date" class="form-control" value="<?= $defaultDueDate ?>" required>
        </div>

        <!-- Place of Supply -->
        <div class="form-group">
          <label class="form-label" for="pos_state_code">Place of Supply (POS) <span style="color: var(--danger);">*</span></label>
          <select name="place_of_supply_state_code" id="pos_state_code" class="form-control" required>
            <?php foreach ($states as $code => $stName): ?>
              <option value="<?= $code ?>" <?= ($business['state_code'] ?? '27') === $code ? 'selected' : '' ?>>
                <?= $code ?> - <?= e($stName) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- PO Number -->
        <div class="form-group">
          <label class="form-label" for="po_number">Purchase Order (PO) #</label>
          <input type="text" name="po_number" id="po_number" class="form-control" placeholder="e.g. PO-2026-99">
        </div>

        <!-- Payment Terms -->
        <div class="form-group">
          <label class="form-label" for="payment_terms">Payment Terms</label>
          <select name="payment_terms" id="payment_terms" class="form-control">
            <?php foreach ($paymentTerms as $termKey => $termLabel): ?>
              <option value="<?= e($termKey) ?>" <?= ($business['default_payment_terms'] ?? 'Net 15') === $termKey ? 'selected' : '' ?>>
                <?= e($termLabel) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Reverse Charge -->
        <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 28px;">
          <input type="checkbox" name="is_reverse_charge" id="is_reverse_charge" value="1">
          <label for="is_reverse_charge" style="font-size: 13px; font-weight: 500; margin: 0; cursor: pointer;">
            Subject to Reverse Charge (RCM)
          </label>
        </div>
      </div>
    </div>
  </div>

  <!-- Line Items Table -->
  <datalist id="products-datalist">
    <?php foreach ($products as $p): ?>
      <option value="<?= e($p['name']) ?>"
              data-id="<?= (int)$p['id'] ?>"
              data-description="<?= e($p['description'] ?? '') ?>"
              data-hsn="<?= e($p['hsn_sac'] ?? '') ?>"
              data-unit="<?= e($p['unit'] ?? 'PCS') ?>"
              data-price="<?= e($p['selling_price'] ?? '0.00') ?>"
              data-rate="<?= (float)($p['gst_rate'] ?? 18) ?>"
              data-taxcat="<?= e($p['tax_category'] ?? 'taxable') ?>">
        <?= e($p['sku'] ? "[{$p['sku']}] " : '') ?><?= e($p['name']) ?> (₹<?= number_format((float)$p['selling_price'], 2) ?>)
      </option>
    <?php endforeach; ?>
  </datalist>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">2. Line Items (Products & Services)</h2>
      <div style="font-size: 12px; color: var(--slate-500);">
        Select from product catalog or type custom items. Intra-state (CGST + SGST) or Inter-state (IGST) calculated automatically.
      </div>
    </div>
    <div class="card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="line-items-table">
          <thead>
            <tr>
              <th style="width: 30%;">Item / Description</th>
              <th style="width: 12%;">HSN/SAC</th>
              <th style="width: 10%;" class="text-right">Qty</th>
              <th style="width: 10%;">Unit</th>
              <th style="width: 12%;" class="text-right">Rate (₹)</th>
              <th style="width: 12%;">Discount</th>
              <th style="width: 10%;">GST Rate</th>
              <th style="width: 12%;" class="text-right">Amount (₹)</th>
              <th style="width: 4%;"></th>
            </tr>
          </thead>
          <tbody id="line-items-body">
            <!-- Default First Row -->
            <tr class="line-item-row">
              <td style="width: 30%;">
                <input type="hidden" name="items[0][product_id]" class="item-product-id" value="">
                <input type="text" name="items[0][name]" list="products-datalist" class="form-control form-control-sm item-name" placeholder="Item Name / Type to search..." required autocomplete="off">
                <textarea name="items[0][description]" class="form-control form-control-sm item-desc" rows="1" placeholder="Optional details..." style="margin-top: 4px;"></textarea>
              </td>
              <td style="width: 12%;">
                <input type="text" name="items[0][hsn_sac]" class="form-control form-control-sm" placeholder="HSN/SAC">
              </td>
              <td style="width: 10%;">
                <input type="number" step="0.001" name="items[0][quantity]" class="form-control form-control-sm item-qty text-right" value="1" min="0.001" required>
              </td>
              <td style="width: 10%;">
                <select name="items[0][unit]" class="form-control form-control-sm">
                  <option value="PCS">PCS</option>
                  <option value="NOS">NOS</option>
                  <option value="BOX">BOX</option>
                  <option value="KGS">KGS</option>
                  <option value="MTR">MTR</option>
                  <option value="SET">SET</option>
                  <option value="HRS">HRS</option>
                  <option value="MTH">MTH</option>
                </select>
              </td>
              <td style="width: 12%;">
                <input type="number" step="0.01" name="items[0][unit_price]" class="form-control form-control-sm item-price text-right" value="0.00" min="0" required>
              </td>
              <td style="width: 12%;">
                <div style="display: flex; gap: 2px;">
                  <input type="number" step="0.01" name="items[0][discount_value]" class="form-control form-control-sm item-disc-val text-right" value="0" min="0">
                  <select name="items[0][discount_type]" class="form-control form-control-sm item-disc-type" style="width: 50px; padding: 4px;">
                    <option value="percentage">%</option>
                    <option value="flat">₹</option>
                  </select>
                </div>
              </td>
              <td style="width: 10%;">
                <select name="items[0][gst_rate]" class="form-control form-control-sm item-gst-rate">
                  <option value="18" selected>18%</option>
                  <option value="12">12%</option>
                  <option value="5">5%</option>
                  <option value="28">28%</option>
                  <option value="0">0%</option>
                </select>
              </td>
              <td class="text-right" style="width: 12%;">
                <span class="line-total-display" style="font-weight: 600; font-size: 13px;">₹ 0.00</span>
              </td>
              <td style="width: 4%; text-align: center;">
                <button type="button" class="btn btn-secondary btn-sm remove-row-btn" style="padding: 4px 6px; color: var(--danger);">&times;</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="padding: 12px 16px; border-top: 1px solid var(--slate-200); background: var(--white); display: flex; justify-content: space-between; align-items: center;">
        <button type="button" id="add-line-item-btn" class="btn btn-secondary btn-sm">
          + Add Line Item
        </button>

        <!-- Quick Catalog Select Helper -->
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 12px; color: var(--slate-500);">Insert from Catalog:</span>
          <select id="catalog-picker" class="form-control form-control-sm" style="width: 220px;" onchange="insertCatalogItem(this)">
            <option value="">-- Choose Catalog Item --</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= $p['id'] ?>" 
                      data-name="<?= e($p['name']) ?>"
                      data-price="<?= $p['selling_price'] ?>"
                      data-hsn="<?= e($p['hsn_sac']) ?>"
                      data-unit="<?= e($p['unit']) ?>"
                      data-gst="<?= $p['gst_rate'] ?>"
                      data-desc="<?= e($p['description']) ?>">
                <?= e($p['name']) ?> (₹<?= number_format($p['selling_price'], 2) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Discounts, Additional Charges, Notes & Live Totals -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    <!-- Notes, Terms & Additional Fields -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">3. Notes, Terms & Charges</h2>
      </div>
      <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <!-- Invoice Level Discount -->
          <div>
            <label class="form-label" for="invoice_discount_value">Invoice Discount (Pre-Tax)</label>
            <div style="display: flex; gap: 4px;">
              <input type="number" step="0.01" name="invoice_discount_value" id="invoice_discount_value" class="form-control" value="0">
              <select name="invoice_discount_type" id="invoice_discount_type" class="form-control" style="width: 70px;">
                <option value="flat">₹</option>
                <option value="percentage">%</option>
              </select>
            </div>
          </div>

          <!-- Additional Charges -->
          <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 8px;">
            <div>
              <label class="form-label" for="additional_charges">Shipping / Extra Charges (₹)</label>
              <input type="number" step="0.01" name="additional_charges" id="additional_charges" class="form-control" value="0">
            </div>
            <div>
              <label class="form-label" for="additional_charges_gst_rate">Freight GST</label>
              <select name="additional_charges_gst_rate" id="additional_charges_gst_rate" class="form-control">
                <option value="0">0% (Nil)</option>
                <option value="5">5%</option>
                <option value="12">12%</option>
                <option value="18" selected>18%</option>
                <option value="28">28%</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="notes">Customer Notes</label>
          <textarea name="notes" id="notes" class="form-control" rows="2"><?= e($business['default_notes']) ?></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" for="terms">Terms & Conditions</label>
          <textarea name="terms" id="terms" class="form-control" rows="3"><?= e($business['default_terms']) ?></textarea>
        </div>
      </div>
    </div>

    <!-- Live Totals Panel -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">4. Financial Summary Preview</h2>
      </div>
      <div class="card-body" style="padding: 16px 20px;">
        <div class="totals-row">
          <span>Gross Line Value:</span>
          <span id="sum_subtotal_gross" style="font-weight: 500;">₹ 0.00</span>
        </div>
        <div class="totals-row">
          <span>Line Discounts:</span>
          <span id="sum_line_discounts" style="color: var(--danger);">- ₹ 0.00</span>
        </div>
        <div class="totals-row">
          <span>Invoice Discount:</span>
          <span id="sum_inv_discount" style="color: var(--danger);">- ₹ 0.00</span>
        </div>
        <div class="totals-row" id="row_add_charges">
          <span>Shipping / Extra Charges:</span>
          <span id="sum_add_charges" style="color: var(--slate-700);">+ ₹ 0.00</span>
        </div>
        <div class="totals-row" style="font-weight: 600; border-top: 1px dashed var(--slate-200); padding-top: 8px;">
          <span>Taxable Turnover:</span>
          <span id="sum_taxable_value">₹ 0.00</span>
        </div>
        
        <!-- Taxes breakdown -->
        <div class="totals-row" id="row_cgst">
          <span id="label_cgst">CGST:</span>
          <span id="sum_cgst">₹ 0.00</span>
        </div>
        <div class="totals-row" id="row_sgst">
          <span id="label_sgst">SGST / UTGST:</span>
          <span id="sum_sgst">₹ 0.00</span>
        </div>
        <div class="totals-row" id="row_igst" style="display: none;">
          <span id="label_igst">IGST (Inter-State):</span>
          <span id="sum_igst">₹ 0.00</span>
        </div>

        <div class="totals-row">
          <span>Total GST:</span>
          <span id="sum_tax_total" style="font-weight: 600;">₹ 0.00</span>
        </div>

        <div class="totals-row">
          <span>Rounding Adjustment:</span>
          <span id="sum_round_off" style="color: var(--slate-500);">₹ 0.00</span>
        </div>

        <div class="totals-row final">
          <span>Total Invoice Amount:</span>
          <span id="sum_final_total" style="color: var(--primary);">₹ 0.00</span>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px;">
          <button type="submit" name="submit_action" value="draft" class="btn btn-secondary btn-lg" style="flex: 1;">
            Save as Draft
          </button>
          <button type="submit" name="submit_action" value="issue" class="btn btn-primary btn-lg" style="flex: 1;">
            Issue Invoice &rarr;
          </button>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
function onCustomerChange(select) {
  const opt = select.options[select.selectedIndex];
  if (opt && opt.dataset.statecode) {
    const pos = document.getElementById('pos_state_code');
    if (pos) {
      pos.value = opt.dataset.statecode;
      pos.dispatchEvent(new Event('change'));
    }
  }
}

function insertCatalogItem(select) {
  const opt = select.options[select.selectedIndex];
  if (!opt || !opt.value) return;

  const container = document.getElementById('line-items-body');
  const rows = container.querySelectorAll('.line-item-row');
  let targetRow = null;

  // Check if first row is untouched
  if (rows.length === 1 && rows[0].querySelector('.item-name').value.trim() === '') {
    targetRow = rows[0];
  } else {
    document.getElementById('add-line-item-btn').click();
    const updatedRows = container.querySelectorAll('.line-item-row');
    targetRow = updatedRows[updatedRows.length - 1];
  }

  targetRow.querySelector('.item-name').value = opt.dataset.name;
  targetRow.querySelector('textarea').value = opt.dataset.desc;
  targetRow.querySelector('.item-price').value = opt.dataset.price;
  targetRow.querySelector('input[placeholder="HSN/SAC"]').value = opt.dataset.hsn;
  targetRow.querySelector('.item-gst-rate').value = opt.dataset.gst;
  targetRow.querySelector('select[name*="[unit]"]').value = opt.dataset.unit;

  select.value = '';
  container.dispatchEvent(new Event('input'));
}
</script>
