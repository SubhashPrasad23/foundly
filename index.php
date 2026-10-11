<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Browse reports';
$statement = $db->query(
    "SELECT r.id, r.type, r.title, r.description, r.location, r.event_date, r.status,
            c.name AS category_name
     FROM reports r
     INNER JOIN categories c ON c.id = r.category_id
     WHERE r.status = 'open'
     ORDER BY r.created_at DESC"
);
$reports = $statement->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>
<section class="bg-slate-950 text-white">
    <div class="mx-auto w-[calc(100%-1.25rem)] max-w-7xl py-14 sm:w-[calc(100%-2rem)] sm:py-20 lg:py-28">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-300">Community lost and found</p>
        <div class="mt-5 max-w-4xl">
            <h1 class="text-3xl font-black leading-tight tracking-tight text-white sm:text-5xl sm:leading-tight lg:text-7xl">Find what matters.</h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300 sm:mt-6 sm:text-lg sm:leading-8">Browse detailed lost and found reports from your
                community in one simple place.</p>
        </div>
        <div class="mt-8 flex flex-wrap gap-3 sm:mt-10">
            <a class="w-full rounded-lg bg-indigo-500 px-5 py-3 text-center font-bold text-white transition hover:bg-indigo-400 sm:w-auto"
                href="#reports">Browse reports</a>
            <a class="w-full rounded-lg border border-white/20 px-5 py-3 text-center font-bold text-white transition hover:bg-white/10 sm:w-auto"
                href="<?= e(app_url('report.php?type=lost')) ?>">Report lost item</a>
        </div>
    </div>
</section>

<section id="reports" class="bg-slate-950">
    <div class="mx-auto w-[calc(100%-1.25rem)] max-w-7xl py-12 sm:w-[calc(100%-2rem)] sm:py-14 lg:py-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-600">Latest activity</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-white sm:text-3xl">All open reports</h2>
            </div>
            <p class="text-sm text-slate-400"><?= count($reports) ?> <?= count($reports) === 1 ? 'report' : 'reports' ?>
            </p>
        </div>

        <?php if (!$reports): ?>
            <div class="mt-8 rounded-2xl border border-dashed border-slate-700 bg-slate-900 p-8 text-center sm:p-12">
                <h3 class="text-xl font-bold text-white">No reports yet</h3>
                <p class="mt-2 text-slate-400">Be the first person to add a report.</p>
            </div>
        <?php else: ?>
            <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($reports as $report): ?>
                    <?= report_card($report) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
