<div class="page-header">
  <div>
    <h1 class="page-title">Business Profile & Banking</h1>
    <div class="page-description">Manage company legal identity, registered office address, bank remittance instructions, and print branding.</div>
  </div>
</div>

<!-- Settings Navigation Tabs -->
<div class="nav-tabs">
  <a href="<?= url('/settings/business') ?>" class="nav-tab-item active">Business & Banking</a>
  <a href="<?= url('/settings/gst') ?>" class="nav-tab-item">GST & Tax Config</a>
  <a href="<?= url('/settings/invoice') ?>" class="nav-tab-item">Document Numbering & Terms</a>
  <a href="<?= url('/settings/tax-rates') ?>" class="nav-tab-item">Tax Slabs</a>
  <a href="<?= url('/settings/account') ?>" class="nav-tab-item">User Security</a>
</div>

<form method="POST" action="<?= url('/settings/business') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <!-- 1. Company Identity -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. Company Identity</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <!-- Logo Upload -->
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="logo">Company Logo (For Invoices & PDFs)</label>
          <div style="display: flex; align-items: center; gap: 16px;">
            <?php if (!empty($business['logo_url'])): ?>
              <div style="width: 80px; height: 80px; border: 1px solid var(--slate-200); border-radius: var(--radius); padding: 4px; display: flex; align-items: center; justify-content: center; background: #fff;">
                <img src="<?= asset($business['logo_url']) ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
              </div>
            <?php endif; ?>
            <div style="flex: 1;">
              <input type="file" name="logo" id="logo" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
              <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">Recommended: High-resolution PNG, JPG, or SVG (max 2 MB). Transparent background works best on PDFs.</div>
              <?php if (!empty($business['logo_url'])): ?>
                <div style="margin-top: 6px;">
                  <label style="font-size: 12px; color: var(--danger); cursor: pointer;">
                    <input type="checkbox" name="remove_logo" value="1"> Remove current logo
                  </label>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="legal_name">Legal Registered Entity Name <span style="color: var(--danger);">*</span></label>
          <input type="text" name="legal_name" id="legal_name" value="<?= e($business['legal_name'] ?? '') ?>" class="form-control" required>
          <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">Exact legal name as registered under GST / MCA / Income Tax PAN.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="trade_name">Trade / Brand Name</label>
          <input type="text" name="trade_name" id="trade_name" value="<?= e($business['trade_name'] ?? '') ?>" class="form-control" placeholder="Brand name for invoices">
        </div>

        <div class="form-group">
          <label class="form-label" for="business_type">Constitution of Business</label>
          <select name="business_type" id="business_type" class="form-control">
            <?php 
              $types = ['Proprietorship', 'Partnership', 'Private Limited Company', 'Public Limited Company', 'Limited Liability Partnership (LLP)', 'Trust / Society', 'Individual / Freelancer'];
              $currType = $business['business_type'] ?? 'Proprietorship';
            ?>
            <?php foreach ($types as $t): ?>
              <option value="<?= $t ?>" <?= ($currType === $t) ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Official Contact Phone</label>
          <input type="text" name="phone" id="phone" value="<?= e($business['phone'] ?? '') ?>" class="form-control" placeholder="+91 98765 43210">
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Billing Email</label>
          <input type="email" name="email" id="email" value="<?= e($business['email'] ?? '') ?>" class="form-control" placeholder="billing@domain.in">
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
          <label class="form-label" for="website">Website URL</label>
          <input type="url" name="website" id="website" value="<?= e($business['website'] ?? '') ?>" class="form-control" placeholder="https://www.yourcompany.in">
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Registered Office Address -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">2. Principal Place of Business (Office Address)</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="form-group">
          <label class="form-label" for="address_line1">Address Line 1</label>
          <input type="text" name="address_line1" id="address_line1" value="<?= e($business['address_line1'] ?? '') ?>" class="form-control" placeholder="Building, Suite, Street">
        </div>

        <div class="form-group">
          <label class="form-label" for="address_line2">Address Line 2 (Optional)</label>
          <input type="text" name="address_line2" id="address_line2" value="<?= e($business['address_line2'] ?? '') ?>" class="form-control" placeholder="Landmark, Area">
        </div>

        <div class="form-group">
          <label class="form-label" for="city">City / District</label>
          <input type="text" name="city" id="city" value="<?= e($business['city'] ?? '') ?>" class="form-control">
        </div>

        <div class="form-group">
          <label class="form-label" for="state_code">State / Union Territory (GST)</label>
          <select name="state_code" id="state_code" class="form-control">
            <option value="">-- Select State --</option>
            <?php foreach ($states as $code => $name): ?>
              <option value="<?= $code ?>" <?= (($business['state_code'] ?? '') === $code) ? 'selected' : '' ?>>
                <?= $code ?> - <?= e($name) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="pincode">Postal PIN Code</label>
          <input type="text" name="pincode" id="pincode" value="<?= e($business['pincode'] ?? '') ?>" class="form-control" maxlength="6" pattern="[0-9]{6}">
        </div>

        <div class="form-group">
          <label class="form-label" for="signature_title">Signatory Title</label>
          <input type="text" name="signature_title" id="signature_title" value="<?= e($business['signature_title'] ?? 'Authorized Signatory') ?>" class="form-control" placeholder="Authorized Signatory / Director">
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Bank Remittance & UPI Payment Details -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">3. Bank Remittance & UPI Payment Details</h2>
    </div>
    <div class="card-body">
      <div style="font-size: 13px; color: var(--slate-500); margin-bottom: 16px;">
        These banking credentials appear directly at the bottom of printed invoices and PDFs for client NEFT/RTGS/UPI remittance.
      </div>

      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="form-group">
          <label class="form-label" for="bank_name">Bank Name</label>
          <input type="text" name="bank_name" id="bank_name" value="<?= e($business['bank_name'] ?? '') ?>" class="form-control" placeholder="e.g. HDFC Bank, ICICI Bank">
        </div>

        <div class="form-group">
          <label class="form-label" for="bank_branch">Branch Name</label>
          <input type="text" name="bank_branch" id="bank_branch" value="<?= e($business['bank_branch'] ?? '') ?>" class="form-control" placeholder="e.g. Bandra Kurla Complex">
        </div>

        <div class="form-group">
          <label class="form-label" for="bank_account_no">Account Number</label>
          <input type="text" name="bank_account_no" id="bank_account_no" value="<?= e($business['bank_account_no'] ?? '') ?>" class="form-control" style="font-family: monospace;" placeholder="e.g. 50200012345678">
        </div>

        <div class="form-group">
          <label class="form-label" for="bank_ifsc">IFSC Code</label>
          <input type="text" name="bank_ifsc" id="bank_ifsc" value="<?= e($business['bank_ifsc'] ?? '') ?>" class="form-control" style="font-family: monospace; text-transform: uppercase;" placeholder="e.g. HDFC0001234" maxlength="11">
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
          <label class="form-label" for="bank_upi_id">Virtual Payment Address (UPI VPA)</label>
          <input type="text" name="bank_upi_id" id="bank_upi_id" value="<?= e($business['bank_upi_id'] ?? '') ?>" class="form-control" style="font-family: monospace;" placeholder="e.g. toollab@hdfcbank">
          <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">Clients can pay instantly via any UPI app (GPay, PhonePe, Paytm).</div>
        </div>
      </div>
    </div>
  </div>

  <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
      Save Business Profile
    </button>
  </div>
</form>
