<div class="page-header">
  <div>
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
      <h1 class="page-title"><?= e($profile['profile_name']) ?></h1>
      <span class="badge badge-<?= e($profile['status']) ?>" style="font-size: 13px; padding: 4px 10px;">
        <?= ucfirst($profile['status']) ?>
      </span>
    </div>
    <div class="page-description">
      Frequency: <strong><?= ucfirst($profile['frequency']) ?></strong> &bull; Customer: <strong><?= e($profile['customer_name']) ?></strong>
    </div>
  </div>

  <div class="header-actions">
    <form action="<?= url('/recurring/' . $profile['id'] . '/toggle') ?>" method="POST" style="margin: 0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary btn-sm">
        <?= $profile['status'] === 'active' ? 'Pause Schedule' : 'Resume Schedule' ?>
      </button>
    </form>

    <?php if ($profile['status'] === 'active'): ?>
      <form action="<?= url('/recurring/' . $profile['id'] . '/generate') ?>" method="POST" style="margin: 0;" onsubmit="return confirm('Generate invoice now for scheduled date <?= $profile['next_issue_date'] ?>?');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary btn-sm">
          Generate Invoice Now &rarr;
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">Schedule Information</h2>
  </div>
  <div class="card-body">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">Next Scheduled Date</div>
        <div style="font-size: 16px; font-weight: 700; color: var(--primary);"><?= format_date($profile['next_issue_date']) ?></div>
      </div>
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">Frequency</div>
        <div style="font-size: 14px; font-weight: 600;"><?= ucfirst($profile['frequency']) ?></div>
      </div>
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">Start Date</div>
        <div style="font-size: 14px; font-weight: 600;"><?= format_date($profile['start_date']) ?></div>
      </div>
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">End Date</div>
        <div style="font-size: 14px; font-weight: 600;"><?= $profile['end_date'] ? format_date($profile['end_date']) : 'Ongoing' ?></div>
      </div>
      <div>
        <div style="font-size: 11px; color: var(--slate-500); text-transform: uppercase; font-weight: 600;">Payment Due Rule</div>
        <div style="font-size: 14px; font-weight: 600;"><?= (int)$profile['due_days'] ?> days after issue</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">Template Line Items</h2>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Item / Service</th>
          <th>HSN/SAC</th>
          <th class="text-right">Qty</th>
          <th>Unit</th>
          <th class="text-right">Unit Price</th>
          <th class="text-right">GST Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td>
              <strong><?= e($it['name']) ?></strong>
              <?php if (!empty($it['description'])): ?>
                <div style="font-size: 12px; color: var(--slate-500);"><?= nl2br(e($it['description'])) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e($it['hsn_sac'] ?: '-') ?></td>
            <td class="text-right"><?= (float)($it['quantity'] ?? 1) ?></td>
            <td><?= e($it['unit'] ?? 'MTH') ?></td>
            <td class="text-right" style="font-weight: 600;"><?= format_currency($it['unit_price'] ?? 0) ?></td>
            <td class="text-right"><?= (float)($it['gst_rate'] ?? 18) ?>%</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">Generated Invoices History (Idempotent Occurrences)</h2>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Scheduled Date</th>
          <th>Generated Invoice</th>
          <th class="text-right">Invoice Total</th>
          <th class="text-center">Invoice Status</th>
          <th class="text-center">Execution Status</th>
          <th>Processed At</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($occurrences)): ?>
          <tr>
            <td colspan="6" class="empty-state">
              No invoices generated yet. Click "Generate Invoice Now" or wait for scheduled execution.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($occurrences as $occ): ?>
            <tr>
              <td><strong><?= format_date($occ['scheduled_date']) ?></strong></td>
              <td>
                <?php if (!empty($occ['invoice_number'])): ?>
                  <a href="<?= url('/invoices/' . $occ['invoice_id']) ?>" style="font-weight: 600;">
                    <?= e($occ['invoice_number']) ?>
                  </a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td class="text-right" style="font-weight: 600;">
                <?= !empty($occ['total_amount']) ? format_currency($occ['total_amount']) : '-' ?>
              </td>
              <td class="text-center">
                <?php if (!empty($occ['invoice_status'])): ?>
                  <span class="badge badge-<?= e($occ['invoice_status']) ?>">
                    <?= ucfirst($occ['invoice_status']) ?>
                  </span>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge badge-<?= $occ['status'] === 'success' ? 'paid' : 'rejected' ?>">
                  <?= ucfirst($occ['status']) ?>
                </span>
              </td>
              <td><?= format_date($occ['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
