<div class="page-header">
  <div>
    <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 4px;">
      <a href="<?= url('/reports') ?>" style="color: var(--slate-500);">&larr; Reports</a> / Customer Statement
    </div>
    <h1 class="page-title">Customer Account Statement</h1>
    <div class="page-description">Comprehensive customer ledger detailing sales invoices, receipts, and net closing balance.</div>
  </div>

  <?php if ($selectedCustomer): ?>
    <div class="header-actions">
      <?php 
        $queryParams = [
          'customer_id' => $customerId,
          'start_date' => $startDate,
          'end_date' => $endDate,
        ];
        $csvUrl = url('/reports/customer-statement?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
        $excelUrl = url('/reports/customer-statement?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
  <?php endif; ?>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
  <form method="GET" action="<?= url('/reports/customer-statement') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: flex-end; margin: 0;">
    <div style="min-width: 220px;">
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">Select Customer <span style="color: var(--danger);">*</span></label>
      <select name="customer_id" class="form-control form-control-sm" required>
        <option value="">-- Choose Customer --</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ((string)$customerId === (string)$c['id']) ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">From Date</label>
      <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-control form-control-sm">
    </div>

    <div>
      <label class="form-label" style="font-size: 11px; margin-bottom: 2px;">To Date</label>
      <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-control form-control-sm">
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Generate Statement</button>
  </form>
</div>

<?php if (!$selectedCustomer): ?>
  <div class="card">
    <div class="empty-state">
      <svg class="empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
      <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;">Select a Customer Account</div>
      <div>Choose a customer from the dropdown above to view their transactional ledger statement.</div>
    </div>
  </div>
<?php else: ?>
  <?php
    $totalDebits = 0.0;
    $totalCredits = 0.0;
    foreach ($ledger as $item) {
      if ($item['type'] !== 'opening_balance') {
        $totalDebits += (float)$item['debit'];
        $totalCredits += (float)$item['credit'];
      }
    }
  ?>

  <!-- Customer Statement Header Card -->
  <div class="card" style="margin-bottom: 24px; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--slate-200); padding-bottom: 16px; margin-bottom: 16px;">
      <div>
        <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400);">Statement For</span>
        <h2 style="font-size: 18px; font-weight: 700; color: var(--slate-900); margin: 2px 0;"><?= e($selectedCustomer['name']) ?></h2>
        <div style="font-size: 12px; color: var(--slate-500);">
          <?php if (!empty($selectedCustomer['gstin'])): ?>
            GSTIN: <span style="font-family: monospace; font-weight: 600;"><?= e($selectedCustomer['gstin']) ?></span> &bull;
          <?php endif; ?>
          <?php if (!empty($selectedCustomer['phone'])): ?>
            Phone: <?= e($selectedCustomer['phone']) ?> &bull;
          <?php endif; ?>
          Email: <?= e($selectedCustomer['email'] ?: '—') ?>
        </div>
        <?php if (!empty($selectedCustomer['billing_address'])): ?>
          <div style="font-size: 12px; color: var(--slate-500); margin-top: 2px;">
            <?= e($selectedCustomer['billing_address']) ?>, <?= e($selectedCustomer['billing_city']) ?>, <?= e($selectedCustomer['billing_state']) ?> - <?= e($selectedCustomer['billing_pincode']) ?>
          </div>
        <?php endif; ?>
      </div>

      <div style="text-align: right;">
        <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--slate-400);">Statement Period</span>
        <div style="font-size: 13px; font-weight: 600; color: var(--slate-800); margin-top: 2px;">
          <?= format_date($startDate) ?> to <?= format_date($endDate) ?>
        </div>
      </div>
    </div>

    <!-- Summary Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; text-align: center;">
      <div style="background: var(--slate-50); padding: 12px; border-radius: var(--radius); border: 1px solid var(--slate-200);">
        <span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--slate-500); display: block;">Opening Balance</span>
        <span style="font-size: 18px; font-weight: 700; color: <?= ($openingBalance > 0) ? 'var(--danger)' : 'var(--slate-800)' ?>;"><?= format_currency($openingBalance) ?></span>
      </div>

      <div style="background: var(--slate-50); padding: 12px; border-radius: var(--radius); border: 1px solid var(--slate-200);">
        <span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--slate-500); display: block;">Invoiced This Period</span>
        <span style="font-size: 18px; font-weight: 700; color: var(--slate-900);"><?= format_currency($totalDebits) ?></span>
      </div>

      <div style="background: var(--slate-50); padding: 12px; border-radius: var(--radius); border: 1px solid var(--slate-200);">
        <span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--slate-500); display: block;">Paid This Period</span>
        <span style="font-size: 18px; font-weight: 700; color: var(--success);"><?= format_currency($totalCredits) ?></span>
      </div>

      <div style="background: var(--slate-50); padding: 12px; border-radius: var(--radius); border: 1px solid var(--slate-200);">
        <span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--slate-500); display: block;">Closing Balance</span>
        <span style="font-size: 18px; font-weight: 700; color: <?= ($closingBalance > 0) ? 'var(--danger)' : 'var(--slate-800)' ?>;">
          <?= format_currency($closingBalance) ?>
        </span>
        <span style="font-size: 11px; color: var(--slate-400); display: block; margin-top: 2px;">
          <?= ($closingBalance > 0) ? 'Receivable from client' : 'Account Settled / Advance' ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Ledger Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Ref #</th>
            <th>Description</th>
            <th class="text-right">Debit / Invoiced (₹)</th>
            <th class="text-right">Credit / Paid (₹)</th>
            <th class="text-right">Running Balance (₹)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ledger)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 36px; color: var(--slate-500);">
                No invoice or payment transactions recorded for this customer in the selected date range.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($ledger as $l): ?>
              <tr>
                <td style="font-size: 12px; color: var(--slate-500); white-space: nowrap;"><?= format_date($l['date']) ?></td>
                <td>
                  <?php if ($l['type'] === 'invoice'): ?>
                    <span style="font-size: 11px; padding: 2px 6px; background: var(--info-light); color: var(--info); border-radius: 4px; font-weight: 600;">Invoice</span>
                  <?php elseif ($l['type'] === 'opening_balance'): ?>
                    <span style="font-size: 11px; padding: 2px 6px; background: var(--slate-200); color: var(--slate-700); border-radius: 4px; font-weight: 600;">Opening</span>
                  <?php else: ?>
                    <span style="font-size: 11px; padding: 2px 6px; background: var(--success-light); color: var(--success); border-radius: 4px; font-weight: 600;">Receipt</span>
                  <?php endif; ?>
                </td>
                <td style="font-family: monospace; font-weight: 600; color: var(--slate-900); white-space: nowrap;">
                  <?= e($l['ref_no']) ?>
                </td>
                <td style="font-size: 12px; color: var(--slate-600);"><?= e($l['description']) ?></td>
                <td class="text-right" style="font-weight: 600; color: var(--slate-900);">
                  <?= ((float)$l['debit'] > 0) ? format_currency($l['debit']) : '—' ?>
                </td>
                <td class="text-right" style="font-weight: 600; color: var(--success);">
                  <?= ((float)$l['credit'] > 0) ? format_currency($l['credit']) : '—' ?>
                </td>
                <td class="text-right" style="font-weight: 700; color: <?= ((float)$l['balance'] > 0) ? 'var(--danger)' : 'var(--slate-800)' ?>;">
                  <?= format_currency($l['balance']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($ledger)): ?>
          <tfoot style="background: var(--slate-100); font-weight: 700; border-top: 2px solid var(--slate-300);">
            <tr>
              <td colspan="4" style="text-align: right; text-transform: uppercase; font-size: 11px;">Statement Totals:</td>
              <td class="text-right"><?= format_currency($totalDebits) ?></td>
              <td class="text-right" style="color: var(--success);"><?= format_currency($totalCredits) ?></td>
              <td class="text-right" style="color: var(--danger); font-size: 15px;"><?= format_currency($closingBalance) ?></td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
<?php endif; ?>
