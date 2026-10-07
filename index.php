<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function list_page_url(int $page, string $search, string $sort, ?int $categoryId, ?int $tagId): string
{
    $query = ['sort' => $sort, 'page' => $page];
    if ($search !== '') {
        $query['q'] = $search;
    }
    if ($categoryId !== null) {
        $query['category'] = $categoryId;
    }
    if ($tagId !== null) {
        $query['tag'] = $tagId;
    }

    return 'index.php?' . http_build_query($query);
}

$allowedSorts = ['newest', 'oldest', 'az'];
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) && in_array($_GET['sort'], $allowedSorts, true)
    ? $_GET['sort']
    : 'newest';
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $page === false ? 1 : $page;
$categoryId = request_id($_GET['category'] ?? null);
$tagId = request_id($_GET['tag'] ?? null);

$questions = [];
$totalPages = 0;
$categories = [];
$tags = [];
$tagsByQuestion = [];
$loadError = null;

try {
    if ($categoryId !== null && !Category::exists($categoryId)) {
        $categoryId = null;
    }
    if ($tagId !== null && !Tag::exists($tagId)) {
        $tagId = null;
    }
    $categories = Category::all();
    $tags = Tag::all();
    $result = Question::search($search, $sort, $page, 10, $categoryId, $tagId);
    $questions = $result['rows'];
    $page = $result['page'];
    $totalPages = $result['pages'];
    $tagsByQuestion = Tag::groupedByQuestion(array_column($questions, 'id'));
} catch (PDOException $e) {
    $loadError = t('load_questions_error');
}

$oldQuestion = '';
if (isset($_SESSION['old_question']) && is_string($_SESSION['old_question'])) {
    $oldQuestion = $_SESSION['old_question'];
}
unset($_SESSION['old_question']);
$oldCategory = isset($_SESSION['old_category']) ? request_id($_SESSION['old_category']) : null;
unset($_SESSION['old_category']);
$oldNewCategory = '';
if (isset($_SESSION['old_new_category']) && is_string($_SESSION['old_new_category'])) {
    $oldNewCategory = $_SESSION['old_new_category'];
}
unset($_SESSION['old_new_category']);
$oldTags = '';
if (isset($_SESSION['old_tags']) && is_string($_SESSION['old_tags'])) {
    $oldTags = $_SESSION['old_tags'];
}
unset($_SESSION['old_tags']);

$pageTitle = t('app_title');
$withStyle = true;
require __DIR__ . '/partials/header.php';
?>
        <?php render_flash(); ?>
        <?php if ($loadError !== null): ?>
            <?php render_alerts([$loadError]); ?>
        <?php endif; ?>

        <?php if ($categories !== []): ?>
            <div class="mb-2">
                <span class="me-2"><?= e(t('category')) ?>:</span>
                <a class="badge <?= $categoryId === null ? 'bg-primary' : 'bg-secondary' ?> text-decoration-none" href="<?= e(list_page_url(1, $search, $sort, null, $tagId)) ?>"><?= e(t('category_all')) ?></a>
                <?php foreach ($categories as $category): ?>
                    <?php $activeCategory = $categoryId === (int) $category['id']; ?>
                    <a class="badge <?= $activeCategory ? 'bg-primary' : 'bg-secondary' ?> text-decoration-none" href="<?= e(list_page_url(1, $search, $sort, $activeCategory ? null : (int) $category['id'], $tagId)) ?>"><?= e((string) $category['name']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($tags !== []): ?>
            <div class="mb-3">
                <span class="me-2"><?= e(t('tags')) ?>:</span>
                <a class="badge rounded-pill <?= $tagId === null ? 'bg-dark' : 'bg-info text-dark' ?> text-decoration-none" href="<?= e(list_page_url(1, $search, $sort, $categoryId, null)) ?>"><?= e(t('tag_all')) ?></a>
                <?php foreach ($tags as $tag): ?>
                    <?php $activeTag = $tagId === (int) $tag['id']; ?>
                    <a class="badge rounded-pill <?= $activeTag ? 'bg-dark' : 'bg-info text-dark' ?> text-decoration-none" href="<?= e(list_page_url(1, $search, $sort, $categoryId, $activeTag ? null : (int) $tag['id'])) ?>"><?= e((string) $tag['name']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="get" class="d-flex flex-wrap gap-2 mb-3">
            <?php if ($categoryId !== null): ?>
                <input type="hidden" name="category" value="<?= (int) $categoryId ?>">
            <?php endif; ?>
            <?php if ($tagId !== null): ?>
                <input type="hidden" name="tag" value="<?= (int) $tagId ?>">
            <?php endif; ?>
            <input type="search" class="form-control" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('search_placeholder')) ?>" aria-label="<?= e(t('search_label')) ?>">
            <select class="form-select w-auto" name="sort" aria-label="<?= e(t('sort_label')) ?>">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= e(t('sort_newest')) ?></option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>><?= e(t('sort_oldest')) ?></option>
                <option value="az" <?= $sort === 'az' ? 'selected' : '' ?>><?= e(t('sort_az')) ?></option>
            </select>
            <button type="submit" class="btn btn-outline-primary"><?= e(t('search_button')) ?></button>
        </form>

        <ul class="list-group">
            <li class="list-group-item">
                <?php if (is_logged_in()): ?>
                    <form action="insert.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="d-flex mb-2">
                            <input type="text" class="form-control me-2" name="qst_name" maxlength="<?= QUESTION_MAX_LENGTH ?>" value="<?= e($oldQuestion) ?>" placeholder="<?= e(t('add_placeholder')) ?>">
                            <button type="submit" class="btn btn-primary" name="add"><?= e(t('add_button')) ?></button>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label" for="category_id"><?= e(t('category')) ?></label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value=""><?= e(t('category_none')) ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['id'] ?>" <?= $oldCategory === (int) $category['id'] ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="form-label mt-2" for="new_category"><?= e(t('new_category')) ?></label>
                                <input id="new_category" type="text" name="new_category" class="form-control" maxlength="<?= Category::MAX_LENGTH ?>" value="<?= e($oldNewCategory) ?>" placeholder="<?= e(t('new_category_placeholder')) ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="tags"><?= e(t('tags')) ?></label>
                                <input id="tags" type="text" name="tags" class="form-control" value="<?= e($oldTags) ?>" placeholder="<?= e(t('tags_placeholder')) ?>">
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="mb-0"><?= e(t('login_to_add')) ?> <a href="login.php"><?= e(t('login')) ?></a></p>
                <?php endif; ?>
            </li>

            <?php foreach ($questions as $row): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <a href="question.php?id=<?= (int) $row['id'] ?>"><?= e($row['nom']) ?></a>
                        <span class="badge bg-secondary ms-2"><?= e(t('answer_count', ['count' => (int) ($row['answer_count'] ?? 0)])) ?></span>
                        <?php if (!empty($row['category_name'])): ?>
                            <a class="badge bg-primary text-decoration-none ms-1" href="<?= e(list_page_url(1, $search, $sort, (int) $row['category_id'], $tagId)) ?>"><?= e((string) $row['category_name']) ?></a>
                        <?php endif; ?>
                        <?php foreach ($tagsByQuestion[(int) $row['id']] ?? [] as $questionTag): ?>
                            <a class="badge rounded-pill bg-info text-dark text-decoration-none" href="<?= e(list_page_url(1, $search, $sort, $categoryId, (int) $questionTag['id'])) ?>"><?= e((string) $questionTag['name']) ?></a>
                        <?php endforeach; ?>
                    </span>
                    <?php $ownsRow = is_logged_in() && (int) ($row['user_id'] ?? 0) === current_user_id(); ?>
                    <div>
                        <a href="content.php?id=<?= (int) $row['id'] ?>" class="btn btn-info btn-sm"><?= e(t($ownsRow ? 'add_content' : 'view_content')) ?></a>
                        <?php if ($ownsRow): ?>
                            <a href="update.php?id=<?= (int) $row['id'] ?>" class="btn btn-success btn-sm mx-1"><?= e(t('edit_question')) ?></a>
                            <form id="delete-form-<?= (int) $row['id'] ?>" action="delete.php" method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button type="button" class="btn btn-danger btn-sm mx-1" data-bs-toggle="modal" data-bs-target="#deleteModal" data-form="delete-form-<?= (int) $row['id'] ?>" data-name="<?= e($row['nom']) ?>"><?= e(t('delete_question')) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
            <?php if ($questions === [] && $loadError === null): ?>
                <li class="list-group-item text-muted"><?= e($search === '' && $categoryId === null && $tagId === null ? t('no_questions') : t('no_results')) ?></li>
            <?php endif; ?>
        </ul>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3" aria-label="<?= e(t('pagination_label')) ?>">
                <ul class="pagination mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <?php if ($page <= 1): ?>
                            <span class="page-link"><?= e(t('previous')) ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= e(list_page_url($page - 1, $search, $sort, $categoryId, $tagId)) ?>"><?= e(t('previous')) ?></a>
                        <?php endif; ?>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(list_page_url($i, $search, $sort, $categoryId, $tagId)) ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <?php if ($page >= $totalPages): ?>
                            <span class="page-link"><?= e(t('next')) ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= e(list_page_url($page + 1, $search, $sort, $categoryId, $tagId)) ?>"><?= e(t('next')) ?></a>
                        <?php endif; ?>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel"><?= e(t('delete_modal_title')) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('close')) ?>"></button>
                    </div>
                    <div class="modal-body">
                        <?= e(t('delete_confirm_before')) ?><strong class="delete-question-name"></strong><?= e(t('delete_confirm_after')) ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(t('cancel')) ?></button>
                        <button type="button" class="btn btn-danger" id="confirmDelete"><?= e(t('delete_question')) ?></button>
                    </div>
                </div>
            </div>
        </div>
<?php
$confirmDelete = true;
require __DIR__ . '/partials/footer.php';
?>
