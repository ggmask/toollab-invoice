<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'ToolLab Invoice') ?> - ToolLab</title>
  <link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
  <link rel="manifest" href="<?= asset('manifest.json') ?>">
  <meta name="theme-color" content="#4F46E5">
  <meta name="app-url" content="<?= rtrim(url(''), '/') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body>
  <div class="app-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="app-main">
      <?php include __DIR__ . '/../components/navbar.php'; ?>

      <main class="content-wrapper">
        <?php include __DIR__ . '/../components/alerts.php'; ?>
        <?= $content ?>
      </main>
    </div>
  </div>

  <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
