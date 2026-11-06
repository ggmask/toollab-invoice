<div class="page-header">
  <div>
    <h1 class="page-title">Credit Notes</h1>
    <div class="page-description">Credit notes issued under Section 34 of the CGST Act for sales returns, rate revisions, or deficiency in service.</div>
  </div>
  <div class="header-actions">
    <a href="<?= url('/credit-notes/create') ?>" class="btn btn-primary">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Issue Credit Note
    </a>
  </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
  <form method="GET" action="<?= url('/credit-notes') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: flex-end; margin: 0;">
    <div style="flex: 1; min-width: 200px;">
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Search Credit Note / Customer / Inv #</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="CN #, customer, original invoice..." class="form-control form-control-sm">
    </div>

    <div style="width: 140px;">
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Status</label>
      <select name="status" class="form-control form-control-sm">
        <option value="">All Statuses</option>
        <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued</option>
        <option value="applied" <?= $status === 'applied' ? 'selected' : '' ?>>Applied</option>
        <option value="void" <?= $status === 'void' ? 'selected' : '' ?>>Void</option>
      </select>
    </div>

    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">From</label>
      <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-control form-control-sm">
    </div>

    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">To</label>
      <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-control form-control-sm">
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($search || $status || $startDate || $endDate): ?>
      <a href="<?= url('/credit-notes') ?>" class="btn btn-secondary btn-sm">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Credit Note #</th>
          <th>Date</th>
          <th>Original Invoice</th>
          <th>Customer</th>
          <th>Reason</th>
          <th class="text-right">Credit Value (₹)</th>
          <th class="text-right">Applied (₹)</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($creditNotes)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 40px; color: var(--slate-500);">
              No credit notes found. <a href="<?= url('/credit-notes/create') ?>">Issue a new credit note</a>.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($creditNotes as $cn): ?>
            <tr>
              <td style="font-weight: 600;">
                <a href="<?= url('/credit-notes/' . $cn['id']) ?>" style="color: var(--primary); text-decoration: none;">
                  <?= e($cn['credit_note_number']) ?>
                </a>
              </td>
              <td style="font-size: 12px; color: var(--slate-600);"><?= format_date($cn['credit_note_date']) ?></td>
              <td>
                <?php if (!empty($cn['invoice_id'])): ?>
                  <a href="<?= url('/invoices/' . $cn['invoice_id']) ?>" style="color: var(--slate-700); text-decoration: underline; font-weight: 600;">
                    <?= e($cn['original_invoice_number']) ?>
                  </a>
                <?php else: ?>
                  <span style="color: var(--slate-700); font-weight: 500;"><?= e($cn['original_invoice_number']) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--slate-800);"><?= e($cn['customer_name']) ?></div>
                <div style="font-size: 11px; color: var(--slate-500);"><?= e($cn['customer_gstin'] ?: 'Unregistered') ?></div>
              </td>
              <td>
                <span class="badge" style="background: var(--slate-100); color: var(--slate-700); font-size: 11px;">
                  <?= e(ucwords(str_replace('_', ' ', $cn['reason']))) ?>
                </span>
              </td>
              <td class="text-right" style="font-weight: 700; color: #b91c1c;">
                <?= format_currency($cn['total_amount']) ?>
              </td>
              <td class="text-right" style="font-weight: 600; color: #047857;">
                <?= format_currency($cn['applied_amount']) ?>
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
                <span class="badge <?= $badgeClass ?>"><?= ucfirst($cn['status']) ?></span>
              </td>
              <td class="text-right" style="white-space: nowrap;">
                <a href="<?= url('/credit-notes/' . $cn['id']) ?>" class="btn btn-secondary btn-sm" title="View">View</a>
                <a href="<?= url('/credit-notes/' . $cn['id'] . '/download') ?>" class="btn btn-secondary btn-sm" title="PDF">PDF</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div style="padding: 16px; border-top: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 12px; color: var(--slate-500);">
        Showing Page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalRecords ?>)
      </div>
      <div style="display: flex; gap: 6px;">
        <?php if ($page > 1): ?>
          <a href="<?= url('/credit-notes?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>" class="btn btn-secondary btn-sm">&larr; Prev</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
          <a href="<?= url('/credit-notes?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>" class="btn btn-secondary btn-sm">Next &rarr;</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

