<div class="page-header">
  <div>
    <h1 class="page-title">Estimates & Quotations</h1>
    <div class="page-description">Prepare, send, and convert quotations into sales invoices.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/estimates/create') ?>" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Create Estimate
    </a>
  </div>
</div>

<div class="filter-bar">
  <form method="GET" action="<?= url('/estimates') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: center; margin: 0;">
    <div style="flex: 1; min-width: 200px;">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by estimate # or customer..." value="<?= e($search) ?>">
    </div>

    <div style="min-width: 140px;">
      <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        <option value="converted" <?= $status === 'converted' ? 'selected' : '' ?>>Converted to Invoice</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($search || $status): ?>
      <a href="<?= url('/estimates') ?>" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Estimate #</th>
          <th>Customer</th>
          <th>Estimate Date</th>
          <th>Valid Until</th>
          <th class="text-right">Total Amount</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($estimates)): ?>
          <tr>
            <td colspan="7" class="empty-state">
              No estimates or quotations found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($estimates as $est): ?>
            <tr>
              <td>
                <a href="<?= url('/estimates/' . $est['id']) ?>" style="font-weight: 600;">
                  <?= e($est['estimate_number']) ?>
                </a>
              </td>
              <td>
                <div style="font-weight: 500; color: var(--slate-900);"><?= e($est['customer_name']) ?></div>
              </td>
              <td><?= format_date($est['estimate_date']) ?></td>
              <td><?= format_date($est['valid_until']) ?></td>
              <td class="text-right" style="font-weight: 600;"><?= format_currency($est['total_amount']) ?></td>
              <td class="text-center">
                <span class="badge badge-<?= e($est['status']) ?>">
                  <?= e(str_replace('_', ' ', $est['status'])) ?>
                </span>
              </td>
              <td class="text-right">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="<?= url('/estimates/' . $est['id']) ?>" class="btn btn-secondary btn-sm">
                    View
                  </a>
                  <a href="<?= url('/estimates/' . $est['id'] . '/download') ?>" class="btn btn-secondary btn-sm">
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

  <?php include __DIR__ . '/../components/pagination.php'; ?>
</div>
