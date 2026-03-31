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

            <!-- ── SUBTASKS ─────────────────────────────────────────────── -->
            <div class="card p-6" id="subtasks">
                <!-- Header with progress -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="list-checks" class="w-4 h-4 text-brand-600"></i>
                        Subtasks
                        <?php if ($progress['total'] > 0): ?>
                        <span class="text-xs font-normal text-slate-400">(<?= $progress['done'] ?>/<?= $progress['total'] ?>)</span>
                        <?php endif; ?>
                    </h3>
                    <?php if ($progress['total'] > 0): ?>
                    <span class="text-sm font-semibold
                        <?= $progress['percent'] >= 100 ? 'text-green-600' : ($progress['percent'] >= 50 ? 'text-brand-600' : 'text-yellow-600') ?>">
                        <?= $progress['percent'] ?>%
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Progress bar -->
                <?php if ($progress['total'] > 0): ?>
                <div class="mb-5">
                    <div class="w-full h-2.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500
                            <?= $progress['percent'] >= 100 ? 'bg-green-500' : ($progress['percent'] >= 50 ? 'bg-brand-600' : 'bg-yellow-400') ?>"
                             style="width:<?= $progress['percent'] ?>%"></div>
                    </div>
                    <?php if ($progress['percent'] >= 100): ?>
                    <p class="text-xs text-green-600 font-medium mt-1 flex items-center gap-1">
                        <i data-lucide="check-circle" class="w-3 h-3"></i> All subtasks completed
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Subtask list -->
                <?php if (!empty($subtasks)): ?>
                <div class="space-y-3 mb-5">
                    <?php foreach ($subtasks as $st): ?>
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">

                        <!-- Subtask header row -->
                        <div class="flex items-start gap-3 p-3
                            <?= $st['status'] === 'termine' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-slate-50 dark:bg-slate-700/30' ?>">

                            <!-- Status icon -->
                            <div class="mt-0.5 shrink-0">
                                <?php if ($st['status'] === 'termine'): ?>
                                <div class="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center">
                                    <i data-lucide="check" class="w-3 h-3 text-white"></i>
                                </div>
                                <?php else: ?>
                                <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-500"></div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium
                                        <?= $st['status'] === 'termine' ? 'text-slate-400 line-through' : 'text-slate-800 dark:text-slate-200' ?>">
                                        <?= htmlspecialchars($st['title']) ?>
                                    </span>
                                    <?php if ($st['assigned_name']): ?>
                                    <span class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-2 py-0.5 rounded-full">
                                        <?= htmlspecialchars($st['assigned_name']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($st['description']): ?>
                                <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($st['description']) ?></p>
                                <?php endif; ?>
                                <?php if ($st['status'] === 'termine'): ?>
                                <p class="text-xs text-green-600 mt-0.5">
                                    Completed by <?= htmlspecialchars($st['completed_by_name'] ?? '—') ?>
                                    on <?= date('d/m/Y H:i', strtotime($st['completed_at'])) ?>
                                </p>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 shrink-0">
                                <?php if ($st['status'] === 'termine'): ?>

                                    <!-- View report button -->
                                    <?php if ($st['report_text'] || $st['report_file']): ?>
                                    <button onclick="toggleReport(<?= $st['id'] ?>)"
                                            class="text-xs text-brand-600 hover:text-brand-700 font-medium px-2 py-1 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-900/30 transition">
                                        View Report
                                    </button>
                                    <?php endif; ?>

                                    <!-- Reopen (tech/admin only) -->
                                    <?php if ($isTech): ?>
                                    <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/subtasks/<?= $st['id'] ?>/reopen">
                                        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                        <button type="submit"
                                                class="text-xs text-slate-400 hover:text-yellow-600 px-2 py-1 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/30 transition"
                                                title="Reopen subtask">
                                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                <?php else: ?>

                                    <!-- Complete button — show to assigned user, tech, or admin -->
                                    <?php
                                    $canComplete = AuthMiddleware::isTechOrAdmin()
                                        || $st['assigned_to'] == $_SESSION['user_id'];
                                    ?>
                                    <?php if ($canComplete): ?>
                                    <button onclick="toggleCompleteForm(<?= $st['id'] ?>)"
                                            class="text-xs btn-primary py-1 px-2.5">
                                        <i data-lucide="check" class="w-3 h-3"></i> Complete
                                    </button>
                                    <?php endif; ?>

                                <?php endif; ?>

                                <!-- Delete (tech/admin only) -->
                                <?php if ($isTech): ?>
                                <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/subtasks/<?= $st['id'] ?>/delete"
                                      onsubmit="return confirm('Delete this subtask?')">
                                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                    <button type="submit"
                                            class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Completion report (shown when View Report is clicked) -->
                        <?php if ($st['status'] === 'termine' && ($st['report_text'] || $st['report_file'])): ?>
                        <div id="report-<?= $st['id'] ?>" class="hidden border-t border-slate-200 dark:border-slate-700 p-3 bg-white dark:bg-slate-800">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Completion Report</p>
                            <?php if ($st['report_text']): ?>
                            <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap mb-2">
                                <?= htmlspecialchars($st['report_text']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($st['report_file']): ?>
                            <a href="<?= UPLOADS_URL ?>/<?= $st['report_file'] ?>" target="_blank"
                               class="inline-flex items-center gap-1.5 text-xs text-brand-600 hover:underline">
                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                <?= htmlspecialchars($st['report_filename'] ?? $st['report_file']) ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Complete form (shown inline when Complete is clicked) -->
                        <?php if ($st['status'] !== 'termine'): ?>
                        <div id="complete-form-<?= $st['id'] ?>" class="hidden border-t border-slate-200 dark:border-slate-700 p-4 bg-white dark:bg-slate-800">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                                Complete "<?= htmlspecialchars($st['title']) ?>"
                            </p>
                            <form method="POST"
                                  action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/subtasks/<?= $st['id'] ?>/complete"
                                  enctype="multipart/form-data"
                                  class="space-y-3">
                                <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

                                <div>
                                    <label class="form-label text-xs">Completion Report <span class="text-slate-400 font-normal">(optional)</span></label>
                                    <textarea name="report_text" rows="3"
                                              class="form-input resize-none text-sm"
                                              placeholder="Describe what was done, findings, or any notes…"></textarea>
                                </div>

                                <div>
                                    <label class="form-label text-xs">Attach Report File <span class="text-slate-400 font-normal">(optional — PDF, doc, image)</span></label>
                                    <input type="file" name="report_file" class="form-input text-xs">
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary text-xs">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Mark as Complete
                                    </button>
                                    <button type="button" onclick="toggleCompleteForm(<?= $st['id'] ?>)"
                                            class="btn-secondary text-xs">Cancel</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-slate-400 text-sm italic mb-4">No subtasks yet.</p>
                <?php endif; ?>

                <!-- Add subtask form (tech/admin only) -->
                <?php if ($isTech): ?>
                <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                    <button onclick="toggleAddForm()"
                            id="add-subtask-btn"
                            class="btn-secondary text-xs w-full justify-center">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Subtask
                    </button>

                    <form id="add-subtask-form"
                          method="POST"
                          action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/subtasks"
                          class="hidden mt-3 space-y-3">
                        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

                        <div>
                            <label class="form-label text-xs">Subtask Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required class="form-input"
                                   placeholder="e.g. Configure firewall rules">
                        </div>

                        <div>
                            <label class="form-label text-xs">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                            <textarea name="description" rows="2" class="form-input resize-none text-sm"
                                      placeholder="Additional details…"></textarea>
                        </div>

                        <div>
                            <label class="form-label text-xs">Assign To</label>
                            <select name="assigned_to" class="form-input">
                                <option value="">— Unassigned —</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"
                                    <?= $u['id'] == $task['assigned_to'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['full_name']) ?> (<?= $u['role'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary text-xs">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Subtask
                            </button>
                            <button type="button" onclick="toggleAddForm()" class="btn-secondary text-xs">Cancel</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <!-- ── END SUBTASKS ──────────────────────────────────────────── -->

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
                        <input type="hidden" name="title"        value="<?= htmlspecialchars($task['title']) ?>">
                        <input type="hidden" name="description"  value="<?= htmlspecialchars($task['description'] ?? '') ?>">
                        <input type="hidden" name="priority"     value="<?= $task['priority'] ?>">
                        <input type="hidden" name="status"       value="<?= $val ?>">
                        <input type="hidden" name="start_date"   value="<?= $task['start_date'] ?? '' ?>">
                        <input type="hidden" name="due_date"     value="<?= $task['due_date'] ?? '' ?>">
                        <?php foreach ($assignees as $a): ?>
                        <input type="hidden" name="assigned_users[]" value="<?= $a['id'] ?>">
                        <?php endforeach; ?>
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
                        <div class="text-right">
                            <?php if (!empty($assignees)): ?>
                            <?php foreach ($assignees as $a): ?>
                            <div class="font-medium text-slate-900 dark:text-white text-sm">
                                <?= htmlspecialchars($a['full_name']) ?>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <span class="text-slate-400 italic font-normal text-sm">Unassigned</span>
                            <?php endif; ?>
                        </div>
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

<script>
// ── Subtask UI helpers ────────────────────────────────────────────────────────

function toggleAddForm() {
    const form = document.getElementById('add-subtask-form');
    const btn  = document.getElementById('add-subtask-btn');
    const hidden = form.classList.contains('hidden');
    form.classList.toggle('hidden', !hidden);
    btn.classList.toggle('hidden', hidden);
    if (!hidden) form.querySelector('input[name="title"]')?.focus();
}

function toggleCompleteForm(id) {
    const el = document.getElementById('complete-form-' + id);
    if (!el) return;
    el.classList.toggle('hidden');
    if (!el.classList.contains('hidden')) {
        el.querySelector('textarea')?.focus();
    }
}

function toggleReport(id) {
    const el = document.getElementById('report-' + id);
    if (el) el.classList.toggle('hidden');
}
</script>