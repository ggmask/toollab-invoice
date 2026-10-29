<div class="page-header">
  <div>
    <h1 class="page-title">Record Payment Receipt</h1>
    <div class="page-description">Record incoming client remittance, select payment mode, and allocate across open invoices.</div>
  </div>

  <div class="header-actions">
    <div style="text-align: right; margin-right: 12px;">
      <span style="font-size: 11px; color: var(--slate-500); display: block; text-transform: uppercase; font-weight: 600;">Receipt Voucher #</span>
      <span style="font-family: monospace; font-size: 15px; font-weight: 700; color: var(--primary);"><?= e($nextReceiptNumber) ?></span>
    </div>
    <a href="<?= url('/payments') ?>" class="btn btn-secondary btn-sm">
      &larr; Back to Payments
    </a>
  </div>
</div>

<form method="POST" action="<?= url('/payments') ?>" id="paymentForm">
  <?= csrf_field() ?>

  <!-- 1. Payment Information -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. Payment Details</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label" for="customer_id">Customer <span style="color: var(--danger);">*</span></label>
          <select name="customer_id" id="customer_id" class="form-control" required onchange="handleCustomerChange(this.value)">
            <option value="">-- Select Customer --</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($selectedCustomerId == $c['id']) ? 'selected' : '' ?>>
                <?= e($c['name']) ?> <?= $c['open_count'] > 0 ? "({$c['open_count']} open invoices)" : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="payment_date">Receipt Date <span style="color: var(--danger);">*</span></label>
          <input type="date" name="payment_date" id="payment_date" value="<?= e($today) ?>" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="amount">Amount Received (₹) <span style="color: var(--danger);">*</span></label>
          <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" placeholder="0.00" required oninput="calculateAllocations()" style="font-size: 16px; font-weight: 700; color: var(--slate-900);">
        </div>

        <div class="form-group">
          <label class="form-label" for="payment_method">Payment Mode <span style="color: var(--danger);">*</span></label>
          <select name="payment_method" id="payment_method" class="form-control" required>
            <?php foreach ($paymentMethods as $mKey => $mLabel): ?>
              <option value="<?= $mKey ?>" <?= ($mKey === 'upi') ? 'selected' : '' ?>><?= e($mLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="reference_number">Transaction / Ref # (UTR / Cheque No)</label>
          <input type="text" name="reference_number" id="reference_number" placeholder="e.g. UTR12345678, UPI Ref" class="form-control">
        </div>

        <div class="form-group">
          <label class="form-label" for="bank_name">Deposited Bank / Account</label>
          <input type="text" name="bank_name" id="bank_name" value="<?= e($business['bank_name'] ?? '') ?>" placeholder="e.g. HDFC Bank, ICICI Bank" class="form-control">
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
          <label class="form-label" for="notes">Notes & Internal Remarks</label>
          <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Optional internal or client payment notes..."></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Invoice Allocations -->
  <div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h2 class="card-title">2. Allocate to Open Invoices</h2>
      <div style="display: flex; gap: 8px;">
        <button type="button" onclick="autoAllocate()" class="btn btn-secondary btn-sm">
          Auto Allocate
        </button>
        <button type="button" onclick="clearAllocations()" class="btn btn-secondary btn-sm" style="color: var(--danger);">
          Clear All
        </button>
      </div>
    </div>
    <div class="card-body">
      <div id="noCustomerNotice" class="<?= $selectedCustomerId ? 'hidden' : '' ?>" style="text-align: center; padding: 32px 16px; color: var(--slate-500);">
        Select a customer above to view and allocate against outstanding invoices.
      </div>

      <div id="noInvoicesNotice" class="<?= ($selectedCustomerId && empty($invoices)) ? '' : 'hidden' ?>" style="text-align: center; padding: 32px 16px; color: var(--slate-500);">
        This customer currently has no unpaid or partially paid invoices. The payment will be retained as unallocated credit.
      </div>

      <div id="invoicesTableWrapper" class="<?= !empty($invoices) ? '' : 'hidden' ?> table-responsive" style="margin-bottom: 20px;">
        <table class="table" id="invoicesTable">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Issue Date</th>
              <th>Due Date</th>
              <th class="text-right">Total Invoice (₹)</th>
              <th class="text-right">Paid Amount (₹)</th>
              <th class="text-right">Balance Due (₹)</th>
              <th class="text-right" style="width: 180px;">Allocated Amount (₹)</th>
            </tr>
          </thead>
          <tbody id="invoicesTableBody">
            <?php foreach ($invoices as $inv): ?>
              <tr data-invoice-id="<?= $inv['id'] ?>" data-balance="<?= $inv['balance_due'] ?>">
                <td style="font-weight: 600;">
                  <a href="<?= url('/invoices/' . $inv['id']) ?>" target="_blank" style="color: var(--primary);">
                    <?= e($inv['invoice_number']) ?> ↗
                  </a>
                </td>
                <td style="font-size: 12px; color: var(--slate-500);"><?= format_date($inv['issue_date']) ?></td>
                <td style="font-size: 12px; color: var(--slate-500);"><?= format_date($inv['due_date']) ?></td>
                <td class="text-right"><?= format_currency($inv['total_amount']) ?></td>
                <td class="text-right" style="color: var(--slate-500);"><?= format_currency($inv['paid_amount']) ?></td>
                <td class="text-right" style="font-weight: 700; color: var(--danger);">
                  <?= format_currency($inv['balance_due']) ?>
                </td>
                <td class="text-right">
                  <input type="number" step="0.01" min="0" max="<?= $inv['balance_due'] ?>"
                         name="allocations[<?= $inv['id'] ?>]"
                         value="<?= ($selectedInvoiceId == $inv['id']) ? min((float)$inv['balance_due'], 0.0) : '0.00' ?>"
                         class="form-control form-control-sm alloc-input"
                         style="text-align: right; font-weight: 600;"
                         oninput="calculateAllocations()">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Allocation Summary Grid -->
      <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius); padding: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; text-align: center;">
        <div>
          <span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--slate-500); display: block;">Received Amount</span>
          <span style="font-size: 18px; font-weight: 700; color: var(--slate-900);" id="summaryReceived">₹0.00</span>
        </div>
        <div>
          <span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--slate-500); display: block;">Total Allocated</span>
          <span style="font-size: 18px; font-weight: 700; color: var(--success);" id="summaryAllocated">₹0.00</span>
        </div>
        <div>
          <span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--slate-500); display: block;">Unallocated Credit</span>
          <span style="font-size: 18px; font-weight: 700; color: var(--slate-700);" id="summaryUnallocated">₹0.00</span>
        </div>
      </div>

      <div id="allocationWarning" class="hidden" style="margin-top: 12px; padding: 10px 14px; background: var(--danger-light); border: 1px solid #FECACA; border-radius: var(--radius); color: #991B1B; font-size: 12px; font-weight: 600;">
        Warning: Total allocated amount exceeds the received payment amount! Please adjust allocation amounts.
      </div>
    </div>
  </div>

  <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
    <a href="<?= url('/payments') ?>" class="btn btn-secondary">Cancel</a>
    <button type="submit" id="submitBtn" class="btn btn-primary" style="padding: 10px 24px;">
      Save & Post Receipt
    </button>
  </div>
</form>

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
                        <td style="font-weight: 600;">
                            <a href="<?= url('/invoices/') ?>${inv.id}" target="_blank" style="color: var(--primary);">
                                ${inv.invoice_number} ↗
                            </a>
                        </td>
                        <td style="font-size: 12px; color: var(--slate-500);">${inv.issue_date}</td>
                        <td style="font-size: 12px; color: var(--slate-500);">${inv.due_date}</td>
                        <td class="text-right">₹${parseFloat(inv.total_amount).toFixed(2)}</td>
                        <td class="text-right" style="color: var(--slate-500);">₹${parseFloat(inv.paid_amount).toFixed(2)}</td>
                        <td class="text-right" style="font-weight: 700; color: var(--danger);">₹${parseFloat(inv.balance_due).toFixed(2)}</td>
                        <td class="text-right">
                            <input type="number" step="0.01" min="0" max="${inv.balance_due}"
                                   name="allocations[${inv.id}]"
                                   value="0.00"
                                   class="form-control form-control-sm alloc-input"
                                   style="text-align: right; font-weight: 600;"
                                   oninput="calculateAllocations()">
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

document.addEventListener('DOMContentLoaded', () => {
    calculateAllocations();
});
</script>
