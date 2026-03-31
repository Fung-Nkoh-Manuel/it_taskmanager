<?php $pageTitle = 'Tasks'; ?>

<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tasks</h1>
            <p class="text-slate-500 text-sm mt-0.5"><?= number_format($total) ?> task<?= $total !== 1 ? 's' : '' ?> found</p>
        </div>
        <?php if ($isTech): ?>
        <a href="<?= APP_URL ?>/tasks/create" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> New Task
        </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= APP_URL ?>/tasks" class="card p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2 relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                       class="form-input pl-9" placeholder="Search tasks…">
            </div>
            <select name="status" class="form-input">
                <option value="">All statuses</option>
                <?php foreach (['a_faire'=>'To Do','en_cours'=>'In Progress','termine'=>'Completed','bloque'=>'Blocked'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $filters['status']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <select name="priority" class="form-input">
                <option value="">All priorities</option>
                <?php foreach (['critique'=>'Critical','haute'=>'High','moyenne'=>'Medium','basse'=>'Low'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $filters['priority']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($isTech): ?>
            <select name="assigned_to" class="form-input">
                <option value="">All members</option>
                <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $filters['assigned_to']==$u['id']?'selected':'' ?>>
                    <?= htmlspecialchars($u['full_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
        <div class="flex gap-2 mt-3">
            <button type="submit" class="btn-primary text-xs px-3 py-1.5">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
            </button>
            <a href="<?= APP_URL ?>/tasks" class="btn-secondary text-xs px-3 py-1.5">
                <i data-lucide="x" class="w-3.5 h-3.5"></i> Reset
            </a>
        </div>
    </form>

    <!-- Table -->
    <div class="card overflow-hidden">
        <?php if (empty($tasks)): ?>
        <div class="py-16 text-center">
            <i data-lucide="inbox" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3"></i>
            <p class="text-slate-500 font-medium">No tasks found</p>
            <p class="text-slate-400 text-sm mt-1">Try adjusting your filters or create a new task.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-600 dark:text-slate-400">Task</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-400 hidden md:table-cell">Priority</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 hidden lg:table-cell">Assigned To</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 hidden xl:table-cell">Due Date</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php
                    $sLabels = ['a_faire'=>'To Do','en_cours'=>'In Progress','termine'=>'Completed','bloque'=>'Blocked'];
                    foreach ($tasks as $task):
                        $isOverdue = $task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'termine';
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition group <?= $isOverdue?'bg-red-50/40 dark:bg-red-900/10':'' ?>">
                        <td class="px-5 py-3.5">
                            <a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>"
                               class="group-hover:text-brand-600 font-medium text-slate-900 dark:text-white block">
                                <?= htmlspecialchars($task['title']) ?>
                            </a>
                            <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-1">
                                By <?= htmlspecialchars($task['creator_name']) ?>
                                <?php if ($isOverdue): ?>
                                <span class="text-red-500 font-medium flex items-center gap-0.5">
                                    <i data-lucide="alert-triangle" class="w-3 h-3"></i> Overdue
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($progress[$task['id']]) && $progress[$task['id']]['total'] > 0):
                                $p = $progress[$task['id']]; ?>
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                    <div class="h-1.5 rounded-full transition-all duration-300
                                        <?= $p['percent'] >= 100 ? 'bg-green-500' : ($p['percent'] >= 50 ? 'bg-brand-500' : 'bg-yellow-400') ?>"
                                         style="width:<?= $p['percent'] ?>%"></div>
                                </div>
                                <span class="text-xs text-slate-400 shrink-0">
                                    <?= $p['done'] ?>/<?= $p['total'] ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5 text-center hidden md:table-cell">
                            <span class="badge-<?= $task['priority'] ?> text-xs font-medium px-2.5 py-1 rounded-full capitalize">
                                <?= $task['priority'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="status-<?= $task['status'] ?> text-xs font-medium px-2.5 py-1 rounded-full">
                                <?= $sLabels[$task['status']] ?? $task['status'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 hidden lg:table-cell">
                            <?php
                            $names = !empty($task['all_assignees'])
                                ? explode('|', $task['all_assignees'])
                                : [];
                            ?>
                            <?php if (!empty($names)): ?>
                            <div class="flex items-center gap-1 flex-wrap">
                                <?php foreach (array_slice($names, 0, 3) as $i => $name): ?>
                                <div class="flex items-center gap-1.5"
                                     title="<?= htmlspecialchars($name) ?>">
                                    <div class="w-6 h-6 rounded-full bg-brand-600/20 text-brand-600 flex items-center justify-center text-xs font-bold shrink-0">
                                        <?= strtoupper(substr($name, 0, 2)) ?>
                                    </div>
                                    <?php if (count($names) === 1): ?>
                                    <span class="text-slate-700 dark:text-slate-300 truncate max-w-[100px] text-xs">
                                        <?= htmlspecialchars($name) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php if (count($names) > 3): ?>
                                <span class="text-xs text-slate-400">+<?= count($names) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-slate-400 text-xs italic">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5 hidden xl:table-cell">
                            <?php if ($task['due_date']): ?>
                            <span class="text-xs <?= $isOverdue?'text-red-500 font-medium':'text-slate-500' ?>">
                                <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                            </span>
                            <?php else: ?><span class="text-slate-400 text-xs">—</span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>"
                                   class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-900/30 transition">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </a>
                                <?php if ($isTech): ?>
                                <a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/edit"
                                   class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (AuthMiddleware::isAdmin()): ?>
                                <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/delete"
                                      onsubmit="return confirm('Delete this task?')">
                                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                    <button type="submit"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
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

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="flex items-center justify-between px-5 py-3 border-t border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500">Page <?= $page ?> of <?= $pages ?></p>
            <div class="flex gap-1">
                <?php $qs = http_build_query(array_merge($filters, ['page' => max(1, $page-1)])); ?>
                <a href="?<?= $qs ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $page<=1?'text-slate-300 cursor-not-allowed':'btn-secondary' ?>">← Prev</a>
                <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++):
                    $qi = http_build_query(array_merge($filters, ['page' => $i])); ?>
                <a href="?<?= $qi ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $i===$page?'bg-brand-600 text-white':'btn-secondary' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php $qn = http_build_query(array_merge($filters, ['page' => min($pages,$page+1)])); ?>
                <a href="?<?= $qn ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $page>=$pages?'text-slate-300 cursor-not-allowed':'btn-secondary' ?>">Next →</a>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>