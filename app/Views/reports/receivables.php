<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/reports') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reports</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900">Ageing Analysis</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">Outstanding Receivables & Ageing</h1>
            <p class="text-sm text-slate-500">Monitor unpaid client receivables, overdue aging brackets, and follow up on pending payments.</p>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
            <?php 
                $queryParams = [
                    'as_of_date' => $asOfDate,
                    'customer_id' => $customerId,
                ];
                $csvUrl = url('/reports/receivables?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
                $excelUrl = url('/reports/receivables?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
        <form method="GET" action="<?= url('/reports/receivables') ?>" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="form-label text-xs">As of Date</label>
                <input type="date" name="as_of_date" value="<?= e($asOfDate) ?>" class="form-input text-sm">
            </div>

            <div>
                <label class="form-label text-xs">Filter Customer</label>
                <select name="customer_id" class="form-select text-sm">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ((string)$customerId === (string)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-primary text-sm h-10 w-full justify-center">
                    Calculate Ageing
                </button>
            </div>
        </form>
    </div>

    <!-- Ageing Bracket Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="card p-4 border-l-4 border-l-emerald-500">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Not Yet Due</span>
            <span class="text-lg font-bold text-slate-900 mt-1 block"><?= format_currency($buckets['not_due']) ?></span>
            <span class="text-[10px] text-emerald-600 font-medium">Within terms</span>
        </div>

        <div class="card p-4 border-l-4 border-l-blue-500">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">1–30 Days</span>
            <span class="text-lg font-bold text-slate-900 mt-1 block"><?= format_currency($buckets['1_30']) ?></span>
            <span class="text-[10px] text-blue-600 font-medium">Mild overdue</span>
        </div>

        <div class="card p-4 border-l-4 border-l-amber-500">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">31–60 Days</span>
            <span class="text-lg font-bold text-slate-900 mt-1 block"><?= format_currency($buckets['31_60']) ?></span>
            <span class="text-[10px] text-amber-600 font-medium">Follow up</span>
        </div>

        <div class="card p-4 border-l-4 border-l-orange-500">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">61–90 Days</span>
            <span class="text-lg font-bold text-slate-900 mt-1 block"><?= format_currency($buckets['61_90']) ?></span>
            <span class="text-[10px] text-orange-600 font-medium">Escalate</span>
        </div>

        <div class="card p-4 border-l-4 border-l-rose-500">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">90+ Days</span>
            <span class="text-lg font-bold text-rose-600 mt-1 block"><?= format_currency($buckets['90_plus']) ?></span>
            <span class="text-[10px] text-rose-600 font-medium">High risk</span>
        </div>

        <div class="card p-4 bg-slate-900 text-white border-l-4 border-l-brand-400">
            <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider block">Total Outstanding</span>
            <span class="text-lg font-bold text-white mt-1 block"><?= format_currency($buckets['total']) ?></span>
            <span class="text-[10px] text-slate-400 font-medium">All pending debt</span>
        </div>
    </div>

    <!-- Outstanding Invoices Ageing Table -->
    <div class="card overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Invoice #</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th class="text-center">Days Overdue</th>
                        <th class="text-center">Ageing Bracket</th>
                        <th class="text-right">Invoice Total (₹)</th>
                        <th class="text-right">Paid Amount (₹)</th>
                        <th class="text-right">Balance Due (₹)</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-10 text-slate-500">
                                Fantastic! There are no outstanding or overdue receivables as of <?= format_date($asOfDate) ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <?php 
                                $days = (int)$r['days_overdue'];
                                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                if ($days > 90) {
                                    $badgeClass = 'bg-rose-50 text-rose-700 border-rose-300';
                                } elseif ($days > 60) {
                                    $badgeClass = 'bg-orange-50 text-orange-700 border-orange-300';
                                } elseif ($days > 30) {
                                    $badgeClass = 'bg-amber-50 text-amber-700 border-amber-300';
                                } elseif ($days > 0) {
                                    $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                }
                            ?>
                            <tr class="hover:bg-slate-50 text-sm">
                                <td>
                                    <div class="font-bold text-slate-900">
                                        <a href="<?= url('/customers/' . $r['customer_id']) ?>" class="hover:text-brand-600">
                                            <?= e($r['customer_name']) ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($r['customer_phone'])): ?>
                                        <div class="text-xs text-slate-500 mt-0.5">📞 <?= e($r['customer_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="font-semibold text-slate-900 whitespace-nowrap">
                                    <a href="<?= url('/invoices/' . $r['id']) ?>" class="text-brand-600 hover:underline">
                                        <?= e($r['invoice_number']) ?>
                                    </a>
                                </td>
                                <td class="text-slate-600 text-xs whitespace-nowrap"><?= format_date($r['issue_date']) ?></td>
                                <td class="text-slate-600 text-xs whitespace-nowrap"><?= format_date($r['due_date']) ?></td>
                                <td class="text-center whitespace-nowrap">
                                    <?php if ($days > 0): ?>
                                        <span class="font-bold text-rose-600"><?= $days ?> days</span>
                                    <?php else: ?>
                                        <span class="text-slate-500 font-medium">In <?= abs($days) ?> days</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border <?= $badgeClass ?>">
                                        <?= e($r['bucket']) ?>
                                    </span>
                                </td>
                                <td class="text-right text-slate-700"><?= format_currency($r['total_amount']) ?></td>
                                <td class="text-right text-slate-500"><?= format_currency($r['paid_amount']) ?></td>
                                <td class="text-right font-bold text-rose-600">
                                    <?= format_currency($r['balance_due']) ?>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="<?= url('/payments/create?customer_id=' . $r['customer_id'] . '&invoice_id=' . $r['id']) ?>" class="btn btn-primary text-xs py-1 px-2.5">
                                        Collect
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($rows)): ?>
                    <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900">
                        <tr>
                            <td colspan="8" class="py-3 px-4 text-right uppercase text-xs tracking-wider">Total Overdue Receivables:</td>
                            <td class="text-right py-3 px-4 text-rose-700 text-base"><?= format_currency($buckets['total']) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
