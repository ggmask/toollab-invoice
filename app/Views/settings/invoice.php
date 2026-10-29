<div class="page-header">
  <div>
    <h1 class="page-title">Document Numbering & Default Terms</h1>
    <div class="page-description">Customize document sequence prefixes, zero-padding, standard credit periods, notes, and legal terms.</div>
  </div>
</div>

<!-- Settings Navigation Tabs -->
<div class="nav-tabs">
  <a href="<?= url('/settings/business') ?>" class="nav-tab-item">Business & Banking</a>
  <a href="<?= url('/settings/gst') ?>" class="nav-tab-item">GST & Tax Config</a>
  <a href="<?= url('/settings/invoice') ?>" class="nav-tab-item active">Document Numbering & Terms</a>
  <a href="<?= url('/settings/tax-rates') ?>" class="nav-tab-item">Tax Slabs</a>
  <a href="<?= url('/settings/account') ?>" class="nav-tab-item">User Security</a>
</div>

<form method="POST" action="<?= url('/settings/invoice') ?>">
  <?= csrf_field() ?>

  <!-- 1. Document Sequences -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">1. Document Numbering Sequences</h2>
    </div>
    <div class="card-body">
      <div style="font-size: 13px; color: var(--slate-500); margin-bottom: 16px;">
        Format: <code>[PREFIX]/[FY]/[NUMBER]</code> (e.g. <code>INV/24-25/0001</code>). Sequences reset automatically on April 1st of each Indian financial year.
      </div>

      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="form-group">
          <label class="form-label" for="invoice_prefix">Invoice Prefix</label>
          <input type="text" name="invoice_prefix" id="invoice_prefix" value="<?= e($business['invoice_prefix'] ?? 'INV') ?>" class="form-control" style="font-family: monospace; text-transform: uppercase;" required maxlength="10">
        </div>

        <div class="form-group">
          <label class="form-label" for="estimate_prefix">Estimate / Quote Prefix</label>
          <input type="text" name="estimate_prefix" id="estimate_prefix" value="<?= e($business['estimate_prefix'] ?? 'EST') ?>" class="form-control" style="font-family: monospace; text-transform: uppercase;" required maxlength="10">
        </div>

        <div class="form-group">
          <label class="form-label" for="payment_prefix">Receipt Voucher Prefix</label>
          <input type="text" name="payment_prefix" id="payment_prefix" value="<?= e($business['payment_prefix'] ?? 'PAY') ?>" class="form-control" style="font-family: monospace; text-transform: uppercase;" required maxlength="10">
        </div>

        <div class="form-group">
          <label class="form-label" for="number_padding">Zero Padding Digits</label>
          <select name="number_padding" id="number_padding" class="form-control" style="font-family: monospace;">
            <?php $currPad = (int)($business['number_padding'] ?? 4); ?>
            <option value="3" <?= ($currPad === 3) ? 'selected' : '' ?>>3 Digits (001)</option>
            <option value="4" <?= ($currPad === 4) ? 'selected' : '' ?>>4 Digits (0001)</option>
            <option value="5" <?= ($currPad === 5) ? 'selected' : '' ?>>5 Digits (00001)</option>
            <option value="6" <?= ($currPad === 6) ? 'selected' : '' ?>>6 Digits (000001)</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Default Due Dates & Credit Terms -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">2. Default Credit Terms & Due Dates</h2>
    </div>
    <div class="card-body">
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <div class="form-group">
          <label class="form-label" for="default_payment_terms">Standard Payment Terms</label>
          <select name="default_payment_terms" id="default_payment_terms" class="form-control">
            <?php 
              $currTerms = $business['default_payment_terms'] ?? 'Net 15';
            ?>
            <?php foreach ($paymentTerms as $tKey => $tLabel): ?>
              <option value="<?= $tKey ?>" <?= ($currTerms === $tKey) ? 'selected' : '' ?>><?= e($tLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="default_due_days">Default Credit Days</label>
          <input type="number" min="0" max="180" name="default_due_days" id="default_due_days" value="<?= (int)($business['default_due_days'] ?? 15) ?>" class="form-control">
          <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">Invoice due date defaults to Issue Date + X days.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Default Boilerplate Text -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">3. Default Customer Notes & Legal Terms</h2>
    </div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label" for="default_notes">Default Customer Notes</label>
        <textarea name="default_notes" id="default_notes" rows="3" class="form-control" placeholder="e.g. Thank you for your business! Please quote invoice number when remitting."><?= e($business['default_notes'] ?? '') ?></textarea>
        <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">Pre-populated into newly created invoices and quotations.</div>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" for="default_terms">Default Terms & Conditions</label>
        <textarea name="default_terms" id="default_terms" rows="4" class="form-control" placeholder="1. Payment is due within standard credit period.&#10;2. Interest @ 18% p.a. will be levied on overdue payments.&#10;3. Goods once sold will not be taken back."><?= e($business['default_terms'] ?? '') ?></textarea>
        <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">Printed on the bottom footer of customer invoices and estimates.</div>
      </div>
    </div>
  </div>

  <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
      Save Numbering & Terms
    </button>
  </div>
</form>
