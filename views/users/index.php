<?php $pageTitle = 'Users'; ?>
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Users</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm"><?= $total ?> user<?= $total > 1 ? 's' : '' ?></p>
        </div>
        <a href="<?= APP_URL ?>/users/create" class="btn-primary">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Add User
        </a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600 dark:text-slate-400">User</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-400 hidden sm:table-cell">Role</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-400 hidden md:table-cell">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 hidden lg:table-cell">Last Login</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php foreach ($users as $u):
                        $rc = [
                            'admin'       => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
                            'technicien'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                            'utilisateur' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                        ];
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-brand-600/20 text-brand-600 flex items-center justify-center font-bold text-sm shrink-0">
                                    <?= strtoupper(substr($u['full_name'], 0, 2)) ?>
                                </div>
                                <div>
                                    <div class="font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($u['full_name']) ?></div>
                                    <div class="text-xs text-slate-400">@<?= htmlspecialchars($u['username']) ?> &bull; <?= htmlspecialchars($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-center hidden sm:table-cell">
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full capitalize <?= $rc[$u['role']] ?? '' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-center hidden md:table-cell">
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full <?= $u['is_active'] ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' ?>">
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-xs text-slate-400 hidden lg:table-cell">
                            <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Never' ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= APP_URL ?>/users/<?= $u['id'] ?>/edit"
                                   class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </a>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" action="<?= APP_URL ?>/users/<?= $u['id'] ?>/toggle">
                                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                    <button type="submit" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 transition">
                                        <i data-lucide="<?= $u['is_active'] ? 'toggle-right' : 'toggle-left' ?>" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                                <form method="POST" action="<?= APP_URL ?>/users/<?= $u['id'] ?>/delete" onsubmit="return confirm('Delete this user?')">
                                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                    <button type="submit" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
        <div class="flex items-center justify-between px-5 py-3 border-t border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500">Page <?= $page ?> / <?= $pages ?></p>
            <div class="flex gap-1">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $i===$page ? 'bg-brand-600 text-white' : 'btn-secondary' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
