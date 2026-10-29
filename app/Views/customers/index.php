<div class="page-header">
  <div>
    <h1 class="page-title">Customers</h1>
    <div class="page-description">Manage client directories, GST registration status, and ledger balances.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/customers/create') ?>" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Add Customer
    </a>
  </div>
</div>

<div class="filter-bar">
  <form method="GET" action="<?= url('/customers') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: center; margin: 0;">
    <div style="flex: 1; min-width: 200px;">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by customer name, GSTIN, phone, email..." value="<?= e($search) ?>">
    </div>

    <div style="min-width: 150px;">
      <select name="gst_status" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="">All GST Types</option>
        <option value="registered_regular" <?= $gstStatus === 'registered_regular' ? 'selected' : '' ?>>Registered Regular</option>
        <option value="registered_composition" <?= $gstStatus === 'registered_composition' ? 'selected' : '' ?>>Composition</option>
        <option value="unregistered" <?= $gstStatus === 'unregistered' ? 'selected' : '' ?>>Unregistered</option>
        <option value="consumer" <?= $gstStatus === 'consumer' ? 'selected' : '' ?>>Consumer</option>
      </select>
    </div>

    <div style="min-width: 120px;">
      <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($search || $gstStatus || $status !== 'active'): ?>
      <a href="<?= url('/customers') ?>" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Customer Name</th>
          <th>Contact Person</th>
          <th>GSTIN</th>
          <th>Phone / Email</th>
          <th class="text-right">Total Invoiced</th>
          <th class="text-right">Outstanding</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr>
            <td colspan="8" class="empty-state">
              No customers found. <a href="<?= url('/customers/create') ?>">Add your first customer</a>.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td>
                <a href="<?= url('/customers/' . $c['id']) ?>" style="font-weight: 600;">
                  <?= e($c['name']) ?>
                </a>
                <div style="font-size: 11px; color: var(--slate-500);">
                  <?= e($c['billing_city'] ? $c['billing_city'] . ', ' . $c['billing_state'] : ($c['billing_state'] ?: 'India')) ?>
                </div>
              </td>
              <td><?= e($c['contact_person'] ?: '-') ?></td>
              <td>
                <?= !empty($c['gstin']) ? '<code>' . e($c['gstin']) . '</code>' : '<span style="color: var(--slate-400);">Unregistered</span>' ?>
              </td>
              <td>
                <div style="font-size: 12px;"><?= e($c['phone'] ?: '-') ?></div>
                <div style="font-size: 11px; color: var(--slate-500);"><?= e($c['email'] ?: '-') ?></div>
              </td>
              <td class="text-right" style="font-weight: 500;">
                <?= format_currency($c['total_invoiced']) ?>
              </td>
              <td class="text-right" style="font-weight: 700; color: <?= $c['total_outstanding'] > 0 ? 'var(--danger)' : 'var(--slate-500)' ?>;">
                <?= format_currency($c['total_outstanding']) ?>
              </td>
              <td class="text-center">
                <span class="badge <?= $c['is_archived'] ? 'badge-void' : 'badge-active' ?>">
                  <?= $c['is_archived'] ? 'Archived' : 'Active' ?>
                </span>
              </td>
              <td class="text-right">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="<?= url('/customers/' . $c['id']) ?>" class="btn btn-secondary btn-sm">
                    View
                  </a>
                  <a href="<?= url('/invoices/create?customer_id=' . $c['id']) ?>" class="btn btn-primary btn-sm" title="New Invoice">
                    + Invoice
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
