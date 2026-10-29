<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/reports') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reports</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900">GSTR-1 Sales Register</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">GST Sales Register (GSTR-1 Ready)</h1>
            <p class="text-sm text-slate-500">Itemized line-by-line GST sales breakdown formatted for monthly and quarterly GSTR-1 return filing.</p>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
            <?php 
                $queryParams = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ];
                $csvUrl = url('/reports/gst-sales?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
                $excelUrl = url('/reports/gst-sales?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
            ?>
            <a href="<?= $csvUrl ?>" class="btn btn-secondary text-xs">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="<?= $excelUrl ?>" class="btn btn-secondary text-xs">
                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export GSTR-1 Excel
            </a>
        </div>
    </div>

    <!-- Date Filter Form -->
    <div class="card p-4">
        <form method="GET" action="<?= url('/reports/gst-sales') ?>" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="form-label text-xs">Filing Period From</label>
                <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-input text-sm">
            </div>

            <div>
                <label class="form-label text-xs">Filing Period To</label>
                <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-input text-sm">
            </div>

            <button type="submit" class="btn btn-primary text-sm h-10 px-4">
                Update Report
            </button>
        </form>
    </div>

    <?php
        $sumTaxable = 0.0;
        $sumCgst = 0.0;
        $sumSgst = 0.0;
        $sumIgst = 0.0;
        $sumTotal = 0.0;
        $b2bCount = 0;
        $b2cCount = 0;

        foreach ($rows as $r) {
            $sumTaxable += (float)$r['taxable_value'];
            $sumCgst += (float)$r['cgst_amount'];
            $sumSgst += (float)$r['sgst_amount'];
            $sumIgst += (float)$r['igst_amount'];
            $sumTotal += (float)$r['line_total'];
            if (!empty($r['customer_gstin'])) {
                $b2bCount++;
            } else {
                $b2cCount++;
            }
        }
    ?>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Taxable Value</span>
            <span class="text-xl font-bold text-slate-900 mt-1 block"><?= format_currency($sumTaxable) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Total assessed base</span>
        </div>
        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total CGST + SGST</span>
            <span class="text-xl font-bold text-brand-600 mt-1 block"><?= format_currency($sumCgst + $sumSgst) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Intra-state tax (50% : 50%)</span>
        </div>
        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total IGST</span>
            <span class="text-xl font-bold text-purple-600 mt-1 block"><?= format_currency($sumIgst) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Inter-state integrated tax</span>
        </div>
        <div class="card p-4 bg-white border border-slate-200">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Gross Invoiced</span>
            <span class="text-xl font-bold text-emerald-600 mt-1 block"><?= format_currency($sumTotal) ?></span>
            <span class="text-xs text-slate-400 mt-1 block">Inclusive of all taxes</span>
        </div>
    </div>

    <!-- Line Item Register Table -->
    <div class="card overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Recipient & GSTIN</th>
                        <th>POS</th>
                        <th>HSN/SAC</th>
                        <th>Item Description</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Taxable (₹)</th>
                        <th class="text-center">Rate</th>
                        <th class="text-right">CGST (₹)</th>
                        <th class="text-right">SGST (₹)</th>
                        <th class="text-right">IGST (₹)</th>
                        <th class="text-right">Total (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="14" class="text-center py-10 text-slate-500 text-sm">
                                No taxable sales line items recorded for this filing window.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="font-semibold text-slate-900 whitespace-nowrap">
                                    <?= e($r['invoice_number']) ?>
                                </td>
                                <td class="text-slate-600 whitespace-nowrap"><?= format_date($r['issue_date']) ?></td>
                                <td>
                                    <?php if (!empty($r['customer_gstin'])): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">B2B</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">B2C</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-medium text-slate-800"><?= e($r['customer_name']) ?></div>
                                    <div class="font-mono text-slate-500 text-[11px]"><?= e($r['customer_gstin'] ?: 'Unregistered') ?></div>
                                </td>
                                <td class="whitespace-nowrap text-slate-600">
                                    <?= e($r['place_of_supply_state_code'] ? $r['place_of_supply_state_code'] . '-' : '') ?><?= e($r['place_of_supply_state']) ?>
                                </td>
                                <td class="font-mono font-medium text-slate-700 whitespace-nowrap"><?= e($r['hsn_sac']) ?></td>
                                <td class="text-slate-700 max-w-xs truncate" title="<?= e($r['item_name']) ?>"><?= e($r['item_name']) ?></td>
                                <td class="text-right text-slate-600 whitespace-nowrap"><?= (float)$r['quantity'] ?> <?= e($r['unit']) ?></td>
                                <td class="text-right text-slate-800 font-medium whitespace-nowrap"><?= format_currency($r['taxable_value']) ?></td>
                                <td class="text-center font-semibold text-slate-700 whitespace-nowrap"><?= (float)$r['gst_rate'] ?>%</td>
                                <td class="text-right text-slate-600 whitespace-nowrap"><?= format_currency($r['cgst_amount']) ?></td>
                                <td class="text-right text-slate-600 whitespace-nowrap"><?= format_currency($r['sgst_amount']) ?></td>
                                <td class="text-right text-slate-600 whitespace-nowrap"><?= format_currency($r['igst_amount']) ?></td>
                                <td class="text-right font-bold text-slate-900 whitespace-nowrap"><?= format_currency($r['line_total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($rows)): ?>
                    <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900 text-xs">
                        <tr>
                            <td colspan="8" class="py-3 px-4 text-right uppercase tracking-wider">Total Liability:</td>
                            <td class="text-right py-3 px-4"><?= format_currency($sumTaxable) ?></td>
                            <td></td>
                            <td class="text-right py-3 px-4"><?= format_currency($sumCgst) ?></td>
                            <td class="text-right py-3 px-4"><?= format_currency($sumSgst) ?></td>
                            <td class="text-right py-3 px-4"><?= format_currency($sumIgst) ?></td>
                            <td class="text-right py-3 px-4 text-brand-700"><?= format_currency($sumTotal) ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
