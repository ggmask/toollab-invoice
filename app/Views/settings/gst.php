<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-slate-500">Settings</span>
            <span class="text-slate-400">/</span>
            <span class="text-sm font-medium text-slate-900">GST Settings</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">GST & Tax Configuration</h1>
        <p class="text-sm text-slate-500">Configure Goods & Services Tax registration status, GSTIN, PAN, and default Place of Supply.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <a href="<?= url('/settings/business') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
                Business & Banking
            </a>
            <a href="<?= url('/settings/gst') ?>" class="border-b-2 border-brand-600 pb-3 text-sm font-semibold text-brand-600">
                GST & Tax Config
            </a>
            <a href="<?= url('/settings/invoice') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
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

    <form method="POST" action="<?= url('/settings/gst') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">GST Registration</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Registration Type -->
                <div class="md:col-span-2">
                    <label class="form-label" for="gst_status">GST Registration Scheme <span class="text-rose-500">*</span></label>
                    <select name="gst_status" id="gst_status" class="form-select" onchange="toggleGstFields(this.value)">
                        <?php 
                            $status = $business['gst_status'] ?? 'registered_regular';
                        ?>
                        <option value="registered_regular" <?= ($status === 'registered_regular') ? 'selected' : '' ?>>Registered Regular Taxpayer (Standard CGST / SGST / IGST)</option>
                        <option value="composition" <?= ($status === 'composition') ? 'selected' : '' ?>>Composition Scheme (Bill of Supply - No Tax Collected)</option>
                        <option value="unregistered" <?= ($status === 'unregistered') ? 'selected' : '' ?>>Unregistered Business (Under ₹20L/₹40L Exemption Threshold)</option>
                        <option value="sez" <?= ($status === 'sez') ? 'selected' : '' ?>>Special Economic Zone (SEZ Unit / Developer - Zero Rated)</option>
                    </select>
                </div>

                <!-- GSTIN -->
                <div>
                    <label class="form-label" for="gstin">Goods & Services Tax Identification Number (GSTIN)</label>
                    <input type="text" name="gstin" id="gstin" value="<?= e($business['gstin'] ?? '') ?>"
                           class="form-input font-mono uppercase" placeholder="e.g. 27AAACT1234A1Z5"
                           maxlength="15" oninput="parseGstin(this.value)">
                    <span class="text-xs text-slate-400 mt-1 block">15-character alphanumeric GST identifier.</span>
                </div>

                <!-- Permanent Account Number (PAN) -->
                <div>
                    <label class="form-label" for="pan">Income Tax PAN</label>
                    <input type="text" name="pan" id="pan" value="<?= e($business['pan'] ?? '') ?>"
                           class="form-input font-mono uppercase" placeholder="e.g. AAACT1234A"
                           maxlength="10">
                    <span class="text-xs text-slate-400 mt-1 block">Automatically extracted from characters 3–12 of GSTIN.</span>
                </div>

                <!-- State / UT -->
                <div class="md:col-span-2">
                    <label class="form-label" for="state_code">Registration State Code (Default Place of Origin)</label>
                    <select name="state_code" id="state_code" class="form-select">
                        <option value="">-- Select State --</option>
                        <?php foreach ($states as $code => $name): ?>
                            <option value="<?= $code ?>" <?= (($business['state_code'] ?? '') === $code) ? 'selected' : '' ?>>
                                <?= $code ?> - <?= e($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-xs text-slate-400 mt-1 block">Determines Intra-State (CGST+SGST) vs Inter-State (IGST) taxation rules.</span>
                </div>
            </div>
        </div>

        <!-- Compliance Architecture Note -->
        <div class="card p-5 bg-blue-50/70 border border-blue-200 text-xs text-blue-900 space-y-2">
            <h4 class="font-bold flex items-center gap-1.5 text-blue-950 text-sm">
                <svg class="w-4 h-4 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Indian GST Rule 46 Invoicing Law Notice
            </h4>
            <p>
                When registered as <strong>Registered Regular</strong>, the application produces official <strong>Tax Invoices</strong> with mandatory fields: GSTIN of supplier & recipient, consecutive serial numbering within the Financial Year, HSN/SAC code, taxable value, and tax split.
            </p>
            <p>
                For <strong>Composition Scheme</strong> or <strong>Unregistered</strong>, invoices are automatically marked as <strong>Bill of Supply</strong> without separate tax charging, in full adherence to Section 31 of the CGST Act.
            </p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-6">
                Save GST Configuration
            </button>
        </div>
    </form>
</div>

<script>
function parseGstin(gstin) {
    gstin = gstin.trim().toUpperCase();
    if (gstin.length >= 2) {
        const stateCode = gstin.substring(0, 2);
        const stateSelect = document.getElementById('state_code');
        if (stateSelect) {
            stateSelect.value = stateCode;
        }
    }
    if (gstin.length >= 12) {
        const pan = gstin.substring(2, 12);
        const panInput = document.getElementById('pan');
        if (panInput) {
            panInput.value = pan;
        }
    }
}
</script>
