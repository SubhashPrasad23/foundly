<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return APP_BASE_PATH . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    global $db;

    if (empty($_SESSION['user']['id'])) {
        redirect('login.php');
    }

    $userId = (int) $_SESSION['user']['id'];
    $statement = $db->prepare('SELECT id, name, email, status FROM users WHERE id = ?');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();

    if (!$user || $user['status'] !== 'active') {
        logout_user();
        redirect('login.php');
    }

    $_SESSION['user'] = $user;
    return $user;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], (bool) $cookie['secure'], (bool) $cookie['httponly']);
    }

    session_destroy();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid request. Please try again.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function get_flash_messages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function format_date(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp ? date('M j, Y', $timestamp) : 'Unknown date';
}

function status_label(string $status): string
{
    return ucfirst($status);
}

function status_classes(string $status): string
{
    return match ($status) {
        'resolved', 'approved' => 'bg-emerald-950 text-emerald-300',
        'rejected' => 'bg-red-950 text-red-300',
        'closed' => 'bg-slate-800 text-slate-300',
        default => 'bg-blue-950 text-blue-300',
    };
}

function type_classes(string $type): string
{
    return $type === 'found'
        ? 'bg-emerald-950 text-emerald-300'
        : 'bg-orange-950 text-orange-300';
}

function report_card(array $report): string
{
    $type = e(ucfirst($report['type']));
    $status = e(status_label($report['status']));
    $typeClass = e(type_classes((string) $report['type']));
    $statusClass = e(status_classes((string) $report['status']));
    $url = e(app_url('report.php?id=' . (int) $report['id']));

    return '<a class="group block overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-sm transition hover:-translate-y-1 hover:border-indigo-500 hover:shadow-lg" href="' . $url . '">'
        . '<div class="flex h-44 items-center justify-center bg-slate-800 text-sm text-slate-400">No photo</div>'
        . '<div class="p-5 sm:p-6">'
        . '<div class="flex flex-wrap gap-2"><span class="rounded-md px-2 py-1 text-xs font-bold uppercase tracking-wide ' . $typeClass . '">' . $type . '</span>'
        . '<span class="rounded-md px-2 py-1 text-xs font-bold uppercase tracking-wide ' . $statusClass . '">' . $status . '</span></div>'
        . '<h3 class="mt-4 break-words text-lg font-bold text-white sm:text-xl">' . e($report['title']) . '</h3>'
        . '<p class="mt-2 text-sm leading-6 text-slate-400">' . e($report['description']) . '</p>'
        . '<dl class="mt-5 space-y-3 border-t border-slate-800 pt-4 text-sm">'
        . '<div class="flex justify-between gap-4"><dt class="shrink-0 text-slate-500">Category</dt><dd class="m-0 min-w-0 break-words text-right font-medium text-slate-200">' . e($report['category_name']) . '</dd></div>'
        . '<div class="flex justify-between gap-4"><dt class="shrink-0 text-slate-500">Location</dt><dd class="m-0 min-w-0 break-words text-right font-medium text-slate-200">' . e($report['location']) . '</dd></div>'
        . '<div class="flex justify-between gap-4"><dt class="shrink-0 text-slate-500">Date</dt><dd class="m-0 min-w-0 break-words text-right font-medium text-slate-200">' . e(format_date($report['event_date'])) . '</dd></div>'
        . '</dl></div></a>';
}
