<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['add'])) {
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

[$title, $error] = validate_required_text(post_string('qst_name'), QUESTION_MAX_LENGTH, 'question');
[$categoryId, $newCategory, $categoryError] = posted_category();
[$tagNames, $tagError] = Tag::parse(post_string('tags'));

if ($error !== null || $categoryError !== null || $tagError !== null) {
    $_SESSION['old_question'] = $title;
    $_SESSION['old_category'] = $categoryId;
    $_SESSION['old_new_category'] = post_string('new_category');
    $_SESSION['old_tags'] = post_string('tags');
    set_flash('danger', $error ?? $categoryError ?? $tagError);
    redirect('index.php');
}

$connection = Database::getInstance()->getConnection();
try {
    $connection->beginTransaction();
    if ($newCategory !== null) {
        $categoryId = Category::findOrCreate($newCategory);
    }
    $questionId = Question::create($title, $userId, $categoryId);
    Tag::sync($questionId, $tagNames);
    $connection->commit();
    set_flash('success', t('add_success'));
} catch (PDOException $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    $_SESSION['old_question'] = $title;
    $_SESSION['old_category'] = $categoryId;
    $_SESSION['old_new_category'] = post_string('new_category');
    $_SESSION['old_tags'] = post_string('tags');
    set_flash('danger', t('add_error'));
}

redirect('index.php');
