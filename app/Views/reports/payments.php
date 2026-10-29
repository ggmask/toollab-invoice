<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/reports') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reports</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900">Collections</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">Payments & Collections Report</h1>
            <p class="text-sm text-slate-500">Track realized cash flow, payment modes, and allocation breakdown against customer invoices.</p>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
            <?php 
                $queryParams = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'method' => $method,
                ];
                $csvUrl = url('/reports/payments?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
                $excelUrl = url('/reports/payments?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
    </div>

    <!-- Filter Form -->
    <div class="card p-4">
        <form method="GET" action="<?= url('/reports/payments') ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="form-label text-xs">From Date</label>
                <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-input text-sm">
            </div>

            <div>
                <label class="form-label text-xs">To Date</label>
                <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-input text-sm">
            </div>

            <div>
                <label class="form-label text-xs">Payment Method</label>
                <select name="method" class="form-select text-sm">
                    <option value="">All Payment Modes</option>
                    <?php foreach ($paymentMethods as $mKey => $mLabel): ?>
                        <option value="<?= $mKey ?>" <?= ($method === $mKey) ? 'selected' : '' ?>><?= e($mLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-primary text-sm h-10 w-full justify-center">
                    Filter Collections
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card p-5 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Collections Received</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block"><?= format_currency($totals['received']) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Active posted payments</span>
        </div>

        <div class="card p-5 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Invoices Cleared / Allocated</span>
            <span class="text-2xl font-bold text-emerald-600 mt-1 block"><?= format_currency($totals['allocated']) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Matched against sales</span>
        </div>

        <div class="card p-5 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Unallocated Advance Funds</span>
            <span class="text-2xl font-bold text-amber-600 mt-1 block"><?= format_currency($totals['unallocated']) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Available for future invoices</span>
        </div>
    </div>

    <!-- Collections Table -->
    <div class="card overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Receipt Date</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Ref / UTR</th>
                        <th class="text-right">Amount Received (₹)</th>
                        <th class="text-right">Allocated (₹)</th>
                        <th class="text-right">Unallocated (₹)</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-500">
                                No collection receipts recorded for the selected period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="hover:bg-slate-50 text-sm">
                                <td class="font-semibold text-slate-900 whitespace-nowrap">
                                    <a href="<?= url('/payments/' . $r['id']) ?>" class="text-brand-600 hover:underline">
                                        <?= e($r['payment_number']) ?>
                                    </a>
                                </td>
                                <td class="text-slate-600 text-xs whitespace-nowrap"><?= format_date($r['payment_date']) ?></td>
                                <td class="font-medium text-slate-800">
                                    <?= e($r['customer_name']) ?>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                        <?= e(strtoupper(str_replace('_', ' ', $r['payment_method']))) ?>
                                    </span>
                                </td>
                                <td class="font-mono text-xs text-slate-500 whitespace-nowrap">
                                    <?= e($r['reference_number'] ?: '—') ?>
                                </td>
                                <td class="text-right font-bold text-slate-900">
                                    <?= format_currency($r['amount']) ?>
                                </td>
                                <td class="text-right text-emerald-600 font-medium">
                                    <?= format_currency($r['allocated_amount']) ?>
                                </td>
                                <td class="text-right text-slate-600">
                                    <?= format_currency($r['unallocated_amount']) ?>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <?php if ($r['status'] === 'posted'): ?>
                                        <span class="badge badge-success">Posted</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Reversed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($rows)): ?>
                    <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900">
                        <tr>
                            <td colspan="5" class="py-3 px-4 text-right uppercase text-xs tracking-wider">Total Active Collections:</td>
                            <td class="text-right py-3 px-4 text-brand-700"><?= format_currency($totals['received']) ?></td>
                            <td class="text-right py-3 px-4 text-emerald-700"><?= format_currency($totals['allocated']) ?></td>
                            <td class="text-right py-3 px-4 text-amber-700"><?= format_currency($totals['unallocated']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
