<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/reports') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reports</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900">Sales Register</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">Sales Register Report</h1>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
            <?php 
                $queryParams = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
                    'customer_id' => $customerId,
                ];
                $csvUrl = url('/reports/sales?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
                $excelUrl = url('/reports/sales?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
        <form method="GET" action="<?= url('/reports/sales') ?>" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label class="form-label text-xs">From Date</label>
                <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-input text-sm">
            </div>

            <div>
                <label class="form-label text-xs">To Date</label>
                <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-input text-sm">
            </div>

            <div>
                <label class="form-label text-xs">Customer</label>
                <select name="customer_id" class="form-select text-sm">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ((string)$customerId === (string)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label text-xs">Status</label>
                <select name="status" class="form-select text-sm">
                    <option value="">Active (Issued / Paid / Partial)</option>
                    <option value="paid" <?= ($status === 'paid') ? 'selected' : '' ?>>Fully Paid</option>
                    <option value="partially_paid" <?= ($status === 'partially_paid') ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="issued" <?= ($status === 'issued') ? 'selected' : '' ?>>Issued / Unpaid</option>
                    <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Drafts</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary text-sm h-10 w-full justify-center">
                    Generate
                </button>
            </div>
        </form>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Taxable Sales</span>
            <span class="text-xl font-bold text-slate-900 mt-1 block"><?= format_currency($totals['taxable']) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Net base turnover</span>
        </div>

        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total GST</span>
            <span class="text-xl font-bold text-purple-600 mt-1 block"><?= format_currency($totals['cgst'] + $totals['sgst'] + $totals['igst']) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">CGST+SGST: <?= format_currency($totals['cgst'] + $totals['sgst']) ?></span>
        </div>

        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Gross Invoiced</span>
            <span class="text-xl font-bold text-brand-600 mt-1 block"><?= format_currency($totals['total']) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Total invoice value</span>
        </div>

        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Realized / Paid</span>
            <span class="text-xl font-bold text-emerald-600 mt-1 block"><?= format_currency($totals['paid']) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Collections received</span>
        </div>

        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Outstanding</span>
            <span class="text-xl font-bold text-rose-600 mt-1 block"><?= format_currency($totals['balance']) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Pending receivables</span>
        </div>
    </div>

    <!-- Register Table -->
    <div class="card overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>GSTIN</th>
                        <th class="text-right">Taxable (₹)</th>
                        <th class="text-right">CGST (₹)</th>
                        <th class="text-right">SGST (₹)</th>
                        <th class="text-right">IGST (₹)</th>
                        <th class="text-right">Total (₹)</th>
                        <th class="text-right">Paid (₹)</th>
                        <th class="text-right">Balance Due (₹)</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="12" class="text-center py-10 text-slate-500">
                                No sales invoices found for the selected period and criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="font-semibold text-slate-900 whitespace-nowrap">
                                    <a href="<?= url('/invoices/' . $r['id']) ?>" class="text-brand-600 hover:underline">
                                        <?= e($r['invoice_number']) ?>
                                    </a>
                                </td>
                                <td class="text-slate-600 text-xs whitespace-nowrap"><?= format_date($r['issue_date']) ?></td>
                                <td class="font-medium text-slate-800 text-xs">
                                    <?= e($r['customer_name']) ?>
                                </td>
                                <td class="font-mono text-xs text-slate-500 whitespace-nowrap">
                                    <?= e($r['customer_gstin'] ?: 'Unregistered') ?>
                                </td>
                                <td class="text-right text-xs text-slate-700"><?= format_currency($r['taxable_value']) ?></td>
                                <td class="text-right text-xs text-slate-600"><?= format_currency($r['cgst_amount']) ?></td>
                                <td class="text-right text-xs text-slate-600"><?= format_currency($r['sgst_amount']) ?></td>
                                <td class="text-right text-xs text-slate-600"><?= format_currency($r['igst_amount']) ?></td>
                                <td class="text-right text-xs font-bold text-slate-900"><?= format_currency($r['total_amount']) ?></td>
                                <td class="text-right text-xs text-emerald-600 font-medium"><?= format_currency($r['paid_amount']) ?></td>
                                <td class="text-right text-xs font-semibold <?= ((float)$r['balance_due'] > 0) ? 'text-rose-600' : 'text-slate-400' ?>">
                                    <?= format_currency($r['balance_due']) ?>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <span class="badge badge-<?= ($r['status'] === 'paid') ? 'success' : (($r['status'] === 'partially_paid') ? 'warning' : 'primary') ?>">
                                        <?= e(ucfirst(str_replace('_', ' ', $r['status']))) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($rows)): ?>
                    <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900">
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-right uppercase text-xs tracking-wider">Total Summary:</td>
                            <td class="text-right py-3 px-4 text-xs"><?= format_currency($totals['taxable']) ?></td>
                            <td class="text-right py-3 px-4 text-xs"><?= format_currency($totals['cgst']) ?></td>
                            <td class="text-right py-3 px-4 text-xs"><?= format_currency($totals['sgst']) ?></td>
                            <td class="text-right py-3 px-4 text-xs"><?= format_currency($totals['igst']) ?></td>
                            <td class="text-right py-3 px-4 text-xs text-brand-700"><?= format_currency($totals['total']) ?></td>
                            <td class="text-right py-3 px-4 text-xs text-emerald-700"><?= format_currency($totals['paid']) ?></td>
                            <td class="text-right py-3 px-4 text-xs text-rose-700"><?= format_currency($totals['balance']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
