<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$pageTitle = 'Dashboard';

$statement = $db->prepare(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(status = 'open'), 0) AS open_count,
            COALESCE(SUM(status = 'resolved'), 0) AS resolved_count
     FROM reports WHERE user_id = ?"
);
$statement->bind_param('i', $user['id']);
$statement->execute();
$stats = $statement->get_result()->fetch_assoc();

$statement = $db->prepare(
    'SELECT r.id, r.type, r.title, r.description, r.location, r.event_date, r.status,
            c.name AS category_name
     FROM reports r
     INNER JOIN categories c ON c.id = r.category_id
     WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 6'
);
$statement->bind_param('i', $user['id']);
$statement->execute();
$recentReports = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>
<section class="bg-slate-950 text-white">
    <div class="mx-auto w-[calc(100%-1.25rem)] max-w-7xl py-12 sm:w-[calc(100%-2rem)] sm:py-16 lg:py-24">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-300">Your Foundly space</p>
        <h1 class="mt-4 text-3xl font-black leading-tight tracking-tight text-white sm:text-5xl">Welcome, <?= e($user['name']) ?>.</h1>
        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg sm:leading-8">Keep track of the lost and found reports connected to
            your account.</p>
    </div>
</section>

<section class="mx-auto w-[calc(100%-1.25rem)] max-w-7xl py-12 sm:w-[calc(100%-2rem)] sm:py-14 lg:py-20">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-sm sm:p-6">
            <p class="text-sm text-slate-400">Total reports</p>
            <p class="mt-2 text-3xl font-black text-white sm:text-4xl"><?= (int) $stats['total'] ?></p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-sm sm:p-6">
            <p class="text-sm text-slate-400">Open reports</p>
            <p class="mt-2 text-3xl font-black text-white sm:text-4xl"><?= (int) $stats['open_count'] ?></p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-sm sm:p-6">
            <p class="text-sm text-slate-400">Resolved reports</p>
            <p class="mt-2 text-3xl font-black text-white sm:text-4xl"><?= (int) $stats['resolved_count'] ?></p>
        </div>
    </div>

    <div class="mt-14 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-600">Recent reports</p>
            <h2 class="mt-2 text-2xl font-black text-white sm:text-3xl">Your latest activity</h2>
        </div>
        <a class="font-bold text-indigo-600 hover:text-indigo-800" href="<?= e(app_url('my-reports.php')) ?>">View my
            reports</a>
    </div>

    <?php if (!$recentReports): ?>
        <div class="mt-8 rounded-2xl border border-dashed border-slate-700 bg-slate-900 p-8 text-center sm:p-12">
            <h3 class="text-xl font-bold text-white">No reports yet</h3>
            <p class="mt-2 text-slate-400">Use the buttons on My report to add your first one.</p>
        </div>
    <?php else: ?>
        <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2">
            <?php foreach ($recentReports as $report): ?>
                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-indigo-400">
                                <?= e(ucfirst($report['type'])) ?> report
                            </p>
                            <h3 class="mt-2 text-lg font-bold text-white sm:text-xl"><?= e($report['title']) ?></h3>
                        </div><span
                            class="rounded-md px-2 py-1 text-xs font-bold uppercase <?= e(status_classes($report['status'])) ?>"><?= e(status_label($report['status'])) ?></span>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-400"><?= e($report['description']) ?></p>
                    <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-800 pt-4 text-sm text-slate-500">
                        <span><?= e($report['category_name']) ?></span><span><?= e($report['location']) ?></span><span><?= e(format_date($report['event_date'])) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
