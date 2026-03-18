<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <title>Error — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="h-full flex items-center justify-center bg-slate-900 text-white">
    <div class="text-center px-4">
        <div class="text-8xl font-black text-slate-700 mb-4"><?= http_response_code() ?></div>
        <h1 class="text-2xl font-bold mb-2"><?= htmlspecialchars($message ?? 'An error occurred.') ?></h1>
        <p class="text-slate-400 mb-6">The page you are looking for was not found or you do not have permission to access it.</p>
        <a href="<?= APP_URL ?>/dashboard"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition">
            ← Back to Dashboard
        </a>
    </div>
</body>
</html>
