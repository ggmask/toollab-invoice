<div class="page-header">
  <div>
    <h1 class="page-title">Edit Draft Invoice <?= e($invoice['invoice_number']) ?></h1>
    <div class="page-description">Modify draft details and recalculate totals before formal issuance.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/invoices/' . $invoice['id']) ?>" class="btn btn-secondary btn-sm">
      &larr; Cancel
    </a>
  </div>
</div>

<form action="<?= url('/invoices/' . $invoice['id']) ?>" method="POST" id="invoice-form">
  <?= csrf_field() ?>
  <input type="hidden" id="supplier_state_code" value="<?= e($business['state_code'] ?? '27') ?>">

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. Document & Customer Information</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="customer_id">Customer <span style="color: var(--danger);">*</span></label>
          <select name="customer_id" id="customer_id" class="form-control" required>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $invoice['customer_id'] == $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?> <?= !empty($c['gstin']) ? '(' . e($c['gstin']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="issue_date">Invoice Date <span style="color: var(--danger);">*</span></label>
          <input type="date" name="issue_date" id="issue_date" class="form-control" value="<?= e($invoice['issue_date']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="due_date">Due Date <span style="color: var(--danger);">*</span></label>
          <input type="date" name="due_date" id="due_date" class="form-control" value="<?= e($invoice['due_date']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="pos_state_code">Place of Supply (POS) <span style="color: var(--danger);">*</span></label>
          <select name="place_of_supply_state_code" id="pos_state_code" class="form-control" required>
            <?php foreach ($states as $code => $stName): ?>
              <option value="<?= $code ?>" <?= ($invoice['place_of_supply_state_code'] ?? '') === $code ? 'selected' : '' ?>>
                <?= $code ?> - <?= e($stName) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="po_number">Purchase Order (PO) #</label>
          <input type="text" name="po_number" id="po_number" class="form-control" value="<?= e($invoice['po_number']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="payment_terms">Payment Terms</label>
          <select name="payment_terms" id="payment_terms" class="form-control">
            <?php foreach ($paymentTerms as $termKey => $termLabel): ?>
              <option value="<?= e($termKey) ?>" <?= ($invoice['payment_terms'] ?? '') === $termKey ? 'selected' : '' ?>>
                <?= e($termLabel) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

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
      <h2 class="card-title">2. Line Items</h2>
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
            <?php foreach ($items as $idx => $it): ?>
              <tr class="line-item-row">
                <td style="width: 30%;">
                  <input type="hidden" name="items[<?= $idx ?>][product_id]" class="item-product-id" value="<?= e($it['product_id'] ?? '') ?>">
                  <input type="text" name="items[<?= $idx ?>][name]" list="products-datalist" class="form-control form-control-sm item-name" value="<?= e($it['name']) ?>" required autocomplete="off">
                  <textarea name="items[<?= $idx ?>][description]" class="form-control form-control-sm item-desc" rows="1" style="margin-top: 4px;"><?= e($it['description']) ?></textarea>
                </td>
                <td style="width: 12%;">
                  <input type="text" name="items[<?= $idx ?>][hsn_sac]" class="form-control form-control-sm" value="<?= e($it['hsn_sac']) ?>">
                </td>
                <td style="width: 10%;">
                  <input type="number" step="0.001" name="items[<?= $idx ?>][quantity]" class="form-control form-control-sm item-qty text-right" value="<?= (float)$it['quantity'] ?>" min="0.001" required>
                </td>
                <td style="width: 10%;">
                  <select name="items[<?= $idx ?>][unit]" class="form-control form-control-sm">
                    <?php foreach ($units as $uKey => $uName): ?>
                      <option value="<?= $uKey ?>" <?= $it['unit'] === $uKey ? 'selected' : '' ?>><?= $uKey ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="width: 12%;">
                  <input type="number" step="0.01" name="items[<?= $idx ?>][unit_price]" class="form-control form-control-sm item-price text-right" value="<?= (float)$it['unit_price'] ?>" min="0" required>
                </td>
                <td style="width: 12%;">
                  <div style="display: flex; gap: 2px;">
                    <input type="number" step="0.01" name="items[<?= $idx ?>][discount_value]" class="form-control form-control-sm item-disc-val text-right" value="<?= (float)$it['discount_value'] ?>" min="0">
                    <select name="items[<?= $idx ?>][discount_type]" class="form-control form-control-sm item-disc-type" style="width: 50px; padding: 4px;">
                      <option value="percentage" <?= $it['discount_type'] === 'percentage' ? 'selected' : '' ?>>%</option>
                      <option value="flat" <?= $it['discount_type'] === 'flat' ? 'selected' : '' ?>>₹</option>
                    </select>
                  </div>
                </td>
                <td style="width: 10%;">
                  <select name="items[<?= $idx ?>][gst_rate]" class="form-control form-control-sm item-gst-rate">
                    <option value="18" <?= (int)$it['gst_rate'] === 18 ? 'selected' : '' ?>>18%</option>
                    <option value="12" <?= (int)$it['gst_rate'] === 12 ? 'selected' : '' ?>>12%</option>
                    <option value="5" <?= (int)$it['gst_rate'] === 5 ? 'selected' : '' ?>>5%</option>
                    <option value="28" <?= (int)$it['gst_rate'] === 28 ? 'selected' : '' ?>>28%</option>
                    <option value="0" <?= (int)$it['gst_rate'] === 0 ? 'selected' : '' ?>>0%</option>
                  </select>
                </td>
                <td class="text-right" style="width: 12%;">
                  <span class="line-total-display" style="font-weight: 600; font-size: 13px;">
                    <?= format_currency($it['line_total']) ?>
                  </span>
                </td>
                <td style="width: 4%; text-align: center;">
                  <button type="button" class="btn btn-secondary btn-sm remove-row-btn" style="padding: 4px 6px; color: var(--danger);">&times;</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="padding: 12px 16px; border-top: 1px solid var(--slate-200); background: var(--white);">
        <button type="button" id="add-line-item-btn" class="btn btn-secondary btn-sm">
          + Add Line Item
        </button>
      </div>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">3. Notes, Terms & Charges</h2>
      </div>
      <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div>
            <label class="form-label" for="invoice_discount_value">Invoice Discount (Pre-Tax)</label>
            <div style="display: flex; gap: 4px;">
              <input type="number" step="0.01" name="invoice_discount_value" id="invoice_discount_value" class="form-control" value="<?= (float)$invoice['invoice_discount_value'] ?>">
              <select name="invoice_discount_type" id="invoice_discount_type" class="form-control" style="width: 70px;">
                <option value="flat" <?= $invoice['invoice_discount_type'] === 'flat' ? 'selected' : '' ?>>₹</option>
                <option value="percentage" <?= $invoice['invoice_discount_type'] === 'percentage' ? 'selected' : '' ?>>%</option>
              </select>
            </div>
          </div>
          <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 8px;">
            <div>
              <label class="form-label" for="additional_charges">Shipping / Extra Charges (₹)</label>
              <input type="number" step="0.01" name="additional_charges" id="additional_charges" class="form-control" value="<?= (float)$invoice['additional_charges'] ?>">
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
          <label class="form-label" for="notes">Notes</label>
          <textarea name="notes" id="notes" class="form-control" rows="2"><?= e($invoice['notes']) ?></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" for="terms">Terms & Conditions</label>
          <textarea name="terms" id="terms" class="form-control" rows="3"><?= e($invoice['terms']) ?></textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2 class="card-title">4. Summary Preview</h2>
      </div>
      <div class="card-body" style="padding: 16px 20px;">
        <div class="totals-row">
          <span>Gross Line Value:</span>
          <span id="sum_subtotal_gross" style="font-weight: 500;">₹ 0.00</span>
        </div>
        <div class="totals-row">
          <span>Discounts:</span>
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
        <div class="totals-row" id="row_cgst">
          <span id="label_cgst">CGST:</span>
          <span id="sum_cgst">₹ 0.00</span>
        </div>
        <div class="totals-row" id="row_sgst">
          <span id="label_sgst">SGST / UTGST:</span>
          <span id="sum_sgst">₹ 0.00</span>
        </div>
        <div class="totals-row" id="row_igst" style="display: none;">
          <span id="label_igst">IGST:</span>
          <span id="sum_igst">₹ 0.00</span>
        </div>
        <div class="totals-row">
          <span>Total Tax:</span>
          <span id="sum_tax_total" style="font-weight: 600;">₹ 0.00</span>
        </div>
        <div class="totals-row" style="align-items: center; justify-content: space-between;">
          <div>
            <div style="font-weight: 500;">Rounding Adjustment / Discount:</div>
            <div style="font-size: 11px; color: var(--slate-400);">Manual input (₹) or click Auto</div>
          </div>
          <div style="display: flex; align-items: center; gap: 6px;">
            <input type="number" step="0.01" name="rounding_adjustment" id="rounding_adjustment" class="form-control form-control-sm text-right" style="width: 100px; font-weight: 600; padding: 4px 8px; height: 30px;" value="<?= e(number_format((float)$invoice['rounding_adjustment'], 2, '.', '')) ?>" data-manual="<?= abs((float)$invoice['rounding_adjustment']) > 0 ? 'true' : 'false' ?>">
            <button type="button" id="btn_auto_round" class="btn <?= abs((float)$invoice['rounding_adjustment']) > 0 ? 'btn-secondary' : 'btn-primary' ?> btn-sm" style="padding: 2px 8px; font-size: 11px; height: 30px;" title="Reset to auto round off">Auto</button>
          </div>
        </div>
        <div class="totals-row final">
          <span>Total Amount:</span>
          <span id="sum_final_total" style="color: var(--primary);">₹ 0.00</span>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px;">
          <button type="submit" name="submit_action" value="draft" class="btn btn-secondary btn-lg" style="flex: 1;">
            Update Draft
          </button>
          <button type="submit" name="submit_action" value="issue" class="btn btn-primary btn-lg" style="flex: 1;">
            Issue Invoice &rarr;
          </button>
        </div>
      </div>
    </div>
  </div>
</form>
