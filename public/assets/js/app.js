// ToolLab Invoice - Client Application Scripts

document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Drawer Navigation
  const mobileToggle = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.app-sidebar');
  const overlay = document.querySelector('.sidebar-overlay');

  if (mobileToggle && sidebar && overlay) {
    const toggleMenu = () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('open');
    };
    mobileToggle.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);
  }

  // 2. Register PWA Service Worker
  if ('serviceWorker' in navigator && window.location.protocol.startsWith('http')) {
    navigator.serviceWorker.register('/sw.js').catch((err) => {
      console.debug('ServiceWorker registration skipped:', err);
    });
  }

  // 3. Line Items Calculator (Invoices and Estimates)
  const lineItemsContainer = document.getElementById('line-items-body');
  if (lineItemsContainer) {
    initLineItemsCalculator();
  }

  // 4. Payment Customer Invoice Allocator
  const paymentCustomerSelect = document.getElementById('payment-customer-select');
  if (paymentCustomerSelect) {
    initPaymentCustomerLoader(paymentCustomerSelect);
  }
});

// Line Items Calculator Engine
function initLineItemsCalculator() {
  const container = document.getElementById('line-items-body');
  const addBtn = document.getElementById('add-line-item-btn');
  const posCodeSelect = document.getElementById('pos_state_code');
  const supplierStateCode = document.getElementById('supplier_state_code')?.value || '';
  const invDiscountValue = document.getElementById('invoice_discount_value');
  const invDiscountType = document.getElementById('invoice_discount_type');
  const additionalChargesInput = document.getElementById('additional_charges');

  function calculateAll() {
    const rows = container.querySelectorAll('.line-item-row');
    const posCode = posCodeSelect?.value || supplierStateCode;
    const isIntraState = !posCode || !supplierStateCode || (posCode.padStart(2, '0') === supplierStateCode.padStart(2, '0'));

    let subtotalGross = 0;
    let lineDiscountsTotal = 0;
    let preDiscTaxableTotal = 0;

    const rowData = [];

    rows.forEach((row) => {
      const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
      const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
      const discVal = parseFloat(row.querySelector('.item-disc-val')?.value) || 0;
      const discType = row.querySelector('.item-disc-type')?.value || 'percentage';
      const gstRate = parseFloat(row.querySelector('.item-gst-rate')?.value) || 0;
      const taxCategory = row.querySelector('.item-tax-cat')?.value || 'taxable';

      const gross = Math.round(qty * price * 100) / 100;
      let discAmt = 0;
      if (discType === 'percentage') {
        discAmt = Math.round(gross * (discVal / 100) * 100) / 100;
      } else {
        discAmt = Math.min(gross, Math.round(discVal * 100) / 100);
      }

      const taxable = Math.max(0, Math.round((gross - discAmt) * 100) / 100);

      subtotalGross += gross;
      lineDiscountsTotal += discAmt;
      preDiscTaxableTotal += taxable;

      rowData.push({ row, qty, price, gross, discAmt, taxable, preTaxable: taxable, gstRate, taxCategory });
    });

    // Invoice-level discount
    const invDiscVal = parseFloat(invDiscountValue?.value) || 0;
    const invDiscType = invDiscountType?.value || 'flat';
    let invDiscAmt = 0;
    if (preDiscTaxableTotal > 0 && invDiscVal > 0) {
      if (invDiscType === 'percentage') {
        invDiscAmt = Math.round(preDiscTaxableTotal * (invDiscVal / 100) * 100) / 100;
      } else {
        invDiscAmt = Math.min(preDiscTaxableTotal, Math.round(invDiscVal * 100) / 100);
      }
    }

    let finalTaxableTotal = 0;
    let totalCgst = 0;
    let totalSgst = 0;
    let totalIgst = 0;

    let allocatedInvDiscSum = 0;
    const count = rowData.length;

    rowData.forEach((item, idx) => {
      let lineTaxable = item.preTaxable;
      if (invDiscAmt > 0 && preDiscTaxableTotal > 0) {
        let proRated = 0;
        if (idx === count - 1) {
          proRated = Math.round((invDiscAmt - allocatedInvDiscSum) * 100) / 100;
        } else {
          proRated = Math.round((invDiscAmt * (item.preTaxable / preDiscTaxableTotal)) * 100) / 100;
          allocatedInvDiscSum += proRated;
        }
        lineTaxable = Math.max(0, Math.round((item.preTaxable - proRated) * 100) / 100);
      }

      let lineTax = 0;
      let cgst = 0, sgst = 0, igst = 0;

      if (item.taxCategory === 'taxable' && item.gstRate > 0 && lineTaxable > 0) {
        if (isIntraState) {
          const halfRate = item.gstRate / 2;
          cgst = Math.round(lineTaxable * (halfRate / 100) * 100) / 100;
          sgst = Math.round(lineTaxable * (halfRate / 100) * 100) / 100;
          lineTax = cgst + sgst;
        } else {
          igst = Math.round(lineTaxable * (item.gstRate / 100) * 100) / 100;
          lineTax = igst;
        }
      }

      const lineTotal = Math.round((lineTaxable + lineTax) * 100) / 100;

      finalTaxableTotal += lineTaxable;
      totalCgst += cgst;
      totalSgst += sgst;
      totalIgst += igst;

      // Update line total text in table
      const lineTotalEl = item.row.querySelector('.line-total-display');
      if (lineTotalEl) {
        lineTotalEl.textContent = formatCurrencyJs(lineTotal);
      }
    });

    const taxTotal = Math.round((totalCgst + totalSgst + totalIgst) * 100) / 100;
    const addCharges = parseFloat(additionalChargesInput?.value) || 0;
    const subtotalWithTax = Math.round((finalTaxableTotal + taxTotal + addCharges) * 100) / 100;
    const finalTotal = Math.round(subtotalWithTax);
    const roundOff = Math.round((finalTotal - subtotalWithTax) * 100) / 100;

    // Update DOM summary cards
    setText('sum_subtotal_gross', formatCurrencyJs(subtotalGross));
    setText('sum_line_discounts', formatCurrencyJs(lineDiscountsTotal));
    setText('sum_inv_discount', formatCurrencyJs(invDiscAmt));
    setText('sum_taxable_value', formatCurrencyJs(finalTaxableTotal));

    const cgstRow = document.getElementById('row_cgst');
    const sgstRow = document.getElementById('row_sgst');
    const igstRow = document.getElementById('row_igst');

    if (isIntraState) {
      if (cgstRow) cgstRow.style.display = 'flex';
      if (sgstRow) sgstRow.style.display = 'flex';
      if (igstRow) igstRow.style.display = 'none';
      setText('sum_cgst', formatCurrencyJs(totalCgst));
      setText('sum_sgst', formatCurrencyJs(totalSgst));
    } else {
      if (cgstRow) cgstRow.style.display = 'none';
      if (sgstRow) sgstRow.style.display = 'none';
      if (igstRow) igstRow.style.display = 'flex';
      setText('sum_igst', formatCurrencyJs(totalIgst));
    }

    setText('sum_tax_total', formatCurrencyJs(taxTotal));
    setText('sum_add_charges', formatCurrencyJs(addCharges));
    setText('sum_round_off', (roundOff >= 0 ? '+' : '') + formatCurrencyJs(roundOff));
    setText('sum_final_total', formatCurrencyJs(finalTotal));
  }

  function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  }

  function formatCurrencyJs(num) {
    return '₹ ' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Row removal handler
  container.addEventListener('click', (e) => {
    if (e.target.closest('.remove-row-btn')) {
      const row = e.target.closest('.line-item-row');
      if (container.querySelectorAll('.line-item-row').length > 1) {
        row.remove();
        calculateAll();
      } else {
        alert('An invoice must have at least one line item.');
      }
    }
  });

  // Input change recalculation
  container.addEventListener('input', calculateAll);
  container.addEventListener('change', calculateAll);

  if (posCodeSelect) posCodeSelect.addEventListener('change', calculateAll);
  if (invDiscountValue) invDiscountValue.addEventListener('input', calculateAll);
  if (invDiscountType) invDiscountType.addEventListener('change', calculateAll);
  if (additionalChargesInput) additionalChargesInput.addEventListener('input', calculateAll);

  // Add line item
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const idx = container.querySelectorAll('.line-item-row').length;
      const row = document.createElement('tr');
      row.className = 'line-item-row';
      row.innerHTML = `
        <td style="width: 30%;">
          <input type="text" name="items[${idx}][name]" class="form-control form-control-sm item-name" placeholder="Item Name / Description" required>
          <textarea name="items[${idx}][description]" class="form-control form-control-sm" rows="1" placeholder="Optional details..." style="margin-top: 4px;"></textarea>
        </td>
        <td style="width: 12%;">
          <input type="text" name="items[${idx}][hsn_sac]" class="form-control form-control-sm" placeholder="HSN/SAC">
        </td>
        <td style="width: 10%;">
          <input type="number" step="0.001" name="items[${idx}][quantity]" class="form-control form-control-sm item-qty text-right" value="1" min="0.001" required>
        </td>
        <td style="width: 10%;">
          <select name="items[${idx}][unit]" class="form-control form-control-sm">
            <option value="PCS">PCS</option>
            <option value="NOS">NOS</option>
            <option value="BOX">BOX</option>
            <option value="KGS">KGS</option>
            <option value="MTR">MTR</option>
            <option value="SET">SET</option>
            <option value="HRS">HRS</option>
            <option value="MTH">MTH</option>
          </select>
        </td>
        <td style="width: 12%;">
          <input type="number" step="0.01" name="items[${idx}][unit_price]" class="form-control form-control-sm item-price text-right" value="0.00" min="0" required>
        </td>
        <td style="width: 12%;">
          <div style="display: flex; gap: 2px;">
            <input type="number" step="0.01" name="items[${idx}][discount_value]" class="form-control form-control-sm item-disc-val text-right" value="0" min="0">
            <select name="items[${idx}][discount_type]" class="form-control form-control-sm item-disc-type" style="width: 50px; padding: 4px;">
              <option value="percentage">%</option>
              <option value="flat">₹</option>
            </select>
          </div>
        </td>
        <td style="width: 10%;">
          <select name="items[${idx}][gst_rate]" class="form-control form-control-sm item-gst-rate">
            <option value="18">18%</option>
            <option value="12">12%</option>
            <option value="5">5%</option>
            <option value="28">28%</option>
            <option value="0">0%</option>
          </select>
        </td>
        <td class="text-right" style="width: 12%;">
          <span class="line-total-display" style="font-weight: 600; font-size: 13px;">₹ 0.00</span>
        </td>
        <td style="width: 4%; text-align: center;">
          <button type="button" class="btn btn-secondary btn-sm remove-row-btn" style="padding: 4px 6px; color: var(--danger);">&times;</button>
        </td>
      `;
      container.appendChild(row);
      calculateAll();
    });
  }

  // Initial calculation
  calculateAll();
}

// Payment Customer Invoice Loader
function initPaymentCustomerLoader(selectEl) {
  const invoiceContainer = document.getElementById('payment-invoices-list');
  const amountInput = document.getElementById('payment_amount');
  const autoAllocateBtn = document.getElementById('auto-allocate-btn');

  function loadInvoices(customerId) {
    if (!customerId) {
      if (invoiceContainer) invoiceContainer.innerHTML = '<p class="text-muted" style="padding: 12px;">Please select a customer to view outstanding invoices.</p>';
      return;
    }

    if (invoiceContainer) {
      invoiceContainer.innerHTML = '<p class="text-muted" style="padding: 12px;">Loading unpaid customer invoices...</p>';
    }

    fetch(`/api/customers/${customerId}/invoices`)
      .then((res) => res.json())
      .then((invoices) => {
        if (!invoices || invoices.length === 0) {
          invoiceContainer.innerHTML = '<div class="alert alert-success" style="margin: 12px 0;">Great news! This customer has zero outstanding invoices.</div>';
          return;
        }

        let html = `
          <table class="table" style="font-size: 13px;">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th class="text-right">Total (₹)</th>
                <th class="text-right">Outstanding (₹)</th>
                <th class="text-right" style="width: 140px;">Allocate Amount (₹)</th>
              </tr>
            </thead>
            <tbody>
        `;

        invoices.forEach((inv) => {
          html += `
            <tr>
              <td><strong>${escapeHtml(inv.invoice_number)}</strong></td>
              <td>${escapeHtml(inv.issue_date)}</td>
              <td>${escapeHtml(inv.due_date)}</td>
              <td class="text-right">${formatCurrencyJs(parseFloat(inv.total_amount))}</td>
              <td class="text-right" style="color: var(--danger); font-weight: 600;">
                ${formatCurrencyJs(parseFloat(inv.balance_due))}
              </td>
              <td class="text-right">
                <input type="number" step="0.01" name="allocations[${inv.id}]" class="form-control form-control-sm text-right invoice-alloc-input" data-balance="${inv.balance_due}" value="0.00" min="0" max="${inv.balance_due}">
              </td>
            </tr>
          `;
        });

        html += `</tbody></table>`;
        invoiceContainer.innerHTML = html;
      })
      .catch((err) => {
        invoiceContainer.innerHTML = '<p class="text-danger" style="padding: 12px;">Failed to load customer invoices.</p>';
      });
  }

  selectEl.addEventListener('change', () => loadInvoices(selectEl.value));

  // Auto-allocate button
  if (autoAllocateBtn) {
    autoAllocateBtn.addEventListener('click', () => {
      let remaining = parseFloat(amountInput?.value) || 0;
      const allocInputs = document.querySelectorAll('.invoice-alloc-input');

      allocInputs.forEach((input) => {
        const bal = parseFloat(input.dataset.balance) || 0;
        if (remaining > 0) {
          const alloc = Math.min(remaining, bal);
          input.value = alloc.toFixed(2);
          remaining = Math.round((remaining - alloc) * 100) / 100;
        } else {
          input.value = '0.00';
        }
      });
    });
  }

  function formatCurrencyJs(num) {
    return '₹ ' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}
