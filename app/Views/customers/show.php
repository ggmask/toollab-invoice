<div class="page-header">
  <div>
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
      <h1 class="page-title"><?= e($customer['name']) ?></h1>
      <span class="badge <?= $customer['is_archived'] ? 'badge-void' : 'badge-active' ?>">
        <?= $customer['is_archived'] ? 'Archived' : 'Active' ?>
      </span>
      <span class="badge" style="background: var(--slate-100); color: var(--slate-700);">
        <?= ucfirst($customer['customer_type']) ?>
      </span>
    </div>
    <div class="page-description">
      <?= !empty($customer['gstin']) ? 'GSTIN: <strong>' . e($customer['gstin']) . '</strong> &bull; ' : '' ?>
      State: <?= e($customer['billing_state'] ?: 'India') ?>
    </div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/invoices/create?customer_id=' . $customer['id']) ?>" class="btn btn-primary btn-sm">
      + New Invoice
    </a>
    <?php if ($metrics['outstanding_balance'] > 0): ?>
      <a href="<?= url('/payments/create?customer_id=' . $customer['id']) ?>" class="btn btn-secondary btn-sm" style="color: var(--success); font-weight: 600;">
        + Record Payment
      </a>
    <?php endif; ?>
    <a href="<?= url('/reports/customer-statement?customer_id=' . $customer['id']) ?>" class="btn btn-secondary btn-sm">
      Account Statement
    </a>
    <a href="<?= url('/customers/' . $customer['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">
      Edit
    </a>
    <form action="<?= url('/customers/' . $customer['id'] . '/archive') ?>" method="POST" style="margin: 0;" onsubmit="return confirm('<?= $customer['is_archived'] ? 'Restore this customer?' : 'Archive this customer? Historical records will remain intact.' ?>');">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger);">
        <?= $customer['is_archived'] ? 'Restore' : 'Archive' ?>
      </button>
    </form>
  </div>
</div>

<!-- 3 Customer Financial Metrics -->
<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-label">Total Invoiced</span>
    <div class="stat-value"><?= format_currency($metrics['total_invoiced']) ?></div>
  </div>
  <div class="stat-card">
    <span class="stat-label">Total Paid / Collected</span>
    <div class="stat-value" style="color: var(--success);"><?= format_currency($metrics['total_paid']) ?></div>
  </div>
  <div class="stat-card">
    <span class="stat-label">Current Outstanding Balance</span>
    <div class="stat-value" style="color: <?= $metrics['outstanding_balance'] > 0 ? 'var(--danger)' : 'var(--slate-800)' ?>;">
      <?= format_currency($metrics['outstanding_balance']) ?>
    </div>
  </div>
</div>

<!-- Customer Profile Card -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">Customer Information</h2>
  </div>
  <div class="card-body">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px;">
      <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500); margin-bottom: 4px;">Contact</div>
        <div style="font-size: 14px; font-weight: 600; color: var(--slate-900);"><?= e($customer['contact_person'] ?: $customer['name']) ?></div>
        <div style="font-size: 13px; color: var(--slate-600); margin-top: 2px;"><?= e($customer['phone'] ?: 'No phone recorded') ?></div>
        <div style="font-size: 13px; color: var(--slate-600);"><?= e($customer['email'] ?: 'No email recorded') ?></div>
      </div>

      <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500); margin-bottom: 4px;">Billing Address</div>
        <div style="font-size: 13px; color: var(--slate-700); line-height: 1.5;">
          <?= nl2br(e($customer['billing_address'])) ?><br>
          <?= e($customer['billing_city']) ?>, <?= e($customer['billing_state']) ?> - <?= e($customer['billing_pincode']) ?>
        </div>
      </div>

      <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500); margin-bottom: 4px;">Shipping Address</div>
        <div style="font-size: 13px; color: var(--slate-700); line-height: 1.5;">
          <?= nl2br(e($customer['shipping_address'] ?: $customer['billing_address'])) ?>
        </div>
      </div>

      <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500); margin-bottom: 4px;">GST & Payment Terms</div>
        <div style="font-size: 13px; color: var(--slate-700);">
          GST Status: <strong><?= ucfirst(str_replace('_', ' ', $customer['gst_status'])) ?></strong><br>
          Payment Terms: <strong><?= e($customer['payment_terms'] ?: 'Net 15') ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Customer Invoices -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">Invoices (<?= count($invoices) ?>)</h2>
    <a href="<?= url('/invoices/create?customer_id=' . $customer['id']) ?>" class="btn btn-primary btn-sm">
      + New Invoice
    </a>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Issue Date</th>
          <th>Due Date</th>
          <th class="text-right">Total</th>
          <th class="text-right">Balance Due</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)): ?>
          <tr><td colspan="7" class="empty-state">No invoices issued to this customer yet.</td></tr>
        <?php else: ?>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td><a href="<?= url('/invoices/' . $inv['id']) ?>" style="font-weight: 600;"><?= e($inv['invoice_number']) ?></a></td>
              <td><?= format_date($inv['issue_date']) ?></td>
              <td><?= format_date($inv['due_date']) ?></td>
              <td class="text-right" style="font-weight: 600;"><?= format_currency($inv['total_amount']) ?></td>
              <td class="text-right" style="font-weight: 700; color: <?= $inv['balance_due'] > 0 ? 'var(--danger)' : 'var(--slate-500)' ?>;">
                <?= format_currency($inv['balance_due']) ?>
              </td>
              <td class="text-center">
                <span class="badge badge-<?= e($inv['status']) ?>"><?= ucfirst(str_replace('_', ' ', $inv['status'])) ?></span>
              </td>
              <td class="text-right">
                <a href="<?= url('/invoices/' . $inv['id']) ?>" class="btn btn-secondary btn-sm">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
