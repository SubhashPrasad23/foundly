<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $statement = $db->prepare('SELECT id, name, email, password, status FROM users WHERE email = ?');
    $statement->bind_param('s', $email);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();

    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
        $error = 'The email or password is incorrect.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'status' => $user['status'],
        ];
        redirect('dashboard.php');
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto w-[calc(100%-1.25rem)] max-w-md py-12 sm:w-[calc(100%-2rem)] sm:py-16 lg:py-24">
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-sm sm:p-8">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-600">Welcome back</p>
        <h1 class="mt-3 text-2xl font-black leading-tight text-white sm:text-3xl">Login to Foundly</h1>
        <?php if ($error): ?>
            <p class="mt-5 rounded-lg bg-red-950 p-3 text-sm text-red-300"><?= e($error) ?></p><?php endif; ?>
        <form class="mt-8 space-y-5" method="post" action="<?= e(app_url('login.php')) ?>">
            <?= csrf_field() ?>
            <div>
                <label class="text-sm font-bold text-slate-300" for="email">Email</label>
                <input
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="email" name="email" type="email" required value="<?= e($email) ?>">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-300" for="password">Password</label>
                <input
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="password" name="password" type="password" required>
            </div>
            <button
                class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-bold text-white transition hover:bg-indigo-700"
                type="submit">Login</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-400">New here? <a
                class="font-bold text-indigo-600 hover:text-indigo-800" href="<?= e(app_url('register.php')) ?>">Create
                an account</a></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
