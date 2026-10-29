<div class="page-header">
  <div>
    <h1 class="page-title">Products & Services Catalog</h1>
    <div class="page-description">Standardize line items, prices, HSN/SAC codes, and default GST rates.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/products/create') ?>" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Add Item
    </a>
  </div>
</div>

<div class="filter-bar">
  <form method="GET" action="<?= url('/products') ?>" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: center; margin: 0;">
    <div style="flex: 1; min-width: 200px;">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by item name, SKU, HSN/SAC..." value="<?= e($search) ?>">
    </div>

    <div style="min-width: 140px;">
      <select name="item_type" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="">All Types</option>
        <option value="goods" <?= $itemType === 'goods' ? 'selected' : '' ?>>Goods (Physical)</option>
        <option value="services" <?= $itemType === 'services' ? 'selected' : '' ?>>Services</option>
      </select>
    </div>

    <div style="min-width: 120px;">
      <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($search || $itemType || $status !== 'active'): ?>
      <a href="<?= url('/products') ?>" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Name & Description</th>
          <th>Type</th>
          <th>SKU</th>
          <th>HSN/SAC</th>
          <th>Unit</th>
          <th class="text-right">Selling Price</th>
          <th class="text-right">GST Rate</th>
          <th class="text-center">Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr>
            <td colspan="9" class="empty-state">
              No products or services found. <a href="<?= url('/products/create') ?>">Add your first item</a>.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <div style="font-weight: 600; color: var(--slate-900);"><?= e($p['name']) ?></div>
                <?php if (!empty($p['description'])): ?>
                  <div style="font-size: 11px; color: var(--slate-500);"><?= e(substr($p['description'], 0, 75)) ?><?= strlen($p['description']) > 75 ? '...' : '' ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge" style="background: var(--slate-100); color: var(--slate-700);">
                  <?= ucfirst($p['item_type']) ?>
                </span>
              </td>
              <td><?= e($p['sku'] ?: '-') ?></td>
              <td><?= e($p['hsn_sac'] ?: '-') ?></td>
              <td><?= e($p['unit']) ?></td>
              <td class="text-right" style="font-weight: 600;"><?= format_currency($p['selling_price']) ?></td>
              <td class="text-right"><?= (float)$p['gst_rate'] ?>%</td>
              <td class="text-center">
                <span class="badge <?= $p['is_archived'] ? 'badge-void' : 'badge-active' ?>">
                  <?= $p['is_archived'] ? 'Archived' : 'Active' ?>
                </span>
              </td>
              <td class="text-right">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="<?= url('/products/' . $p['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">
                    Edit
                  </a>
                  <form action="<?= url('/products/' . $p['id'] . '/archive') ?>" method="POST" style="margin: 0;" onsubmit="return confirm('<?= $p['is_archived'] ? 'Restore this item?' : 'Archive this item?' ?>');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger);">
                      <?= $p['is_archived'] ? 'Restore' : 'Archive' ?>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php include __DIR__ . '/../components/pagination.php'; ?>
</div>
