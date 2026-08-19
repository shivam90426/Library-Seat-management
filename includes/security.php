<?php

function library_system_bootstrap(): void
{
    if (headers_sent()) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function require_csrf_token(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';

    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        if (stripos($accept, 'application/json') !== false || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Security token mismatch. Please refresh and try again.'
            ]);
        } else {
            echo 'Security token mismatch. Please refresh and try again.';
        }
        exit;
    }
}

function require_request_method(string $method): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($method)) {
        http_response_code(405);
        header('Allow: ' . strtoupper($method));
        exit;
    }
}

function require_login(?string $role = null): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }

    if ($role !== null && ($_SESSION['role'] ?? null) !== $role) {
        header('Location: ../login.php');
        exit;
    }
}

function require_api_login(?string $role = null): void
{
    if (empty($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    if ($role !== null && ($_SESSION['role'] ?? null) !== $role) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }
}

function set_logged_in_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['name'] = $user['name'] ?? '';
    $_SESSION['role'] = $user['role'] ?? 'user';
    csrf_token();
}

function clear_session_and_redirect(string $location): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
    header('Location: ' . $location);
    exit;
}

function normalized_date_or_today(?string $value): string
{
    if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
        return $value;
    }

    return date('Y-m-d');
}
?>
