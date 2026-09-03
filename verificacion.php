<?php
// verificacion.php — Paso 2 del 2FA: valida el OTP de un solo uso y crea la sesión.
// Comparación con hash_equals, límite de intentos y regeneración de ID de sesión.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=UTF-8');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$userId = (int) ($input['user_id'] ?? $_POST['user_id'] ?? 0);
$otp    = trim((string) ($input['otp'] ?? $_POST['otp'] ?? ''));

if (!$userId || !preg_match('/^\d{6}$/', $otp)) {
    json_out(['status' => 'error', 'message' => 'Ingrese el código de 6 dígitos.']);
}

try {
    $pdo = db();
} catch (Exception $e) {
    error_log('[DB] verificacion connection failed');
    json_out(['status' => 'error', 'message' => 'Error del servidor. Intente más tarde.']);
}

// Límite de intentos de OTP por usuario
if (is_locked($pdo, 'otp:' . $userId, MAX_OTP_ATTEMPTS, LOGIN_LOCKOUT_SECS)) {
    $pdo->prepare('UPDATE usuarios SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?')->execute([$userId]);
    json_out(['status' => 'error', 'message' => 'Demasiados intentos. Solicite un nuevo código.']);
}

$stmt = $pdo->prepare('SELECT id, nombre, email, otp_code, otp_expires_at FROM usuarios WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 1. Existencia (código activo)
if (!$user || empty($user['otp_code'])) {
    json_out(['status' => 'error', 'message' => 'Código no válido o utilizado previamente.']);
}

// 2. Expiración
if (strtotime($user['otp_expires_at']) < time()) {
    $pdo->prepare('UPDATE usuarios SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?')->execute([$userId]);
    json_out(['status' => 'error', 'message' => 'El código ha expirado.']);
}

// 3. Coincidencia en tiempo constante
if (!hash_equals((string) $user['otp_code'], $otp)) {
    register_failure($pdo, 'otp:' . $userId);
    json_out(['status' => 'error', 'message' => 'Código incorrecto.']);
}

// 4. Consumo único + sesión nueva (anti-fijación)
clear_failures($pdo, 'otp:' . $userId);
$pdo->prepare('UPDATE usuarios SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?')->execute([$userId]);

session_regenerate_id(true);
$_SESSION['user_id']       = $user['id'];
$_SESSION['username']      = $user['nombre'];
$_SESSION['email']         = $user['email'];
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

json_out(['status' => 'success', 'message' => 'Acceso permitido.', 'redirect' => 'inicio.php']);
