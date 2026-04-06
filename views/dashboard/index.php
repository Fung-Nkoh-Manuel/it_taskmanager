<?php $pageTitle = 'Dashboard'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Dashboard</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">
                Welcome back, <?= htmlspecialchars($user['full_name']) ?> 👋
            </p>
        </div>
        <div class="text-sm text-slate-400"><?= date('l, F j, Y') ?></div>
    </div>

    <!-- Stat cards row 1 -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $cards = [
            ['label'=>'Total Tasks',   'value'=>$stats['total']??0,     'icon'=>'check-square',  'color'=>'blue',   'sub'=>'All categories'],
            ['label'=>'In Progress',   'value'=>$stats['en_cours']??0,  'icon'=>'loader',        'color'=>'indigo', 'sub'=>'Active'],
            ['label'=>'Completed',     'value'=>$stats['termine']??0,   'icon'=>'check-circle',  'color'=>'green',  'sub'=>'Done'],
            ['label'=>'Overdue',       'value'=>$stats['en_retard']??0, 'icon'=>'alert-triangle','color'=>'red',    'sub'=>'Past due date'],
        ];
        $colorMap = [
            'blue'   => 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400',
            'indigo' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400',
            'green'  => 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400',
            'red'    => 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400',
        ];

        foreach ($cards as $card): ?>
        <div class="card p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?= $colorMap[$card['color']] ?>">
                <i data-lucide="<?= $card['icon'] ?>" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format((int)$card['value']) ?></div>
                <div class="text-sm font-medium text-slate-700 dark:text-slate-200"><?= $card['label'] ?></div>
                <div class="text-xs text-slate-400"><?= $card['sub'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Stat cards row 2 -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <?php foreach ([
            ['icon'=>'clock', 'bg'=>'yellow', 'value'=>$stats['a_faire']??0, 'label'=>'To Do'],
            ['icon'=>'ban',   'bg'=>'orange', 'value'=>$stats['bloque']??0,  'label'=>'Blocked'],
            ['icon'=>'zap',   'bg'=>'purple', 'value'=>$stats['urgentes']??0,'label'=>'Urgent (3 days)'],
        ] as $s):
        $bg = [
            'yellow' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600',
            'orange' => 'bg-orange-100 dark:bg-orange-900/40 text-orange-600',
            'purple' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-600',
        ][$s['bg']]; ?>
        <div class="card p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl <?= $bg ?> flex items-center justify-center shrink-0">
                <i data-lucide="<?= $s['icon'] ?>" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xl font-bold"><?= (int)$s['value'] ?></div>
                <div class="text-sm text-slate-500"><?= $s['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card p-6 lg:col-span-2">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Monthly Overview</h3>
            <div class="h-52"><canvas id="monthlyChart"></canvas></div>
        </div>
        <div class="card p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">By Priority</h3>
            <div class="h-52 flex items-center justify-center"><canvas id="priorityChart"></canvas></div>
        </div>
    </div>

    <!-- Overdue + Recent -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <?php if (!empty($overdue)): ?>
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i> Overdue Tasks
                </h3>
                <span class="text-xs bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 px-2 py-0.5 rounded-full">
                    <?= count($overdue) ?>
                </span>
            </div>
            <div class="space-y-2">
                <?php foreach (array_slice($overdue, 0, 5) as $t): ?>
                <a href="<?= APP_URL ?>/tasks/<?= $t['id'] ?>"
                   class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                    <div class="w-2 h-2 rounded-full bg-red-500 shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate group-hover:text-brand-600">
                            <?= htmlspecialchars($t['title']) ?>
                        </div>
                        <div class="text-xs text-slate-400">
                            <?= htmlspecialchars($t['assigned_name'] ?? 'Unassigned') ?>
                            &bull; <?= date('d/m/Y', strtotime($t['due_date'])) ?>
                        </div>
                    </div>
                    <span class="text-xs badge-<?= $t['priority'] ?> px-2 py-0.5 rounded-full font-medium shrink-0">
                        <?= $t['priority'] ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Recent Tasks</h3>
                <a href="<?= APP_URL ?>/tasks" class="text-xs text-brand-600 hover:text-brand-700 font-medium">View all →</a>
            </div>
            <div class="space-y-2">
                <?php
                $sLabels = ['a_faire'=>'To Do','en_cours'=>'In Progress','termine'=>'Completed','bloque'=>'Blocked'];
                foreach (array_slice($recentTasks, 0, 6) as $t): ?>
                <a href="<?= APP_URL ?>/tasks/<?= $t['id'] ?>"
                   class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate group-hover:text-brand-600">
                            <?= htmlspecialchars($t['title']) ?>
                        </div>
                        <div class="text-xs text-slate-400"><?= htmlspecialchars($t['assigned_name'] ?? 'Unassigned') ?></div>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span class="text-xs status-<?= $t['status'] ?> px-2 py-0.5 rounded-full font-medium">
                            <?= $sLabels[$t['status']] ?? $t['status'] ?>
                        </span>
                        <?php if ($t['due_date']): ?>
                        <span class="text-xs text-slate-400"><?= date('d/m', strtotime($t['due_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Team performance (admin) -->
    <?php if ($isAdmin && !empty($statsByUser)): ?>
    <div class="card p-6">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Team Performance</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="pb-3 text-left font-medium text-slate-500">Technician</th>
                        <th class="pb-3 text-center font-medium text-slate-500">Total</th>
                        <th class="pb-3 text-center font-medium text-slate-500">In Progress</th>
                        <th class="pb-3 text-center font-medium text-slate-500">Completed</th>
                        <th class="pb-3 text-center font-medium text-slate-500">Overdue</th>
                        <th class="pb-3 text-right font-medium text-slate-500">Completion Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php foreach ($statsByUser as $u):
                        $rate = $u['total'] > 0 ? round($u['terminees'] / $u['total'] * 100) : 0;
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                        <td class="py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-brand-600/20 text-brand-600 flex items-center justify-center text-xs font-bold">
                                    <?= strtoupper(substr($u['full_name'], 0, 2)) ?>
                                </div>
                                <span class="font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($u['full_name']) ?></span>
                            </div>
                        </td>
                        <td class="py-3 text-center font-semibold"><?= $u['total'] ?></td>
                        <td class="py-3 text-center"><span class="status-en_cours px-2 py-0.5 rounded-full text-xs"><?= $u['en_cours'] ?></span></td>
                        <td class="py-3 text-center"><span class="status-termine px-2 py-0.5 rounded-full text-xs"><?= $u['terminees'] ?></span></td>
                        <td class="py-3 text-center">
                            <?php if ($u['en_retard'] > 0): ?>
                            <span class="status-bloque px-2 py-0.5 rounded-full text-xs"><?= $u['en_retard'] ?></span>
                            <?php else: ?><span class="text-slate-400">—</span><?php endif; ?>
                        </td>
                        <td class="py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-20 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700">
                                    <div class="h-1.5 rounded-full <?= $rate>=70?'bg-green-500':($rate>=40?'bg-yellow-500':'bg-red-500') ?>"
                                         style="width:<?= $rate ?>%"></div>
                                </div>
                                <span class="text-xs font-medium w-8 text-right"><?= $rate ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const isDark    = document.documentElement.classList.contains('dark');
const textColor = isDark ? '#94a3b8' : '#64748b';
const gridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(0,0,0,0.07)';

Chart.defaults.color       = textColor;
Chart.defaults.borderColor = gridColor;
Chart.defaults.font.family = "'Inter', sans-serif";

const monthly = <?= json_encode($monthlyStats ?? []) ?>;
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthly.map(r => r.month),
        datasets: [
            { label:'Created',   data: monthly.map(r=>r.created),   backgroundColor:'rgba(59,130,246,0.8)',  borderRadius:6 },
            { label:'Completed', data: monthly.map(r=>r.completed), backgroundColor:'rgba(34,197,94,0.8)',  borderRadius:6 },
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ position:'top', labels:{ boxWidth:10, padding:16 } } },
        scales:{ x:{grid:{display:false}}, y:{beginAtZero:true, ticks:{precision:0}} }
    }
});

const pData   = <?= json_encode($byPriority ?? []) ?>;
const pLabels = { critique:'Critical', haute:'High', moyenne:'Medium', basse:'Low' };
const pColors = { critique:'#ef4444', haute:'#f97316', moyenne:'#3b82f6', basse:'#22c55e' };
new Chart(document.getElementById('priorityChart'), {
    type: 'doughnut',
    data: {
        labels: pData.map(r => pLabels[r.priority]||r.priority),
        datasets:[{
            data: pData.map(r=>r.total),
            backgroundColor: pData.map(r=>pColors[r.priority]||'#94a3b8'),
            borderWidth:0, hoverOffset:4,
        }]
    },
    options:{ responsive:true, maintainAspectRatio:false, cutout:'65%',
        plugins:{ legend:{ position:'bottom', labels:{ boxWidth:10, padding:12 } } } }
});
</script>
