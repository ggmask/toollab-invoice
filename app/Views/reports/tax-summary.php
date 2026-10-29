<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <a href="<?= url('/reports') ?>" style="color: var(--slate-500);">&larr; Reports</a> / GST Tax Liability
    </div>
    <h1 class="page-title">GST Tax Liability Summary</h1>
    <div class="page-description">Output tax liability grouped by GST tax rate slabs (0%, 5%, 12%, 18%, 28%) for GSTR-3B Table 3.1 preparation.</div>
  </div>

  <div class="header-actions">
    <?php 
      $queryParams = [
        'start_date' => $startDate,
        'end_date' => $endDate,
      ];
      $csvUrl = url('/reports/tax-summary?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
      $excelUrl = url('/reports/tax-summary?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
  <form method="GET" action="<?= url('/reports/tax-summary') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: flex-end; margin: 0;">
    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Tax Period From</label>
      <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-control form-control-sm">
    </div>

    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Tax Period To</label>
      <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-control form-control-sm">
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Update Summary</button>
  </form>
</div>

<?php
  $totTaxable = 0.0;
  $totCgst = 0.0;
  $totSgst = 0.0;
  $totIgst = 0.0;
  $totCess = 0.0;
  $totTax = 0.0;
  $totGrand = 0.0;

  foreach ($rows as $r) {
    $totTaxable += (float)$r['total_taxable'];
    $totCgst += (float)$r['total_cgst'];
    $totSgst += (float)$r['total_sgst'];
    $totIgst += (float)$r['total_igst'];
    $totCess += (float)$r['total_cess'];
    $totTax += (float)$r['total_tax'];
    $totGrand += (float)$r['total_amount'];
  }
?>

<!-- Slabs Table Card -->
<div class="card" style="margin-bottom: 24px;">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Tax Slab</th>
          <th class="text-center">Invoices</th>
          <th class="text-right">Taxable Turnover (₹)</th>
          <th class="text-right">CGST (₹)</th>
          <th class="text-right">SGST (₹)</th>
          <th class="text-right">IGST (₹)</th>
          <th class="text-right">Cess (₹)</th>
          <th class="text-right">Total Tax Liability (₹)</th>
          <th class="text-right">Gross Turnover (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 40px; color: var(--slate-500);">
              No taxable sales recorded for this period.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td style="font-weight: 700; white-space: nowrap;">
                <span style="display: inline-block; padding: 3px 8px; background: var(--slate-100); border-radius: 4px; font-size: 12px;">
                  GST <?= (float)$r['gst_rate'] ?>% Slab
                </span>
              </td>
              <td class="text-center" style="font-weight: 600; color: var(--slate-700);">
                <?= (int)$r['invoice_count'] ?>
              </td>
              <td class="text-right" style="font-weight: 600; color: var(--slate-900);">
                <?= format_currency($r['total_taxable']) ?>
              </td>
              <td class="text-right" style="color: var(--slate-700);">
                <?= format_currency($r['total_cgst']) ?>
              </td>
              <td class="text-right" style="color: var(--slate-700);">
                <?= format_currency($r['total_sgst']) ?>
              </td>
              <td class="text-right" style="color: var(--slate-700);">
                <?= format_currency($r['total_igst']) ?>
              </td>
              <td class="text-right" style="color: var(--slate-500);">
                <?= format_currency($r['total_cess']) ?>
              </td>
              <td class="text-right" style="font-weight: 700; color: #9333EA;">
                <?= format_currency($r['total_tax']) ?>
              </td>
              <td class="text-right" style="font-weight: 700; color: var(--slate-900);">
                <?= format_currency($r['total_amount']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
        <tfoot style="background: var(--slate-100); font-weight: 700; border-top: 2px solid var(--slate-300);">
          <tr>
            <td colspan="2" style="text-align: right; text-transform: uppercase; font-size: 11px;">Total Output Liability:</td>
            <td class="text-right"><?= format_currency($totTaxable) ?></td>
            <td class="text-right"><?= format_currency($totCgst) ?></td>
            <td class="text-right"><?= format_currency($totSgst) ?></td>
            <td class="text-right"><?= format_currency($totIgst) ?></td>
            <td class="text-right"><?= format_currency($totCess) ?></td>
            <td class="text-right" style="color: #9333EA; font-size: 14px;"><?= format_currency($totTax) ?></td>
            <td class="text-right" style="color: var(--slate-900); font-size: 14px;"><?= format_currency($totGrand) ?></td>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- GSTR-3B Advisory Card -->
<div class="card" style="padding: 18px 22px; background: #FAF5FF; border: 1px solid #E9D5FF; border-radius: var(--radius);">
  <h4 style="font-size: 14px; font-weight: 700; color: #581C87; margin-bottom: 6px;">
    GSTR-3B Table 3.1 Filing Guidance
  </h4>
  <p style="font-size: 13px; color: #6B21A8; line-height: 1.5; margin: 0 0 6px 0;">
    Use the values from this summary to populate <strong>Section 3.1(a) — Outward taxable supplies (other than zero rated, nil rated and exempted)</strong> in your monthly or quarterly GSTR-3B filing.
  </p>
  <p style="font-size: 12px; color: #7E22CE; margin: 0;">
    Intra-State sales incur equal splits of <strong>CGST (50%)</strong> and <strong>SGST (50%)</strong>, while Inter-State sales attract <strong>IGST (100%)</strong>.
  </p>
</div>
