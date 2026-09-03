<?php
// forgot.php — Recuperación de contraseña en 2 pasos verificando acceso al correo.
// action=request: {identifier} (usuario o correo) → envía código de 6 dígitos (15 min).
// action=reset:   {email, code, new_password} → valida el código y guarda el hash nuevo.
// Respuestas genéricas para no enumerar cuentas.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_POST['action'] ?? 'request';

try {
    $pdo = db();
} catch (Exception $e) {
    error_log('[DB] forgot connection failed');
    json_out(['status' => 'error', 'message' => 'Error del servidor. Intente más tarde.']);
}

if ($action === 'request') {
    $identifier = trim((string) ($input['identifier'] ?? $_POST['identifier'] ?? ''));
    if ($identifier === '') {
        json_out(['status' => 'error', 'message' => 'Ingrese su usuario o correo.']);
    }

    // Buscar por correo o por nombre (homónimos: se usa la coincidencia más reciente).
    if (str_contains($identifier, '@')) {
        $stmt = $pdo->prepare('SELECT id, nombre, email FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$identifier]);
    } else {
        $stmt = $pdo->prepare('SELECT id, nombre, email FROM usuarios WHERE nombre = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$identifier]);
    }
    $user = $stmt->fetch();

    // Mensaje idéntico exista o no la cuenta (anti-enumeración).
    $generic = 'Si la cuenta existe, enviamos un código a su correo.';
    if (!$user) {
        json_out(['status' => 'reset_sent', 'message' => $generic, 'email_masked' => mask_email($identifier)]);
    }

    if (is_locked($pdo, 'forgot:' . $user['email'], MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_SECS)) {
        json_out(['status' => 'error', 'message' => 'Demasiados intentos. Intente en 15 minutos.']);
    }

    $code = str_pad((string) random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
    $exp = date('Y-m-d H:i:s', time() + RESET_TTL_SECONDS);
    $pdo->prepare('UPDATE usuarios SET reset_code = ?, reset_expires_at = ? WHERE id = ?')
        ->execute([$code, $exp, $user['id']]);

    $sent = send_code_mail(
        $user['email'], $user['nombre'],
        'Recuperación de contraseña - PKTechnologies',
        $code, 'Recuperar contraseña', 'tu código para cambiar la contraseña es:'
    );
    if (!$sent) {
        register_failure($pdo, 'forgot:' . $user['email']);
        json_out(['status' => 'error', 'message' => 'No se pudo enviar el correo. Intente más tarde.']);
    }
    clear_failures($pdo, 'forgot:' . $user['email']);
    json_out(['status' => 'reset_sent', 'message' => $generic, 'email_masked' => mask_email($user['email'])]);
}

if ($action === 'reset') {
    $email = trim((string) ($input['email'] ?? $_POST['email'] ?? ''));
    $code  = trim((string) ($input['code'] ?? $_POST['code'] ?? ''));
    $nueva = (string) ($input['new_password'] ?? $_POST['new_password'] ?? '');
    if (!is_string($nueva)) $nueva = '';

    if (!valid_email($email) || !preg_match('/^\d{6}$/', $code)) {
        json_out(['status' => 'error', 'message' => 'Datos no válidos. Revise el código de 6 dígitos.']);
    }
    if (!valid_password($nueva)) {
        json_out(['status' => 'error', 'message' => 'La contraseña debe tener mínimo 8 caracteres, letras y números.']);
    }

    if (is_locked($pdo, 'reset:' . mb_strtolower($email), MAX_OTP_ATTEMPTS, LOGIN_LOCKOUT_SECS)) {
        json_out(['status' => 'error', 'message' => 'Demasiados intentos. Solicite un nuevo código.']);
    }

    $stmt = $pdo->prepare('SELECT id, reset_code, reset_expires_at FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || empty($user['reset_code'])) {
        json_out(['status' => 'error', 'message' => 'Código no válido o expirado. Solicite uno nuevo.']);
    }
    if (strtotime($user['reset_expires_at']) < time()) {
        $pdo->prepare('UPDATE usuarios SET reset_code = NULL, reset_expires_at = NULL WHERE id = ?')->execute([$user['id']]);
        json_out(['status' => 'error', 'message' => 'El código ha expirado. Solicite uno nuevo.']);
    }
    if (!hash_equals((string) $user['reset_code'], $code)) {
        register_failure($pdo, 'reset:' . mb_strtolower($email));
        json_out(['status' => 'error', 'message' => 'Código incorrecto.']);
    }

    $pdo->prepare('UPDATE usuarios SET password_hash = ?, salt = NULL, reset_code = NULL, reset_expires_at = NULL WHERE id = ?')
        ->execute([password_hash($nueva, PASSWORD_DEFAULT), $user['id']]);
    clear_failures($pdo, 'reset:' . mb_strtolower($email));
    clear_failures($pdo, 'login:' . mb_strtolower($email));
    json_out(['status' => 'success', 'message' => 'Contraseña actualizada. Ya puede iniciar sesión.']);
}

json_out(['status' => 'error', 'message' => 'Acción no válida.']);
