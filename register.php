<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || strlen($name) > 100) {
        $error = 'Enter a name between 1 and 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $statement->bind_param('sss', $name, $email, $hash);
            $statement->execute();
            set_flash('success', 'Account created. You can now log in.');
            redirect('login.php');
        } catch (mysqli_sql_exception $exception) {
            $error = 'That email address is already registered.';
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto w-[calc(100%-1.25rem)] max-w-md py-12 sm:w-[calc(100%-2rem)] sm:py-16 lg:py-24">
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-sm sm:p-8">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-600">Join the community</p>
        <h1 class="mt-3 text-2xl font-black leading-tight text-white sm:text-3xl">Create an account</h1>
        <?php if ($error): ?>
            <p class="mt-5 rounded-lg bg-red-950 p-3 text-sm text-red-300"><?= e($error) ?></p><?php endif; ?>
        <form class="mt-8 space-y-5" method="post" action="<?= e(app_url('register.php')) ?>">
            <?= csrf_field() ?>
            <div><label class="text-sm font-bold text-slate-300" for="name">Name</label><input
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="name" name="name" required value="<?= e($name) ?>"></div>
            <div><label class="text-sm font-bold text-slate-300" for="email">Email</label><input
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="email" name="email" type="email" required value="<?= e($email) ?>"></div>
            <div><label class="text-sm font-bold text-slate-300" for="password">Password</label><input
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="password" name="password" type="password" minlength="8" required></div>
            <div><label class="text-sm font-bold text-slate-300" for="confirm_password">Confirm password</label><input
                    class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-base text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-950"
                    id="confirm_password" name="confirm_password" type="password" minlength="8" required></div>
            <button
                class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-bold text-white transition hover:bg-indigo-700"
                type="submit">Create account</button>
        </form>
        <div class="mt-6 text-center">
            <p class="text-sm text-slate-400">Already have an account?</p>
            <a class="mt-3 inline-flex w-full justify-center rounded-lg border border-slate-700 px-4 py-3 font-bold text-indigo-300 transition hover:border-indigo-500 hover:text-white"
                href="<?= e(app_url('login.php')) ?>">Login</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
