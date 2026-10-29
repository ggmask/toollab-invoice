<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/payments') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-800">Payments</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900">Record Receipt</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">Record Payment Receipt</h1>
        </div>
        <div class="text-right">
            <span class="text-xs text-slate-500 block">Receipt Voucher #</span>
            <span class="text-base font-mono font-bold text-brand-600"><?= e($nextReceiptNumber) ?></span>
        </div>
    </div>

    <form method="POST" action="<?= url('/payments') ?>" id="paymentForm" class="space-y-6">
        <?= csrf_field() ?>

        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">Payment Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Customer Selection -->
                <div class="md:col-span-1">
                    <label class="form-label" for="customer_id">Customer <span class="text-rose-500">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select" required onchange="handleCustomerChange(this.value)">
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($selectedCustomerId == $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['name']) ?> <?= $c['open_count'] > 0 ? "({$c['open_count']} open)" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Payment Date -->
                <div>
                    <label class="form-label" for="payment_date">Receipt Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="payment_date" id="payment_date" value="<?= e($today) ?>" class="form-input" required>
                </div>

                <!-- Amount Received -->
                <div>
                    <label class="form-label" for="amount">Amount Received (₹) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-medium">₹</span>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-input pl-8 font-semibold text-lg text-slate-900" placeholder="0.00" required oninput="calculateAllocations()">
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="form-label" for="payment_method">Payment Mode <span class="text-rose-500">*</span></label>
                    <select name="payment_method" id="payment_method" class="form-select" required>
                        <?php foreach ($paymentMethods as $mKey => $mLabel): ?>
                            <option value="<?= $mKey ?>" <?= ($mKey === 'upi') ? 'selected' : '' ?>><?= e($mLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reference / Transaction ID -->
                <div>
                    <label class="form-label" for="reference_number">Transaction / Ref #</label>
                    <input type="text" name="reference_number" id="reference_number" placeholder="UTR / UPI Ref / Cheque No" class="form-input">
                </div>

                <!-- Bank Name / Account -->
                <div>
                    <label class="form-label" for="bank_name">Deposited Bank / Account</label>
                    <input type="text" name="bank_name" id="bank_name" value="<?= e($business['bank_name'] ?? '') ?>" placeholder="e.g. HDFC Bank, ICICI Bank" class="form-input">
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="form-label" for="notes">Notes & Remarks</label>
                <textarea name="notes" id="notes" rows="2" class="form-textarea" placeholder="Internal or customer notes..."></textarea>
            </div>
        </div>

        <!-- Invoice Allocation Section -->
        <div class="card p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Allocate to Open Invoices</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Apply received funds against outstanding customer invoices.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="autoAllocate()" class="btn btn-secondary text-xs py-1.5 px-3">
                        <svg class="w-3.5 h-3.5 mr-1 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Auto Allocate
                    </button>
                    <button type="button" onclick="clearAllocations()" class="btn btn-ghost text-xs py-1.5 px-3 text-slate-500 hover:text-rose-600">
                        Clear All
                    </button>
                </div>
            </div>

            <div id="noCustomerNotice" class="<?= $selectedCustomerId ? 'hidden' : '' ?> py-8 text-center text-slate-400 text-sm">
                Select a customer above to load outstanding invoices.
            </div>

            <div id="noInvoicesNotice" class="<?= ($selectedCustomerId && empty($invoices)) ? '' : 'hidden' ?> py-8 text-center text-slate-500 text-sm">
                This customer currently has no unpaid or partially paid invoices. The payment will be retained as unallocated credit.
            </div>

            <div id="invoicesTableWrapper" class="<?= !empty($invoices) ? '' : 'hidden' ?> overflow-x-auto">
                <table class="table" id="invoicesTable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th class="text-right">Total (₹)</th>
                            <th class="text-right">Already Paid (₹)</th>
                            <th class="text-right">Balance Due (₹)</th>
                            <th class="text-right w-48">Allocated Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="invoicesTableBody" class="divide-y divide-slate-200">
                        <?php foreach ($invoices as $inv): ?>
                            <tr data-invoice-id="<?= $inv['id'] ?>" data-balance="<?= $inv['balance_due'] ?>">
                                <td class="font-semibold text-slate-900">
                                    <a href="<?= url('/invoices/' . $inv['id']) ?>" target="_blank" class="text-brand-600 hover:underline">
                                        <?= e($inv['invoice_number']) ?> ↗
                                    </a>
                                </td>
                                <td class="text-slate-600 text-sm"><?= format_date($inv['issue_date']) ?></td>
                                <td class="text-slate-600 text-sm"><?= format_date($inv['due_date']) ?></td>
                                <td class="text-right text-slate-700"><?= format_currency($inv['total_amount']) ?></td>
                                <td class="text-right text-slate-500"><?= format_currency($inv['paid_amount']) ?></td>
                                <td class="text-right font-semibold text-rose-600">
                                    <?= format_currency($inv['balance_due']) ?>
                                </td>
                                <td class="text-right">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs">₹</span>
                                        <input type="number" step="0.01" min="0" max="<?= $inv['balance_due'] ?>"
                                               name="allocations[<?= $inv['id'] ?>]"
                                               value="<?= ($selectedInvoiceId == $inv['id']) ? min((float)$inv['balance_due'], 0.0) : '0.00' ?>"
                                               class="form-input pl-7 text-right text-sm font-medium alloc-input"
                                               oninput="calculateAllocations()">
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Allocation Summary -->
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                <div>
                    <span class="text-xs text-slate-500 block uppercase font-medium">Received Amount</span>
                    <span class="text-lg font-bold text-slate-900" id="summaryReceived">₹0.00</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block uppercase font-medium">Total Allocated</span>
                    <span class="text-lg font-bold text-emerald-600" id="summaryAllocated">₹0.00</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block uppercase font-medium">Unallocated Credit</span>
                    <span class="text-lg font-bold text-slate-700" id="summaryUnallocated">₹0.00</span>
                </div>
            </div>

            <div id="allocationWarning" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-md text-xs text-rose-700 font-medium">
                Warning: Total allocated amount exceeds the received payment amount! Please adjust allocation amounts.
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="<?= url('/payments') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" id="submitBtn" class="btn btn-primary px-6">
                Save & Post Receipt
            </button>
        </div>
    </form>
</div>

<script>
function handleCustomerChange(customerId) {
    if (!customerId) {
        document.getElementById('noCustomerNotice').classList.remove('hidden');
        document.getElementById('noInvoicesNotice').classList.add('hidden');
        document.getElementById('invoicesTableWrapper').classList.add('hidden');
        document.getElementById('invoicesTableBody').innerHTML = '';
        calculateAllocations();
        return;
    }

    // Fetch invoices via JSON endpoint
    fetch('<?= url('/payments/customer-invoices/') ?>' + customerId)
        .then(res => res.json())
        .then(invoices => {
            document.getElementById('noCustomerNotice').classList.add('hidden');
            const tbody = document.getElementById('invoicesTableBody');
            tbody.innerHTML = '';

            if (invoices.length === 0) {
                document.getElementById('noInvoicesNotice').classList.remove('hidden');
                document.getElementById('invoicesTableWrapper').classList.add('hidden');
            } else {
                document.getElementById('noInvoicesNotice').classList.add('hidden');
                document.getElementById('invoicesTableWrapper').classList.remove('hidden');

                invoices.forEach(inv => {
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-invoice-id', inv.id);
                    tr.setAttribute('data-balance', inv.balance_due);
                    tr.innerHTML = `
                        <td class="font-semibold text-slate-900">
                            <a href="<?= url('/invoices/') ?>${inv.id}" target="_blank" class="text-brand-600 hover:underline">
                                ${inv.invoice_number} ↗
                            </a>
                        </td>
                        <td class="text-slate-600 text-sm">${inv.issue_date}</td>
                        <td class="text-slate-600 text-sm">${inv.due_date}</td>
                        <td class="text-right text-slate-700">₹${parseFloat(inv.total_amount).toFixed(2)}</td>
                        <td class="text-right text-slate-500">₹${parseFloat(inv.paid_amount).toFixed(2)}</td>
                        <td class="text-right font-semibold text-rose-600">₹${parseFloat(inv.balance_due).toFixed(2)}</td>
                        <td class="text-right">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs">₹</span>
                                <input type="number" step="0.01" min="0" max="${inv.balance_due}"
                                       name="allocations[${inv.id}]"
                                       value="0.00"
                                       class="form-input pl-7 text-right text-sm font-medium alloc-input"
                                       oninput="calculateAllocations()">
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
            calculateAllocations();
        })
        .catch(err => {
            console.error('Failed to load customer invoices:', err);
        });
}

function calculateAllocations() {
    const totalAmount = parseFloat(document.getElementById('amount').value) || 0;
    const inputs = document.querySelectorAll('.alloc-input');
    let totalAlloc = 0;

    inputs.forEach(input => {
        const val = parseFloat(input.value) || 0;
        totalAlloc += val;
    });

    totalAlloc = Math.round(totalAlloc * 100) / 100;
    const unallocated = Math.max(0, Math.round((totalAmount - totalAlloc) * 100) / 100);

    document.getElementById('summaryReceived').textContent = '₹' + totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summaryAllocated').textContent = '₹' + totalAlloc.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summaryUnallocated').textContent = '₹' + unallocated.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const warning = document.getElementById('allocationWarning');
    const submitBtn = document.getElementById('submitBtn');

    if (totalAlloc > totalAmount + 0.01) {
        warning.classList.remove('hidden');
        submitBtn.disabled = true;
    } else {
        warning.classList.add('hidden');
        submitBtn.disabled = false;
    }
}

function autoAllocate() {
    let remaining = parseFloat(document.getElementById('amount').value) || 0;
    if (remaining <= 0) {
        alert('Please enter an amount received first.');
        document.getElementById('amount').focus();
        return;
    }

    const rows = document.querySelectorAll('#invoicesTableBody tr');
    rows.forEach(tr => {
        const balance = parseFloat(tr.getAttribute('data-balance')) || 0;
        const input = tr.querySelector('.alloc-input');
        if (!input) return;

        if (remaining > 0) {
            const alloc = Math.min(remaining, balance);
            input.value = alloc.toFixed(2);
            remaining -= alloc;
        } else {
            input.value = '0.00';
        }
    });

    calculateAllocations();
}

function clearAllocations() {
    document.querySelectorAll('.alloc-input').forEach(input => input.value = '0.00');
    calculateAllocations();
}

// Initial calculation on load if values present
document.addEventListener('DOMContentLoaded', () => {
    calculateAllocations();
});
</script>
