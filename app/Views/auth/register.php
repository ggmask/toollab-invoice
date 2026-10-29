<form action="<?= url('/register') ?>" method="POST">
  <?= csrf_field() ?>

  <div class="form-group">
    <label class="form-label" for="name">Your Full Name</label>
    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Ramesh Patel" required autofocus>
  </div>

  <div class="form-group">
    <label class="form-label" for="business_name">Business or Firm Name</label>
    <input type="text" id="business_name" name="business_name" class="form-control" placeholder="e.g. Patel Traders / Acme Solutions" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="email">Email Address</label>
    <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="password">Password (Minimum 6 characters)</label>
    <input type="password" id="password" name="password" class="form-control" placeholder="Create a secure password" required minlength="6">
  </div>

  <div class="form-group">
    <label class="form-label" for="password_confirmation">Confirm Password</label>
    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repeat password" required minlength="6">
  </div>

  <div style="margin-bottom: 20px;">
    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
      Create Business Account
    </button>
  </div>

  <div style="text-align: center; font-size: 13px; color: var(--slate-600);">
    Already registered? <a href="<?= url('/login') ?>" style="font-weight: 600;">Sign In</a>
  </div>
</form>
