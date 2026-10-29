<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <a href="<?= url('/reports') ?>" style="color: var(--slate-500);">&larr; Reports</a> / GSTR-1 Register
    </div>
    <h1 class="page-title">GST Sales Register (GSTR-1 Ready)</h1>
    <div class="page-description">Itemized line-by-line GST sales breakdown formatted for monthly and quarterly GSTR-1 return filing.</div>
  </div>

  <div class="header-actions">
    <?php 
      $queryParams = [
        'start_date' => $startDate,
        'end_date' => $endDate,
      ];
      $csvUrl = url('/reports/gst-sales?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
      $excelUrl = url('/reports/gst-sales?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
      Export GSTR-1 Excel (.xls)
    </a>
  </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
  <form method="GET" action="<?= url('/reports/gst-sales') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: flex-end; margin: 0;">
    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Filing Period From</label>
      <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-control form-control-sm">
    </div>

    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Filing Period To</label>
      <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-control form-control-sm">
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Update Register</button>
  </form>
</div>

<?php
  $sumTaxable = 0.0;
  $sumCgst = 0.0;
  $sumSgst = 0.0;
  $sumIgst = 0.0;
  $sumTotal = 0.0;

  foreach ($rows as $r) {
    $sumTaxable += (float)$r['taxable_value'];
    $sumCgst += (float)$r['cgst_amount'];
    $sumSgst += (float)$r['sgst_amount'];
    $sumIgst += (float)$r['igst_amount'];
    $sumTotal += (float)$r['line_total'];
  }
?>

<!-- Metric Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
  <div class="stat-card">
    <div class="stat-label">Taxable Base Turnover</div>
    <div class="stat-value"><?= format_currency($sumTaxable) ?></div>
    <div class="stat-subtext">Net base consideration</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Total CGST + SGST</div>
    <div class="stat-value" style="color: var(--primary);"><?= format_currency($sumCgst + $sumSgst) ?></div>
    <div class="stat-subtext">Intra-state split (50:50)</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Total IGST</div>
    <div class="stat-value" style="color: #9333EA;"><?= format_currency($sumIgst) ?></div>
    <div class="stat-subtext">Inter-state integrated tax</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Total Gross Invoiced</div>
    <div class="stat-value" style="color: var(--success);"><?= format_currency($sumTotal) ?></div>
    <div class="stat-subtext">Inclusive of all taxes</div>
  </div>
</div>

<!-- Register Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Date</th>
          <th>Type</th>
          <th>Customer & GSTIN</th>
          <th>POS</th>
          <th>HSN/SAC</th>
          <th>Item Description</th>
          <th class="text-right">Qty</th>
          <th class="text-right">Taxable (₹)</th>
          <th class="text-center">Rate</th>
          <th class="text-right">CGST (₹)</th>
          <th class="text-right">SGST (₹)</th>
          <th class="text-right">IGST (₹)</th>
          <th class="text-right">Total (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="14" style="text-align: center; padding: 40px; color: var(--slate-500);">
              No taxable sales line items recorded for this filing window.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td style="font-weight: 600; white-space: nowrap;">
                <?= e($r['invoice_number']) ?>
              </td>
              <td style="font-size: 12px; color: var(--slate-500); white-space: nowrap;"><?= format_date($r['issue_date']) ?></td>
              <td>
                <?php if (!empty($r['customer_gstin'])): ?>
                  <span style="font-size: 10px; font-weight: 700; background: var(--info-light); color: var(--info); padding: 2px 6px; border-radius: 4px;">B2B</span>
                <?php else: ?>
                  <span style="font-size: 10px; font-weight: 700; background: var(--slate-100); color: var(--slate-600); padding: 2px 6px; border-radius: 4px;">B2C</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--slate-800); font-size: 12px;"><?= e($r['customer_name']) ?></div>
                <div style="font-family: monospace; font-size: 11px; color: var(--slate-500);"><?= e($r['customer_gstin'] ?: 'Unregistered') ?></div>
              </td>
              <td style="font-size: 12px; color: var(--slate-600); white-space: nowrap;">
                <?= e($r['place_of_supply_state_code'] ? $r['place_of_supply_state_code'] . '-' : '') ?><?= e($r['place_of_supply_state']) ?>
              </td>
              <td style="font-family: monospace; font-weight: 600; font-size: 12px;"><?= e($r['hsn_sac']) ?></td>
              <td style="font-size: 12px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($r['item_name']) ?>">
                <?= e($r['item_name']) ?>
              </td>
              <td class="text-right" style="font-size: 12px; color: var(--slate-600);"><?= (float)$r['quantity'] ?> <?= e($r['unit']) ?></td>
              <td class="text-right" style="font-weight: 600; font-size: 12px;"><?= format_currency($r['taxable_value']) ?></td>
              <td class="text-center" style="font-weight: 600; font-size: 12px;"><?= (float)$r['gst_rate'] ?>%</td>
              <td class="text-right" style="font-size: 12px; color: var(--slate-500);"><?= format_currency($r['cgst_amount']) ?></td>
              <td class="text-right" style="font-size: 12px; color: var(--slate-500);"><?= format_currency($r['sgst_amount']) ?></td>
              <td class="text-right" style="font-size: 12px; color: var(--slate-500);"><?= format_currency($r['igst_amount']) ?></td>
              <td class="text-right" style="font-weight: 700; color: var(--slate-900); font-size: 12px;"><?= format_currency($r['line_total']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
        <tfoot style="background: var(--slate-100); font-weight: 700; border-top: 2px solid var(--slate-300);">
          <tr>
            <td colspan="8" style="text-align: right; text-transform: uppercase; font-size: 11px;">Total GST Output Liability:</td>
            <td class="text-right"><?= format_currency($sumTaxable) ?></td>
            <td></td>
            <td class="text-right"><?= format_currency($sumCgst) ?></td>
            <td class="text-right"><?= format_currency($sumSgst) ?></td>
            <td class="text-right"><?= format_currency($sumIgst) ?></td>
            <td class="text-right" style="color: var(--primary);"><?= format_currency($sumTotal) ?></td>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
