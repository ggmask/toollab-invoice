<?php
$user = auth_user();
$biz = active_business();
?>
<header class="app-navbar">
  <div class="navbar-left">
    <button type="button" class="mobile-toggle" aria-label="Toggle navigation">
      <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
    <div style="font-size: 13px; font-weight: 500; color: var(--slate-600);">
      Workspace: <strong style="color: var(--slate-900);"><?= e($biz['trade_name'] ?: $biz['legal_name'] ?? 'ToolLab') ?></strong>
    </div>
  </div>

  <div class="navbar-right">
    <!-- Quick Create Button -->
    <a href="<?= url('/invoices/create') ?>" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Create Invoice
    </a>

    <!-- User Profile Dropdown -->
    <div style="display: flex; align-items: center; gap: 10px;">
      <div style="font-size: 13px; font-weight: 600; color: var(--slate-800);">
        <?= e($user['name'] ?? 'User') ?>
      </div>
      <form action="<?= url('/logout') ?>" method="POST" style="margin: 0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;">
          Sign Out
        </button>
      </form>
    </div>
  </div>
</header>
