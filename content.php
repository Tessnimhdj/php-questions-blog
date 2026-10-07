<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$errors = [];
$success = '';
$question = '';
$content = '';
$showForm = false;
$isOwner = false;
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$id = request_id($isPost ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null));

if ($id === null) {
    $errors[] = t('invalid_id');
} else {
    try {
        $row = Question::find($id);

        if ($row === null) {
            $errors[] = t('question_not_found');
        } else {
            $showForm = true;
            $question = (string) $row['nom'];
            $content = (string) ($row['contenu'] ?? '');
            $ownerId = current_user_id();
            $isOwner = $ownerId !== null && Question::isOwnedBy($id, $ownerId);
        }
    } catch (PDOException $e) {
        $errors[] = t('load_question_error');
    }
}

if ($isPost && $showForm && $id !== null) {
    $ownerId = current_user_id();
    if ($ownerId === null) {
        $errors[] = t('auth_required');
    } elseif (!Question::isOwnedBy($id, $ownerId)) {
        $errors[] = t('forbidden');
    } else {
        [$submitted, $contentError] = validate_required_text(post_string('content'), CONTENT_MAX_LENGTH, 'content');
        $content = $submitted;

        if (!csrf_valid()) {
            $errors[] = t('invalid_request_reload');
        }
        if ($contentError !== null) {
            $errors[] = $contentError;
        }

        if ($errors === []) {
            try {
                Question::updateContent($id, $ownerId, $submitted);
                $success = t('content_saved');
            } catch (PDOException $e) {
                $errors[] = t('content_save_error');
            }
        }
    }
}

$pageTitle = t('content_title');
require __DIR__ . '/partials/header.php';
?>
        <?php render_alerts($errors); ?>
        <?php if ($success !== ''): ?>
            <?php render_alerts([$success], 'success'); ?>
        <?php endif; ?>

        <?php if ($showForm && $id !== null): ?>
            <h1 class="h4 text-center text-danger mb-4"><?= e(t('question_heading')) ?> <?= e($question) ?></h1>
            <?php if ($isOwner): ?>
                <form action="content.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                    <label for="content"><?= e(t('content_label')) ?></label>
                    <textarea id="content" name="content" rows="10" maxlength="<?= CONTENT_MAX_LENGTH ?>" class="form-control"><?= e($content) ?></textarea>
                    <div class="mt-3">
                        <button type="submit" name="save" class="btn btn-success"><?= e(t('save')) ?></button>
                        <a href="index.php" class="btn btn-secondary"><?= e(t('back')) ?></a>
                    </div>
                </form>
            <?php else: ?>
                <div class="border rounded p-3 mb-3"><?= nl2br(e($content)) ?></div>
                <a href="index.php" class="btn btn-secondary"><?= e(t('back')) ?></a>
            <?php endif; ?>
        <?php else: ?>
            <a href="index.php" class="btn btn-secondary"><?= e(t('back')) ?></a>
        <?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
