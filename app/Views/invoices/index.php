<div class="page-header">
  <div>
    <h1 class="page-title">Sales Invoices</h1>
    <div class="page-description">Generate, manage, track payment status, and export tax invoices.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/invoices/create') ?>" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Create Invoice
    </a>
  </div>
</div>

<!-- Search & Filter Bar -->
<div class="filter-bar">
  <form method="GET" action="<?= url('/invoices') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: center; margin: 0;">
    <div style="flex: 1; min-width: 200px;">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by invoice #, customer name, GSTIN..." value="<?= e($search) ?>">
    </div>

    <div style="min-width: 140px;">
      <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued</option>
        <option value="partially_paid" <?= $status === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
        <option value="overdue" <?= $status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
        <option value="void" <?= $status === 'void' ? 'selected' : '' ?>>Void</option>
      </select>
    </div>

    <div style="min-width: 180px;">
      <select name="customer_id" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="">All Customers</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $customerId == $c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($search || $status || $customerId || $startDate || $endDate): ?>
      <a href="<?= url('/invoices') ?>" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Invoices Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Customer</th>
          <th>Issue Date</th>
          <th>Due Date</th>
          <th class="text-right">Total Amount</th>
          <th class="text-right">Paid Amount</th>
          <th class="text-right">Balance Due</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)): ?>
          <tr>
            <td colspan="9" class="empty-state">
              No invoices found matching your filter criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td>
                <a href="<?= url('/invoices/' . $inv['id']) ?>" style="font-weight: 600;">
                  <?= e($inv['invoice_number']) ?>
                </a>
                <div style="font-size: 11px; color: var(--slate-500);">
                  <?= e(ucfirst(str_replace('_', ' ', $inv['document_type']))) ?>
                </div>
              </td>
              <td>
                <div style="font-weight: 500; color: var(--slate-900);"><?= e($inv['customer_name']) ?></div>
                <div style="font-size: 11px; color: var(--slate-500);"><?= e($inv['customer_gstin'] ?: 'Unregistered') ?></div>
              </td>
              <td><?= format_date($inv['issue_date']) ?></td>
              <td>
                <?= format_date($inv['due_date']) ?>
                <?php if ($inv['status'] !== 'paid' && $inv['status'] !== 'void' && strtotime($inv['due_date']) < time()): ?>
                  <span style="font-size: 10px; color: var(--danger); font-weight: 600; display: block;">Overdue</span>
                <?php endif; ?>
              </td>
              <td class="text-right" style="font-weight: 600;"><?= format_currency($inv['total_amount']) ?></td>
              <td class="text-right" style="color: var(--success);"><?= format_currency($inv['paid_amount']) ?></td>
              <td class="text-right" style="font-weight: 700; color: <?= $inv['balance_due'] > 0 ? 'var(--danger)' : 'var(--slate-500)' ?>;">
                <?= format_currency($inv['balance_due']) ?>
              </td>
              <td class="text-center">
                <span class="badge badge-<?= e($inv['status']) ?>">
                  <?= e(str_replace('_', ' ', $inv['status'])) ?>
                </span>
              </td>
              <td class="text-right">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="<?= url('/invoices/' . $inv['id']) ?>" class="btn btn-secondary btn-sm" title="View details">
                    View
                  </a>
                  <a href="<?= url('/invoices/' . $inv['id'] . '/download') ?>" class="btn btn-secondary btn-sm" title="Download PDF">
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
