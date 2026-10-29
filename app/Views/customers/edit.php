<div class="page-header">
  <div>
    <h1 class="page-title">Edit Customer <?= e($customer['name']) ?></h1>
    <div class="page-description">Update billing addresses, GST details, or contact person.</div>
  </div>

  <div class="header-actions">
    <a href="<?= url('/customers/' . $customer['id']) ?>" class="btn btn-secondary btn-sm">
      &larr; Cancel
    </a>
  </div>
</div>

<form action="<?= url('/customers/' . $customer['id']) ?>" method="POST">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. Basic Details</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="name">Customer / Business Name <span style="color: var(--danger);">*</span></label>
          <input type="text" name="name" id="name" class="form-control" value="<?= e($customer['name']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="contact_person">Contact Person</label>
          <input type="text" name="contact_person" id="contact_person" class="form-control" value="<?= e($customer['contact_person']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="customer_type">Customer Type</label>
          <select name="customer_type" id="customer_type" class="form-control">
            <option value="business" <?= $customer['customer_type'] === 'business' ? 'selected' : '' ?>>Business (B2B)</option>
            <option value="individual" <?= $customer['customer_type'] === 'individual' ? 'selected' : '' ?>>Individual / Consumer (B2C)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" name="email" id="email" class="form-control" value="<?= e($customer['email']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone Number</label>
          <input type="text" name="phone" id="phone" class="form-control" value="<?= e($customer['phone']) ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">2. GST & Tax Registration</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="form-group">
          <label class="form-label" for="gst_status">GST Registration Status</label>
          <select name="gst_status" id="gst_status" class="form-control">
            <option value="registered_regular" <?= $customer['gst_status'] === 'registered_regular' ? 'selected' : '' ?>>Registered Regular</option>
            <option value="registered_composition" <?= $customer['gst_status'] === 'registered_composition' ? 'selected' : '' ?>>Composition</option>
            <option value="unregistered" <?= $customer['gst_status'] === 'unregistered' ? 'selected' : '' ?>>Unregistered</option>
            <option value="consumer" <?= $customer['gst_status'] === 'consumer' ? 'selected' : '' ?>>Consumer</option>
            <option value="overseas" <?= $customer['gst_status'] === 'overseas' ? 'selected' : '' ?>>Overseas</option>
            <option value="sez" <?= $customer['gst_status'] === 'sez' ? 'selected' : '' ?>>SEZ</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="gstin">GSTIN</label>
          <input type="text" name="gstin" id="gstin" class="form-control" value="<?= e($customer['gstin']) ?>" maxlength="15">
        </div>

        <div class="form-group">
          <label class="form-label" for="pan">PAN</label>
          <input type="text" name="pan" id="pan" class="form-control" value="<?= e($customer['pan']) ?>" maxlength="10">
        </div>

        <div class="form-group">
          <label class="form-label" for="payment_terms">Default Payment Terms</label>
          <select name="payment_terms" id="payment_terms" class="form-control">
            <?php foreach ($paymentTerms as $termKey => $termLabel): ?>
              <option value="<?= e($termKey) ?>" <?= $customer['payment_terms'] === $termKey ? 'selected' : '' ?>>
                <?= e($termLabel) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">3. Address Information</h2>
    </div>
    <div class="card-body">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div>
          <div style="font-size: 13px; font-weight: 600; color: var(--slate-800); margin-bottom: 12px;">Billing Address</div>
          <div class="form-group">
            <label class="form-label" for="billing_address">Street Address</label>
            <textarea name="billing_address" id="billing_address" class="form-control" rows="2"><?= e($customer['billing_address']) ?></textarea>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="billing_city">City</label>
              <input type="text" name="billing_city" id="billing_city" class="form-control" value="<?= e($customer['billing_city']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="billing_pincode">PIN Code</label>
              <input type="text" name="billing_pincode" id="billing_pincode" class="form-control" value="<?= e($customer['billing_pincode']) ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="billing_state_code">State <span style="color: var(--danger);">*</span></label>
            <select name="billing_state_code" id="billing_state_code" class="form-control" required>
              <?php foreach ($states as $code => $stName): ?>
                <option value="<?= $code ?>" <?= $customer['billing_state_code'] === $code ? 'selected' : '' ?>>
                  <?= $code ?> - <?= e($stName) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <div style="font-size: 13px; font-weight: 600; color: var(--slate-800); margin-bottom: 12px;">Shipping Address</div>
          <div class="form-group">
            <label class="form-label" for="shipping_address">Shipping Street Address</label>
            <textarea name="shipping_address" id="shipping_address" class="form-control" rows="2"><?= e($customer['shipping_address']) ?></textarea>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="shipping_city">City</label>
              <input type="text" name="shipping_city" id="shipping_city" class="form-control" value="<?= e($customer['shipping_city']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="shipping_pincode">PIN Code</label>
              <input type="text" name="shipping_pincode" id="shipping_pincode" class="form-control" value="<?= e($customer['shipping_pincode']) ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="shipping_state_code">Shipping State</label>
            <select name="shipping_state_code" id="shipping_state_code" class="form-control">
              <option value="">-- Same as Billing --</option>
              <?php foreach ($states as $code => $stName): ?>
                <option value="<?= $code ?>" <?= $customer['shipping_state_code'] === $code ? 'selected' : '' ?>>
                  <?= $code ?> - <?= e($stName) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-top: 16px; margin-bottom: 0;">
        <label class="form-label" for="notes">Notes</label>
        <textarea name="notes" id="notes" class="form-control" rows="2"><?= e($customer['notes']) ?></textarea>
      </div>

      <div style="margin-top: 24px;">
        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
          Update Customer &rarr;
        </button>
      </div>
    </div>
  </div>
</form>
