<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$userId = current_user_id();
if ($userId === null) {
    set_flash('danger', t('auth_required'));
    redirect('login.php');
}

$errors = [];
$questionName = '';
$showForm = false;
$id = null;
$categoryId = null;
$newCategory = '';
$tagsInput = '';
$categories = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = t('invalid_request_reload');
    }

    $id = request_id($_POST['id'] ?? null);
    if ($id === null) {
        $errors[] = t('invalid_id');
    }

    [$questionName, $nameError] = validate_required_text(post_string('qst_name'), QUESTION_MAX_LENGTH, 'question');
    if ($nameError !== null) {
        $errors[] = $nameError;
    }
    $newCategory = post_string('new_category');
    [$categoryId, $pendingCategory, $categoryError] = posted_category();
    if ($categoryError !== null) {
        $errors[] = $categoryError;
    }
    $tagsInput = post_string('tags');
    [$tagNames, $tagError] = Tag::parse($tagsInput);
    if ($tagError !== null) {
        $errors[] = $tagError;
    }

    if ($id !== null) {
        try {
            $existing = Question::find($id);
            if ($existing === null) {
                $errors[] = t('question_not_found');
            } elseif (!Question::isOwnedBy($id, $userId)) {
                set_flash('danger', t('forbidden'));
                redirect('index.php');
            } else {
                $showForm = true;
            }
        } catch (PDOException $e) {
            $errors[] = t('load_question_error');
        }
    }

    if ($errors === [] && $id !== null) {
        $connection = Database::getInstance()->getConnection();
        try {
            $connection->beginTransaction();
            if ($pendingCategory !== null) {
                $categoryId = Category::findOrCreate($pendingCategory);
            }
            Question::updateName($id, $userId, $questionName, $categoryId);
            Tag::sync($id, $tagNames);
            $connection->commit();
            set_flash('success', t('update_success'));
            redirect('index.php');
        } catch (PDOException $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $errors[] = t('save_edit_error');
        }
    }
} else {
    $id = request_id($_GET['id'] ?? null);
    if ($id === null) {
        $errors[] = t('invalid_id');
    } else {
        try {
            $row = Question::find($id);
            if ($row === null) {
                $errors[] = t('question_not_found');
            } elseif (!Question::isOwnedBy($id, $userId)) {
                set_flash('danger', t('forbidden'));
                redirect('index.php');
            } else {
                $questionName = (string) $row['nom'];
                $categoryId = $row['category_id'] !== null ? (int) $row['category_id'] : null;
                $tagsInput = implode(', ', Tag::namesForQuestion($id));
                $showForm = true;
            }
        } catch (PDOException $e) {
            $errors[] = t('load_question_error');
        }
    }
}

try {
    $categories = Category::all();
} catch (PDOException $e) {
    $errors[] = t('load_question_error');
    $showForm = false;
}

$pageTitle = t('edit_title');
require __DIR__ . '/partials/header.php';
?>
        <?php render_alerts($errors); ?>

        <?php if ($showForm && $id !== null): ?>
            <form action="update.php" method="post" class="mx-auto" style="max-width: 36rem;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <div class="mb-3">
                    <label for="qst_name" class="form-label"><?= e(t('edit_label')) ?></label>
                    <input id="qst_name" type="text" class="form-control" name="qst_name" maxlength="<?= QUESTION_MAX_LENGTH ?>" value="<?= e($questionName) ?>">
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label"><?= e(t('category')) ?></label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value=""><?= e(t('category_none')) ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="new_category" class="form-label mt-2"><?= e(t('new_category')) ?></label>
                    <input id="new_category" type="text" name="new_category" class="form-control" maxlength="<?= Category::MAX_LENGTH ?>" value="<?= e($newCategory) ?>" placeholder="<?= e(t('new_category_placeholder')) ?>">
                </div>
                <div class="mb-3">
                    <label for="tags" class="form-label"><?= e(t('tags')) ?></label>
                    <input id="tags" type="text" name="tags" class="form-control" value="<?= e($tagsInput) ?>" placeholder="<?= e(t('tags_placeholder')) ?>">
                </div>
                <button type="submit" name="update" class="btn btn-success"><?= e(t('save_edit')) ?></button>
                <a href="index.php" class="btn btn-secondary"><?= e(t('cancel')) ?></a>
            </form>
        <?php else: ?>
            <a href="index.php" class="btn btn-secondary"><?= e(t('back')) ?></a>
        <?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
