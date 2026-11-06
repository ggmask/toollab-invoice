<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <a href="<?= url('/reports') ?>" style="color: var(--slate-500);">&larr; Reports</a> / Sales Register
    </div>
    <h1 class="page-title">Sales Register Report</h1>
    <div class="page-description">Detailed ledger of all outward invoices, taxable base, GST breakdown, and collections.</div>
  </div>

  <div class="header-actions">
    <?php 
      $queryParams = [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'status' => $status,
        'customer_id' => $customerId,
      ];
      $csvUrl = url('/reports/sales?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
      $excelUrl = url('/reports/sales?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
  <form method="GET" action="<?= url('/reports/sales') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: flex-end; margin: 0;">
    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">From Date</label>
      <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-control form-control-sm">
    </div>

    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">To Date</label>
      <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-control form-control-sm">
    </div>

    <div style="min-width: 180px;">
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Customer</label>
      <select name="customer_id" class="form-control form-control-sm">
        <option value="">All Customers</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ((string)$customerId === (string)$c['id']) ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width: 150px;">
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Invoice Status</label>
      <select name="status" class="form-control form-control-sm">
        <option value="">Active (Issued / Paid / Partial)</option>
        <option value="paid" <?= ($status === 'paid') ? 'selected' : '' ?>>Fully Paid</option>
        <option value="partially_paid" <?= ($status === 'partially_paid') ? 'selected' : '' ?>>Partially Paid</option>
        <option value="issued" <?= ($status === 'issued') ? 'selected' : '' ?>>Issued / Unpaid</option>
        <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Drafts</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Update Register</button>
  </form>
</div>

<!-- Metric Stat Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 24px;">
  <div class="stat-card">
    <div class="stat-label">Taxable Sales</div>
    <div class="stat-value"><?= format_currency($totals['taxable']) ?></div>
    <div class="stat-subtext">Net base turnover</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Total GST Collected</div>
    <div class="stat-value" style="color: #9333EA;"><?= format_currency($totals['cgst'] + $totals['sgst'] + $totals['igst']) ?></div>
    <div class="stat-subtext">CGST: <?= format_currency($totals['cgst']) ?> | SGST: <?= format_currency($totals['sgst']) ?> | IGST: <?= format_currency($totals['igst']) ?></div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Gross Invoiced</div>
    <div class="stat-value" style="color: var(--primary);"><?= format_currency($totals['total']) ?></div>
    <div class="stat-subtext">Invoice gross total</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Realized / Paid</div>
    <div class="stat-value" style="color: var(--success);"><?= format_currency($totals['paid']) ?></div>
    <div class="stat-subtext">Collections received</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Outstanding Balance</div>
    <div class="stat-value" style="color: var(--danger);"><?= format_currency($totals['balance']) ?></div>
    <div class="stat-subtext">Pending client debt</div>
  </div>
</div>

<!-- Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Date</th>
          <th>Customer</th>
          <th>GSTIN</th>
          <th>Supply / POS</th>
          <th class="text-right">Taxable (₹)</th>
          <th class="text-right">CGST (₹)</th>
          <th class="text-right">SGST (₹)</th>
          <th class="text-right">IGST (₹)</th>
          <th class="text-right">Total (₹)</th>
          <th class="text-right">Paid (₹)</th>
          <th class="text-right">Balance Due (₹)</th>
          <th class="text-center">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="13" style="text-align: center; padding: 40px; color: var(--slate-500);">
              No sales invoices found for the selected period and criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td style="font-weight: 600; white-space: nowrap;">
                <a href="<?= url('/invoices/' . $r['id']) ?>" style="color: var(--primary);">
                  <?= e($r['invoice_number']) ?>
                </a>
              </td>
              <td style="font-size: 12px; color: var(--slate-500); white-space: nowrap;"><?= format_date($r['issue_date']) ?></td>
              <td style="font-weight: 500; color: var(--slate-800); font-size: 13px;">
                <?= e($r['customer_name']) ?>
              </td>
              <td style="font-family: monospace; font-size: 11px; color: var(--slate-500); white-space: nowrap;">
                <?= e($r['customer_gstin'] ?: 'Unregistered') ?>
              </td>
              <td>
                <div>
                  <?php if (($r['supply_type'] ?? '') === 'inter_state' || (float)($r['igst_amount'] ?? 0) > 0): ?>
                    <span class="badge" style="background: rgba(147, 51, 234, 0.1); color: #9333EA; font-size: 10px; font-weight: 700;">Inter-State</span>
                  <?php else: ?>
                    <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #2563EB; font-size: 10px; font-weight: 700;">Intra-State</span>
                  <?php endif; ?>
                </div>
                <div style="font-size: 11px; color: var(--slate-500); margin-top: 2px; white-space: nowrap;">
                  <?= e($r['place_of_supply_state_code'] ? $r['place_of_supply_state_code'] . '-' : '') ?><?= e($r['place_of_supply_state'] ?: 'Default') ?>
                </div>
              </td>
              <td class="text-right" style="font-size: 13px;"><?= format_currency($r['taxable_value']) ?></td>
              <td class="text-right" style="font-size: 12px; color: var(--slate-500);"><?= format_currency($r['cgst_amount']) ?></td>
              <td class="text-right" style="font-size: 12px; color: var(--slate-500);"><?= format_currency($r['sgst_amount']) ?></td>
              <td class="text-right" style="font-size: 12px; color: var(--slate-500);"><?= format_currency($r['igst_amount']) ?></td>
              <td class="text-right" style="font-weight: 700; color: var(--slate-900);"><?= format_currency($r['total_amount']) ?></td>
              <td class="text-right" style="color: var(--success); font-weight: 600;"><?= format_currency($r['paid_amount']) ?></td>
              <td class="text-right" style="font-weight: 700; color: <?= ((float)$r['balance_due'] > 0) ? 'var(--danger)' : 'var(--slate-400)' ?>;">
                <?= format_currency($r['balance_due']) ?>
              </td>
              <td class="text-center" style="white-space: nowrap;">
                <span class="badge badge-<?= e($r['status']) ?>">
                  <?= e(ucfirst(str_replace('_', ' ', $r['status']))) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
        <tfoot style="background: var(--slate-100); font-weight: 700; border-top: 2px solid var(--slate-300);">
          <tr>
            <td colspan="5" style="text-align: right; text-transform: uppercase; font-size: 11px;">Total Summary:</td>
            <td class="text-right"><?= format_currency($totals['taxable']) ?></td>
            <td class="text-right"><?= format_currency($totals['cgst']) ?></td>
            <td class="text-right"><?= format_currency($totals['sgst']) ?></td>
            <td class="text-right"><?= format_currency($totals['igst']) ?></td>
            <td class="text-right" style="color: var(--primary);"><?= format_currency($totals['total']) ?></td>
            <td class="text-right" style="color: var(--success);"><?= format_currency($totals['paid']) ?></td>
            <td class="text-right" style="color: var(--danger);"><?= format_currency($totals['balance']) ?></td>
            <td></td>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
