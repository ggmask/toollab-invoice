<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-slate-500">Settings</span>
            <span class="text-slate-400">/</span>
            <span class="text-sm font-medium text-slate-900">Account</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">My Account & Security</h1>
        <p class="text-sm text-slate-500">Update your account credentials, display name, and password authentication.</p>
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
            <a href="<?= url('/settings/tax-rates') ?>" class="border-b-2 border-transparent pb-3 text-sm font-medium text-slate-500 hover:text-slate-700">
                Tax Slabs
            </a>
            <a href="<?= url('/settings/account') ?>" class="border-b-2 border-brand-600 pb-3 text-sm font-semibold text-brand-600">
                User Security
            </a>
        </nav>
    </div>

    <form method="POST" action="<?= url('/settings/account') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Profile Details -->
        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">User Profile</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label" for="name">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="<?= e($user['name'] ?? '') ?>" class="form-input" required>
                </div>

                <div>
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" value="<?= e($user['email'] ?? '') ?>" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" disabled>
                    <span class="text-xs text-slate-400 mt-1 block">Your email is the unique account identifier.</span>
                </div>
            </div>
        </div>

        <!-- Password Change -->
        <div class="card p-6 space-y-6">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-3">Change Password</h2>
            <p class="text-xs text-slate-500 -mt-2">Leave password fields blank if you do not wish to change your current password.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label" for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="form-input" placeholder="••••••••">
                </div>

                <div>
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-input" placeholder="At least 6 characters" minlength="6">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-6">
                Update Account Settings
            </button>
        </div>
    </form>
</div>
