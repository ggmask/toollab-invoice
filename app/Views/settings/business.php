<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-slate-500">Settings</span>
            <span class="text-slate-400">/</span>
            <span class="text-sm font-medium text-slate-900">Business Profile</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">Business Profile & Banking</h1>
        <p class="text-sm text-slate-500">Manage company information, registered office address, bank remittance instructions, and print branding.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <a href="<?= url('/settings/business') ?>" class="border-b-2 border-brand-600 pb-3 text-sm font-semibold text-brand-600">
                Business & Banking
            </a>
            <a href="<?= url('/settings/gst') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
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

    <form method="POST" action="<?= url('/settings/business') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Company Identity -->
        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">Company Identity</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <label class="form-label" for="legal_name">Legal Registered Entity Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="legal_name" id="legal_name" value="<?= e($business['legal_name'] ?? '') ?>" class="form-input" required>
                    <span class="text-xs text-slate-400 mt-1 block">Exact legal name as registered under GST / MCA / PAN.</span>
                </div>

                <div>
                    <label class="form-label" for="trade_name">Trade / Brand Name</label>
                    <input type="text" name="trade_name" id="trade_name" value="<?= e($business['trade_name'] ?? '') ?>" class="form-input">
                    <span class="text-xs text-slate-400 mt-1 block">Display name for invoice letterhead.</span>
                </div>

                <div>
                    <label class="form-label" for="business_type">Constitution of Business</label>
                    <select name="business_type" id="business_type" class="form-select">
                        <?php 
                            $types = ['Proprietorship', 'Partnership', 'Private Limited Company', 'Public Limited Company', 'Limited Liability Partnership (LLP)', 'Trust / Society', 'Individual / Freelancer'];
                            $currType = $business['business_type'] ?? 'Proprietorship';
                        ?>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= ($currType === $t) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="phone">Official Contact Phone</label>
                    <input type="text" name="phone" id="phone" value="<?= e($business['phone'] ?? '') ?>" class="form-input" placeholder="+91 98765 43210">
                </div>

                <div>
                    <label class="form-label" for="email">Billing Email</label>
                    <input type="email" name="email" id="email" value="<?= e($business['email'] ?? '') ?>" class="form-input" placeholder="billing@domain.in">
                </div>

                <div class="md:col-span-3">
                    <label class="form-label" for="website">Website URL</label>
                    <input type="url" name="website" id="website" value="<?= e($business['website'] ?? '') ?>" class="form-input" placeholder="https://www.yourcompany.in">
                </div>
            </div>
        </div>

        <!-- Registered Office Address -->
        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">Principal Place of Business (Office Address)</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label" for="address_line1">Address Line 1</label>
                    <input type="text" name="address_line1" id="address_line1" value="<?= e($business['address_line1'] ?? '') ?>" class="form-input" placeholder="Building, Suite, Street">
                </div>

                <div>
                    <label class="form-label" for="address_line2">Address Line 2 (Optional)</label>
                    <input type="text" name="address_line2" id="address_line2" value="<?= e($business['address_line2'] ?? '') ?>" class="form-input" placeholder="Landmark, Area">
                </div>

                <div>
                    <label class="form-label" for="city">City / District</label>
                    <input type="text" name="city" id="city" value="<?= e($business['city'] ?? '') ?>" class="form-input">
                </div>

                <div>
                    <label class="form-label" for="state_code">State / Union Territory (GST)</label>
                    <select name="state_code" id="state_code" class="form-select">
                        <option value="">-- Select State --</option>
                        <?php foreach ($states as $code => $name): ?>
                            <option value="<?= $code ?>" <?= (($business['state_code'] ?? '') === $code) ? 'selected' : '' ?>>
                                <?= $code ?> - <?= e($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="pincode">Postal PIN Code</label>
                    <input type="text" name="pincode" id="pincode" value="<?= e($business['pincode'] ?? '') ?>" class="form-input" maxlength="6" pattern="[0-9]{6}">
                </div>

                <div>
                    <label class="form-label" for="signature_title">Signatory Title</label>
                    <input type="text" name="signature_title" id="signature_title" value="<?= e($business['signature_title'] ?? 'Authorized Signatory') ?>" class="form-input" placeholder="Authorized Signatory / Director">
                </div>
            </div>
        </div>

        <!-- Bank & Remittance Details -->
        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">Bank Remittance & UPI Payment Details</h2>
            <p class="text-xs text-slate-500 -mt-2">These banking credentials appear directly at the bottom of printed invoices and PDFs for client NEFT/RTGS/UPI remittance.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label" for="bank_name">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name" value="<?= e($business['bank_name'] ?? '') ?>" class="form-input" placeholder="e.g. HDFC Bank, ICICI Bank">
                </div>

                <div>
                    <label class="form-label" for="bank_branch">Branch Name</label>
                    <input type="text" name="bank_branch" id="bank_branch" value="<?= e($business['bank_branch'] ?? '') ?>" class="form-input" placeholder="e.g. Bandra Kurla Complex">
                </div>

                <div>
                    <label class="form-label" for="bank_account_no">Account Number</label>
                    <input type="text" name="bank_account_no" id="bank_account_no" value="<?= e($business['bank_account_no'] ?? '') ?>" class="form-input font-mono" placeholder="e.g. 50200012345678">
                </div>

                <div>
                    <label class="form-label" for="bank_ifsc">IFSC Code</label>
                    <input type="text" name="bank_ifsc" id="bank_ifsc" value="<?= e($business['bank_ifsc'] ?? '') ?>" class="form-input font-mono uppercase" placeholder="e.g. HDFC0001234" maxlength="11">
                </div>

                <div class="md:col-span-2">
                    <label class="form-label" for="bank_upi_id">Virtual Payment Address (UPI VPA)</label>
                    <input type="text" name="bank_upi_id" id="bank_upi_id" value="<?= e($business['bank_upi_id'] ?? '') ?>" class="form-input font-mono" placeholder="e.g. toollab@hdfcbank">
                    <span class="text-xs text-slate-400 mt-1 block">Clients can pay instantly via any UPI app (GPay, PhonePe, Paytm).</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-6">
                Save Business Profile
            </button>
        </div>
    </form>
</div>
