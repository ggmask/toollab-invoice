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
        'section' => $section,
      ];
      $csvUrl = url('/reports/gst-sales?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
      $excelUrl = url('/reports/gst-sales?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
    ?>
    <a href="<?= $csvUrl ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Export CSV (<?= strtoupper($section) ?>)
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
  <form method="GET" action="<?= url('/reports/gst-sales') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: flex-end; margin: 0;">
    <input type="hidden" name="section" value="<?= e($section) ?>">
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
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
  <div class="stat-card">
    <div class="stat-label">Taxable Base Turnover</div>
    <div class="stat-value"><?= format_currency($sumTaxable) ?></div>
    <div class="stat-subtext">Net base consideration</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Total CGST + SGST</div>
    <div class="stat-value" style="color: var(--primary);"><?= format_currency($sumCgst + $sumSgst) ?></div>
    <div class="stat-subtext">Intra-state tax (50:50)</div>
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

<!-- Section Navigation Tabs -->
<div style="display: flex; gap: 8px; border-bottom: 1px solid var(--slate-200); margin-bottom: 16px; overflow-x: auto; padding-bottom: 2px;">
  <?php
    $tabUrl = function(string $s) use ($startDate, $endDate) {
      return url('/reports/gst-sales?' . http_build_query(['start_date' => $startDate, 'end_date' => $endDate, 'section' => $s]));
    };
  ?>
  <a href="<?= $tabUrl('all') ?>" class="btn btn-sm <?= $section === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
    All Items <span class="badge" style="margin-left: 4px; background: rgba(0,0,0,0.1);"><?= count($rows) ?></span>
  </a>
  <a href="<?= $tabUrl('b2b') ?>" class="btn btn-sm <?= $section === 'b2b' ? 'btn-primary' : 'btn-secondary' ?>">
    Table 4 (B2B) <span class="badge" style="margin-left: 4px; background: rgba(0,0,0,0.1);"><?= count($b2bRows) ?></span>
  </a>
  <a href="<?= $tabUrl('b2c') ?>" class="btn btn-sm <?= $section === 'b2c' ? 'btn-primary' : 'btn-secondary' ?>">
    Table 5 & 7 (B2C) <span class="badge" style="margin-left: 4px; background: rgba(0,0,0,0.1);"><?= count($b2cLargeRows) + count($b2cSmallRows) ?></span>
  </a>
  <a href="<?= $tabUrl('hsn') ?>" class="btn btn-sm <?= $section === 'hsn' ? 'btn-primary' : 'btn-secondary' ?>">
    Table 12 (HSN Summary) <span class="badge" style="margin-left: 4px; background: rgba(0,0,0,0.1);"><?= count($hsnSummary) ?></span>
  </a>
  <a href="<?= $tabUrl('nil') ?>" class="btn btn-sm <?= $section === 'nil' ? 'btn-primary' : 'btn-secondary' ?>">
    Table 8 (Nil/Exempt) <span class="badge" style="margin-left: 4px; background: rgba(0,0,0,0.1);"><?= count($nilExemptRows) ?></span>
  </a>
</div>

<!-- Register Content -->
<div class="card">
  <?php if ($section === 'hsn'): ?>
    <!-- Table 12: HSN Summary -->
    <div class="card-header" style="padding: 12px 16px; border-bottom: 1px solid var(--slate-200); background: var(--slate-50);">
      <strong>GSTR-1 Table 12: Summary of Outward Supplies by HSN / SAC</strong>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>HSN / SAC</th>
            <th>Description</th>
            <th>UQC</th>
            <th class="text-right">Total Qty</th>
            <th class="text-right">Total Value (₹)</th>
            <th class="text-right">Taxable Value (₹)</th>
            <th class="text-right">IGST (₹)</th>
            <th class="text-right">CGST (₹)</th>
            <th class="text-right">SGST (₹)</th>
            <th class="text-right">Total Tax (₹)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($hsnSummary)): ?>
            <tr><td colspan="10" style="text-align: center; padding: 30px; color: var(--slate-500);">No HSN entries found.</td></tr>
          <?php else: ?>
            <?php foreach ($hsnSummary as $h): ?>
              <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--primary);"><?= e($h['hsn_sac']) ?></td>
                <td><?= e($h['description']) ?></td>
                <td><span style="font-size: 11px; background: var(--slate-100); padding: 2px 6px; border-radius: 3px;"><?= e($h['uqc']) ?></span></td>
                <td class="text-right"><?= number_format($h['total_qty'], 2) ?></td>
                <td class="text-right" style="font-weight: 600;"><?= format_currency($h['total_value']) ?></td>
                <td class="text-right"><?= format_currency($h['taxable_value']) ?></td>
                <td class="text-right"><?= format_currency($h['igst_amount']) ?></td>
                <td class="text-right"><?= format_currency($h['cgst_amount']) ?></td>
                <td class="text-right"><?= format_currency($h['sgst_amount']) ?></td>
                <td class="text-right" style="font-weight: 700; color: var(--primary);"><?= format_currency($h['total_tax']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  <?php else: ?>
    <!-- Line Item Tables for All, B2B, B2C, or Nil -->
    <?php
      $displayRows = match($section) {
        'b2b' => $b2bRows,
        'b2c' => array_merge($b2cLargeRows, $b2cSmallRows),
        'nil' => $nilExemptRows,
        default => $rows,
      };
    ?>
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
            <th>Description</th>
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
          <?php if (empty($displayRows)): ?>
            <tr>
              <td colspan="14" style="text-align: center; padding: 40px; color: var(--slate-500);">
                No transactions recorded for this section in the selected period.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($displayRows as $r): ?>
              <tr>
                <td style="font-weight: 600; white-space: nowrap;">
                  <a href="<?= url('/invoices/' . $r['invoice_id']) ?>" style="color: var(--primary); text-decoration: none;">
                    <?= e($r['invoice_number']) ?>
                  </a>
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
      </table>
    </div>
  <?php endif; ?>
</div>
