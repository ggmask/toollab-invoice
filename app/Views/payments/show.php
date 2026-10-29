<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header / Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/payments') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-800">Payments</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900"><?= e($payment['payment_number']) ?></span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-2xl font-bold text-slate-900"><?= e($payment['payment_number']) ?></h1>
                <?php if ($payment['status'] === 'posted'): ?>
                    <span class="badge badge-success">Posted Receipt</span>
                <?php else: ?>
                    <span class="badge badge-danger">Reversed</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= url('/payments/' . $payment['id'] . '/print') ?>" target="_blank" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Voucher
            </a>
            <a href="<?= url('/payments/' . $payment['id'] . '/pdf') ?>" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download PDF
            </a>
            <?php if ($payment['status'] === 'posted'): ?>
                <button type="button" onclick="document.getElementById('reverseModal').classList.remove('hidden')" class="btn btn-danger">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                    </svg>
                    Reverse Receipt
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($payment['status'] === 'reversed'): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 space-y-1">
            <div class="flex items-center gap-2 font-semibold">
                <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                This payment was reversed on <?= format_date($payment['reversed_at']) ?>
            </div>
            <p class="text-sm text-rose-700 pl-7">
                <strong>Reason:</strong> <?= e($payment['reversal_reason']) ?>
            </p>
            <p class="text-xs text-rose-600 pl-7">
                All invoice balances associated with this receipt have been restored.
            </p>
        </div>
    <?php endif; ?>

    <!-- Receipt Voucher Container -->
    <div class="card p-8 space-y-8 bg-white border border-slate-200 shadow-sm">
        <!-- Voucher Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-4">
                <img src="<?= asset('logo.png') ?>" alt="<?= e($business['legal_name']) ?>" class="h-12 w-auto max-w-[140px] object-contain">
                <div>
                    <h2 class="text-lg font-bold text-slate-900"><?= e($business['trade_name'] ?: $business['legal_name']) ?></h2>
                    <p class="text-xs text-slate-500 mt-0.5"><?= e($business['address_line1']) ?>, <?= e($business['city']) ?>, <?= e($business['state']) ?> - <?= e($business['pincode']) ?></p>
                    <p class="text-xs text-slate-500">GSTIN: <span class="font-mono font-medium"><?= e($business['gstin'] ?: 'Unregistered') ?></span></p>
                </div>
            </div>
            <div class="text-left sm:text-right">
                <span class="inline-block px-3 py-1 bg-brand-50 text-brand-700 text-xs font-bold uppercase rounded-md tracking-wider">Payment Receipt Voucher</span>
                <p class="text-xl font-bold font-mono text-slate-900 mt-2"><?= e($payment['payment_number']) ?></p>
                <p class="text-xs text-slate-500 mt-1">Receipt Date: <span class="font-medium text-slate-800"><?= format_date($payment['payment_date']) ?></span></p>
            </div>
        </div>

        <!-- Receipt Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-5 rounded-lg border border-slate-200">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Received From</span>
                <h3 class="text-base font-bold text-slate-900 mt-1">
                    <a href="<?= url('/customers/' . $payment['customer_id']) ?>" class="hover:text-brand-600">
                        <?= e($payment['customer_name']) ?>
                    </a>
                </h3>
                <?php if ($payment['customer_gstin']): ?>
                    <p class="text-xs text-slate-600 mt-1">GSTIN: <span class="font-mono font-medium"><?= e($payment['customer_gstin']) ?></span></p>
                <?php endif; ?>
                <?php if ($payment['billing_address']): ?>
                    <p class="text-xs text-slate-500 mt-1"><?= e($payment['billing_address']) ?>, <?= e($payment['billing_city']) ?>, <?= e($payment['billing_state']) ?> - <?= e($payment['billing_pincode']) ?></p>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Payment Mode</span>
                    <span class="text-sm font-semibold text-slate-800 mt-1 block">
                        <?= e(strtoupper(str_replace('_', ' ', $payment['payment_method']))) ?>
                    </span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Reference / UTR</span>
                    <span class="text-sm font-mono text-slate-800 mt-1 block">
                        <?= e($payment['reference_number'] ?: '—') ?>
                    </span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Deposited Bank</span>
                    <span class="text-sm text-slate-800 mt-1 block">
                        <?= e($payment['bank_name'] ?: '—') ?>
                    </span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Amount Received</span>
                    <span class="text-base font-bold text-emerald-600 mt-1 block">
                        <?= format_currency($payment['amount']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Amount In Words -->
        <div class="border-l-4 border-brand-500 pl-4 py-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Amount in Words</span>
            <p class="text-sm font-medium text-slate-800 italic mt-0.5">
                <?= e(App\Support\NumberToWords::toWords((float)$payment['amount'])) ?> Only
            </p>
        </div>

        <!-- Allocation Breakdown -->
        <div>
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Invoice Allocations</h3>
            <div class="table-container border border-slate-200 rounded-lg">
                <table class="table">
                    <thead>
                        <tr class="bg-slate-50">
                            <th>Invoice #</th>
                            <th>Issue Date</th>
                            <th class="text-right">Total Invoice (₹)</th>
                            <th class="text-right">Allocated Amount (₹)</th>
                            <th class="text-right">Current Remaining (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($allocations)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-slate-500 text-sm italic">
                                    No direct invoice allocations. Entire amount is held as unallocated credit.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allocations as $a): ?>
                                <tr>
                                    <td class="font-semibold text-slate-900">
                                        <a href="<?= url('/invoices/' . $a['invoice_id']) ?>" class="text-brand-600 hover:underline">
                                            <?= e($a['invoice_number']) ?>
                                        </a>
                                    </td>
                                    <td class="text-slate-600 text-sm"><?= format_date($a['issue_date']) ?></td>
                                    <td class="text-right text-slate-700"><?= format_currency($a['total_amount']) ?></td>
                                    <td class="text-right font-bold text-emerald-600"><?= format_currency($a['amount']) ?></td>
                                    <td class="text-right text-slate-600"><?= format_currency($a['current_balance']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-slate-50 font-semibold border-t border-slate-200">
                        <tr>
                            <td colspan="3" class="text-right py-2 text-slate-700">Total Allocated:</td>
                            <td class="text-right py-2 text-emerald-600 font-bold"><?= format_currency($payment['allocated_amount']) ?></td>
                            <td></td>
                        </tr>
                        <?php if ((float)$payment['unallocated_amount'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-right py-2 text-amber-700">Unallocated Advance Balance:</td>
                                <td class="text-right py-2 text-amber-700 font-bold"><?= format_currency($payment['unallocated_amount']) ?></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if (!empty($payment['notes'])): ?>
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Notes</span>
                <p class="text-sm text-slate-700 mt-1 whitespace-pre-line"><?= e($payment['notes']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Signature -->
        <div class="flex justify-between items-end pt-8 border-t border-slate-200">
            <div class="text-xs text-slate-400">
                <p>This is a computer-generated payment voucher.</p>
                <p>System Record ID: <?= e($payment['uuid']) ?></p>
            </div>
            <div class="text-center w-56">
                <div class="h-16 border-b border-dashed border-slate-300"></div>
                <p class="text-xs font-bold text-slate-800 mt-2"><?= e($business['trade_name'] ?: $business['legal_name']) ?></p>
                <p class="text-[11px] text-slate-500"><?= e($business['signature_title'] ?: 'Authorized Signatory') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Reversal Confirmation Modal -->
<?php if ($payment['status'] === 'posted'): ?>
    <div id="reverseModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="card max-w-md w-full p-6 space-y-4 shadow-xl">
            <div class="flex items-center gap-3 text-rose-600">
                <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Reverse Payment Receipt</h3>
                    <p class="text-xs text-slate-500">This action will unapply funds and restore invoice balances.</p>
                </div>
            </div>

            <form method="POST" action="<?= url('/payments/' . $payment['id'] . '/reverse') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label" for="reversal_reason">Reason for Reversal <span class="text-rose-500">*</span></label>
                    <textarea name="reversal_reason" id="reversal_reason" rows="3" class="form-textarea" required placeholder="e.g. Cheque bounced, entered incorrect customer account, duplicate entry..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('reverseModal').classList.add('hidden')" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        Confirm Reversal
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
