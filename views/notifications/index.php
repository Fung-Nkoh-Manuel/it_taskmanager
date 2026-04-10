<?php $pageTitle = 'Notifications'; ?>

<div class="max-w-2xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Notifications</h1>
            <p class="text-slate-500 text-sm"><?= count($notifications) ?> notification<?= count($notifications) !== 1 ? 's' : '' ?></p>
        </div>
        <?php if (!empty($notifications)): ?>
        <form method="POST" action="<?= APP_URL ?>/notifications/read-all">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
            <button type="submit" class="btn-secondary text-xs">
                <i data-lucide="check-check" class="w-3.5 h-3.5"></i> Mark all read
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (AuthMiddleware::isAdmin()): ?>
    <form method="POST" action="<?= APP_URL ?>/notifications/send-due-reminders" class="card p-3 flex items-center justify-between">
        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
        <p class="text-sm text-slate-600 dark:text-slate-300">Trigger due-date reminder emails (next 24 hours)</p>
        <button type="submit" class="btn-secondary text-xs">
            <i data-lucide="send" class="w-3.5 h-3.5"></i> Send reminders
        </button>
    </form>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
    <div class="card py-16 text-center">
        <i data-lucide="bell-off" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3"></i>
        <p class="text-slate-500 font-medium">No notifications</p>
        <p class="text-slate-400 text-sm mt-1">You are all caught up!</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
        <?php
        $icons = [
            'assignation'  => ['bell',           'brand-600'],
            'echeance'     => ['clock',           'yellow-600'],
            'commentaire'  => ['message-circle',  'green-600'],
            'statut'       => ['refresh-cw',      'blue-600'],
            'systeme'      => ['info',            'slate-500'],
        ];
        foreach ($notifications as $n):
            [$ico, $col] = $icons[$n['type']] ?? ['bell', 'slate-500'];
        ?>
        <div class="card p-4 flex items-start gap-3 <?= !$n['is_read'] ? 'border-l-4 border-brand-500' : '' ?>">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0
                <?= !$n['is_read'] ? 'bg-brand-100 dark:bg-brand-900/40' : 'bg-slate-100 dark:bg-slate-700' ?>">
                <i data-lucide="<?= $ico ?>" class="w-4 h-4 text-<?= $col ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-slate-800 dark:text-slate-200 <?= !$n['is_read'] ? 'font-medium' : '' ?>">
                    <?= htmlspecialchars($n['message']) ?>
                </p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></span>
                    <?php if ($n['task_id']): ?>
                    <a href="<?= APP_URL ?>/tasks/<?= $n['task_id'] ?>" class="text-xs text-brand-600 hover:underline">View task</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$n['is_read']): ?>
            <form method="POST" action="<?= APP_URL ?>/notifications/read/<?= $n['id'] ?>">
                <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                <button type="submit" class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-400 hover:text-brand-600 transition" title="Mark as read">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
