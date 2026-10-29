<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Sign In') ?> - ToolLab Invoice</title>
  <link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
  <link rel="manifest" href="<?= asset('manifest.json') ?>">
  <meta name="theme-color" content="#4F46E5">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <style>
    body {
      background-color: var(--slate-50);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 24px;
    }
    .auth-card {
      width: 100%;
      max-width: 440px;
      background: var(--white);
      border: 1px solid var(--slate-200);
      border-radius: var(--radius-lg);
      padding: 32px;
      box-shadow: var(--shadow-md);
    }
    .auth-header {
      text-align: center;
      margin-bottom: 28px;
    }
    .auth-logo {
      width: 56px;
      height: 56px;
      margin-bottom: 12px;
      border-radius: 8px;
    }
    .auth-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--slate-900);
      margin: 0 0 6px;
    }
    .auth-subtitle {
      font-size: 13px;
      color: var(--slate-500);
      margin: 0;
    }
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-header">
      <img src="<?= asset('logo.png') ?>" alt="ToolLab Logo" class="auth-logo">
      <h1 class="auth-title">ToolLab Invoice</h1>
      <p class="auth-subtitle">Professional Invoicing & Business Management</p>
    </div>

    <?php include __DIR__ . '/../components/alerts.php'; ?>

    <?= $content ?>
  </div>
  <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
