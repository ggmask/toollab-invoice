<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-slate-500">Settings</span>
            <span class="text-slate-400">/</span>
            <span class="text-sm font-medium text-slate-900">Document Settings</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">Document Numbering & Default Terms</h1>
        <p class="text-sm text-slate-500">Customize document sequence prefixes, zero-padding, standard credit periods, notes, and legal terms.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <a href="<?= url('/settings/business') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
                Business & Banking
            </a>
            <a href="<?= url('/settings/gst') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
                GST & Tax Config
            </a>
            <a href="<?= url('/settings/invoice') ?>" class="border-b-2 border-brand-600 pb-3 text-sm font-semibold text-brand-600">
                Document Numbering & Terms
            </a>
            <a href="<?= url('/settings/tax-rates') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
                Tax Slabs
            </a>
            <a href="<?= url('/settings/account') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
                User Security
            </a>
        </nav>
    </div>

    <form method="POST" action="<?= url('/settings/invoice') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Document Sequences -->
        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">Document Numbering Sequences</h2>
            <p class="text-xs text-slate-500 -mt-2">Format: <code>[PREFIX]/[FY]/[NUMBER]</code> (e.g. <code>INV/24-25/0001</code>). Sequences reset automatically on April 1st of each financial year.</p>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="form-label" for="invoice_prefix">Invoice Prefix</label>
                    <input type="text" name="invoice_prefix" id="invoice_prefix" value="<?= e($business['invoice_prefix'] ?? 'INV') ?>" class="form-input font-mono uppercase" required maxlength="10">
                </div>

                <div>
                    <label class="form-label" for="estimate_prefix">Estimate / Quote Prefix</label>
                    <input type="text" name="estimate_prefix" id="estimate_prefix" value="<?= e($business['estimate_prefix'] ?? 'EST') ?>" class="form-input font-mono uppercase" required maxlength="10">
                </div>

                <div>
                    <label class="form-label" for="payment_prefix">Receipt Voucher Prefix</label>
                    <input type="text" name="payment_prefix" id="payment_prefix" value="<?= e($business['payment_prefix'] ?? 'PAY') ?>" class="form-input font-mono uppercase" required maxlength="10">
                </div>

                <div>
                    <label class="form-label" for="number_padding">Zero Padding Digits</label>
                    <select name="number_padding" id="number_padding" class="form-select font-mono">
                        <?php $currPad = (int)($business['number_padding'] ?? 4); ?>
                        <option value="3" <?= ($currPad === 3) ? 'selected' : '' ?>>3 Digits (001)</option>
                        <option value="4" <?= ($currPad === 4) ? 'selected' : '' ?>>4 Digits (0001)</option>
                        <option value="5" <?= ($currPad === 5) ? 'selected' : '' ?>>5 Digits (00001)</option>
                        <option value="6" <?= ($currPad === 6) ? 'selected' : '' ?>>6 Digits (000001)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Default Payment Terms & Deadlines -->
        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">Default Due Dates & Terms</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label" for="default_payment_terms">Standard Payment Terms</label>
                    <select name="default_payment_terms" id="default_payment_terms" class="form-select">
                        <?php 
                            $currTerms = $business['default_payment_terms'] ?? 'Net 15';
                        ?>
                        <?php foreach ($paymentTerms as $tKey => $tLabel): ?>
                            <option value="<?= $tKey ?>" <?= ($currTerms === $tKey) ? 'selected' : '' ?>><?= e($tLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="default_due_days">Default Credit Days</label>
                    <input type="number" min="0" max="180" name="default_due_days" id="default_due_days" value="<?= (int)($business['default_due_days'] ?? 15) ?>" class="form-input">
                    <span class="text-xs text-slate-400 mt-1 block">Due date automatically calculated as Issue Date + X days.</span>
                </div>
            </div>
        </div>

        <!-- Default Boilerplate Text -->
        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">Default Customer Notes & Legal Terms</h2>

            <div class="space-y-4">
                <div>
                    <label class="form-label" for="default_notes">Default Customer Notes</label>
                    <textarea name="default_notes" id="default_notes" rows="3" class="form-textarea" placeholder="e.g. Thank you for your business! Please quote invoice number when transferring funds."><?= e($business['default_notes'] ?? '') ?></textarea>
                    <span class="text-xs text-slate-400 mt-1 block">Pre-populated into newly created invoices and quotes.</span>
                </div>

                <div>
                    <label class="form-label" for="default_terms">Default Terms & Conditions</label>
                    <textarea name="default_terms" id="default_terms" rows="4" class="form-textarea" placeholder="1. Payment is due within standard credit days.&#10;2. Interest @ 18% p.a. will be charged on overdue payments.&#10;3. Goods/Services once sold are not returnable."><?= e($business['default_terms'] ?? '') ?></textarea>
                    <span class="text-xs text-slate-400 mt-1 block">Printed on the bottom footer of customer invoices and estimates.</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-6">
                Save Numbering & Terms
            </button>
        </div>
    </form>
</div>
