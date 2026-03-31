<?php
$isEdit    = !empty($task['id']);
$pageTitle = $isEdit ? 'Edit Task' : 'New Task';
$v = fn($f, $def='') => htmlspecialchars($task[$f] ?? $def);
?>

<div class="max-w-3xl mx-auto space-y-5">
    <div class="flex items-center gap-3">
        <a href="<?= APP_URL ?>/tasks" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white"><?= $pageTitle ?></h1>
            <p class="text-slate-500 text-sm"><?= $isEdit ? 'Update task details' : 'Create a new task for your team' ?></p>
        </div>
    </div>

    <form method="POST" action="<?= $isEdit ? APP_URL.'/tasks/'.$task['id'] : APP_URL.'/tasks' ?>" class="space-y-5">
        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

        <!-- General info -->
        <div class="card p-6 space-y-5">
            <h2 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4 text-brand-600"></i> General Information
            </h2>

            <div>
                <label class="form-label">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="<?= $v('title') ?>" required maxlength="200"
                       class="form-input <?= !empty($errors['title'])?'border-red-500':'' ?>"
                       placeholder="e.g. Update production server">
                <?php if (!empty($errors['title'])): ?><p class="form-error"><?= $errors['title'] ?></p><?php endif; ?>
            </div>

            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="4" class="form-input resize-none"
                          placeholder="Describe the task in detail…"><?= $v('description') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" class="form-input">
                        <?php foreach (['basse'=>'🟢 Low','moyenne'=>'🔵 Medium','haute'=>'🟠 High','critique'=>'🔴 Critical'] as $val=>$lbl): ?>
                        <option value="<?= $val ?>" <?= ($task['priority']??'moyenne')===$val?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" class="form-input">
                        <?php foreach (['a_faire'=>'To Do','en_cours'=>'In Progress','termine'=>'Completed','bloque'=>'Blocked'] as $val=>$lbl): ?>
                        <option value="<?= $val ?>" <?= ($task['status']??'a_faire')===$val?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Assignment & dates -->
        <div class="card p-6 space-y-5">
            <h2 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-brand-600"></i> Assignment & Planning
            </h2>

            <div>
                <label class="form-label">Assign To
                    <span class="text-slate-400 font-normal text-xs">(hold Ctrl / Cmd to select multiple)</span>
                </label>
                <select name="assigned_users[]" multiple class="form-input" style="height:auto;min-height:100px">
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"
                        <?= in_array($u['id'], $assigneeIds ?? []) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['full_name']) ?> (<?= $u['role'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-slate-400 mt-1">Leave blank to leave unassigned</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" value="<?= $v('start_date') ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="<?= $v('due_date') ?>"
                           class="form-input <?= !empty($errors['due_date'])?'border-red-500':'' ?>">
                    <?php if (!empty($errors['due_date'])): ?><p class="form-error"><?= $errors['due_date'] ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <a href="<?= $isEdit ? APP_URL.'/tasks/'.$task['id'] : APP_URL.'/tasks' ?>" class="btn-secondary">
                <i data-lucide="x" class="w-4 h-4"></i> Cancel
            </a>
            <button type="submit" class="btn-primary">
                <i data-lucide="<?= $isEdit?'save':'plus' ?>" class="w-4 h-4"></i>
                <?= $isEdit ? 'Save Changes' : 'Create Task' ?>
            </button>
        </div>
    </form>
</div>