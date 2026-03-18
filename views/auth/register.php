<!DOCTYPE html>
<html lang="en" class="h-full"
      x-data="{ darkMode: localStorage.getItem('darkMode')==='true' }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="min-h-full bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex items-center justify-center p-4 py-8">

<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
</div>

<div class="w-full max-w-md relative z-10">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 shadow-xl shadow-blue-600/40 mb-4">
            <i data-lucide="zap" class="w-7 h-7 text-white"></i>
        </div>
        <h1 class="text-2xl font-bold text-white"><?= APP_NAME ?></h1>
        <p class="text-slate-400 text-sm mt-1">Create your account</p>
    </div>

    <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-8 shadow-2xl">
        <h2 class="text-white font-semibold text-lg mb-6">Register</h2>

        <form method="POST" action="<?= APP_URL ?>/register" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

            <?php $e = $errors ?? []; $o = $old ?? []; ?>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($o['full_name'] ?? '') ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       placeholder="John Smith">
                <?php if (!empty($e['full_name'])): ?><p class="text-xs text-red-400 mt-1"><?= $e['full_name'] ?></p><?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($o['username'] ?? '') ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       placeholder="jsmith">
                <?php if (!empty($e['username'])): ?><p class="text-xs text-red-400 mt-1"><?= $e['username'] ?></p><?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($o['email'] ?? '') ?>" required
                       class="w-full px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       placeholder="john@example.com">
                <?php if (!empty($e['email'])): ?><p class="text-xs text-red-400 mt-1"><?= $e['email'] ?></p><?php endif; ?>
            </div>

            <div x-data="{ show: false }">
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" required minlength="6"
                           class="w-full pr-10 px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                           placeholder="Min. 6 characters">
                    <button type="button" @click="show=!show"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                        <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
                <?php if (!empty($e['password'])): ?><p class="text-xs text-red-400 mt-1"><?= $e['password'] ?></p><?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Confirm Password</label>
                <input type="password" name="password_confirm" required
                       class="w-full px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       placeholder="Repeat password">
                <?php if (!empty($e['password2'])): ?><p class="text-xs text-red-400 mt-1"><?= $e['password2'] ?></p><?php endif; ?>
            </div>

            <button type="submit"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-lg shadow-blue-600/30 text-sm">
                Create Account
            </button>
        </form>

        <p class="text-center text-slate-400 text-sm mt-6">
            Already have an account?
            <a href="<?= APP_URL ?>/login" class="text-blue-400 hover:text-blue-300 font-medium">Sign in</a>
        </p>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>
