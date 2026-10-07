<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = t('invalid_request_reload');
    }

    $email = strtolower(trim(post_string('email')));
    $password = post_string('password');

    if ($email === '') {
        $errors[] = t('email_required');
    }
    if ($password === '') {
        $errors[] = t('password_required');
    }

    if ($errors === []) {
        try {
            $user = User::findByEmail($email);
            if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
                $errors[] = t('login_failed');
            } else {
                login_user($user);
                redirect('index.php');
            }
        } catch (PDOException $e) {
            $errors[] = t('login_failed');
        }
    }
}

$pageTitle = t('login_title');
require __DIR__ . '/partials/header.php';
?>
        <?php render_alerts($errors); ?>
        <form action="login.php" method="post" class="mx-auto" style="max-width: 28rem;">
            <h1 class="h4 mb-3"><?= e(t('login_title')) ?></h1>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label for="email" class="form-label"><?= e(t('email')) ?></label>
                <input id="email" type="email" name="email" class="form-control" maxlength="255" value="<?= e($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"><?= e(t('password')) ?></label>
                <input id="password" type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" name="login"><?= e(t('login_button')) ?></button>
            <p class="mt-3 mb-0"><?= e(t('no_account')) ?> <a href="register.php"><?= e(t('register')) ?></a></p>
        </form>
<?php require __DIR__ . '/partials/footer.php'; ?>
