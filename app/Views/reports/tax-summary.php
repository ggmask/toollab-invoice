<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/reports') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reports</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900">Tax Liability</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">GST Tax Liability Summary</h1>
            <p class="text-sm text-slate-500">Output tax liability grouped by GST tax rate slabs (0%, 5%, 12%, 18%, 28%) for GSTR-3B preparation.</p>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
            <?php 
                $queryParams = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ];
                $csvUrl = url('/reports/tax-summary?' . http_build_query(array_merge($queryParams, ['export' => 'csv'])));
                $excelUrl = url('/reports/tax-summary?' . http_build_query(array_merge($queryParams, ['export' => 'excel'])));
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
        <form method="GET" action="<?= url('/reports/tax-summary') ?>" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="form-label text-xs">Tax Period From</label>
                <input type="date" name="start_date" value="<?= e($startDate) ?>" class="form-input text-sm">
            </div>

            <div>
                <label class="form-label text-xs">Tax Period To</label>
                <input type="date" name="end_date" value="<?= e($endDate) ?>" class="form-input text-sm">
            </div>

            <button type="submit" class="btn btn-primary text-sm h-10 px-4">
                Update Summary
            </button>
        </form>
    </div>

    <?php
        $totTaxable = 0.0;
        $totCgst = 0.0;
        $totSgst = 0.0;
        $totIgst = 0.0;
        $totCess = 0.0;
        $totTax = 0.0;
        $totGrand = 0.0;

        foreach ($rows as $r) {
            $totTaxable += (float)$r['total_taxable'];
            $totCgst += (float)$r['total_cgst'];
            $totSgst += (float)$r['total_sgst'];
            $totIgst += (float)$r['total_igst'];
            $totCess += (float)$r['total_cess'];
            $totTax += (float)$r['total_tax'];
            $totGrand += (float)$r['total_amount'];
        }
    ?>

    <!-- Slabs Table -->
    <div class="card overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tax Slab</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-right">Taxable Turnover (₹)</th>
                        <th class="text-right">Central GST / CGST (₹)</th>
                        <th class="text-right">State GST / SGST (₹)</th>
                        <th class="text-right">Integrated GST / IGST (₹)</th>
                        <th class="text-right">Cess (₹)</th>
                        <th class="text-right">Total Tax Liability (₹)</th>
                        <th class="text-right">Gross Turnover (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-500">
                                No taxable sales recorded for this period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="hover:bg-slate-50 text-sm">
                                <td class="font-bold text-slate-900 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-800">
                                        GST <?= (float)$r['gst_rate'] ?>% Slab
                                    </span>
                                </td>
                                <td class="text-center text-slate-600 font-medium">
                                    <?= (int)$r['invoice_count'] ?>
                                </td>
                                <td class="text-right font-medium text-slate-900">
                                    <?= format_currency($r['total_taxable']) ?>
                                </td>
                                <td class="text-right text-slate-700">
                                    <?= format_currency($r['total_cgst']) ?>
                                </td>
                                <td class="text-right text-slate-700">
                                    <?= format_currency($r['total_sgst']) ?>
                                </td>
                                <td class="text-right text-slate-700">
                                    <?= format_currency($r['total_igst']) ?>
                                </td>
                                <td class="text-right text-slate-500">
                                    <?= format_currency($r['total_cess']) ?>
                                </td>
                                <td class="text-right font-bold text-purple-700">
                                    <?= format_currency($r['total_tax']) ?>
                                </td>
                                <td class="text-right font-bold text-slate-900">
                                    <?= format_currency($r['total_amount']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($rows)): ?>
                    <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900">
                        <tr>
                            <td colspan="2" class="py-3.5 px-4 text-right uppercase text-xs tracking-wider">Total Output Liability:</td>
                            <td class="text-right py-3.5 px-4"><?= format_currency($totTaxable) ?></td>
                            <td class="text-right py-3.5 px-4"><?= format_currency($totCgst) ?></td>
                            <td class="text-right py-3.5 px-4"><?= format_currency($totSgst) ?></td>
                            <td class="text-right py-3.5 px-4"><?= format_currency($totIgst) ?></td>
                            <td class="text-right py-3.5 px-4"><?= format_currency($totCess) ?></td>
                            <td class="text-right py-3.5 px-4 text-purple-700 text-base"><?= format_currency($totTax) ?></td>
                            <td class="text-right py-3.5 px-4 text-slate-900 text-base"><?= format_currency($totGrand) ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- GSTR-3B Guidance Note -->
    <div class="card p-5 bg-purple-50/60 border border-purple-200 rounded-lg text-xs text-purple-900 space-y-2">
        <h4 class="font-bold flex items-center gap-1.5 text-purple-950 text-sm">
            <svg class="w-4 h-4 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            GSTR-3B Table 3.1 Filing Reference
        </h4>
        <p>
            Use the values from this summary to populate <strong>Section 3.1(a) — Outward taxable supplies (other than zero rated, nil rated and exempted)</strong> in your monthly or quarterly GSTR-3B filing.
        </p>
        <p>
            Remember that Intra-State sales incur equal splits of <strong>CGST (50%)</strong> and <strong>SGST (50%)</strong>, while Inter-State sales attract <strong>IGST (100%)</strong>. Input Tax Credits (ITC) can be offset against these output liabilities as per GST council sequencing rules.
        </p>
    </div>
</div>
