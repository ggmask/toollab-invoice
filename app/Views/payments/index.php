<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Payments & Receipts</h1>
            <p class="text-sm text-slate-500 mt-1">Track incoming customer collections, allocations against invoices, and payment vouchers.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/reports/payments') ?>" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Collections Report
            </a>
            <a href="<?= url('/payments/create') ?>" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Record Payment
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card p-4">
        <form method="GET" action="<?= url('/payments') ?>" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="form-label text-xs">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Receipt #, Ref, Customer..." class="form-input pl-9 text-sm">
                </div>
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
                <label class="form-label text-xs">Payment Method</label>
                <select name="method" class="form-select text-sm">
                    <option value="">All Methods</option>
                    <?php foreach ($paymentMethods as $mKey => $mLabel): ?>
                        <option value="<?= $mKey ?>" <?= ($method === $mKey) ? 'selected' : '' ?>>
                            <?= e($mLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="form-label text-xs">Status</label>
                    <select name="status" class="form-select text-sm">
                        <option value="">All Statuses</option>
                        <option value="posted" <?= ($status === 'posted') ? 'selected' : '' ?>>Posted</option>
                        <option value="reversed" <?= ($status === 'reversed') ? 'selected' : '' ?>>Reversed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary text-sm h-10 px-3">Filter</button>
                <?php if ($search || $method || $status || $customerId): ?>
                    <a href="<?= url('/payments') ?>" class="btn btn-ghost text-sm h-10 px-2 text-slate-400 hover:text-slate-600" title="Clear Filters">
                        ✕
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="card overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Ref / Bank</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Allocated</th>
                        <th class="text-right">Unallocated</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-12 text-slate-500">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <p class="text-base font-semibold text-slate-800">No payment receipts found</p>
                                <p class="text-sm text-slate-500 mt-1">Record a new payment to update invoice balances.</p>
                                <a href="<?= url('/payments/create') ?>" class="btn btn-primary mt-4 inline-flex">
                                    Record Payment
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="font-semibold text-slate-900">
                                    <a href="<?= url('/payments/' . $p['id']) ?>" class="text-brand-600 hover:text-brand-700 hover:underline">
                                        <?= e($p['payment_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= url('/customers/' . $p['customer_id']) ?>" class="font-medium text-slate-800 hover:text-brand-600">
                                        <?= e($p['customer_name']) ?>
                                    </a>
                                </td>
                                <td class="text-slate-600 text-sm whitespace-nowrap">
                                    <?= format_date($p['payment_date']) ?>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                        <?= e(strtoupper(str_replace('_', ' ', $p['payment_method']))) ?>
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    <div><?= e($p['reference_number'] ?: '—') ?></div>
                                    <?php if (!empty($p['bank_name'])): ?>
                                        <div class="text-slate-400 text-[11px]"><?= e($p['bank_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-bold text-slate-900">
                                    <?= format_currency($p['amount']) ?>
                                </td>
                                <td class="text-right text-emerald-600 font-medium">
                                    <?= format_currency($p['allocated_amount']) ?>
                                </td>
                                <td class="text-right text-slate-500">
                                    <?php if ((float)$p['unallocated_amount'] > 0): ?>
                                        <span class="text-amber-600 font-medium"><?= format_currency($p['unallocated_amount']) ?></span>
                                    <?php else: ?>
                                        <span>₹0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($p['status'] === 'posted'): ?>
                                        <span class="badge badge-success">Posted</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Reversed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= url('/payments/' . $p['id']) ?>" class="p-1.5 text-slate-400 hover:text-brand-600 rounded" title="View Voucher">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <a href="<?= url('/payments/' . $p['id'] . '/pdf') ?>" class="p-1.5 text-slate-400 hover:text-slate-700 rounded" title="Download PDF">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="p-4 border-t border-slate-200">
                <?= \App\Support\View::component('pagination', [
                    'page' => $page,
                    'totalPages' => $totalPages,
                    'baseUrl' => url('/payments'),
                    'queryParams' => array_filter([
                        'search' => $search,
                        'customer_id' => $customerId,
                        'method' => $method,
                        'status' => $status
                    ])
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
