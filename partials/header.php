<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? '';
$bootstrapCss = current_lang() === 'ar'
    ? 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css'
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css';
$bootstrapIntegrity = current_lang() === 'ar'
    ? 'sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N'
    : 'sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC';
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= current_lang() === 'ar' ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link href="<?= e($bootstrapCss) ?>" rel="stylesheet" integrity="<?= e($bootstrapIntegrity) ?>" crossorigin="anonymous">
    <?php if (!empty($withStyle)): ?>
        <link rel="stylesheet" href="style.css">
    <?php endif; ?>
</head>

<body>
    <div class="container py-4">
        <nav class="navbar navbar-light bg-light mb-4 px-3" aria-label="<?= e(t('language')) ?>">
            <a class="navbar-brand" href="index.php"><?= e(t('app_title')) ?></a>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group" role="group" aria-label="<?= e(t('language')) ?>">
                    <?php foreach (['ar' => 'lang_ar', 'fr' => 'lang_fr', 'en' => 'lang_en'] as $code => $labelKey): ?>
                        <a class="btn btn-sm <?= current_lang() === $code ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(lang_url($code)) ?>"><?= e(t($labelKey)) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php if (is_logged_in()): ?>
                    <span class="navbar-text"><?= e(current_username()) ?></span>
                    <form action="logout.php" method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" name="logout"><?= e(t('logout')) ?></button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-primary" href="login.php"><?= e(t('login')) ?></a>
                    <a class="btn btn-sm btn-primary" href="register.php"><?= e(t('register')) ?></a>
                <?php endif; ?>
            </div>
        </nav>
