<?php

declare(strict_types=1);

const QUESTION_MAX_LENGTH = 30;
const CONTENT_MAX_LENGTH = 5000;
const ANSWER_MAX_LENGTH = 5000;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => false,
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_valid(): bool
{
    $stored = $_SESSION['csrf_token'] ?? '';
    $sent = $_POST['csrf_token'] ?? '';

    if (!is_string($stored) || !is_string($sent) || $stored === '' || $sent === '') {
        return false;
    }

    return hash_equals($stored, $sent);
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type === 'success' ? 'success' : 'danger',
        'message' => $message,
    ];
}

function render_flash(): void
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    if (!is_array($flash) || !isset($flash['message']) || !is_string($flash['message'])) {
        return;
    }

    $type = ($flash['type'] ?? '') === 'success' ? 'success' : 'danger';
    render_alerts([$flash['message']], $type);
}

function render_alerts(array $messages, string $type = 'danger'): void
{
    $class = $type === 'success' ? 'alert-success' : 'alert-danger';

    foreach ($messages as $message) {
        if (!is_string($message) || $message === '') {
            continue;
        }

        echo '<div class="alert ' . $class . '" role="alert">' . e($message) . '</div>';
    }
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? $value : '';
}

function validate_required_text(string $value, int $maxLength, string $label): array
{
    $value = trim($value);

    if ($value === '') {
        return ['', t($label . '_required')];
    }

    if (mb_strlen($value, 'UTF-8') > $maxLength) {
        return [$value, t($label . '_max', ['max' => $maxLength])];
    }

    return [$value, null];
}

function request_id(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    return $id === false ? null : $id;
}

function posted_category_id(): array
{
    $raw = $_POST['category_id'] ?? '';
    if (!is_string($raw) || trim($raw) === '') {
        return [null, null];
    }

    $id = request_id($raw);
    if ($id === null || !Category::exists($id)) {
        return [null, t('category_invalid')];
    }

    return [$id, null];
}

function posted_category(): array
{
    [$existingId, $existingError] = posted_category_id();
    [$newName, $nameError] = Category::normalize(post_string('new_category'));
    if ($nameError !== null) {
        return [$existingId, null, $nameError];
    }
    if ($newName !== null) {
        return [$existingId, $newName, null];
    }

    return [$existingId, null, $existingError];
}

function current_lang(): string
{
    $lang = $_SESSION['lang'] ?? 'ar';

    return in_array($lang, ['ar', 'fr', 'en'], true) ? $lang : 'ar';
}

function load_lang(string $lang): array
{
    $file = __DIR__ . '/lang/' . $lang . '.php';
    if (!is_file($file)) {
        return [];
    }

    $messages = require $file;

    return is_array($messages) ? $messages : [];
}

function translations(): array
{
    static $cache = [];
    $lang = current_lang();

    if (!isset($cache[$lang])) {
        $cache[$lang] = $lang === 'ar'
            ? load_lang('ar')
            : array_merge(load_lang('ar'), load_lang($lang));
    }

    return $cache[$lang];
}

function t(string $key, array $replace = []): string
{
    $text = translations()[$key] ?? $key;

    foreach ($replace as $name => $value) {
        if (!is_scalar($value)) {
            continue;
        }

        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }

    return $text;
}

function lang_url(string $lang): string
{
    $params = $_GET;
    $params['lang'] = $lang;
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));

    return $script . '?' . http_build_query($params);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $requestedLang = $_GET['lang'] ?? null;
    if (is_string($requestedLang) && in_array($requestedLang, ['ar', 'fr', 'en'], true)) {
        $_SESSION['lang'] = $requestedLang;
        $params = $_GET;
        unset($params['lang']);
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
        $query = http_build_query($params);
        redirect($script . ($query !== '' ? '?' . $query : ''));
    }
}

function current_user_id(): ?int
{
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
        return null;
    }

    $id = (int) $id;

    return $id > 0 ? $id : null;
}

function current_username(): string
{
    $name = $_SESSION['username'] ?? '';

    return is_string($name) ? $name : '';
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['role'] = (string) ($user['role'] ?? 'user');
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
    session_regenerate_id(true);
}

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Question.php';
require_once __DIR__ . '/src/User.php';
require_once __DIR__ . '/src/Answer.php';
require_once __DIR__ . '/src/Category.php';
require_once __DIR__ . '/src/Tag.php';
