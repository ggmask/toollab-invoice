<form action="<?= url('/forgot-password') ?>" method="POST">
  <?= csrf_field() ?>

  <div style="font-size: 13px; color: var(--slate-600); margin-bottom: 20px;">
    Enter your registered email address and we will generate a secure reset link.
  </div>

  <div class="form-group">
    <label class="form-label" for="email">Email Address</label>
    <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required autofocus>
  </div>

  <div style="margin-bottom: 20px;">
    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
      Send Password Reset Link
    </button>
  </div>

  <div style="text-align: center; font-size: 13px; color: var(--slate-600);">
    Remembered your password? <a href="<?= url('/login') ?>" style="font-weight: 600;">Back to Sign In</a>
  </div>
</form>
