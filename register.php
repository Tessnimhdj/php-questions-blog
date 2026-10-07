<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

const PASSWORD_MIN_LENGTH = 8;

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = t('invalid_request_reload');
    }

    $username = trim(post_string('username'));
    $email = strtolower(trim(post_string('email')));
    $password = post_string('password');
    $confirm = post_string('password_confirm');

    if ($username === '') {
        $errors[] = t('username_required');
    } elseif (!preg_match('/^[\p{L}\p{N}_]{3,50}$/u', $username)) {
        $errors[] = t('username_invalid');
    }

    if ($email === '') {
        $errors[] = t('email_required');
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email, 'UTF-8') > 255) {
        $errors[] = t('email_invalid');
    }

    if ($password === '') {
        $errors[] = t('password_required');
    } elseif (mb_strlen($password, 'UTF-8') < PASSWORD_MIN_LENGTH) {
        $errors[] = t('password_min', ['min' => PASSWORD_MIN_LENGTH]);
    }

    if ($password !== $confirm) {
        $errors[] = t('password_mismatch');
    }

    if ($errors === [] && User::emailTaken($email)) {
        $errors[] = t('email_taken');
    }

    if ($errors === []) {
        try {
            $userId = User::create($username, $email, $password);
            login_user([
                'id' => $userId,
                'username' => $username,
                'role' => 'user',
            ]);
            set_flash('success', t('register_success'));
            redirect('index.php');
        } catch (PDOException $e) {
            $errors[] = t('email_taken');
        }
    }
}

$pageTitle = t('register_title');
require __DIR__ . '/partials/header.php';
?>
        <?php render_alerts($errors); ?>
        <form action="register.php" method="post" class="mx-auto" style="max-width: 28rem;">
            <h1 class="h4 mb-3"><?= e(t('register_title')) ?></h1>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label for="username" class="form-label"><?= e(t('username')) ?></label>
                <input id="username" type="text" name="username" class="form-control" maxlength="50" value="<?= e($username) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label"><?= e(t('email')) ?></label>
                <input id="email" type="email" name="email" class="form-control" maxlength="255" value="<?= e($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"><?= e(t('password')) ?></label>
                <input id="password" type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password_confirm" class="form-label"><?= e(t('password_confirm')) ?></label>
                <input id="password_confirm" type="password" name="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" name="register"><?= e(t('register_button')) ?></button>
            <p class="mt-3 mb-0"><?= e(t('have_account')) ?> <a href="login.php"><?= e(t('login')) ?></a></p>
        </form>
<?php require __DIR__ . '/partials/footer.php'; ?>
