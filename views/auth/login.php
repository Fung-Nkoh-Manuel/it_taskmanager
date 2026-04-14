<!DOCTYPE html>
<html lang="en" class="h-full"
      x-data="{ darkMode: localStorage.getItem('darkMode')==='true' }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex items-center justify-center p-4">

<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
</div>

<div class="w-full max-w-md relative z-10">
    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 shadow-xl shadow-blue-600/40 mb-4">
            <i data-lucide="zap" class="w-7 h-7 text-white"></i>
        </div>
        <h1 class="text-2xl font-bold text-white"><?= APP_NAME ?></h1>
        <p class="text-slate-400 text-sm mt-1">Sign in to your workspace</p>
    </div>

    <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-8 shadow-2xl">
        <h2 class="text-white font-semibold text-lg mb-6">Sign In</h2>

        <?php if (!empty($error)): ?>
        <div class="flex items-center gap-2 bg-red-500/20 border border-red-500/30 text-red-300 text-sm rounded-xl px-4 py-3 mb-5">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($flash) && $flash['type'] === 'success'): ?>
        <div class="flex items-center gap-2 bg-green-500/20 border border-green-500/30 text-green-300 text-sm rounded-xl px-4 py-3 mb-5">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/login" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Email or Username</label>
                <div class="relative">
                    <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input type="text" name="login" required autofocus
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                           placeholder="admin or admin@ittasks.local">
                </div>
            </div>

            <div x-data="{ show: false }">
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input :type="show ? 'text' : 'password'" name="password" required
                           class="w-full pl-10 pr-10 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                           placeholder="••••••••">
                    <button type="button" @click="show=!show"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                        <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-lg shadow-blue-600/30 text-sm">
                Sign In
            </button>
        </form>

        <p class="text-center text-slate-400 text-sm mt-6">
            No account yet?
            <a href="<?= APP_URL ?>/register" class="text-blue-400 hover:text-blue-300 font-medium">Create one</a>
        </p>
    </div>

  
</div>

<script>lucide.createIcons();</script>
</body>
</html>
