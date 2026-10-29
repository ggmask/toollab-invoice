<form action="<?= url('/login') ?>" method="POST">
  <?= csrf_field() ?>

  <div class="form-group">
    <label class="form-label" for="email">Email Address</label>
    <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required autofocus value="admin@toollab.in">
  </div>

  <div class="form-group">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
      <a href="<?= url('/forgot-password') ?>" style="font-size: 12px;">Forgot password?</a>
    </div>
    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required value="password123">
  </div>

  <div style="margin-bottom: 20px;">
    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
      Sign In to Dashboard
    </button>
  </div>

  <div style="text-align: center; font-size: 13px; color: var(--slate-600);">
    Don't have an account? <a href="<?= url('/register') ?>" style="font-weight: 600;">Create Workspace</a>
  </div>

  <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--slate-200); text-align: center; font-size: 12px; color: var(--slate-500);">
    Default local login: <strong>admin@toollab.in</strong> / <strong>password123</strong>
  </div>
</form>
