<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$errors = [];
$draft = '';
$editingId = null;
$editDraft = '';
$editFailed = false;

$id = request_id($_GET['id'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedId = request_id($_POST['question_id'] ?? null);
    if ($postedId !== null) {
        $id = $postedId;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = current_user_id();
    if (!csrf_valid()) {
        $errors[] = t('invalid_request_reload');
    } elseif ($userId === null) {
        set_flash('danger', t('auth_required'));
        redirect($id !== null ? 'login.php' : 'index.php');
    } elseif ($id === null || Question::find($id) === null) {
        $errors[] = $id === null ? t('invalid_id') : t('question_not_found');
    } else {
        $action = post_string('answer_action');
        if ($action === 'add') {
            [$draft, $bodyError] = validate_required_text(post_string('body'), ANSWER_MAX_LENGTH, 'answer');
            if ($bodyError !== null) {
                $errors[] = $bodyError;
            } else {
                try {
                    Answer::create($id, $userId, $draft);
                    set_flash('success', t('answer_saved'));
                    redirect('question.php?id=' . $id);
                } catch (PDOException $e) {
                    $errors[] = t('answer_save_error');
                }
            }
        } elseif ($action === 'update') {
            $answerId = request_id($_POST['answer_id'] ?? null);
            $editingId = $answerId;
            if ($answerId === null || !Answer::isOwnedBy($answerId, $userId)) {
                $errors[] = t('answer_forbidden');
            } else {
                [$editDraft, $bodyError] = validate_required_text(post_string('body'), ANSWER_MAX_LENGTH, 'answer');
                if ($bodyError !== null) {
                    $editFailed = true;
                    $errors[] = $bodyError;
                } else {
                    try {
                        Answer::update($answerId, $userId, $editDraft);
                        set_flash('success', t('answer_updated'));
                        redirect('question.php?id=' . $id);
                    } catch (PDOException $e) {
                        $errors[] = t('answer_save_error');
                    }
                }
            }
        } elseif ($action === 'delete') {
            $answerId = request_id($_POST['answer_id'] ?? null);
            if ($answerId === null || !Answer::isOwnedBy($answerId, $userId)) {
                set_flash('danger', t('answer_forbidden'));
            } elseif (Answer::delete($answerId, $userId) === 0) {
                set_flash('danger', t('answer_save_error'));
            } else {
                set_flash('success', t('answer_deleted'));
            }
            redirect('question.php?id=' . $id);
        } else {
            $errors[] = t('invalid_request');
        }
    }
}

$question = null;
$answers = [];
$loadError = null;

if ($id === null) {
    $loadError = t('invalid_id');
} else {
    try {
        $question = Question::find($id);
        if ($question === null) {
            $loadError = t('question_not_found');
        } else {
            $answers = Answer::forQuestion($id);
        }
    } catch (PDOException $e) {
        $loadError = t('load_question_error');
    }
}

$pageTitle = $question === null ? t('question_heading') : (string) $question['nom'];
require __DIR__ . '/partials/header.php';
?>
        <?php render_flash(); ?>
        <?php render_alerts($errors); ?>
        <?php if ($loadError !== null): ?>
            <?php render_alerts([$loadError]); ?>
            <a href="index.php" class="btn btn-secondary"><?= e(t('back_to_questions')) ?></a>
        <?php else: ?>
            <h1 class="h3"><?= e((string) $question['nom']) ?></h1>
            <?php if ((string) ($question['contenu'] ?? '') !== ''): ?>
                <p class="border rounded p-3"><?= nl2br(e((string) $question['contenu'])) ?></p>
            <?php endif; ?>

            <h2 class="h5 mt-4"><?= e(t('answers')) ?></h2>
            <?php if ($answers === []): ?>
                <p class="text-muted"><?= e(t('no_answers')) ?></p>
            <?php endif; ?>
            <?php foreach ($answers as $answer): ?>
                <?php
                $answerId = (int) $answer['id'];
                $ownsAnswer = is_logged_in() && (int) $answer['user_id'] === current_user_id();
                $bodyValue = ($editFailed && $editingId === $answerId) ? $editDraft : (string) $answer['body'];
                ?>
                <article class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><?= e((string) $answer['username']) ?></strong>
                            <span class="text-muted small"><?= e((string) $answer['created_at']) ?></span>
                        </div>
                        <p class="mb-3"><?= nl2br(e((string) $answer['body'])) ?></p>
                        <?php if ($ownsAnswer): ?>
                            <form action="question.php?id=<?= (int) $id ?>" method="post" class="mb-2">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="question_id" value="<?= (int) $id ?>">
                                <input type="hidden" name="answer_id" value="<?= $answerId ?>">
                                <label class="form-label" for="answer-<?= $answerId ?>"><?= e(t('edit_answer')) ?></label>
                                <textarea id="answer-<?= $answerId ?>" name="body" rows="4" maxlength="<?= ANSWER_MAX_LENGTH ?>" class="form-control"><?= e($bodyValue) ?></textarea>
                                <button type="submit" name="answer_action" value="update" class="btn btn-success btn-sm mt-2"><?= e(t('save')) ?></button>
                            </form>
                            <form action="question.php?id=<?= (int) $id ?>" method="post">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="question_id" value="<?= (int) $id ?>">
                                <input type="hidden" name="answer_id" value="<?= $answerId ?>">
                                <button type="submit" name="answer_action" value="delete" class="btn btn-danger btn-sm"><?= e(t('delete_answer')) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (is_logged_in()): ?>
                <form action="question.php?id=<?= (int) $id ?>" method="post" class="mt-4">
                    <h2 class="h5"><?= e(t('add_answer')) ?></h2>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="question_id" value="<?= (int) $id ?>">
                    <label class="form-label" for="body"><?= e(t('answer_label')) ?></label>
                    <textarea id="body" name="body" rows="5" maxlength="<?= ANSWER_MAX_LENGTH ?>" class="form-control"><?= e($draft) ?></textarea>
                    <button type="submit" name="answer_action" value="add" class="btn btn-primary mt-2"><?= e(t('add_answer')) ?></button>
                </form>
            <?php else: ?>
                <p class="mt-4"><?= e(t('login_to_answer')) ?> <a href="login.php"><?= e(t('login')) ?></a></p>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary mt-3"><?= e(t('back_to_questions')) ?></a>
        <?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
