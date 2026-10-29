<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <a href="<?= url('/reports') ?>" style="color: var(--slate-500);">&larr; Reports</a> / Collections Report
    </div>
    <h1 class="page-title">Payments & Collections Report</h1>
    <div class="page-description">Track realized cash inflow, payment modes, and allocation breakdown against customer invoices.</div>
  </div>

  <div class="header-actions">
    <?php 
      $queryParams = [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'method' => $method,
      ];
      $csvUrl = url('/reports/payments?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
      $excelUrl = url('/reports/payments?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
    ?>
    <a href="<?= $csvUrl ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Export CSV
    </a>
    <a href="<?= $excelUrl ?>" class="btn btn-secondary btn-sm" style="color: var(--success);">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Export Excel (.xls)
    </a>
  </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
  <form method="GET" action="<?= url('/reports/payments') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: flex-end; margin: 0;">
    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">From Date</label>
      <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-control form-control-sm">
    </div>

    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">To Date</label>
      <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-control form-control-sm">
    </div>

    <div style="min-width: 180px;">
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Payment Mode</label>
      <select name="method" class="form-control form-control-sm">
        <option value="">All Payment Modes</option>
        <?php foreach ($paymentMethods as $mKey => $mLabel): ?>
          <option value="<?= $mKey ?>" <?= ($method === $mKey) ? 'selected' : '' ?>><?= e($mLabel) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Filter Collections</button>
  </form>
</div>

<!-- Summary Metrics -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
  <div class="stat-card">
    <div class="stat-label">Total Collections Received</div>
    <div class="stat-value" style="color: var(--slate-900);"><?= format_currency($totals['received']) ?></div>
    <div class="stat-subtext">Active posted receipts</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Invoices Cleared / Allocated</div>
    <div class="stat-value" style="color: var(--success);"><?= format_currency($totals['allocated']) ?></div>
    <div class="stat-subtext">Applied against sales debts</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Unallocated Advance Funds</div>
    <div class="stat-value" style="color: var(--warning);"><?= format_currency($totals['unallocated']) ?></div>
    <div class="stat-subtext">Credit for future billing</div>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Receipt #</th>
          <th>Receipt Date</th>
          <th>Customer</th>
          <th>Method</th>
          <th>Ref / UTR</th>
          <th class="text-right">Amount Received (₹)</th>
          <th class="text-right">Allocated (₹)</th>
          <th class="text-right">Unallocated (₹)</th>
          <th class="text-center">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 40px; color: var(--slate-500);">
              No collection receipts recorded for the selected period.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td style="font-weight: 600; white-space: nowrap;">
                <a href="<?= url('/payments/' . $r['id']) ?>" style="color: var(--primary);">
                  <?= e($r['payment_number']) ?>
                </a>
              </td>
              <td style="font-size: 12px; color: var(--slate-500); white-space: nowrap;"><?= format_date($r['payment_date']) ?></td>
              <td style="font-weight: 500; color: var(--slate-800);">
                <?= e($r['customer_name']) ?>
              </td>
              <td>
                <span style="font-size: 11px; padding: 2px 6px; background: var(--slate-100); border-radius: 4px; font-weight: 600;">
                  <?= e(strtoupper(str_replace('_', ' ', $r['payment_method']))) ?>
                </span>
              </td>
              <td style="font-family: monospace; font-size: 11px; color: var(--slate-500); white-space: nowrap;">
                <?= e($r['reference_number'] ?: '—') ?>
              </td>
              <td class="text-right" style="font-weight: 700; color: var(--slate-900);">
                <?= format_currency($r['amount']) ?>
              </td>
              <td class="text-right" style="color: var(--success); font-weight: 600;">
                <?= format_currency($r['allocated_amount']) ?>
              </td>
              <td class="text-right" style="color: var(--slate-600);">
                <?= format_currency($r['unallocated_amount']) ?>
              </td>
              <td class="text-center" style="white-space: nowrap;">
                <span class="badge badge-<?= ($r['status'] === 'posted') ? 'posted' : 'reversed' ?>">
                  <?= e(ucfirst($r['status'])) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
        <tfoot style="background: var(--slate-100); font-weight: 700; border-top: 2px solid var(--slate-300);">
          <tr>
            <td colspan="5" style="text-align: right; text-transform: uppercase; font-size: 11px;">Total Active Collections:</td>
            <td class="text-right" style="color: var(--primary);"><?= format_currency($totals['received']) ?></td>
            <td class="text-right" style="color: var(--success);"><?= format_currency($totals['allocated']) ?></td>
            <td class="text-right" style="color: var(--warning);"><?= format_currency($totals['unallocated']) ?></td>
            <td></td>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
