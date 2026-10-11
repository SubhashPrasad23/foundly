<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$reportId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;

$statement = $db->prepare(
    'SELECT id, user_id, type, title, status, verification_question, verification_answer_hash
     FROM reports WHERE id = ?'
);
$statement->bind_param('i', $reportId);
$statement->execute();
$report = $statement->get_result()->fetch_assoc();

if (!$report || $report['type'] !== 'found' || $report['status'] !== 'open') {
    http_response_code(404);
    exit('This item cannot be claimed.');
}

if ((int) $report['user_id'] === (int) $user['id']) {
    exit('You cannot claim your own report.');
}

$existingStatement = $db->prepare('SELECT id FROM claims WHERE report_id = ? AND claimant_id = ? LIMIT 1');
$existingStatement->bind_param('ii', $reportId, $user['id']);
$existingStatement->execute();
if ($existingStatement->get_result()->fetch_assoc()) {
    set_flash('success', 'You have already submitted a claim for this item.');
    redirect('report.php?id=' . $reportId);
}

$error = '';
$claimDetails = '';
$verificationAnswer = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $claimDetails = trim((string) ($_POST['claim_details'] ?? ''));
    $verificationAnswer = trim((string) ($_POST['verification_answer'] ?? ''));

    if ($claimDetails === '') {
        $error = 'Tell the report owner why you believe this is your item.';
    } elseif ($report['verification_question'] && $verificationAnswer === '') {
        $error = 'Answer the private verification question.';
    } elseif ($report['verification_question'] && !password_verify($verificationAnswer, (string) $report['verification_answer_hash'])) {
        $error = 'The private answer does not match.';
    } else {
        try {
            $verificationMatches = $report['verification_question'] ? 1 : null;
            $insert = $db->prepare('INSERT INTO claims (report_id, claimant_id, claim_details, verification_matches) VALUES (?, ?, ?, ?)');
            $insert->bind_param('iisi', $reportId, $user['id'], $claimDetails, $verificationMatches);
            $insert->execute();
            set_flash('success', 'Your claim was submitted.');
            redirect('report.php?id=' . $reportId);
        } catch (mysqli_sql_exception $exception) {
            error_log('Claim creation failed: ' . $exception->getMessage());
            $error = 'Your claim could not be submitted. Please try again.';
        }
    }
}

$pageTitle = 'Claim item';
require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto w-[calc(100%-1.25rem)] max-w-2xl py-10 sm:w-[calc(100%-2rem)] sm:py-14 lg:py-20">
    <a class="font-bold text-indigo-400 hover:text-indigo-300"
        href="<?= e(app_url('report.php?id=' . $reportId)) ?>">&larr; Back to report</a>
    <div class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-sm sm:p-8">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-400">Claim found item</p>
        <h1 class="mt-3 break-words text-2xl font-black leading-tight text-white sm:text-4xl"><?= e($report['title']) ?></h1>
        <?php if ($error): ?>
            <div class="mt-6 rounded-lg border border-red-800 bg-red-950 p-4 text-sm text-red-300"><?= e($error) ?></div>
        <?php endif; ?>
        <form class="mt-8 space-y-6" method="post" action="<?= e(app_url('claim.php?id=' . $reportId)) ?>">
            <?= csrf_field() ?>
            <div><label class="text-sm font-bold text-slate-300" for="claim_details">Why is this your
                    item?</label><textarea
                    class="mt-2 min-h-32 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="claim_details" name="claim_details" maxlength="3000" required><?= e($claimDetails) ?></textarea>
            </div>
            <?php if ($report['verification_question']): ?>
                <div class="rounded-xl border border-indigo-900 bg-indigo-950/40 p-5">
                    <p class="font-bold text-indigo-200">Private question</p>
                    <p class="mt-2 text-indigo-100"><?= e($report['verification_question']) ?></p><label
                        class="mt-5 block text-sm font-bold text-slate-300" for="verification_answer">Your
                        answer</label><input
                        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                        id="verification_answer" name="verification_answer" required>
                </div>
            <?php endif; ?>
            <button
                class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-bold text-white transition hover:bg-indigo-700"
                type="submit">Submit claim</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
