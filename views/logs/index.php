<?php $pageTitle = 'Activity Log'; ?>

<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Activity Log</h1>
        <p class="text-slate-500 text-sm"><?= number_format($total) ?> events recorded</p>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600 dark:text-slate-400">Action</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 hidden md:table-cell">User</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 hidden lg:table-cell">Entity</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 hidden xl:table-cell">IP</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-400">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php
                    $actionColors = [
                        'login'        => 'bg-green-100  text-green-700  dark:bg-green-900/40  dark:text-green-400',
                        'logout'       => 'bg-slate-100  text-slate-600  dark:bg-slate-700     dark:text-slate-300',
                        'login_failed' => 'bg-red-100    text-red-700    dark:bg-red-900/40    dark:text-red-400',
                        'task_created' => 'bg-blue-100   text-blue-700   dark:bg-blue-900/40   dark:text-blue-400',
                        'task_updated' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
                        'task_deleted' => 'bg-red-100    text-red-700    dark:bg-red-900/40    dark:text-red-400',
                        'register'     => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
                    ];
                    foreach ($logs as $log):
                        $color = $actionColors[$log['action']] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                        <td class="px-5 py-3">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full <?= $color ?>">
                                <?= htmlspecialchars($log['action']) ?>
                            </span>
                            <?php if ($log['details']): ?>
                            <div class="text-xs text-slate-400 mt-0.5 truncate max-w-xs"><?= htmlspecialchars($log['details']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-slate-700 dark:text-slate-300">
                            <?= htmlspecialchars($log['full_name'] ?? '—') ?>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <?php if ($log['entity']): ?>
                            <span class="text-xs text-slate-500"><?= htmlspecialchars($log['entity']) ?> #<?= $log['entity_id'] ?></span>
                            <?php else: ?><span class="text-slate-400">—</span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3 hidden xl:table-cell text-xs text-slate-400 font-mono">
                            <?= htmlspecialchars($log['ip_address'] ?? '—') ?>
                        </td>
                        <td class="px-4 py-3 text-right text-xs text-slate-400">
                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
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
                <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
                <a href="?page=<?= $i ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $i===$page ? 'bg-brand-600 text-white' : 'btn-secondary' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
