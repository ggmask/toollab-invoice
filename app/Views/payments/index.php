<div class="page-header">
  <div>
    <h1 class="page-title">Payments & Receipts</h1>
    <div class="page-description">Track incoming customer collections, allocations against invoices, and payment vouchers.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/reports/payments') ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      Collections Report
    </a>
    <a href="<?= url('/payments/create') ?>" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Record Payment
    </a>
  </div>
</div>

<!-- Search & Filter Bar -->
<div class="filter-bar">
  <form method="GET" action="<?= url('/payments') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: center; margin: 0;">
    <div style="flex: 1; min-width: 200px;">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Receipt #, Reference, Customer name..." value="<?= e($search ?? '') ?>">
    </div>

    <div style="min-width: 180px;">
      <select name="customer_id" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="">All Customers</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ((string)$customerId === (string)$c['id']) ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width: 150px;">
      <select name="method" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="">All Payment Modes</option>
        <?php foreach ($paymentMethods as $mKey => $mLabel): ?>
          <option value="<?= $mKey ?>" <?= ($method === $mKey) ? 'selected' : '' ?>>
            <?= e($mLabel) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width: 130px;">
      <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="posted" <?= ($status === 'posted') ? 'selected' : '' ?>>Posted</option>
        <option value="reversed" <?= ($status === 'reversed') ? 'selected' : '' ?>>Reversed</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($search || $method || $status || $customerId): ?>
      <a href="<?= url('/payments') ?>" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Payments Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Receipt #</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Method</th>
          <th>Ref / Bank</th>
          <th class="text-right">Amount</th>
          <th class="text-right">Allocated</th>
          <th class="text-right">Unallocated</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr>
            <td colspan="10">
              <div class="empty-state">
                <svg class="empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;">No payment receipts found</div>
                <div style="margin-bottom: 16px;">Record a new customer receipt voucher to update invoice balances.</div>
                <a href="<?= url('/payments/create') ?>" class="btn btn-primary btn-sm">Record Payment</a>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td>
                <a href="<?= url('/payments/' . $p['id']) ?>" style="font-weight: 600;">
                  <?= e($p['payment_number']) ?>
                </a>
              </td>
              <td>
                <a href="<?= url('/customers/' . $p['customer_id']) ?>" style="font-weight: 500; color: var(--slate-800);">
                  <?= e($p['customer_name']) ?>
                </a>
              </td>
              <td style="font-size: 12px; color: var(--slate-500); white-space: nowrap;">
                <?= format_date($p['payment_date']) ?>
              </td>
              <td>
                <span style="font-size: 11px; padding: 2px 6px; background: var(--slate-100); border-radius: 4px; font-weight: 600;">
                  <?= e(strtoupper(str_replace('_', ' ', $p['payment_method']))) ?>
                </span>
              </td>
              <td style="font-size: 12px; color: var(--slate-500);">
                <div><?= e($p['reference_number'] ?: '—') ?></div>
                <?php if (!empty($p['bank_name'])): ?>
                  <div style="font-size: 11px; color: var(--slate-400);"><?= e($p['bank_name']) ?></div>
                <?php endif; ?>
              </td>
              <td class="text-right" style="font-weight: 700; color: var(--slate-900);">
                <?= format_currency($p['amount']) ?>
              </td>
              <td class="text-right" style="color: var(--success); font-weight: 600;">
                <?= format_currency($p['allocated_amount']) ?>
              </td>
              <td class="text-right">
                <?php if ((float)$p['unallocated_amount'] > 0): ?>
                  <span style="color: var(--warning); font-weight: 600;"><?= format_currency($p['unallocated_amount']) ?></span>
                <?php else: ?>
                  <span style="color: var(--slate-400);">₹0.00</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge badge-<?= ($p['status'] === 'posted') ? 'posted' : 'reversed' ?>">
                  <?= e(ucfirst($p['status'])) ?>
                </span>
              </td>
              <td class="text-right">
                <div style="display: flex; justify-content: flex-end; gap: 6px;">
                  <a href="<?= url('/payments/' . $p['id']) ?>" class="btn btn-secondary btn-sm" title="View Voucher">
                    View
                  </a>
                  <a href="<?= url('/payments/' . $p['id'] . '/pdf') ?>" class="btn btn-secondary btn-sm" title="PDF">
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

  <?php if ($totalPages > 1): ?>
    <div style="padding: 12px 20px; border-top: 1px solid var(--slate-200);">
      <?= \App\Support\View::component('pagination', [
        'page' => $page,
        'totalPages' => $totalPages,
        'baseUrl' => url('/payments'),
        'queryParams' => array_filter([
          'search' => $search,
          'customer_id' => $customerId,
          'method' => $method,
          'status' => $status
        ])
      ]) ?>
    </div>
  <?php endif; ?>
</div>
