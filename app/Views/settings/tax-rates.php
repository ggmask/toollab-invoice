<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-500">Settings</span>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-900">Tax Slabs</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">GST Tax Rates & Slabs</h1>
            <p class="text-sm text-slate-500">Configure standard Indian GST rates (0%, 5%, 12%, 18%, 28%) and custom tax classifications.</p>
        </div>

        <button type="button" onclick="document.getElementById('addRateModal').classList.remove('hidden')" class="btn btn-primary">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Tax Rate
        </button>
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
            <a href="<?= url('/settings/invoice') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
                Document Numbering & Terms
            </a>
            <a href="<?= url('/settings/tax-rates') ?>" class="border-b-2 border-brand-600 pb-3 text-sm font-semibold text-brand-600">
                Tax Slabs
            </a>
            <a href="<?= url('/settings/account') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
                User Security
            </a>
        </nav>
    </div>

    <!-- Tax Rates Table -->
    <div class="card overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tax Name</th>
                        <th class="text-center">Rate (%)</th>
                        <th class="text-center">Intra-State Split</th>
                        <th class="text-center">Inter-State</th>
                        <th>Classification</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($taxRates)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-500">
                                No tax rates defined yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($taxRates as $tr): ?>
                            <?php 
                                $rate = (float)$tr['rate'];
                                $half = $rate / 2;
                            ?>
                            <tr class="hover:bg-slate-50">
                                <td class="font-bold text-slate-900">
                                    <?= e($tr['name']) ?>
                                </td>
                                <td class="text-center font-bold text-brand-600">
                                    <?= $rate ?>%
                                </td>
                                <td class="text-center text-xs text-slate-600">
                                    <?php if ($rate > 0): ?>
                                        <span class="bg-slate-100 px-2 py-0.5 rounded font-mono">CGST <?= $half ?>% + SGST <?= $half ?>%</span>
                                    <?php else: ?>
                                        <span class="text-slate-400">0%</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-xs text-slate-600">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded font-mono">IGST <?= $rate ?>%</span>
                                </td>
                                <td>
                                    <span class="text-xs uppercase font-medium text-slate-600">
                                        <?= e(str_replace('_', ' ', $tr['tax_category'])) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($tr['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-ghost">Disabled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Tax Rate Modal -->
<div id="addRateModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="card max-w-md w-full p-6 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Add GST Tax Rate</h3>
            <button type="button" onclick="document.getElementById('addRateModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                ✕
            </button>
        </div>

        <form method="POST" action="<?= url('/settings/tax-rates') ?>" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="form-label" for="name">Rate Display Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="name" required class="form-input" placeholder="e.g. GST 18%, Exempt, Nil">
            </div>

            <div>
                <label class="form-label" for="rate">Tax Percentage (%) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" max="100" name="rate" id="rate" required class="form-input font-bold" placeholder="18.00">
            </div>

            <div>
                <label class="form-label" for="tax_category">GST Category</label>
                <select name="tax_category" id="tax_category" class="form-select">
                    <option value="taxable">Taxable (Standard)</option>
                    <option value="nil_rated">Nil Rated</option>
                    <option value="exempt">Exempt</option>
                    <option value="non_gst">Non-GST</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('addRateModal').classList.add('hidden')" class="btn btn-secondary">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Create Rate
                </button>
            </div>
        </form>
    </div>
</div>
