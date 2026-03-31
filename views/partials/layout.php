<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <!-- MUST be first — applies dark class before anything renders -->
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',
                            300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',
                            600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a'
                        }
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        /* ── Alpine ──────────────────────────────────────────────── */
        [x-cloak] { display: none !important; }

        /* ── Scrollbar ───────────────────────────────────────────── */
        .scrollbar-thin::-webkit-scrollbar { width: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 2px; }

        /* ── Sidebar — always dark, never affected by theme toggle ── */
        aside { background-color: #0f172a !important; }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 150ms;
            color: #94a3b8;
            text-decoration: none;
        }
        .sidebar-link:hover {
            color: #ffffff;
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar-link.active {
            background-color: #2563eb;
            color: #ffffff !important;
            box-shadow: 0 10px 15px -3px rgba(37,99,235,0.3);
        }
        .sidebar-link.active:hover {
            background-color: #1d4ed8;
            color: #ffffff !important;
        }

        /* ── Badges ──────────────────────────────────────────────── */
        .badge-critique { @apply bg-red-100    text-red-700    dark:bg-red-900/40    dark:text-red-400;    }
        .badge-haute    { @apply bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400; }
        .badge-moyenne  { @apply bg-blue-100   text-blue-700   dark:bg-blue-900/40   dark:text-blue-400;   }
        .badge-basse    { @apply bg-green-100  text-green-700  dark:bg-green-900/40  dark:text-green-400;  }

        /* ── Status pills ────────────────────────────────────────── */
        .status-a_faire  { @apply bg-slate-100 text-slate-700 dark:bg-slate-700    dark:text-slate-300; }
        .status-en_cours { @apply bg-blue-100  text-blue-700  dark:bg-blue-900/40  dark:text-blue-400;  }
        .status-termine  { @apply bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400; }
        .status-bloque   { @apply bg-red-100   text-red-700   dark:bg-red-900/40   dark:text-red-400;   }

        /* ── UI components ───────────────────────────────────────── */
        .card          { @apply bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700; }
        .btn-primary   { @apply inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm; }
        .btn-secondary { @apply inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-xl border border-slate-200 dark:border-slate-600 transition-colors; }
        .btn-danger    { @apply inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors; }
        .form-label    { @apply block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5; }
        .form-error    { @apply text-xs text-red-600 dark:text-red-400 mt-1; }

        /* ── Form inputs — pure CSS, no @apply, works in all modes ── */
        .form-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #0f172a;
            font-size: 0.875rem;
            transition: all 150ms;
            outline: none;
        }
        .form-input:focus {
            border-color: transparent;
            box-shadow: 0 0 0 2px #3b82f6;
        }
        .form-input::placeholder { color: #94a3b8; }

        .dark .form-input {
            border-color: #475569;
            background-color: #334155;
            color: #f1f5f9;
        }
        .dark .form-input::placeholder { color: #64748b; }
        .dark .form-input:focus {
            border-color: transparent;
            box-shadow: 0 0 0 2px #3b82f6;
        }

        .form-input option            { background-color: #ffffff; color: #0f172a; }
        .dark .form-input option      { background-color: #334155; color: #f1f5f9; }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans antialiased"
      x-data="{
          darkMode: localStorage.getItem('darkMode') === 'true',
          sidebarOpen: false,
          toggleDark() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('darkMode', this.darkMode);
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      }">
<?php
$currentUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base        = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$currentPath = substr($currentUri, strlen($base)) ?: '/';
$isActive    = fn(string $p) =>
    ($p !== '/' && str_starts_with($currentPath, $p)) || ($p === '/' && $currentPath === '/')
    ? 'active' : '';
?>

<!-- ═══ SIDEBAR ═══════════════════════════════════════════════════════════════ -->
<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 dark:bg-slate-950 flex flex-col transition-transform duration-300"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-700/60 shrink-0">
        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center shadow-lg shadow-brand-600/40">
            <i data-lucide="zap" class="w-4 h-4 text-white"></i>
        </div>
        <div>
            <div class="font-bold text-sm leading-tight" style="color:#ffffff"><?= APP_NAME ?></div>
            <div class="text-xs" style="color:#64748b">v<?= APP_VERSION ?></div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto scrollbar-thin">
        <div class="text-xs font-semibold uppercase tracking-wider px-3 mb-2" style="color:#64748b">Main</div>

        <a href="<?= APP_URL ?>/dashboard" class="sidebar-link <?= $isActive('/dashboard') ?: $isActive('/') ?>">
            <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
        </a>
        <a href="<?= APP_URL ?>/tasks" class="sidebar-link <?= $isActive('/tasks') ?>">
            <i data-lucide="check-square" class="w-4 h-4 shrink-0"></i> Tasks
            <?php
            $tm  = new TaskModel();
            $cnt = $tm->countFiltered(
                ['status' => 'a_faire'],
                $_SESSION['user_id'],
                $_SESSION['user_role']
            );
            ?>
            <?php if ($cnt > 0): ?>
            <span class="ml-auto bg-brand-600 text-white text-xs rounded-full px-2 py-0.5"><?= $cnt ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= APP_URL ?>/calendar" class="sidebar-link <?= $isActive('/calendar') ?>">
            <i data-lucide="calendar" class="w-4 h-4 shrink-0"></i> Calendar
        </a>
        <a href="<?= APP_URL ?>/notifications" class="sidebar-link <?= $isActive('/notifications') ?>">
            <i data-lucide="bell" class="w-4 h-4 shrink-0"></i> Notifications
            <?php if ($unreadCount > 0): ?>
            <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-0.5"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>

        <?php if (AuthMiddleware::isTechOrAdmin()): ?>
        <div class="text-xs font-semibold uppercase tracking-wider px-3 mt-4 mb-2" style="color:#64748b">Administration</div>
        <?php if (AuthMiddleware::isAdmin()): ?>
        <a href="<?= APP_URL ?>/users" class="sidebar-link <?= $isActive('/users') ?>">
            <i data-lucide="users" class="w-4 h-4 shrink-0"></i> Users
        </a>
        <a href="<?= APP_URL ?>/logs" class="sidebar-link <?= $isActive('/logs') ?>">
            <i data-lucide="scroll-text" class="w-4 h-4 shrink-0"></i> Activity Log
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <!-- User info -->
    <div class="px-3 pb-4 border-t border-slate-700/60 pt-3">
        <a href="<?= APP_URL ?>/profile"
           class="flex items-center gap-3 px-3 py-2 rounded-xl transition group"
           onmouseover="this.style.backgroundColor='rgba(255,255,255,0.1)'"
           onmouseout="this.style.backgroundColor='transparent'">
            <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 2)) ?>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-medium truncate" style="color:#ffffff">
                    <?= htmlspecialchars($currentUser['full_name'] ?? '') ?>
                </div>
                <div class="text-xs capitalize" style="color:#64748b">
                    <?= htmlspecialchars($currentUser['role'] ?? '') ?>
                </div>
            </div>
            <i data-lucide="settings" class="w-4 h-4 ml-auto shrink-0" style="color:#64748b"></i>
        </a>
    </div>
</aside>

<!-- Mobile overlay -->
<div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false"
     class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

<!-- ═══ MAIN ═══════════════════════════════════════════════════════════════════ -->
<div class="lg:pl-64 flex flex-col min-h-full">

    <!-- Topbar -->
    <header class="sticky top-0 z-20 h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-700 flex items-center px-4 lg:px-6 gap-4">
        <button @click="sidebarOpen=true" class="lg:hidden text-slate-500 hover:text-slate-900 dark:hover:text-white">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <div class="flex-1"></div>

        <!-- Dark mode -->
        <button @click="toggleDark()"
                class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-200 shadow-sm border border-slate-200 dark:border-slate-700">
            <i data-lucide="sun"  class="w-5 h-5" x-show="darkMode"  x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 rotate-90 scale-75" x-transition:enter-end="opacity-100 rotate-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0 -rotate-90 scale-75"></i>
            <i data-lucide="moon" class="w-5 h-5" x-show="!darkMode" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -rotate-90 scale-75" x-transition:enter-end="opacity-100 rotate-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0 rotate-90 scale-75"></i>
        </button>

        <!-- Notifications bell -->
        <a href="<?= APP_URL ?>/notifications"
           class="relative w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition">
            <i data-lucide="bell" class="w-4 h-4"></i>
            <?php if ($unreadCount > 0): ?>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white dark:ring-slate-900"></span>
            <?php endif; ?>
        </a>

        <?php if (AuthMiddleware::isTechOrAdmin()): ?>
        <a href="<?= APP_URL ?>/tasks/create" class="btn-primary text-xs hidden sm:inline-flex">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i> New Task
        </a>
        <?php endif; ?>

        <a href="<?= APP_URL ?>/logout"
           class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
           title="Logout">
            <i data-lucide="log-out" class="w-4 h-4"></i>
        </a>
    </header>

    <!-- Flash message -->
    <?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
    <?php if ($flash): ?>
    <div x-data="{ show: true }" x-show="show" x-cloak
         x-init="setTimeout(() => show = false, 4500)"
         class="fixed top-20 right-4 z-50 max-w-sm w-full"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-4"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-end="opacity-0">
        <div class="rounded-xl shadow-lg p-4 flex items-start gap-3
            <?= $flash['type'] === 'success'
                ? 'bg-green-50 dark:bg-green-900/40 border border-green-200 dark:border-green-700'
                : 'bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-700' ?>">
            <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>"
               class="w-5 h-5 shrink-0 mt-0.5 <?= $flash['type'] === 'success' ? 'text-green-600' : 'text-red-600' ?>"></i>
            <p class="text-sm <?= $flash['type'] === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </p>
            <button @click="show=false" class="ml-auto text-current opacity-50 hover:opacity-100">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Page content -->
    <main class="flex-1 p-4 lg:p-6">
        <?php require_once $content; ?>
    </main>

    <footer class="py-3 px-6 text-center text-xs text-slate-400 dark:text-slate-600 border-t border-slate-200 dark:border-slate-800">
        <?= APP_NAME ?> v<?= APP_VERSION ?> &mdash; IT Department &copy; <?= date('Y') ?>
    </footer>
</div>

<script>
lucide.createIcons();
// Keyboard shortcuts
document.addEventListener('keydown', e => {
    if (e.altKey) {
        const map = { n:'/tasks/create', d:'/dashboard', t:'/tasks', c:'/calendar' };
        if (map[e.key]) { e.preventDefault(); location.href = '<?= APP_URL ?>' + map[e.key]; }
    }
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
        e.preventDefault();
        document.querySelector('input[name="search"]')?.focus();
    }
});
</script>
</body>
</html>