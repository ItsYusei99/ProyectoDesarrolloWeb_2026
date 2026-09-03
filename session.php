<?php
// session.php — Arranque de sesión endurecido + timeout de inactividad.
// Incluir al inicio de páginas con sesión (inicio.php, dashboard.php, logout.php).

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,          // cookie de sesión (muere al cerrar navegador)
    'path'     => '/',
    'secure'   => $isHttps,   // solo HTTPS cuando aplique (en HTTP local queda false)
    'httponly' => true,       // JS no puede leer la cookie
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Timeout por inactividad
if (isset($_SESSION['authenticated'], $_SESSION['last_activity'])
    && $_SESSION['authenticated'] === true
    && (time() - (int) $_SESSION['last_activity']) > SESSION_LIFETIME
) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], '', $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: index.html');
    exit;
}
$_SESSION['last_activity'] = time();

// Token CSRF para formularios (logout POST)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function require_auth(): void {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Location: index.html');
        exit;
    }
}
