<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Financial Reports & Compliance</h1>
        <p class="text-sm text-slate-500 mt-1">Audit-ready financial statements, GST filings, receivables ageing, and transaction ledgers.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Sales Register -->
        <a href="<?= url('/reports/sales') ?>" class="card p-6 hover:border-brand-500 hover:shadow-md transition-all group flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-brand-600">Sales Register</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Chronological register of all sales invoices issued, taxable values, CGST/SGST/IGST breakdown, and outstanding balances.
                </p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-brand-600">
                <span>View Sales &bull; CSV &bull; Excel</span>
                <span>→</span>
            </div>
        </a>

        <!-- GST Sales Register (GSTR-1) -->
        <a href="<?= url('/reports/gst-sales') ?>" class="card p-6 hover:border-brand-500 hover:shadow-md transition-all group flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-brand-600">GSTR-1 Sales Register</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Itemized line-by-line GST report formatted for GST portal return filing (Table 4 B2B, Table 5/7 B2C, HSN/SAC breakdown).
                </p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-emerald-600">
                <span>GSTR-1 Ready &bull; Export</span>
                <span>→</span>
            </div>
        </a>

        <!-- Tax Liability Summary -->
        <a href="<?= url('/reports/tax-summary') ?>" class="card p-6 hover:border-brand-500 hover:shadow-md transition-all group flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-brand-600">Tax Liability Summary</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Aggregated GST liability grouped by tax slab (0%, 5%, 12%, 18%, 28%), showing split between CGST, SGST, IGST, and Cess.
                </p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-purple-600">
                <span>GST Slabs &bull; Monthly Filing</span>
                <span>→</span>
            </div>
        </a>

        <!-- Receivables & Ageing -->
        <a href="<?= url('/reports/receivables') ?>" class="card p-6 hover:border-brand-500 hover:shadow-md transition-all group flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-brand-600">Receivables & Ageing</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Accounts receivable breakdown segmented into 1-30, 31-60, 61-90, and 90+ days overdue buckets for working capital management.
                </p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-amber-600">
                <span>Overdue Ageing &bull; Follow-ups</span>
                <span>→</span>
            </div>
        </a>

        <!-- Payments & Collections -->
        <a href="<?= url('/reports/payments') ?>" class="card p-6 hover:border-brand-500 hover:shadow-md transition-all group flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-brand-600">Payments & Collections</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Cash inflow audit report grouped by payment mode (UPI, NEFT, Net Banking, Cheque), references, and invoice allocation status.
                </p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                <span>Bank Collections &bull; Audit</span>
                <span>→</span>
            </div>
        </a>

        <!-- Customer Ledger Statement -->
        <a href="<?= url('/reports/customer-statement') ?>" class="card p-6 hover:border-brand-500 hover:shadow-md transition-all group flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 group-hover:text-brand-600">Customer Statement</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Complete running ledger for any customer account: chronological debits (invoices), credits (receipts), and net closing balance.
                </p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-teal-600">
                <span>Account Ledger &bull; Closing Balance</span>
                <span>→</span>
            </div>
        </a>
    </div>
</div>
