<div class="page-header">
  <div>
    <h1 class="page-title">Financial Reports & Compliance</h1>
    <div class="page-description">Audit-ready financial statements, GST GSTR-1/3B filings, receivables ageing, and transaction ledgers.</div>
  </div>
</div>

<div class="reports-grid">
  <!-- Sales Register -->
  <a href="<?= url('/reports/sales') ?>" class="report-card">
    <div>
      <div class="report-icon" style="background: var(--primary-light); color: var(--primary);">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      </div>
      <div class="report-title">Sales Register</div>
      <div class="report-desc">
        Chronological register of all sales invoices issued, taxable values, CGST/SGST/IGST breakdown, and outstanding balances.
      </div>
    </div>
    <div class="report-footer">
      <span>View Sales &bull; CSV &bull; Excel</span>
      <span>&rarr;</span>
    </div>
  </a>

  <!-- GST Sales Register (GSTR-1) -->
  <a href="<?= url('/reports/gst-sales') ?>" class="report-card">
    <div>
      <div class="report-icon" style="background: var(--success-light); color: var(--success);">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <div class="report-title">GSTR-1 Sales Register</div>
      <div class="report-desc">
        Itemized line-by-line GST report formatted for GST portal return filing (Table 4 B2B, Table 5/7 B2C, HSN/SAC breakdown).
      </div>
    </div>
    <div class="report-footer" style="color: var(--success);">
      <span>GSTR-1 Ready &bull; Export</span>
      <span>&rarr;</span>
    </div>
  </a>

  <!-- Tax Liability Summary -->
  <a href="<?= url('/reports/tax-summary') ?>" class="report-card">
    <div>
      <div class="report-icon" style="background: #FAF5FF; color: #9333EA;">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
      </div>
      <div class="report-title">Tax Liability Summary</div>
      <div class="report-desc">
        Aggregated GST liability grouped by tax slab (0%, 5%, 12%, 18%, 28%), showing split between CGST, SGST, IGST, and Cess.
      </div>
    </div>
    <div class="report-footer" style="color: #9333EA;">
      <span>GST Slabs &bull; Monthly Filing</span>
      <span>&rarr;</span>
    </div>
  </a>

  <!-- Receivables & Ageing -->
  <a href="<?= url('/reports/receivables') ?>" class="report-card">
    <div>
      <div class="report-icon" style="background: var(--warning-light); color: var(--warning);">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div class="report-title">Receivables & Ageing</div>
      <div class="report-desc">
        Accounts receivable breakdown segmented into 1-30, 31-60, 61-90, and 90+ days overdue buckets for working capital management.
      </div>
    </div>
    <div class="report-footer" style="color: var(--warning);">
      <span>Overdue Ageing &bull; Follow-ups</span>
      <span>&rarr;</span>
    </div>
  </a>

  <!-- Payments & Collections -->
  <a href="<?= url('/reports/payments') ?>" class="report-card">
    <div>
      <div class="report-icon" style="background: var(--info-light); color: var(--info);">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      </div>
      <div class="report-title">Payments & Collections</div>
      <div class="report-desc">
        Cash inflow audit report grouped by payment mode (UPI, NEFT, Net Banking, Cheque), references, and invoice allocation status.
      </div>
    </div>
    <div class="report-footer" style="color: var(--info);">
      <span>Bank Collections &bull; Audit</span>
      <span>&rarr;</span>
    </div>
  </a>

  <!-- Customer Ledger Statement -->
  <a href="<?= url('/reports/customer-statement') ?>" class="report-card">
    <div>
      <div class="report-icon" style="background: #F0FDFA; color: #0D9488;">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>
      <div class="report-title">Customer Statement</div>
      <div class="report-desc">
        Complete running ledger for any customer account: chronological debits (invoices), credits (receipts), and net closing balance.
      </div>
    </div>
    <div class="report-footer" style="color: #0D9488;">
      <span>Account Ledger &bull; Closing Balance</span>
      <span>&rarr;</span>
    </div>
  </a>
</div>
