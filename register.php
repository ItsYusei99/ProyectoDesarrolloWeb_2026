<?php
// register.php — Creación de usuarios con verificación del correo en 2 pasos.
// action=request: {nombre, email, password} → guarda pendiente y envía código (15 min).
// action=confirm: {email, code} → verifica el código y crea la cuenta (bcrypt).

declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_POST['action'] ?? 'request';

try {
    $pdo = db();
} catch (Exception $e) {
    error_log('[DB] register connection failed');
    json_out(['status' => 'error', 'message' => 'Error del servidor. Intente más tarde.']);
}

if ($action === 'request') {
    $nombre = trim((string) ($input['nombre'] ?? $input['username'] ?? $_POST['nombre'] ?? ''));
    $email  = trim((string) ($input['email'] ?? $_POST['email'] ?? ''));
    $pass   = (string) ($input['password'] ?? $_POST['password'] ?? '');
    if (!is_string($pass)) $pass = '';

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        json_out(['status' => 'error', 'message' => 'Ingrese un nombre de usuario válido.']);
    }
    if (!valid_email($email)) {
        json_out(['status' => 'error', 'message' => 'Ingrese un correo válido.']);
    }
    if (!valid_password($pass)) {
        json_out(['status' => 'error', 'message' => 'La contraseña debe tener mínimo 8 caracteres, letras y números.']);
    }

    // El correo es el identificador único: no puede repetirse.
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_out(['status' => 'error', 'message' => 'Ese correo ya está registrado. Use “Olvidé mi contraseña” si es suyo.']);
    }

    if (is_locked($pdo, 'register:' . mb_strtolower($email), MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_SECS)) {
        json_out(['status' => 'error', 'message' => 'Demasiados intentos. Intente en 15 minutos.']);
    }

    $code = str_pad((string) random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
    $exp = date('Y-m-d H:i:s', time() + RESET_TTL_SECONDS);
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO registro_pendiente (nombre, email, password_hash, code, expires_at, attempts) VALUES (?, ?, ?, ?, ?, 0)
                   ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), password_hash = VALUES(password_hash), code = VALUES(code), expires_at = VALUES(expires_at), attempts = 0')
        ->execute([$nombre, $email, $hash, $code, $exp]);

    $sent = send_code_mail(
        $email, $nombre,
        'Confirme su cuenta - PKTechnologies',
        $code, 'Crear cuenta', 'tu código para confirmar tu cuenta es:'
    );
    if (!$sent) {
        register_failure($pdo, 'register:' . mb_strtolower($email));
        json_out(['status' => 'error', 'message' => 'No se pudo enviar el correo. Intente más tarde.']);
    }
    clear_failures($pdo, 'register:' . mb_strtolower($email));
    json_out(['status' => 'code_sent', 'message' => 'Enviamos un código a su correo para confirmar la cuenta.', 'email_masked' => mask_email($email)]);
}

if ($action === 'confirm') {
    $email = trim((string) ($input['email'] ?? $_POST['email'] ?? ''));
    $code  = trim((string) ($input['code'] ?? $_POST['code'] ?? ''));

    if (!valid_email($email) || !preg_match('/^\d{6}$/', $code)) {
        json_out(['status' => 'error', 'message' => 'Datos no válidos. Revise el código de 6 dígitos.']);
    }

    $stmt = $pdo->prepare('SELECT id, nombre, email, password_hash, code, expires_at, attempts FROM registro_pendiente WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $pend = $stmt->fetch();
    if (!$pend) {
        json_out(['status' => 'error', 'message' => 'No hay registro pendiente para ese correo.']);
    }
    if (strtotime($pend['expires_at']) < time()) {
        $pdo->prepare('DELETE FROM registro_pendiente WHERE id = ?')->execute([$pend['id']]);
        json_out(['status' => 'error', 'message' => 'El código ha expirado. Registre de nuevo.']);
    }
    if ((int) $pend['attempts'] >= MAX_OTP_ATTEMPTS) {
        $pdo->prepare('DELETE FROM registro_pendiente WHERE id = ?')->execute([$pend['id']]);
        json_out(['status' => 'error', 'message' => 'Demasiados intentos. Registre de nuevo.']);
    }
    if (!hash_equals((string) $pend['code'], $code)) {
        $pdo->prepare('UPDATE registro_pendiente SET attempts = attempts + 1 WHERE id = ?')->execute([$pend['id']]);
        json_out(['status' => 'error', 'message' => 'Código incorrecto.']);
    }

    // Verificado que tiene acceso al correo: crear la cuenta.
    try {
        $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash, salt) VALUES (?, ?, ?, NULL)')
            ->execute([$pend['nombre'], $pend['email'], $pend['password_hash']]);
    } catch (Exception $e) {
        $pdo->prepare('DELETE FROM registro_pendiente WHERE id = ?')->execute([$pend['id']]);
        json_out(['status' => 'error', 'message' => 'Ese correo ya está registrado.']);
    }
    $pdo->prepare('DELETE FROM registro_pendiente WHERE id = ?')->execute([$pend['id']]);
    clear_failures($pdo, 'register:' . mb_strtolower($email));
    json_out(['status' => 'success', 'message' => 'Cuenta creada. Ya puede iniciar sesión.']);
}

json_out(['status' => 'error', 'message' => 'Acción no válida.']);
