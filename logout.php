<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['logout']) || !csrf_valid()) {
    set_flash('danger', t('invalid_request'));
    redirect('index.php');
}

logout_user();
redirect('index.php');
