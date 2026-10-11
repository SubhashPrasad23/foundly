<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$reportId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
if ($reportId > 0) {
    $statement = $db->prepare(
        'SELECT r.id, r.user_id, r.type, r.title, r.description, r.location, r.event_date,
                r.verification_question, r.status, r.created_at, c.name AS category_name
         FROM reports r
         INNER JOIN categories c ON c.id = r.category_id
         WHERE r.id = ?'
    );
    $statement->bind_param('i', $reportId);
    $statement->execute();
    $report = $statement->get_result()->fetch_assoc();

    if (!$report) {
        http_response_code(404);
        exit('Report not found.');
    }

    $viewer = current_user();
    $hasClaim = false;
    if ($viewer && (int) $viewer['id'] !== (int) $report['user_id']) {
        $claimStatement = $db->prepare('SELECT id FROM claims WHERE report_id = ? AND claimant_id = ? LIMIT 1');
        $claimStatement->bind_param('ii', $reportId, $viewer['id']);
        $claimStatement->execute();
        $hasClaim = (bool) $claimStatement->get_result()->fetch_assoc();
    }

    $pageTitle = $report['title'];
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="mx-auto w-[calc(100%-1.25rem)] max-w-4xl py-10 sm:w-[calc(100%-2rem)] sm:py-14 lg:py-20">
        <a class="font-bold text-indigo-600 hover:text-indigo-800" href="<?= e(app_url('index.php')) ?>">&larr; Back to
            browse</a>
        <article class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-sm sm:p-10">
            <div class="flex flex-wrap gap-2"><span
                    class="rounded-md px-2 py-1 text-xs font-bold uppercase <?= e(type_classes($report['type'])) ?>"><?= e(ucfirst($report['type'])) ?></span><span
                    class="rounded-md px-2 py-1 text-xs font-bold uppercase <?= e(status_classes($report['status'])) ?>"><?= e(status_label($report['status'])) ?></span>
            </div>
            <h1 class="mt-5 break-words text-2xl font-black leading-tight tracking-tight text-white sm:text-4xl"><?= e($report['title']) ?></h1>
            <p class="mt-5 text-lg leading-8 text-slate-400"><?= nl2br(e($report['description'])) ?></p>
            <dl class="mt-8 grid grid-cols-1 gap-4 border-t border-slate-800 pt-6 sm:grid-cols-3">
                <div>
                    <dt class="text-sm text-slate-500">Category</dt>
                    <dd class="mt-1 font-bold text-slate-200"><?= e($report['category_name']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Location</dt>
                    <dd class="mt-1 font-bold text-slate-200"><?= e($report['location']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Date</dt>
                    <dd class="mt-1 font-bold text-slate-200"><?= e(format_date($report['event_date'])) ?></dd>
                </div>
            </dl>
            <?php if ($report['verification_question']): ?>
                <div class="mt-8 rounded-xl border border-indigo-900 bg-indigo-950/50 p-5">
                    <h2 class="font-bold text-indigo-200">Private verification question</h2>
                    <p class="mt-2 text-indigo-100"><?= e($report['verification_question']) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($report['type'] === 'found' && $report['status'] === 'open'): ?>
                <div class="mt-8 border-t border-slate-800 pt-6">
                    <?php if ((int) ($viewer['id'] ?? 0) === (int) $report['user_id']): ?>
                        <p class="text-sm text-slate-500">This is your found report.</p>
                    <?php elseif ($hasClaim): ?>
                        <p class="text-sm text-emerald-300">You have already submitted a claim for this item.</p>
                    <?php elseif ($viewer): ?>
                        <a class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-3 text-center font-bold text-white transition hover:bg-indigo-700 sm:w-auto"
                            href="<?= e(app_url('claim.php?id=' . (int) $report['id'])) ?>">Claim this item</a>
                    <?php else: ?>
                        <a class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-3 text-center font-bold text-white transition hover:bg-indigo-700 sm:w-auto"
                            href="<?= e(app_url('login.php')) ?>">Login to claim</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>
    <?php require __DIR__ . '/includes/footer.php';
    exit;
}

$user = require_login();

$type = (string) ($_GET['type'] ?? $_POST['type'] ?? 'lost');
if (!in_array($type, ['lost', 'found'], true)) {
    $type = 'lost';
}

$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$error = '';
$categoryId = (int) ($_POST['category_id'] ?? 0);
$title = '';
$description = '';
$location = '';
$eventDate = '';
$verificationQuestion = '';
$verificationAnswer = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $eventDate = (string) ($_POST['event_date'] ?? '');
    $verificationQuestion = trim((string) ($_POST['verification_question'] ?? ''));
    $verificationAnswer = trim((string) ($_POST['verification_answer'] ?? ''));

    $categoryIds = array_map('intval', array_column($categories, 'id'));
    if (!in_array($categoryId, $categoryIds, true)) {
        $error = 'Choose a category.';
    } elseif ($title === '' || strlen($title) > 150) {
        $error = 'Enter a title between 1 and 150 characters.';
    } elseif ($description === '') {
        $error = 'Enter a description.';
    } elseif ($location === '' || strlen($location) > 255) {
        $error = 'Enter a location between 1 and 255 characters.';
    } elseif ($eventDate === '' || !strtotime($eventDate) || date('Y-m-d', strtotime($eventDate)) !== $eventDate) {
        $error = 'Choose a valid date.';
    } elseif ($eventDate > date('Y-m-d')) {
        $error = 'The date cannot be in the future.';
    } elseif (strlen($verificationQuestion) > 255 || strlen($verificationAnswer) > 255) {
        $error = 'The private question and answer must be 255 characters or fewer.';
    } elseif (($verificationQuestion === '') !== ($verificationAnswer === '')) {
        $error = 'Enter both a private question and answer, or leave both blank.';
    } else {
        try {
            $question = $verificationQuestion !== '' ? $verificationQuestion : null;
            $answerHash = $verificationAnswer !== '' ? password_hash($verificationAnswer, PASSWORD_DEFAULT) : null;
            $statement = $db->prepare('INSERT INTO reports (user_id, category_id, type, title, description, location, event_date, verification_question, verification_answer_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->bind_param('iisssssss', $user['id'], $categoryId, $type, $title, $description, $location, $eventDate, $question, $answerHash);
            $statement->execute();
            set_flash('success', 'Your report was created.');
            redirect('my-reports.php');
        } catch (mysqli_sql_exception $exception) {
            error_log('Report creation failed: ' . $exception->getMessage());
            $error = 'The report could not be saved. Check the database setup and try again.';
        }
    }
}

$pageTitle = $type === 'found' ? 'Report found item' : 'Report lost item';
require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto w-[calc(100%-1.25rem)] max-w-3xl py-10 sm:w-[calc(100%-2rem)] sm:py-14 lg:py-20">
    <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-600">Add a report</p>
    <h1 class="mt-3 text-3xl font-black leading-tight tracking-tight text-white sm:text-5xl"><?= e($pageTitle) ?></h1>
    <p class="mt-4 text-base leading-7 text-slate-400 sm:text-lg">Share the important details. Photos and file uploads are not required.</p>

    <?php if ($error): ?>
        <div class="mt-6 rounded-lg border border-red-800 bg-red-950 p-4 text-sm text-red-300"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="mt-8 space-y-6 rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-sm sm:p-8" method="post"
        action="<?= e(app_url('report.php?type=' . $type)) ?>">
        <?= csrf_field() ?><input type="hidden" name="type" value="<?= e($type) ?>">
        <div><label class="text-sm font-bold text-slate-300" for="title">Item title</label><input
                class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                id="title" name="title" maxlength="150" required value="<?= e($title) ?>" placeholder="Black wallet">
        </div>
        <div><label class="text-sm font-bold text-slate-300" for="description">Description</label><textarea
                class="mt-2 min-h-32 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                id="description" name="description" maxlength="5000" required><?= e($description) ?></textarea></div>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div><label class="text-sm font-bold text-slate-300" for="category_id">Category</label><select
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="category_id" name="category_id" required>
                    <option value="">Choose a category</option><?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?>
                </select></div>
            <div><label class="text-sm font-bold text-slate-300" for="location">Location</label><input
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="location" name="location" maxlength="255" required value="<?= e($location) ?>"></div>
            <div><label class="text-sm font-bold text-slate-300" for="event_date">Date</label><input
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="event_date" name="event_date" type="date" max="<?= e(date('Y-m-d')) ?>" required
                    value="<?= e($eventDate) ?>"></div>
        </div>
        <div class="rounded-xl border border-indigo-900 bg-indigo-950/40 p-5">
            <h2 class="font-bold text-indigo-200">Private verification</h2>
            <p class="mt-1 text-sm text-indigo-300">Optional. The question can be shown publicly, but the answer is
                stored securely and never displayed.</p>
            <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div><label class="text-sm font-bold text-slate-300" for="verification_question">Question</label><input
                        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                        id="verification_question" name="verification_question" maxlength="255"
                        value="<?= e($verificationQuestion) ?>" placeholder="What detail is not public?"></div>
                <div><label class="text-sm font-bold text-slate-300" for="verification_answer">Private
                        answer</label><input
                         class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                        id="verification_answer" name="verification_answer" maxlength="255"
                        value="<?= e($verificationAnswer) ?>" placeholder="Your private answer"></div>
            </div>
        </div>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-bold text-white transition hover:bg-indigo-700"
            type="submit">Publish report</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
