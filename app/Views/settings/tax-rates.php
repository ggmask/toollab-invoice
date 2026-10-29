<div class="page-header">
  <div>
    <h1 class="page-title">GST Tax Rates & Slabs</h1>
    <div class="page-description">Configure standard Indian GST rates (0%, 5%, 12%, 18%, 28%) and custom tax exemptions.</div>
  </div>

  <div class="header-actions">
    <button type="button" onclick="document.getElementById('addRateModal').classList.remove('hidden')" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Add Tax Rate
    </button>
  </div>
</div>

<!-- Settings Navigation Tabs -->
<div class="nav-tabs">
  <a href="<?= url('/settings/business') ?>" class="nav-tab-item">Business & Banking</a>
  <a href="<?= url('/settings/gst') ?>" class="nav-tab-item">GST & Tax Config</a>
  <a href="<?= url('/settings/invoice') ?>" class="nav-tab-item">Document Numbering & Terms</a>
  <a href="<?= url('/settings/tax-rates') ?>" class="nav-tab-item active">Tax Slabs</a>
  <a href="<?= url('/settings/account') ?>" class="nav-tab-item">User Security</a>
</div>

<!-- Rates Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Tax Name</th>
          <th class="text-center">Rate (%)</th>
          <th class="text-center">Intra-State Split</th>
          <th class="text-center">Inter-State</th>
          <th>Classification</th>
          <th class="text-center">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($taxRates)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 36px; color: var(--slate-500);">
              No tax rates defined yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($taxRates as $tr): ?>
            <?php 
              $rate = (float)$tr['rate'];
              $half = $rate / 2;
            ?>
            <tr>
              <td style="font-weight: 700; color: var(--slate-900);">
                <?= e($tr['name']) ?>
              </td>
              <td class="text-center" style="font-weight: 700; color: var(--primary);">
                <?= $rate ?>%
              </td>
              <td class="text-center" style="font-size: 12px; color: var(--slate-600);">
                <?php if ($rate > 0): ?>
                  <span style="background: var(--slate-100); padding: 2px 6px; border-radius: 4px; font-family: monospace;">CGST <?= $half ?>% + SGST <?= $half ?>%</span>
                <?php else: ?>
                  <span style="color: var(--slate-400);">0%</span>
                <?php endif; ?>
              </td>
              <td class="text-center" style="font-size: 12px; color: var(--slate-600);">
                <span style="background: var(--slate-100); padding: 2px 6px; border-radius: 4px; font-family: monospace;">IGST <?= $rate ?>%</span>
              </td>
              <td style="font-size: 12px; text-transform: uppercase; font-weight: 600; color: var(--slate-500);">
                <?= e(str_replace('_', ' ', $tr['tax_category'])) ?>
              </td>
              <td class="text-center">
                <?php if ($tr['is_active']): ?>
                  <span class="badge badge-paid">Active</span>
                <?php else: ?>
                  <span class="badge badge-void">Disabled</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Tax Rate Modal -->
<div id="addRateModal" class="hidden modal-backdrop">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title">Add GST Tax Rate</h3>
      <button type="button" onclick="document.getElementById('addRateModal').classList.add('hidden')" class="modal-close">&times;</button>
    </div>

    <form method="POST" action="<?= url('/settings/tax-rates') ?>">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="name">Rate Display Name <span style="color: var(--danger);">*</span></label>
          <input type="text" name="name" id="name" required class="form-control" placeholder="e.g. GST 18%, Exempt, Nil Rated">
        </div>

        <div class="form-group">
          <label class="form-label" for="rate">Tax Percentage (%) <span style="color: var(--danger);">*</span></label>
          <input type="number" step="0.01" min="0" max="100" name="rate" id="rate" required class="form-control" style="font-weight: 700; font-size: 16px;" placeholder="18.00">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" for="tax_category">GST Category</label>
          <select name="tax_category" id="tax_category" class="form-control">
            <option value="taxable">Taxable (Standard)</option>
            <option value="nil_rated">Nil Rated</option>
            <option value="exempt">Exempt</option>
            <option value="non_gst">Non-GST</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="document.getElementById('addRateModal').classList.add('hidden')" class="btn btn-secondary">
          Cancel
        </button>
        <button type="submit" class="btn btn-primary">
          Create Rate
        </button>
      </div>
    </form>
  </div>
</div>
