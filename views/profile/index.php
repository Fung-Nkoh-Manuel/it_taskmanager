<?php $pageTitle = 'My Profile'; ?>

<div class="max-w-2xl mx-auto space-y-5">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My Profile</h1>

    <!-- Tabs -->
    <div class="flex gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl w-fit">
        <?php foreach (['profile' => 'Profile', 'password' => 'Password'] as $t => $l): ?>
        <button onclick="switchTab('<?= $t ?>')"
                id="tab-btn-<?= $t ?>"
                class="px-4 py-2 text-sm font-medium rounded-lg transition
                       <?= ($tab ?? 'profile') === $t
                           ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
                           : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' ?>">
            <?= $l ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Profile tab -->
    <div id="tab-profile" class="<?= ($tab ?? 'profile') !== 'profile' ? 'hidden' : '' ?>">
        <?php if (!empty($flash) && $flash['type'] === 'success' && ($tab ?? 'profile') === 'profile'): ?>
        <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 text-sm rounded-xl px-4 py-3">
            <i data-lucide="check-circle" class="w-4 h-4"></i> <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/profile" enctype="multipart/form-data" class="card p-6 space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

            <!-- Avatar -->
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-brand-600/20 text-brand-600 flex items-center justify-center text-2xl font-bold shrink-0">
                    <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 2)) ?>
                </div>
                <div>
                    <label class="form-label">Profile Photo</label>
                    <input type="file" name="avatar" accept="image/*" class="form-input text-xs">
                    <p class="text-xs text-slate-400 mt-0.5">JPG, PNG, WEBP — max 2 MB</p>
                    <?php if (!empty($errors['avatar'])): ?>
                    <p class="form-error"><?= $errors['avatar'] ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required
                       class="form-input <?= !empty($errors['full_name']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['full_name'])): ?>
                <p class="form-error"><?= $errors['full_name'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                       class="form-input <?= !empty($errors['email']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['email'])): ?>
                <p class="form-error"><?= $errors['email'] ?></p>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Username</label>
                    <input type="text" value="@<?= htmlspecialchars($user['username'] ?? '') ?>"
                           disabled class="form-input opacity-60 cursor-not-allowed">
                </div>
                <div>
                    <label class="form-label">Role</label>
                    <input type="text" value="<?= htmlspecialchars($user['role'] ?? '') ?>"
                           disabled class="form-input opacity-60 cursor-not-allowed capitalize">
                </div>
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                <i data-lucide="save" class="w-4 h-4"></i> Save Profile
            </button>
        </form>
    </div>

    <!-- Password tab -->
    <div id="tab-password" class="<?= ($tab ?? 'profile') !== 'password' ? 'hidden' : '' ?>">
        <?php if (!empty($flash) && $flash['type'] === 'success' && ($tab ?? 'profile') === 'password'): ?>
        <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 text-sm rounded-xl px-4 py-3">
            <i data-lucide="check-circle" class="w-4 h-4"></i> <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/profile/password" class="card p-6 space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

            <div>
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" required
                       class="form-input <?= !empty($errors['current_password']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['current_password'])): ?>
                <p class="form-error"><?= $errors['current_password'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" required minlength="6"
                       class="form-input <?= !empty($errors['new_password']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['new_password'])): ?>
                <p class="form-error"><?= $errors['new_password'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" required
                       class="form-input <?= !empty($errors['confirm_password']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['confirm_password'])): ?>
                <p class="form-error"><?= $errors['confirm_password'] ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                <i data-lucide="lock" class="w-4 h-4"></i> Change Password
            </button>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    ['profile', 'password'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById('tab-btn-' + t);
        if (t === tab) {
            btn.classList.add('bg-white', 'dark:bg-slate-700', 'text-slate-900', 'dark:text-white', 'shadow-sm');
            btn.classList.remove('text-slate-500', 'hover:text-slate-900', 'dark:hover:text-white');
        } else {
            btn.classList.remove('bg-white', 'dark:bg-slate-700', 'text-slate-900', 'dark:text-white', 'shadow-sm');
            btn.classList.add('text-slate-500', 'hover:text-slate-900', 'dark:hover:text-white');
        }
    });
}
</script>
