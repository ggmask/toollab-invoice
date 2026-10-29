<div class="page-header">
  <div>
    <h1 class="page-title">Recurring Invoices</h1>
    <div class="page-description">Automate repeat billings for subscriptions, retainers, and regular contracts.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/recurring/create') ?>" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      New Recurring Profile
    </a>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Profile Name</th>
          <th>Customer</th>
          <th>Frequency</th>
          <th>Next Run Date</th>
          <th>Last Generated</th>
          <th class="text-center">Mode</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($profiles)): ?>
          <tr>
            <td colspan="8" class="empty-state">
              No recurring profiles found. Automate recurring retainers or services with a new profile.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($profiles as $prof): ?>
            <tr>
              <td>
                <a href="<?= url('/recurring/' . $prof['id']) ?>" style="font-weight: 600;">
                  <?= e($prof['profile_name']) ?>
                </a>
              </td>
              <td><?= e($prof['customer_name']) ?></td>
              <td><?= ucfirst($prof['frequency']) ?></td>
              <td style="font-weight: 600; color: var(--primary);">
                <?= format_date($prof['next_issue_date']) ?>
              </td>
              <td>
                <?php if (!empty($prof['last_invoice_number'])): ?>
                  <a href="<?= url('/invoices/' . $prof['last_generated_invoice_id']) ?>">
                    <?= e($prof['last_invoice_number']) ?>
                  </a>
                  <div style="font-size: 11px; color: var(--slate-500);"><?= format_date($prof['last_generated_at']) ?></div>
                <?php else: ?>
                  <span style="color: var(--slate-400);">Never</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge" style="background: var(--slate-100); color: var(--slate-700);">
                  <?= strtoupper($prof['generation_mode']) ?>
                </span>
              </td>
              <td class="text-center">
                <span class="badge badge-<?= e($prof['status']) ?>">
                  <?= ucfirst($prof['status']) ?>
                </span>
              </td>
              <td class="text-right">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="<?= url('/recurring/' . $prof['id']) ?>" class="btn btn-secondary btn-sm">
                    View
                  </a>
                  <?php if ($prof['status'] === 'active'): ?>
                    <form action="<?= url('/recurring/' . $prof['id'] . '/generate') ?>" method="POST" style="margin: 0;" onsubmit="return confirm('Generate invoice now for scheduled date <?= $prof['next_issue_date'] ?>?');">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-primary btn-sm" title="Generate next invoice immediately">
                        Generate Now
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
