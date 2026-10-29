<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <a href="<?= url('/reports') ?>" style="color: var(--slate-500);">&larr; Reports</a> / Ageing Analysis
    </div>
    <h1 class="page-title">Outstanding Receivables & Ageing</h1>
    <div class="page-description">Monitor unpaid client receivables, overdue aging brackets, and follow up on pending payments.</div>
  </div>

  <div class="header-actions">
    <?php 
      $queryParams = [
        'as_of_date' => $asOfDate,
        'customer_id' => $customerId,
      ];
      $csvUrl = url('/reports/receivables?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
      $excelUrl = url('/reports/receivables?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
  <form method="GET" action="<?= url('/reports/receivables') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: flex-end; margin: 0;">
    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">As of Date</label>
      <input type="date" name="as_of_date" value="<?= e($asOfDate) ?>" class="form-control form-control-sm">
    </div>

    <div style="min-width: 200px;">
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Filter Customer</label>
      <select name="customer_id" class="form-control form-control-sm">
        <option value="">All Customers</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ((string)$customerId === (string)$c['id']) ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Calculate Ageing</button>
  </form>
</div>

<!-- Ageing Bracket Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-bottom: 24px;">
  <div class="stat-card" style="border-left: 4px solid var(--success);">
    <div class="stat-label">Not Yet Due</div>
    <div class="stat-value"><?= format_currency($buckets['not_due']) ?></div>
    <div class="stat-subtext" style="color: var(--success);">Within credit terms</div>
  </div>

  <div class="stat-card" style="border-left: 4px solid var(--info);">
    <div class="stat-label">1–30 Days</div>
    <div class="stat-value"><?= format_currency($buckets['1_30']) ?></div>
    <div class="stat-subtext" style="color: var(--info);">Early overdue</div>
  </div>

  <div class="stat-card" style="border-left: 4px solid var(--warning);">
    <div class="stat-label">31–60 Days</div>
    <div class="stat-value"><?= format_currency($buckets['31_60']) ?></div>
    <div class="stat-subtext" style="color: var(--warning);">Follow up required</div>
  </div>

  <div class="stat-card" style="border-left: 4px solid #F97316;">
    <div class="stat-label">61–90 Days</div>
    <div class="stat-value" style="color: #EA580C;"><?= format_currency($buckets['61_90']) ?></div>
    <div class="stat-subtext" style="color: #EA580C;">Escalation needed</div>
  </div>

  <div class="stat-card" style="border-left: 4px solid var(--danger);">
    <div class="stat-label">90+ Days</div>
    <div class="stat-value" style="color: var(--danger);"><?= format_currency($buckets['90_plus']) ?></div>
    <div class="stat-subtext" style="color: var(--danger);">High risk exposure</div>
  </div>

  <div class="stat-card" style="background: var(--slate-900); color: var(--white); border-left: 4px solid var(--primary);">
    <div class="stat-label" style="color: var(--slate-300);">Total Receivables</div>
    <div class="stat-value" style="color: var(--white);"><?= format_currency($buckets['total']) ?></div>
    <div class="stat-subtext" style="color: var(--slate-400);">All outstanding debt</div>
  </div>
</div>

<!-- Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Invoice #</th>
          <th>Issue Date</th>
          <th>Due Date</th>
          <th class="text-center">Days Overdue</th>
          <th class="text-center">Ageing Bracket</th>
          <th class="text-right">Invoice Total (₹)</th>
          <th class="text-right">Paid Amount (₹)</th>
          <th class="text-right">Balance Due (₹)</th>
          <th class="text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="10" style="text-align: center; padding: 40px; color: var(--slate-500);">
              Excellent! There are no outstanding or overdue receivables as of <?= format_date($asOfDate) ?>.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php 
              $days = (int)$r['days_overdue'];
            ?>
            <tr>
              <td>
                <div style="font-weight: 600; color: var(--slate-900);">
                  <a href="<?= url('/customers/' . $r['customer_id']) ?>" style="color: var(--slate-900);">
                    <?= e($r['customer_name']) ?>
                  </a>
                </div>
                <?php if (!empty($r['customer_phone'])): ?>
                  <div style="font-size: 11px; color: var(--slate-500);">📞 <?= e($r['customer_phone']) ?></div>
                <?php endif; ?>
              </td>
              <td style="font-weight: 600; white-space: nowrap;">
                <a href="<?= url('/invoices/' . $r['id']) ?>" style="color: var(--primary);">
                  <?= e($r['invoice_number']) ?>
                </a>
              </td>
              <td style="font-size: 12px; color: var(--slate-500); white-space: nowrap;"><?= format_date($r['issue_date']) ?></td>
              <td style="font-size: 12px; color: var(--slate-500); white-space: nowrap;"><?= format_date($r['due_date']) ?></td>
              <td class="text-center" style="white-space: nowrap;">
                <?php if ($days > 0): ?>
                  <span style="font-weight: 700; color: var(--danger);"><?= $days ?> days</span>
                <?php else: ?>
                  <span style="color: var(--slate-500);">In <?= abs($days) ?> days</span>
                <?php endif; ?>
              </td>
              <td class="text-center" style="white-space: nowrap;">
                <span class="badge badge-<?= ($days > 30) ? 'overdue' : 'issued' ?>">
                  <?= e($r['bucket']) ?>
                </span>
              </td>
              <td class="text-right" style="font-size: 13px;"><?= format_currency($r['total_amount']) ?></td>
              <td class="text-right" style="color: var(--slate-500); font-size: 13px;"><?= format_currency($r['paid_amount']) ?></td>
              <td class="text-right" style="font-weight: 700; color: var(--danger); font-size: 13px;">
                <?= format_currency($r['balance_due']) ?>
              </td>
              <td class="text-right" style="white-space: nowrap;">
                <a href="<?= url('/payments/create?customer_id=' . $r['customer_id'] . '&invoice_id=' . $r['id']) ?>" class="btn btn-primary btn-sm">
                  Collect
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
        <tfoot style="background: var(--slate-100); font-weight: 700; border-top: 2px solid var(--slate-300);">
          <tr>
            <td colspan="8" style="text-align: right; text-transform: uppercase; font-size: 11px;">Total Overdue Receivables:</td>
            <td class="text-right" style="color: var(--danger); font-size: 15px;"><?= format_currency($buckets['total']) ?></td>
            <td></td>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
