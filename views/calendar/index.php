<?php $pageTitle = 'Calendar'; ?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<style>
.fc{--fc-border-color:theme('colors.slate.200');--fc-today-bg-color:rgba(59,130,246,0.08);font-family:'Inter',sans-serif}
.dark .fc{--fc-border-color:rgba(148,163,184,0.15);--fc-page-bg-color:transparent;--fc-neutral-bg-color:rgba(30,41,59,0.5)}
.dark .fc-col-header-cell-cushion,.dark .fc-daygrid-day-number,.dark .fc-list-event-title a{color:#cbd5e1!important}
.dark .fc-toolbar-title{color:#f1f5f9}
.fc-event{border-radius:6px;font-size:11px;padding:1px 4px;font-weight:500}
.fc-toolbar-title{font-size:1.1rem!important;font-weight:700!important}
.fc-button{border-radius:8px!important;font-size:12px!important;font-weight:500!important}
.fc-button-primary{background-color:#2563eb!important;border-color:#2563eb!important}
.fc-button-primary:not(.fc-button-active):hover{background-color:#1d4ed8!important}
.fc-button-active{background-color:#1d4ed8!important}
</style>

<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Calendar</h1>
            <p class="text-slate-500 text-sm mt-0.5">Visualize tasks over time</p>
        </div>
        <?php if (AuthMiddleware::isTechOrAdmin()): ?>
        <a href="<?= APP_URL ?>/tasks/create" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> New Task
        </a>
        <?php endif; ?>
    </div>

    <div class="card px-5 py-3 flex flex-wrap gap-4 text-xs font-medium">
        <span class="text-slate-500">Priority:</span>
        <?php foreach (['critique'=>['#ef4444','Critical'],'haute'=>['#f97316','High'],'moyenne'=>['#3b82f6','Medium'],'basse'=>['#22c55e','Low']] as $k=>[$c,$l]): ?>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:<?= $c ?>"></span><?= $l ?></span>
        <?php endforeach; ?>
    </div>

    <div class="card p-5">
        <div id="calendar" class="min-h-[600px]"></div>
    </div>
</div>

<div id="eventModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-start justify-between mb-4">
            <h3 id="modalTitle" class="font-bold text-slate-900 dark:text-white text-lg pr-4"></h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div id="modalBody" class="space-y-2 text-sm"></div>
        <div class="mt-5 flex gap-2">
            <a id="modalLink" href="#" class="btn-primary text-xs flex-1 justify-center"><i data-lucide="arrow-right" class="w-3.5 h-3.5"></i> View Task</a>
            <button onclick="closeModal()" class="btn-secondary text-xs">Close</button>
        </div>
    </div>
</div>

<script>
const APP_URL='<?= APP_URL ?>',CAN_EDIT=<?= AuthMiddleware::isTechOrAdmin()?'true':'false' ?>,CSRF='<?= $csrfToken ?>';
document.addEventListener('DOMContentLoaded',()=>{
    const cal=new FullCalendar.Calendar(document.getElementById('calendar'),{
        headerToolbar:{left:'prev,next today',center:'title',right:'dayGridMonth,timeGridWeek,listMonth'},
        initialView:'dayGridMonth',height:'auto',editable:CAN_EDIT,eventDurationEditable:CAN_EDIT,nowIndicator:true,navLinks:true,
        events:(info,ok,fail)=>{fetch(`${APP_URL}/calendar/events?start=${info.startStr.slice(0,10)}&end=${info.endStr.slice(0,10)}`).then(r=>r.json()).then(ok).catch(fail)},
        eventClick:info=>{
            const p=info.event.extendedProps,s={a_faire:'To Do',en_cours:'In Progress',termine:'Completed',bloque:'Blocked'};
            document.getElementById('modalTitle').textContent=info.event.title;
            document.getElementById('modalBody').innerHTML=`<div class="flex gap-2"><span class="badge-${p.priority} px-2 py-0.5 rounded-full text-xs font-medium capitalize">${p.priority}</span><span class="status-${p.status} px-2 py-0.5 rounded-full text-xs">${s[p.status]||p.status}</span></div>${p.assigned_name?`<p class="text-slate-500"><strong>Assigned to:</strong> ${p.assigned_name}</p>`:''}`;
            document.getElementById('modalLink').href=p.url;
            const m=document.getElementById('eventModal');m.classList.remove('hidden');m.classList.add('flex');lucide.createIcons();
        },
        eventDrop:info=>{const s=info.event.startStr.slice(0,10),e=info.event.end?new Date(info.event.end-86400000).toISOString().slice(0,10):s;fetch(`${APP_URL}/api/tasks/${info.event.id}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({_csrf:CSRF,start_date:s,due_date:e})}).catch(()=>info.revert())},
        eventResize:info=>{const s=info.event.startStr.slice(0,10),e=info.event.end?new Date(info.event.end-86400000).toISOString().slice(0,10):s;fetch(`${APP_URL}/api/tasks/${info.event.id}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({_csrf:CSRF,start_date:s,due_date:e})}).catch(()=>info.revert())},
    });
    cal.render();
    new MutationObserver(()=>cal.render()).observe(document.documentElement,{attributes:true,attributeFilter:['class']});
});
function closeModal(){const m=document.getElementById('eventModal');m.classList.add('hidden');m.classList.remove('flex')}
document.getElementById('eventModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closeModal()});
</script>
