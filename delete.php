<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('danger', t('delete_get_only'));
    redirect('index.php');
}

if (!csrf_valid()) {
    set_flash('danger', t('invalid_request'));
    redirect('index.php');
}

$userId = current_user_id();
if ($userId === null) {
    set_flash('danger', t('auth_required'));
    redirect('login.php');
}

$id = request_id($_POST['id'] ?? null);
if ($id === null) {
    set_flash('danger', t('invalid_id'));
    redirect('index.php');
}

try {
    if (Question::find($id) === null) {
        set_flash('danger', t('question_not_found'));
    } elseif (!Question::isOwnedBy($id, $userId)) {
        set_flash('danger', t('forbidden'));
    } elseif (Question::delete($id, $userId) === 0) {
        set_flash('danger', t('delete_error'));
    } else {
        set_flash('success', t('delete_success'));
    }
} catch (PDOException $e) {
    set_flash('danger', t('delete_error'));
}

redirect('index.php');
