<div class="page-header">
  <div>
    <h1 class="page-title">Add New Customer</h1>
    <div class="page-description">Record billing information, shipping details, and GST registration status.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/customers') ?>" class="btn btn-secondary btn-sm">
      &larr; Back to Customers
    </a>
  </div>
</div>

<form action="<?= url('/customers') ?>" method="POST">
  <?= csrf_field() ?>

  <!-- Basic Information -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. Basic Details</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="name">Customer / Business Name <span style="color: var(--danger);">*</span></label>
          <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Acme Retail Solutions LLP" required autofocus>
        </div>

        <div class="form-group">
          <label class="form-label" for="contact_person">Contact Person</label>
          <input type="text" name="contact_person" id="contact_person" class="form-control" placeholder="e.g. Rajesh Sharma">
        </div>

        <div class="form-group">
          <label class="form-label" for="customer_type">Customer Type</label>
          <select name="customer_type" id="customer_type" class="form-control">
            <option value="business">Business (B2B)</option>
            <option value="individual">Individual / Consumer (B2C)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" name="email" id="email" class="form-control" placeholder="accounts@client.com">
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone Number</label>
          <input type="text" name="phone" id="phone" class="form-control" placeholder="+91 98765 43210">
        </div>
      </div>
    </div>
  </div>

  <!-- Tax & GST Details -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">2. GST & Tax Registration</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="form-group">
          <label class="form-label" for="gst_status">GST Registration Status</label>
          <select name="gst_status" id="gst_status" class="form-control" onchange="onGstStatusChange(this)">
            <option value="registered_regular">Registered Business - Regular</option>
            <option value="registered_composition">Registered Business - Composition</option>
            <option value="unregistered">Unregistered Business</option>
            <option value="consumer">Consumer / Retail Individual</option>
            <option value="overseas">Overseas / Export Client</option>
            <option value="sez">Special Economic Zone (SEZ)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="gstin">GSTIN (15 Digits)</label>
          <input type="text" name="gstin" id="gstin" class="form-control" placeholder="27AABCA5678B1Z2" maxlength="15" oninput="autoFillGstinState(this)">
          <div style="font-size: 11px; color: var(--slate-500); margin-top: 2px;">
            First 2 digits determine default State.
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="pan">Permanent Account Number (PAN)</label>
          <input type="text" name="pan" id="pan" class="form-control" placeholder="AAAAA0000A" maxlength="10">
        </div>

        <div class="form-group">
          <label class="form-label" for="payment_terms">Default Payment Terms</label>
          <select name="payment_terms" id="payment_terms" class="form-control">
            <?php foreach ($paymentTerms as $termKey => $termLabel): ?>
              <option value="<?= e($termKey) ?>" <?= $termKey === 'Net 15' ? 'selected' : '' ?>>
                <?= e($termLabel) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Addresses -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">3. Billing & Shipping Address</h2>
    </div>
    <div class="card-body">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Billing Address -->
        <div>
          <div style="font-size: 13px; font-weight: 600; color: var(--slate-800); margin-bottom: 12px;">Billing Address</div>
          <div class="form-group">
            <label class="form-label" for="billing_address">Street Address</label>
            <textarea name="billing_address" id="billing_address" class="form-control" rows="2" placeholder="Building, Street, Landmark..."></textarea>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="billing_city">City</label>
              <input type="text" name="billing_city" id="billing_city" class="form-control" placeholder="e.g. Mumbai">
            </div>
            <div class="form-group">
              <label class="form-label" for="billing_pincode">PIN Code</label>
              <input type="text" name="billing_pincode" id="billing_pincode" class="form-control" placeholder="e.g. 400001" maxlength="10">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="billing_state_code">State <span style="color: var(--danger);">*</span></label>
            <select name="billing_state_code" id="billing_state_code" class="form-control" required>
              <option value="">-- Select State --</option>
              <?php foreach ($states as $code => $stName): ?>
                <option value="<?= $code ?>"><?= $code ?> - <?= e($stName) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Shipping Address -->
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="font-size: 13px; font-weight: 600; color: var(--slate-800);">Shipping Address</div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="copyBillingAddress()">Same as Billing</button>
          </div>

          <div class="form-group">
            <label class="form-label" for="shipping_address">Shipping Street Address</label>
            <textarea name="shipping_address" id="shipping_address" class="form-control" rows="2" placeholder="Leave blank if same as billing"></textarea>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="shipping_city">City</label>
              <input type="text" name="shipping_city" id="shipping_city" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label" for="shipping_pincode">PIN Code</label>
              <input type="text" name="shipping_pincode" id="shipping_pincode" class="form-control" maxlength="10">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="shipping_state_code">Shipping State</label>
            <select name="shipping_state_code" id="shipping_state_code" class="form-control">
              <option value="">-- Same as Billing --</option>
              <?php foreach ($states as $code => $stName): ?>
                <option value="<?= $code ?>"><?= $code ?> - <?= e($stName) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-top: 16px; margin-bottom: 0;">
        <label class="form-label" for="notes">Internal Notes</label>
        <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Private internal notes regarding customer..."></textarea>
      </div>

      <div style="margin-top: 24px;">
        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
          Save Customer &rarr;
        </button>
      </div>
    </div>
  </div>
</form>

<script>
function autoFillGstinState(input) {
  const gstin = input.value.trim().toUpperCase();
  input.value = gstin;
  if (gstin.length >= 2) {
    const code = gstin.substring(0, 2);
    const select = document.getElementById('billing_state_code');
    if (select && select.querySelector(`option[value="${code}"]`)) {
      select.value = code;
    }
    // Auto fill PAN (characters 3 to 12)
    if (gstin.length >= 12) {
      document.getElementById('pan').value = gstin.substring(2, 12);
    }
  }
}

function copyBillingAddress() {
  document.getElementById('shipping_address').value = document.getElementById('billing_address').value;
  document.getElementById('shipping_city').value = document.getElementById('billing_city').value;
  document.getElementById('shipping_pincode').value = document.getElementById('billing_pincode').value;
  document.getElementById('shipping_state_code').value = document.getElementById('billing_state_code').value;
}
</script>
