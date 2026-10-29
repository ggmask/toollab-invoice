<div class="page-header">
  <div>
    <h1 class="page-title">GST & Tax Configuration</h1>
    <div class="page-description">Configure Goods & Services Tax registration status, GSTIN, PAN, and default Place of Origin.</div>
  </div>
</div>

<!-- Settings Navigation Tabs -->
<div class="nav-tabs">
  <a href="<?= url('/settings/business') ?>" class="nav-tab-item">Business & Banking</a>
  <a href="<?= url('/settings/gst') ?>" class="nav-tab-item active">GST & Tax Config</a>
  <a href="<?= url('/settings/invoice') ?>" class="nav-tab-item">Document Numbering & Terms</a>
  <a href="<?= url('/settings/tax-rates') ?>" class="nav-tab-item">Tax Slabs</a>
  <a href="<?= url('/settings/account') ?>" class="nav-tab-item">User Security</a>
</div>

<form method="POST" action="<?= url('/settings/gst') ?>">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">GST Registration & Tax Identity</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <div class="form-group" style="grid-column: 1 / -1;">
          <label class="form-label" for="gst_status">GST Registration Scheme <span style="color: var(--danger);">*</span></label>
          <select name="gst_status" id="gst_status" class="form-control">
            <?php 
              $status = $business['gst_status'] ?? 'registered_regular';
            ?>
            <option value="registered_regular" <?= ($status === 'registered_regular') ? 'selected' : '' ?>>Registered Regular Taxpayer (Standard CGST / SGST / IGST Tax Invoices)</option>
            <option value="composition" <?= ($status === 'composition') ? 'selected' : '' ?>>Composition Scheme (Bill of Supply - No Tax Collected)</option>
            <option value="unregistered" <?= ($status === 'unregistered') ? 'selected' : '' ?>>Unregistered Business (Under ₹20L/₹40L Exemption Threshold)</option>
            <option value="sez" <?= ($status === 'sez') ? 'selected' : '' ?>>Special Economic Zone (SEZ Unit / Developer - Zero Rated)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="gstin">GSTIN (15-Character Identifier)</label>
          <input type="text" name="gstin" id="gstin" value="<?= e($business['gstin'] ?? '') ?>"
                 class="form-control" style="font-family: monospace; text-transform: uppercase;"
                 placeholder="e.g. 27AAACT1234A1Z5" maxlength="15" oninput="parseGstin(this.value)">
          <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">15-character alphanumeric GST identifier.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="pan">Income Tax PAN</label>
          <input type="text" name="pan" id="pan" value="<?= e($business['pan'] ?? '') ?>"
                 class="form-control" style="font-family: monospace; text-transform: uppercase;"
                 placeholder="e.g. AAACT1234A" maxlength="10">
          <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">Automatically extracted from characters 3–12 of GSTIN.</div>
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
          <label class="form-label" for="state_code">Registration State Code (Default Place of Origin)</label>
          <select name="state_code" id="state_code" class="form-control">
            <option value="">-- Select State --</option>
            <?php foreach ($states as $code => $name): ?>
              <option value="<?= $code ?>" <?= (($business['state_code'] ?? '') === $code) ? 'selected' : '' ?>>
                <?= $code ?> - <?= e($name) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">
            Supplies within this state attract equal CGST + SGST (Intra-State). Supplies to other states attract IGST (Inter-State).
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statutory Law Card -->
  <div class="card" style="padding: 18px 22px; background: var(--info-light); border: 1px solid #BAE6FD; border-radius: var(--radius);">
    <h4 style="font-size: 14px; font-weight: 700; color: #0369A1; margin-bottom: 6px;">
      Indian GST Rule 46 Invoicing Law Notice
    </h4>
    <p style="font-size: 13px; color: #0284C7; line-height: 1.5; margin: 0 0 6px 0;">
      When registered as <strong>Registered Regular</strong>, the application produces official <strong>Tax Invoices</strong> with mandatory fields: GSTIN of supplier & recipient, consecutive serial numbering within the Financial Year, HSN/SAC codes, taxable value, and tax splits.
    </p>
    <p style="font-size: 12px; color: #0369A1; margin: 0;">
      For <strong>Composition Scheme</strong> or <strong>Unregistered</strong>, invoices are automatically generated as <strong>Bill of Supply</strong> without tax charging, adhering strictly to Section 31 of the CGST Act.
    </p>
  </div>

  <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
      Save GST Configuration
    </button>
  </div>
</form>

<script>
function parseGstin(gstin) {
    gstin = gstin.trim().toUpperCase();
    if (gstin.length >= 2) {
        const stateCode = gstin.substring(0, 2);
        const stateSelect = document.getElementById('state_code');
        if (stateSelect) {
            stateSelect.value = stateCode;
        }
    }
    if (gstin.length >= 12) {
        const pan = gstin.substring(2, 12);
        const panInput = document.getElementById('pan');
        if (panInput) {
            panInput.value = pan;
        }
    }
}
</script>
