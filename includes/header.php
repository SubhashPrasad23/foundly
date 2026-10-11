<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Foundly';
$currentUser = current_user();
$flashMessages = get_flash_messages();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Foundly</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/tailwind.generated.css')) ?>">
</head>
<body class="min-h-screen bg-slate-950 text-slate-200">
<header class="border-b border-slate-800 bg-slate-900">
    <div class="mx-auto flex w-[calc(100%-1.25rem)] max-w-7xl flex-wrap items-center justify-between gap-4 py-4 sm:w-[calc(100%-2rem)] sm:py-5">
        <a class="text-2xl font-black tracking-tight text-indigo-600" href="<?= e(app_url('index.php')) ?>">Foundly</a>
        <details class="relative sm:hidden">
            <summary class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-700 text-slate-200 transition hover:border-indigo-500 hover:bg-slate-800 [&::-webkit-details-marker]:hidden">
                <span class="sr-only">Open navigation menu</span>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </summary>
            <nav class="absolute right-0 z-20 mt-3 flex w-56 flex-col gap-2 rounded-xl border border-slate-700 bg-slate-900 p-3 text-sm font-semibold shadow-xl">
                <a class="rounded-lg px-3 py-2 text-slate-300 transition hover:bg-slate-800 hover:text-indigo-300" href="<?= e(app_url('index.php')) ?>">Browse</a>
                <?php if ($currentUser): ?>
                    <a class="rounded-lg px-3 py-2 text-slate-300 transition hover:bg-slate-800 hover:text-indigo-300" href="<?= e(app_url('dashboard.php')) ?>">Dashboard</a>
                    <a class="rounded-lg px-3 py-2 text-slate-300 transition hover:bg-slate-800 hover:text-indigo-300" href="<?= e(app_url('my-reports.php')) ?>">My report</a>
                    <a class="rounded-lg bg-slate-950 px-3 py-2 text-center text-white transition hover:bg-slate-800" href="<?= e(app_url('logout.php')) ?>">Logout</a>
                <?php else: ?>
                    <a class="rounded-lg bg-slate-950 px-3 py-2 text-center text-white transition hover:bg-slate-800" href="<?= e(app_url('login.php')) ?>">Login</a>
                <?php endif; ?>
            </nav>
        </details>
        <nav class="hidden w-auto flex-col gap-2 text-sm font-semibold sm:flex sm:flex-row sm:items-center sm:gap-x-5 sm:gap-y-2">
            <a class="rounded-lg px-3 py-2 text-slate-300 transition hover:bg-slate-800 hover:text-indigo-300" href="<?= e(app_url('index.php')) ?>">Browse</a>
            <?php if ($currentUser): ?>
                <a class="rounded-lg px-3 py-2 text-slate-300 transition hover:bg-slate-800 hover:text-indigo-300" href="<?= e(app_url('dashboard.php')) ?>">Dashboard</a>
                <a class="rounded-lg px-3 py-2 text-slate-300 transition hover:bg-slate-800 hover:text-indigo-300" href="<?= e(app_url('my-reports.php')) ?>">My report</a>
                <a class="rounded-lg bg-slate-950 px-3 py-2 text-center text-white transition hover:bg-slate-800" href="<?= e(app_url('logout.php')) ?>">Logout</a>
            <?php else: ?>
                <a class="rounded-lg bg-slate-950 px-3 py-2 text-center text-white transition hover:bg-slate-800" href="<?= e(app_url('login.php')) ?>">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
    <?php if ($flashMessages): ?>
        <div class="mx-auto w-[calc(100%-1.25rem)] max-w-7xl pt-5 sm:w-[calc(100%-2rem)]">
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="mb-3 rounded-lg border px-4 py-3 text-sm <?= $type === 'success' ? 'border-emerald-800 bg-emerald-950 text-emerald-300' : 'border-red-800 bg-red-950 text-red-300' ?>"><?= e($message) ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
