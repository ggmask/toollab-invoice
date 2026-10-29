<div class="page-header">
  <div>
    <h1 class="page-title">Configure Recurring Profile</h1>
    <div class="page-description">Set up recurring invoices that run automatically or on-demand.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/recurring') ?>" class="btn btn-secondary btn-sm">
      &larr; Back to Recurring
    </a>
  </div>
</div>

<form action="<?= url('/recurring') ?>" method="POST" id="invoice-form">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. Schedule & Customer Profile</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="profile_name">Profile Name <span style="color: var(--danger);">*</span></label>
          <input type="text" name="profile_name" id="profile_name" class="form-control" placeholder="e.g. Acme Cloud Retainer / Monthly SLA" required>
        </div>

        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="customer_id">Select Customer <span style="color: var(--danger);">*</span></label>
          <select name="customer_id" id="customer_id" class="form-control" required>
            <option value="">-- Choose Customer --</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= e($c['name']) ?> <?= !empty($c['gstin']) ? '(' . e($c['gstin']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="frequency">Billing Frequency <span style="color: var(--danger);">*</span></label>
          <select name="frequency" id="frequency" class="form-control" required>
            <?php foreach ($frequencies as $freqKey => $freqLabel): ?>
              <option value="<?= $freqKey ?>" <?= $freqKey === 'monthly' ? 'selected' : '' ?>>
                <?= e($freqLabel) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="start_date">First Issue Date <span style="color: var(--danger);">*</span></label>
          <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $today ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="end_date">End Date (Optional)</label>
          <input type="date" name="end_date" id="end_date" class="form-control" placeholder="Leave empty for ongoing">
        </div>

        <div class="form-group">
          <label class="form-label" for="due_days">Due Days After Issue</label>
          <input type="number" name="due_days" id="due_days" class="form-control" value="15" min="0">
        </div>

        <div class="form-group">
          <label class="form-label" for="generation_mode">Generation Mode</label>
          <select name="generation_mode" id="generation_mode" class="form-control">
            <option value="manual">Manual Review (Generate on click)</option>
            <option value="auto">Automatic (CLI / Cron runner)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="po_number">PO # / Contract Ref</label>
          <input type="text" name="po_number" id="po_number" class="form-control" placeholder="e.g. CTR-2026-99">
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">2. Recurring Line Items</h2>
    </div>
    <div class="card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="line-items-table">
          <thead>
            <tr>
              <th style="width: 35%;">Item / Description</th>
              <th style="width: 15%;">HSN/SAC</th>
              <th style="width: 10%;" class="text-right">Qty</th>
              <th style="width: 10%;">Unit</th>
              <th style="width: 15%;" class="text-right">Rate (₹)</th>
              <th style="width: 10%;">GST Rate</th>
              <th style="width: 5%;"></th>
            </tr>
          </thead>
          <tbody id="line-items-body">
            <tr class="line-item-row">
              <td>
                <input type="text" name="items[0][name]" class="form-control form-control-sm item-name" placeholder="Service / Subscription Name" required>
                <textarea name="items[0][description]" class="form-control form-control-sm" rows="1" placeholder="Description..." style="margin-top: 4px;"></textarea>
              </td>
              <td>
                <input type="text" name="items[0][hsn_sac]" class="form-control form-control-sm" placeholder="HSN/SAC">
              </td>
              <td>
                <input type="number" step="0.001" name="items[0][quantity]" class="form-control form-control-sm item-qty text-right" value="1" min="0.001" required>
              </td>
              <td>
                <select name="items[0][unit]" class="form-control form-control-sm">
                  <option value="MTH">MTH</option>
                  <option value="HRS">HRS</option>
                  <option value="PCS">PCS</option>
                  <option value="SET">SET</option>
                </select>
              </td>
              <td>
                <input type="number" step="0.01" name="items[0][unit_price]" class="form-control form-control-sm item-price text-right" value="0.00" min="0" required>
              </td>
              <td>
                <select name="items[0][gst_rate]" class="form-control form-control-sm item-gst-rate">
                  <option value="18" selected>18%</option>
                  <option value="12">12%</option>
                  <option value="5">5%</option>
                  <option value="28">28%</option>
                  <option value="0">0%</option>
                </select>
              </td>
              <td style="text-align: center;">
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

  <div class="card">
    <div class="card-body">
      <div class="form-group">
        <label class="form-label" for="notes">Notes to appear on generated invoices</label>
        <textarea name="notes" id="notes" class="form-control" rows="2"><?= e($business['default_notes']) ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label" for="terms">Terms & Conditions</label>
        <textarea name="terms" id="terms" class="form-control" rows="2"><?= e($business['default_terms']) ?></textarea>
      </div>

      <div style="margin-top: 24px;">
        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
          Create Recurring Schedule &rarr;
        </button>
      </div>
    </div>
  </div>
</form>

<script>
function insertCatalogItem(select) {
  const opt = select.options[select.selectedIndex];
  if (!opt || !opt.value) return;

  const container = document.getElementById('line-items-body');
  const rows = container.querySelectorAll('.line-item-row');
  let targetRow = null;

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
}
</script>
