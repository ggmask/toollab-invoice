<div class="page-header">
  <div>
    <h1 class="page-title">Add Product or Service</h1>
    <div class="page-description">Add an item to your catalog for quick insertion into sales invoices.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/products') ?>" class="btn btn-secondary btn-sm">
      &larr; Back to Catalog
    </a>
  </div>
</div>

<form action="<?= url('/products') ?>" method="POST">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Item Details</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="name">Product or Service Name <span style="color: var(--danger);">*</span></label>
          <input type="text" name="name" id="name" class="form-control" placeholder="e.g. 80mm Thermal Receipt Printer" required autofocus>
        </div>

        <div class="form-group">
          <label class="form-label" for="item_type">Item Classification</label>
          <select name="item_type" id="item_type" class="form-control">
            <option value="goods">Goods (Physical Product)</option>
            <option value="services">Services (Consulting / Work / SLA)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="sku">SKU or Item Code</label>
          <input type="text" name="sku" id="sku" class="form-control" placeholder="e.g. PRN-80-USB">
        </div>

        <div class="form-group">
          <label class="form-label" for="hsn_sac">HSN or SAC Code</label>
          <input type="text" name="hsn_sac" id="hsn_sac" class="form-control" placeholder="e.g. 844332 or 998314">
        </div>

        <div class="form-group">
          <label class="form-label" for="unit">Unit of Measurement (UOM)</label>
          <select name="unit" id="unit" class="form-control">
            <?php foreach ($units as $uCode => $uLabel): ?>
              <option value="<?= $uCode ?>" <?= $uCode === 'PCS' ? 'selected' : '' ?>><?= e($uLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="selling_price">Default Selling Price (₹) <span style="color: var(--danger);">*</span></label>
          <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control" placeholder="0.00" required min="0">
        </div>

        <div class="form-group">
          <label class="form-label" for="gst_rate">Default GST Tax Rate</label>
          <select name="gst_rate" id="gst_rate" class="form-control">
            <?php foreach ($gstRates as $rateVal => $rateLabel): ?>
              <option value="<?= $rateVal ?>" <?= (float)$rateVal === 18.0 ? 'selected' : '' ?>><?= e($rateLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="tax_category">Tax Category</label>
          <select name="tax_category" id="tax_category" class="form-control">
            <option value="taxable">Standard Taxable</option>
            <option value="exempt">Exempt Supply</option>
            <option value="nil_rated">Nil Rated</option>
            <option value="non_gst">Non-GST Supply</option>
          </select>
        </div>
      </div>

      <div class="form-group" style="margin-top: 16px; margin-bottom: 0;">
        <label class="form-label" for="description">Detailed Description / Specifications</label>
        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Description printed on line items..."></textarea>
      </div>

      <div style="margin-top: 24px;">
        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
          Save Catalog Item &rarr;
        </button>
      </div>
    </div>
  </div>
</form>
