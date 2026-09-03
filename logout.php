<?php
// logout.php — Cierra la sesión. Acepta POST con CSRF (recomendado) y GET (compatibilidad).
declare(strict_types=1);
require_once __DIR__ . '/session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $sent = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $sent)) {
        header('Location: inicio.php');
        exit;
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], '', $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: index.html');
exit;
