<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$biz = active_business();
?>
<aside class="app-sidebar">
  <div class="sidebar-header">
    <img src="<?= asset('logo.png') ?>" alt="ToolLab Logo" class="sidebar-logo">
    <div>
      <div class="sidebar-title">ToolLab Invoice</div>
      <div class="sidebar-subtitle">Smart Billing & GST</div>
    </div>
  </div>

  <div class="sidebar-menu">
    <div class="nav-group-label">Core Modules</div>
    <a href="<?= url('/dashboard') ?>" class="nav-link <?= str_contains($currentPath, '/dashboard') ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      Dashboard
    </a>

    <a href="<?= url('/invoices') ?>" class="nav-link <?= (str_contains($currentPath, '/invoices') && !str_contains($currentPath, '/recurring')) ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Invoices
    </a>

    <a href="<?= url('/estimates') ?>" class="nav-link <?= str_contains($currentPath, '/estimates') ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
      </svg>
      Estimates
    </a>

    <a href="<?= url('/recurring') ?>" class="nav-link <?= str_contains($currentPath, '/recurring') ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
      </svg>
      Recurring
    </a>

    <div class="nav-group-label">Manage</div>
    <a href="<?= url('/customers') ?>" class="nav-link <?= str_contains($currentPath, '/customers') ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      Customers
    </a>

    <a href="<?= url('/products') ?>" class="nav-link <?= str_contains($currentPath, '/products') ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
      </svg>
      Products
    </a>

    <a href="<?= url('/payments') ?>" class="nav-link <?= str_contains($currentPath, '/payments') ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      Payments
    </a>

    <div class="nav-group-label">Accounting & Insights</div>
    <a href="<?= url('/reports') ?>" class="nav-link <?= str_contains($currentPath, '/reports') ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      Reports
    </a>

    <a href="<?= url('/settings/business') ?>" class="nav-link <?= str_contains($currentPath, '/settings') ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      Settings
    </a>
  </div>

  <div class="sidebar-footer">
    <div class="business-pill">
      <div class="business-avatar">
        <?= strtoupper(substr($biz['legal_name'] ?? 'TL', 0, 2)) ?>
      </div>
      <div class="business-info">
        <div class="business-name" title="<?= e($biz['legal_name'] ?? 'ToolLab') ?>">
          <?= e($biz['trade_name'] ?: $biz['legal_name'] ?? 'ToolLab') ?>
        </div>
        <div class="business-gstin">
          <?= !empty($biz['gstin']) ? 'GSTIN: ' . e($biz['gstin']) : 'Unregistered' ?>
        </div>
      </div>
    </div>
  </div>
</aside>
<div class="sidebar-overlay"></div>
