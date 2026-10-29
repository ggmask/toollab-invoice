<div class="page-header">
  <div>
    <h1 class="page-title">My Account & Security</h1>
    <div class="page-description">Update your user display name, authentication credentials, and secure password.</div>
  </div>
</div>

<!-- Settings Navigation Tabs -->
<div class="nav-tabs">
  <a href="<?= url('/settings/business') ?>" class="nav-tab-item">Business & Banking</a>
  <a href="<?= url('/settings/gst') ?>" class="nav-tab-item">GST & Tax Config</a>
  <a href="<?= url('/settings/invoice') ?>" class="nav-tab-item">Document Numbering & Terms</a>
  <a href="<?= url('/settings/tax-rates') ?>" class="nav-tab-item">Tax Slabs</a>
  <a href="<?= url('/settings/account') ?>" class="nav-tab-item active">User Security</a>
</div>

<form method="POST" action="<?= url('/settings/account') ?>">
  <?= csrf_field() ?>

  <!-- 1. Profile Details -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. User Profile</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <div class="form-group">
          <label class="form-label" for="name">Full Name <span style="color: var(--danger);">*</span></label>
          <input type="text" name="name" id="name" value="<?= e($user['name'] ?? '') ?>" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" value="<?= e($user['email'] ?? '') ?>" class="form-control" style="background: var(--slate-100); color: var(--slate-500); cursor: not-allowed;" disabled>
          <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">Your registered email is your unique account identifier.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Password Change -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">2. Change Password</h2>
    </div>
    <div class="card-body">
      <div style="font-size: 13px; color: var(--slate-500); margin-bottom: 16px;">
        Leave these fields blank if you do not wish to change your current password.
      </div>

      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <div class="form-group">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" name="current_password" id="current_password" class="form-control" placeholder="••••••••">
        </div>

        <div class="form-group">
          <label class="form-label" for="new_password">New Password</label>
          <input type="password" name="new_password" id="new_password" class="form-control" placeholder="At least 6 characters" minlength="6">
        </div>
      </div>
    </div>
  </div>

  <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
      Update Account Settings
    </button>
  </div>
</form>
