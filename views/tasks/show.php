<?php
$pageTitle   = $task['title'];
$isTech      = AuthMiddleware::isTechOrAdmin();
$isAdmin     = AuthMiddleware::isAdmin();
$sLabels     = ['a_faire'=>'To Do','en_cours'=>'In Progress','termine'=>'Completed','bloque'=>'Blocked'];
?>

<div class="max-w-4xl mx-auto space-y-5">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex items-start gap-3">
            <a href="<?= APP_URL ?>/tasks"
               class="mt-1 w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="badge-<?= $task['priority'] ?> text-xs font-medium px-2.5 py-0.5 rounded-full capitalize"><?= $task['priority'] ?></span>
                    <span class="status-<?= $task['status'] ?> text-xs font-medium px-2.5 py-0.5 rounded-full"><?= $sLabels[$task['status']] ?></span>
                    <span class="text-xs text-slate-400">#<?= $task['id'] ?></span>
                </div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($task['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Created by <?= htmlspecialchars($task['creator_name']) ?> on <?= date('d/m/Y \a\t H:i', strtotime($task['created_at'])) ?>
                </p>
            </div>
        </div>

        <?php if ($isTech): ?>
        <div class="flex gap-2 shrink-0">
            <a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/edit" class="btn-secondary text-xs">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
            </a>
            <?php if ($isAdmin): ?>
            <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/delete"
                  onsubmit="return confirm('Permanently delete this task?')">
                <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                <button type="submit" class="btn-danger text-xs">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- Main -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Description -->
            <div class="card p-6">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4 text-brand-600"></i> Description
                </h3>
                <?php if ($task['description']): ?>
                <div class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-wrap">
                    <?= htmlspecialchars($task['description']) ?>
                </div>
                <?php else: ?>
                <p class="text-slate-400 text-sm italic">No description provided.</p>
                <?php endif; ?>
            </div>

            <!-- Quick status change -->
            <?php if ($isTech): ?>
            <div class="card p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm flex items-center gap-2">
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-brand-600"></i> Quick Status Change
                </h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($sLabels as $val => $lbl): ?>
                    <?php if ($val !== $task['status']): ?>
                    <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>">
                        <input type="hidden" name="_csrf"        value="<?= $csrfToken ?>">
                        <input type="hidden" name="status"       value="<?= $val ?>">
                        <input type="hidden" name="title"        value="<?= htmlspecialchars($task['title']) ?>">
                        <input type="hidden" name="description"  value="<?= htmlspecialchars($task['description'] ?? '') ?>">
                        <input type="hidden" name="priority"     value="<?= $task['priority'] ?>">
                        <input type="hidden" name="assigned_to"  value="<?= $task['assigned_to'] ?? '' ?>">
                        <input type="hidden" name="start_date"   value="<?= $task['start_date'] ?? '' ?>">
                        <input type="hidden" name="due_date"     value="<?= $task['due_date'] ?? '' ?>">
                        <button type="submit" class="text-xs status-<?= $val ?> px-3 py-1.5 rounded-lg font-medium hover:ring-2 hover:ring-offset-1 transition">
                            → <?= $lbl ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Attachments -->
            <div class="card p-6" id="attachments">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i data-lucide="paperclip" class="w-4 h-4 text-brand-600"></i>
                    Attachments (<?= count($attachments) ?>)
                </h3>

                <?php if (!empty($attachments)): ?>
                <div class="space-y-2 mb-4">
                    <?php foreach ($attachments as $att): ?>
                    <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <i data-lucide="file" class="w-4 h-4 text-brand-600 shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">
                                <?= htmlspecialchars($att['original_name']) ?>
                            </div>
                            <div class="text-xs text-slate-400">
                                <?= round($att['file_size']/1024, 1) ?> KB
                                &bull; <?= htmlspecialchars($att['full_name']) ?>
                                &bull; <?= date('d/m/Y', strtotime($att['created_at'])) ?>
                            </div>
                        </div>
                        <a href="<?= UPLOADS_URL ?>/<?= $att['filename'] ?>" target="_blank"
                           class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-900/30 transition">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        </a>
                        <?php if ($isTech): ?>
                        <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/delete-attachment/<?= $att['id'] ?>"
                              onsubmit="return confirm('Delete this attachment?')">
                            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                            <button type="submit"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/upload" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                    <div class="flex gap-2">
                        <input type="file" name="attachment" class="form-input text-xs flex-1">
                        <button type="submit" class="btn-secondary text-xs shrink-0">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i> Attach
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Max 10 MB — PDF, images, documents, archives accepted</p>
                </form>
            </div>

            <!-- Comments -->
            <div class="card p-6" id="comments">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i data-lucide="message-circle" class="w-4 h-4 text-brand-600"></i>
                    Comments (<?= count($comments) ?>)
                </h3>

                <?php if (empty($comments)): ?>
                <p class="text-slate-400 text-sm italic mb-4">No comments yet.</p>
                <?php else: ?>
                <div class="space-y-4 mb-5">
                    <?php foreach ($comments as $c): ?>
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-600/20 text-brand-600 flex items-center justify-center text-xs font-bold shrink-0">
                            <?= strtoupper(substr($c['full_name'], 0, 2)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-sm text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($c['full_name']) ?>
                                </span>
                                <span class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                            </div>
                            <div class="text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-700/50 rounded-xl px-4 py-3 whitespace-pre-wrap">
                                <?= htmlspecialchars($c['content']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/comment">
                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                            <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 2)) ?>
                        </div>
                        <div class="flex-1">
                            <textarea name="content" rows="3" required class="form-input resize-none text-sm"
                                      placeholder="Write a comment…"></textarea>
                            <div class="flex justify-end mt-2">
                                <button type="submit" class="btn-primary text-xs">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i> Comment
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-5">
            <div class="card p-5 space-y-4">
                <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-slate-500">Assigned to</span>
                        <span class="font-medium text-slate-900 dark:text-white text-right">
                            <?= $task['assigned_name']
                                ? htmlspecialchars($task['assigned_name'])
                                : '<span class="text-slate-400 italic font-normal">Unassigned</span>' ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-slate-500">Created by</span>
                        <span class="font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($task['creator_name']) ?></span>
                    </div>
                    <?php if ($task['start_date']): ?>
                    <div class="flex justify-between gap-2">
                        <span class="text-slate-500">Start date</span>
                        <span class="font-medium"><?= date('d/m/Y', strtotime($task['start_date'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($task['due_date']):
                        $over = $task['due_date'] < date('Y-m-d') && $task['status'] !== 'termine'; ?>
                    <div class="flex justify-between gap-2">
                        <span class="text-slate-500">Due date</span>
                        <span class="font-medium <?= $over?'text-red-500':'' ?>">
                            <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                            <?php if ($over): ?><span class="text-xs">(Overdue)</span><?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($task['completed_at']): ?>
                    <div class="flex justify-between gap-2">
                        <span class="text-slate-500">Completed</span>
                        <span class="font-medium text-green-600"><?= date('d/m/Y', strtotime($task['completed_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between gap-2">
                        <span class="text-slate-500">Updated</span>
                        <span class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($task['updated_at'])) ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($history)): ?>
            <div class="card p-5">
                <h3 class="font-semibold text-slate-900 dark:text-white text-sm mb-3">History</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin">
                    <?php foreach ($history as $h): ?>
                    <div class="flex gap-2 text-xs">
                        <div class="w-1.5 h-1.5 rounded-full bg-brand-600 mt-1.5 shrink-0"></div>
                        <div>
                            <span class="font-medium text-slate-800 dark:text-slate-200"><?= htmlspecialchars($h['full_name']) ?></span>
                            <span class="text-slate-500"> — <?= htmlspecialchars($h['action']) ?></span>
                            <?php if ($h['field_name']): ?>
                            <div class="text-slate-400 mt-0.5">
                                <?= htmlspecialchars($h['field_name']) ?>:
                                <?php if ($h['old_value']): ?>
                                <span class="line-through"><?= htmlspecialchars($h['old_value']) ?></span> →
                                <?php endif; ?>
                                <span class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($h['new_value'] ?? '') ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="text-slate-400"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
