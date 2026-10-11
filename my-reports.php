<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$pageTitle = 'My report';

$statement = $db->prepare(
    'SELECT r.id, r.type, r.title, r.description, r.location, r.event_date, r.status, r.created_at,
            c.name AS category_name
     FROM reports r
     INNER JOIN categories c ON c.id = r.category_id
     WHERE r.user_id = ? ORDER BY r.created_at DESC'
);
$statement->bind_param('i', $user['id']);
$statement->execute();
$reports = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

$claimStatement = $db->prepare(
    'SELECT cl.id, cl.report_id, cl.claim_details, cl.verification_matches, cl.status, cl.created_at,
            u.name AS claimant_name, u.email AS claimant_email
     FROM claims cl
     INNER JOIN reports r ON r.id = cl.report_id
     INNER JOIN users u ON u.id = cl.claimant_id
     WHERE r.user_id = ?
     ORDER BY cl.created_at DESC'
);
$claimStatement->bind_param('i', $user['id']);
$claimStatement->execute();
$claimsByReport = [];
foreach ($claimStatement->get_result()->fetch_all(MYSQLI_ASSOC) as $claim) {
    $claimsByReport[(int) $claim['report_id']][] = $claim;
}

require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto w-[calc(100%-1.25rem)] max-w-7xl py-12 sm:w-[calc(100%-2rem)] sm:py-14 lg:py-20">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-600">Your reports</p>
            <h1 class="mt-3 text-3xl font-black leading-tight tracking-tight text-white sm:text-5xl">My report</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-400 sm:text-lg">Create a lost or found report and keep your details in one
                place.</p>
        </div>
        <div class="flex w-full flex-wrap gap-3 sm:w-auto"><a
                class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-center font-bold text-white transition hover:bg-indigo-700 sm:w-auto"
                href="<?= e(app_url('report.php?type=lost')) ?>">Report lost</a><a
                class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-center font-bold text-white transition hover:bg-emerald-700 sm:w-auto"
                href="<?= e(app_url('report.php?type=found')) ?>">Report found</a></div>
    </div>

    <?php if (!$reports): ?>
        <div class="mt-10 rounded-2xl border border-dashed border-slate-700 bg-slate-900 p-8 text-center sm:p-12">
            <h2 class="text-xl font-bold text-white">No reports yet</h2>
            <p class="mt-2 text-slate-400">Choose Report lost or Report found to get started.</p>
        </div>
    <?php else: ?>
        <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <?php foreach ($reports as $report): ?>
                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-indigo-400">
                                <?= e(ucfirst($report['type'])) ?> report
                            </p>
                            <h2 class="mt-2 text-xl font-bold text-white sm:text-2xl"><?= e($report['title']) ?></h2>
                        </div><span
                            class="rounded-md px-2 py-1 text-xs font-bold uppercase <?= e(status_classes($report['status'])) ?>"><?= e(status_label($report['status'])) ?></span>
                    </div>
                    <p class="mt-5 leading-7 text-slate-400"><?= e($report['description']) ?></p>
                    <dl class="mt-6 space-y-3 border-t border-slate-800 pt-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Category</dt>
                            <dd class="m-0 text-right font-medium text-slate-200"><?= e($report['category_name']) ?></dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Location</dt>
                            <dd class="m-0 text-right font-medium text-slate-200"><?= e($report['location']) ?></dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Event date</dt>
                            <dd class="m-0 text-right font-medium text-slate-200"><?= e(format_date($report['event_date'])) ?>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Created</dt>
                            <dd class="m-0 text-right font-medium text-slate-200"><?= e(format_date($report['created_at'])) ?>
                            </dd>
                        </div>
                    </dl>
                    <?php $reportClaims = $claimsByReport[(int) $report['id']] ?? []; ?>
                    <?php if ($reportClaims): ?>
                        <div class="mt-6 border-t border-slate-800 pt-5">
                            <h3 class="text-lg font-bold text-white">Claims received</h3>
                            <div class="mt-4 space-y-4">
                                <?php foreach ($reportClaims as $claim): ?>
                                    <div class="rounded-xl border border-indigo-900 bg-indigo-950/40 p-4">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="font-bold text-indigo-200"><?= e($claim['claimant_name']) ?></p>
                                                <p class="text-sm text-indigo-300"><?= e($claim['claimant_email']) ?></p>
                                            </div>
                                            <span
                                                class="rounded-md bg-slate-800 px-2 py-1 text-xs font-bold uppercase text-slate-300"><?= e(status_label($claim['status'])) ?></span>
                                        </div>
                                        <p class="mt-3 text-sm leading-6 text-slate-300"><?= e($claim['claim_details']) ?></p>
                                        <p class="mt-3 text-xs text-slate-400">Submitted <?= e(format_date($claim['created_at'])) ?> ·
                                            <?= $claim['verification_matches'] === null ? 'No private answer required' : ($claim['verification_matches'] ? 'Private answer matched' : 'Private answer did not match') ?>
                                        </p>
                                        <?php if ($claim['status'] === 'pending'): ?>
                                            <div class="mt-4 flex flex-wrap gap-3 border-t border-indigo-900 pt-4">
                                                <form method="post" action="<?= e(app_url('claim-action.php')) ?>">
                                                    <?= csrf_field() ?><input type="hidden" name="claim_id"
                                                        value="<?= (int) $claim['id'] ?>"><input type="hidden" name="action"
                                                        value="approve">
                                                    <button
                                                        class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-emerald-500"
                                                        type="submit">Approve and resolve</button>
                                                </form>
                                                <form method="post" action="<?= e(app_url('claim-action.php')) ?>">
                                                    <?= csrf_field() ?><input type="hidden" name="claim_id"
                                                        value="<?= (int) $claim['id'] ?>"><input type="hidden" name="action" value="reject">
                                                    <button
                                                        class="rounded-lg bg-red-700 px-3 py-2 text-sm font-bold text-white transition hover:bg-red-600"
                                                        type="submit">Reject</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
