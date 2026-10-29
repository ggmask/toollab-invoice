<div class="page-header">
  <div>
    <h1 class="page-title">Business Overview</h1>
    <div class="page-description">Real-time invoicing metrics, payment collections, and outstanding receivables.</div>
  </div>

  <div class="header-actions">
    <!-- Period Filter Form -->
    <form method="GET" action="<?= url('/dashboard') ?>" style="display: flex; gap: 8px; margin: 0;">
      <select name="period" class="form-control form-control-sm" onchange="this.form.submit()" style="font-weight: 500;">
        <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month (<?= date('M Y') ?>)</option>
        <option value="last_month" <?= $period === 'last_month' ? 'selected' : '' ?>>Last Month</option>
        <option value="this_fy" <?= $period === 'this_fy' ? 'selected' : '' ?>>This Financial Year (FY <?= \App\Support\FinancialYear::current() ?>)</option>
        <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>All Time</option>
      </select>
    </form>

    <a href="<?= url('/invoices/create') ?>" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      New Invoice
    </a>
  </div>
</div>

<!-- Quick Action Shortcuts -->
<div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
  <a href="<?= url('/invoices/create') ?>" class="btn btn-secondary btn-sm" style="background: var(--white);">
    + Create Invoice
  </a>
  <a href="<?= url('/estimates/create') ?>" class="btn btn-secondary btn-sm" style="background: var(--white);">
    + Create Estimate
  </a>
  <a href="<?= url('/payments/create') ?>" class="btn btn-secondary btn-sm" style="background: var(--white);">
    + Record Payment
  </a>
  <a href="<?= url('/customers/create') ?>" class="btn btn-secondary btn-sm" style="background: var(--white);">
    + Add Customer
  </a>
  <a href="<?= url('/products/create') ?>" class="btn btn-secondary btn-sm" style="background: var(--white);">
    + Add Product / Service
  </a>
</div>

<!-- 4 Key Financial Summary Cards -->
<div class="stats-grid">
  <!-- Card 1: Invoiced in Period -->
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-label">Invoiced in Period</span>
      <div class="stat-icon" style="background: var(--info-light); color: var(--info);">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
    </div>
    <div class="stat-value"><?= format_currency($invoiceStats['total_invoiced'] ?? 0) ?></div>
    <div class="stat-subtext">
      <?= (int)($invoiceStats['count'] ?? 0) ?> issued invoice(s)
    </div>
  </div>

  <!-- Card 2: Collections in Period -->
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-label">Payments Received</span>
      <div class="stat-icon" style="background: var(--success-light); color: var(--success);">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>
    <div class="stat-value" style="color: var(--success);"><?= format_currency($paymentStats['total_received'] ?? 0) ?></div>
    <div class="stat-subtext">
      <?= (int)($paymentStats['count'] ?? 0) ?> posted receipt(s)
    </div>
  </div>

  <!-- Card 3: Total Outstanding Receivables -->
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-label">Outstanding Balance</span>
      <div class="stat-icon" style="background: var(--warning-light); color: var(--warning);">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>
    <div class="stat-value" style="color: #B45309;"><?= format_currency($receivableStats['total_outstanding'] ?? 0) ?></div>
    <div class="stat-subtext">
      <?= (int)($receivableStats['unpaid_count'] ?? 0) ?> pending invoice(s)
    </div>
  </div>

  <!-- Card 4: Overdue Receivables -->
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-label">Overdue Amount</span>
      <div class="stat-icon" style="background: var(--danger-light); color: var(--danger);">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
      </div>
    </div>
    <div class="stat-value" style="color: var(--danger);"><?= format_currency($overdueStats['overdue_amount'] ?? 0) ?></div>
    <div class="stat-subtext">
      <?= (int)($overdueStats['overdue_count'] ?? 0) ?> past due date
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
  <!-- Recent Invoices Table -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <h2 class="card-title">Recent Invoices</h2>
      <a href="<?= url('/invoices') ?>" style="font-size: 13px; font-weight: 500;">View All &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Customer</th>
            <th>Issue Date</th>
            <th class="text-right">Total</th>
            <th class="text-right">Balance Due</th>
            <th class="text-center">Status</th>
            <th class="text-right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentInvoices)): ?>
            <tr>
              <td colspan="7" class="empty-state">
                No invoices found. <a href="<?= url('/invoices/create') ?>">Create your first invoice</a>.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentInvoices as $inv): ?>
              <tr>
                <td>
                  <a href="<?= url('/invoices/' . $inv['id']) ?>" style="font-weight: 600;">
                    <?= e($inv['invoice_number']) ?>
                  </a>
                </td>
                <td>
                  <div style="font-weight: 500; color: var(--slate-900);"><?= e($inv['customer_name']) ?></div>
                </td>
                <td><?= format_date($inv['issue_date']) ?></td>
                <td class="text-right" style="font-weight: 600;"><?= format_currency($inv['total_amount']) ?></td>
                <td class="text-right" style="font-weight: 600; color: <?= $inv['balance_due'] > 0 ? 'var(--danger)' : 'var(--slate-500)' ?>;">
                  <?= format_currency($inv['balance_due']) ?>
                </td>
                <td class="text-center">
                  <span class="badge badge-<?= e($inv['status']) ?>">
                    <?= e(str_replace('_', ' ', $inv['status'])) ?>
                  </span>
                </td>
                <td class="text-right">
                  <a href="<?= url('/invoices/' . $inv['id']) ?>" class="btn btn-secondary btn-sm" style="padding: 2px 8px;">
                    View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Invoices Needing Attention / Overdue -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <h2 class="card-title">Pending & Due Soon</h2>
      <a href="<?= url('/invoices?status=overdue') ?>" style="font-size: 13px; font-weight: 500;">Filter Overdue</a>
    </div>
    <div class="card-body" style="padding: 12px 16px;">
      <?php if (empty($attentionInvoices)): ?>
        <div style="text-align: center; padding: 24px 0; color: var(--slate-500); font-size: 13px;">
          All active invoices are fully paid or up to date!
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach ($attentionInvoices as $att): ?>
            <?php $isOverdue = (int)$att['days_overdue'] > 0; ?>
            <div style="padding: 12px; background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: 8px;">
              <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 4px;">
                <a href="<?= url('/invoices/' . $att['id']) ?>" style="font-weight: 600;">
                  <?= e($att['invoice_number']) ?>
                </a>
                <span style="font-weight: 700; color: var(--danger); font-size: 13px;">
                  <?= format_currency($att['balance_due']) ?>
                </span>
              </div>
              <div style="font-size: 12px; color: var(--slate-700); margin-bottom: 4px;">
                <?= e($att['customer_name']) ?>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                <span style="color: <?= $isOverdue ? 'var(--danger)' : 'var(--slate-500)' ?>; font-weight: <?= $isOverdue ? '600' : 'normal' ?>;">
                  <?= $isOverdue ? (int)$att['days_overdue'] . ' days overdue' : 'Due ' . format_date($att['due_date']) ?>
                </span>
                <a href="<?= url('/payments/create?customer_id=' . $att['customer_id'] . '&invoice_id=' . $att['id']) ?>" style="font-weight: 600;">
                  Record Payment &rarr;
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
