<?php
$isEdit    = !empty($user['id']);
$pageTitle = $isEdit ? 'Edit User' : 'New User';
$v = fn($f, $d = '') => htmlspecialchars($user[$f] ?? $d);
?>

<div class="max-w-xl mx-auto space-y-5">
    <div class="flex items-center gap-3">
        <a href="<?= APP_URL ?>/users" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white"><?= $pageTitle ?></h1>
    </div>

    <form method="POST" action="<?= $isEdit ? APP_URL.'/users/'.$user['id'] : APP_URL.'/users' ?>" class="card p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
        <?php $e = $errors ?? []; ?>

        <?php if (!$isEdit): ?>
        <div>
            <label class="form-label">Username <span class="text-red-500">*</span></label>
            <input type="text" name="username" value="<?= $v('username') ?>" required
                   class="form-input <?= !empty($e['username']) ? 'border-red-500' : '' ?>" placeholder="jsmith">
            <?php if (!empty($e['username'])): ?><p class="form-error"><?= $e['username'] ?></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <div>
            <label class="form-label">Full Name <span class="text-red-500">*</span></label>
            <input type="text" name="full_name" value="<?= $v('full_name') ?>" required
                   class="form-input <?= !empty($e['full_name']) ? 'border-red-500' : '' ?>" placeholder="John Smith">
            <?php if (!empty($e['full_name'])): ?><p class="form-error"><?= $e['full_name'] ?></p><?php endif; ?>
        </div>

        <div>
            <label class="form-label">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="<?= $v('email') ?>" required
                   class="form-input <?= !empty($e['email']) ? 'border-red-500' : '' ?>" placeholder="john@example.com">
            <?php if (!empty($e['email'])): ?><p class="form-error"><?= $e['email'] ?></p><?php endif; ?>
        </div>

        <div>
            <label class="form-label">Role</label>
            <select name="role" class="form-input">
                <?php foreach (['utilisateur'=>'User','technicien'=>'Technician','admin'=>'Administrator'] as $val=>$lbl): ?>
                <option value="<?= $val ?>" <?= ($user['role'] ?? 'utilisateur') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($isEdit): ?>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded"
                   <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
            <label for="is_active" class="text-sm text-slate-700 dark:text-slate-300">Account active</label>
        </div>
        <?php endif; ?>

        <div>
            <label class="form-label"><?= $isEdit ? 'New Password (leave blank to keep current)' : 'Password *' ?></label>
            <input type="password" name="password" <?= !$isEdit ? 'required' : '' ?> minlength="6"
                   class="form-input" placeholder="<?= $isEdit ? 'Optional' : 'Min. 6 characters' ?>">
            <?php if (!empty($e['password'])): ?><p class="form-error"><?= $e['password'] ?></p><?php endif; ?>
        </div>

        <div class="flex gap-3 pt-2">
            <a href="<?= APP_URL ?>/users" class="btn-secondary flex-1 justify-center">Cancel</a>
            <button type="submit" class="btn-primary flex-1 justify-center">
                <i data-lucide="save" class="w-4 h-4"></i>
                <?= $isEdit ? 'Save Changes' : 'Create User' ?>
            </button>
        </div>
    </form>
</div>
