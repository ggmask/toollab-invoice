<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/reports') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reports</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900">Statement</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">Customer Account Statement</h1>
            <p class="text-sm text-slate-500">Comprehensive customer ledger detailing sales invoices, receipts, and net outstanding balance.</p>
        </div>

        <?php if ($selectedCustomer): ?>
            <!-- Export Buttons -->
            <div class="flex items-center gap-2">
                <?php 
                    $queryParams = [
                        'customer_id' => $customerId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ];
                    $csvUrl = url('/reports/customer-statement?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
                    $excelUrl = url('/reports/customer-statement?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
                ?>
                <a href="<?= $csvUrl ?>" class="btn btn-secondary text-xs">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export CSV
                </a>
                <a href="<?= $excelUrl ?>" class="btn btn-secondary text-xs">
                    <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel (.xls)
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filter Form -->
    <div class="card p-4">
        <form method="GET" action="<?= url('/reports/customer-statement') ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="form-label text-xs">Select Customer <span class="text-rose-500">*</span></label>
                <select name="customer_id" class="form-select text-sm" required>
                    <option value="">-- Choose Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ((string)$customerId === (string)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label text-xs">From Date</label>
                <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-input text-sm">
            </div>

            <div>
                <label class="form-label text-xs">To Date</label>
                <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-input text-sm">
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-primary text-sm h-10 w-full justify-center">
                    Generate Statement
                </button>
            </div>
        </form>
    </div>

    <?php if (!$selectedCustomer): ?>
        <div class="card p-12 text-center text-slate-500">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <p class="text-base font-semibold text-slate-800">Select a Customer Account</p>
            <p class="text-sm text-slate-500 mt-1">Choose a customer from the dropdown above to view their transactional ledger statement.</p>
        </div>
    <?php else: ?>
        <?php
            $totalDebits = 0.0;
            $totalCredits = 0.0;
            foreach ($ledger as $item) {
                $totalDebits += (float)$item['debit'];
                $totalCredits += (float)$item['credit'];
            }
        ?>

        <!-- Customer Summary Header Card -->
        <div class="card p-6 bg-white border border-slate-200">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pb-6 border-b border-slate-200">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Statement For</span>
                    <h2 class="text-xl font-bold text-slate-900 mt-0.5"><?= e($selectedCustomer['name']) ?></h2>
                    <p class="text-xs text-slate-500 mt-1">
                        <?php if (!empty($selectedCustomer['gstin'])): ?>
                            GSTIN: <span class="font-mono font-medium"><?= e($selectedCustomer['gstin']) ?></span> &bull;
                        <?php endif; ?>
                        <?php if (!empty($selectedCustomer['phone'])): ?>
                            Phone: <?= e($selectedCustomer['phone']) ?> &bull;
                        <?php endif; ?>
                        Email: <?= e($selectedCustomer['email'] ?: '—') ?>
                    </p>
                    <?php if (!empty($selectedCustomer['billing_address'])): ?>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?= e($selectedCustomer['billing_address']) ?>, <?= e($selectedCustomer['billing_city']) ?>, <?= e($selectedCustomer['billing_state']) ?> - <?= e($selectedCustomer['billing_pincode']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="text-left sm:text-right">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Statement Window</span>
                    <p class="text-sm font-semibold text-slate-800 mt-0.5">
                        <?= format_date($startDate) ?> to <?= format_date($endDate) ?>
                    </p>
                </div>
            </div>

            <!-- Ledger Summary Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 text-center">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Invoiced (Debits)</span>
                    <span class="text-xl font-bold text-slate-900 mt-1 block"><?= format_currency($totalDebits) ?></span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Received (Credits)</span>
                    <span class="text-xl font-bold text-emerald-600 mt-1 block"><?= format_currency($totalCredits) ?></span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Net Closing Balance</span>
                    <span class="text-xl font-bold <?= ($closingBalance > 0) ? 'text-rose-600' : 'text-slate-700' ?> mt-1 block">
                        <?= format_currency($closingBalance) ?>
                    </span>
                    <span class="text-xs text-slate-400"><?= ($closingBalance > 0) ? 'Receivable from customer' : 'Settled / Advance' ?></span>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="card overflow-hidden">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Ref #</th>
                            <th>Description</th>
                            <th class="text-right">Debit / Invoiced (₹)</th>
                            <th class="text-right">Credit / Paid (₹)</th>
                            <th class="text-right">Running Balance (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($ledger)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-500">
                                    No invoice or payment transactions recorded for this customer in the selected date range.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ledger as $l): ?>
                                <tr class="hover:bg-slate-50 text-sm">
                                    <td class="text-slate-600 text-xs whitespace-nowrap"><?= format_date($l['date']) ?></td>
                                    <td>
                                        <?php if ($l['type'] === 'invoice'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">Invoice</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">Receipt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-mono font-medium text-slate-900 whitespace-nowrap">
                                        <?= e($l['ref_no']) ?>
                                    </td>
                                    <td class="text-slate-700 text-xs"><?= e($l['description']) ?></td>
                                    <td class="text-right font-medium text-slate-900">
                                        <?= ((float)$l['debit'] > 0) ? format_currency($l['debit']) : '—' ?>
                                    </td>
                                    <td class="text-right font-medium text-emerald-600">
                                        <?= ((float)$l['credit'] > 0) ? format_currency($l['credit']) : '—' ?>
                                    </td>
                                    <td class="text-right font-bold <?= ((float)$l['balance'] > 0) ? 'text-rose-600' : 'text-slate-800' ?>">
                                        <?= format_currency($l['balance']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($ledger)): ?>
                        <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900">
                            <tr>
                                <td colspan="4" class="py-3 px-4 text-right uppercase text-xs tracking-wider">Statement Totals:</td>
                                <td class="text-right py-3 px-4 text-slate-900"><?= format_currency($totalDebits) ?></td>
                                <td class="text-right py-3 px-4 text-emerald-700"><?= format_currency($totalCredits) ?></td>
                                <td class="text-right py-3 px-4 text-rose-700 text-base"><?= format_currency($closingBalance) ?></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
