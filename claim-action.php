<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
check_csrf();

$claimId = filter_var($_POST['claim_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$action = (string) ($_POST['action'] ?? '');

if (!$claimId || !in_array($action, ['approve', 'reject'], true)) {
    set_flash('error', 'That claim action is not valid.');
    redirect('my-reports.php');
}

$statement = $db->prepare(
    "SELECT cl.id, cl.report_id, r.status AS report_status
     FROM claims cl
     INNER JOIN reports r ON r.id = cl.report_id
     WHERE cl.id = ? AND r.user_id = ? AND cl.status = 'pending'"
);
$statement->bind_param('ii', $claimId, $user['id']);
$statement->execute();
$claim = $statement->get_result()->fetch_assoc();

if (!$claim) {
    set_flash('error', 'That claim is no longer available for review.');
    redirect('my-reports.php');
}

try {
    $db->begin_transaction();

    if ($action === 'approve') {
        $updateClaim = $db->prepare("UPDATE claims SET status = 'approved' WHERE id = ?");
        $updateClaim->bind_param('i', $claimId);
        $updateClaim->execute();

        $updateReport = $db->prepare("UPDATE reports SET status = 'resolved' WHERE id = ? AND user_id = ?");
        $updateReport->bind_param('ii', $claim['report_id'], $user['id']);
        $updateReport->execute();

        $rejectOthers = $db->prepare("UPDATE claims SET status = 'rejected' WHERE report_id = ? AND id <> ? AND status = 'pending'");
        $rejectOthers->bind_param('ii', $claim['report_id'], $claimId);
        $rejectOthers->execute();
        $message = 'Claim approved and report resolved.';
    } else {
        $updateClaim = $db->prepare("UPDATE claims SET status = 'rejected' WHERE id = ?");
        $updateClaim->bind_param('i', $claimId);
        $updateClaim->execute();
        $message = 'Claim rejected.';
    }

    $db->commit();
    set_flash('success', $message);
} catch (Throwable $exception) {
    $db->rollback();
    error_log('Claim action failed: ' . $exception->getMessage());
    set_flash('error', 'The claim could not be updated. Please try again.');
}

redirect('my-reports.php');
