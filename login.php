<?php
// login.php — Paso 1 del 2FA: valida credenciales y emite OTP de 6 dígitos.
// Respuestas genéricas (sin enumeración) y sin exponer el OTP ni secretos.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$nombre   = trim($input['username'] ?? $_POST['username'] ?? '');
$password = $input['password'] ?? $_POST['password'] ?? '';
if (!is_string($password)) $password = '';

if ($nombre === '' || $password === '') {
    json_out(['status' => 'error', 'message' => 'Por favor, ingrese usuario y contraseña.']);
}

try {
    $pdo = db();
} catch (Exception $e) {
    error_log('[DB] login connection failed');
    json_out(['status' => 'error', 'message' => 'Error del servidor. Intente más tarde.']);
}

// Bloqueo por fuerza bruta (mismo mensaje genérico para no enumerar usuarios)
if (is_locked($pdo, 'login:' . mb_strtolower($nombre), MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_SECS)) {
    json_out(['status' => 'error', 'message' => 'Demasiados intentos. Intente en 15 minutos.']);
}

try {
    // Homónimos permitidos por diseño: puede haber varios registros con el mismo nombre.
    $stmt = $pdo->prepare('SELECT id, nombre, email, salt, password_hash FROM usuarios WHERE nombre = ?');
    $stmt->execute([$nombre]);
    $cuentas = $stmt->fetchAll();

    $usuarioAutenticado = null;
    foreach ($cuentas as $cuenta) {
        $stored = (string) ($cuenta['password_hash'] ?? '');
        $ok = false;
        if (is_bcrypt_hash($stored)) {
            $ok = password_verify($password, $stored);
            // Rehash si cambió el algoritmo/coste
            if ($ok && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $cuenta['id']]);
            }
        } else {
            // Legado SHA-256(password+salt): verificar y migrar a bcrypt en caliente
            $hashCalculado = hash('sha256', $password . ($cuenta['salt'] ?? ''));
            if (hash_equals($stored, $hashCalculado)) {
                $ok = true;
                $pdo->prepare('UPDATE usuarios SET password_hash = ?, salt = NULL WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $cuenta['id']]);
            }
        }
        if ($ok) { $usuarioAutenticado = $cuenta; break; }
    }

    if (!$usuarioAutenticado) {
        register_failure($pdo, 'login:' . mb_strtolower($nombre));
        json_out(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
    }

    clear_failures($pdo, 'login:' . mb_strtolower($nombre));

    $otp = str_pad((string) random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
    $expiracion = date('Y-m-d H:i:s', time() + OTP_TTL_SECONDS);
    $pdo->prepare('UPDATE usuarios SET otp_code = ?, otp_expires_at = ? WHERE id = ?')
        ->execute([$otp, $expiracion, $usuarioAutenticado['id']]);

    $destinatarioEmail = $usuarioAutenticado['email'];

    $autoload = __DIR__ . '/vendor/autoload.php';
    $mailSent = false;
    if (is_file($autoload)) {
        require_once $autoload;
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = (string) env('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = (string) env('SMTP_USER', '');
            $mail->Password   = (string) env('SMTP_PASS', '');
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) env('SMTP_PORT', '587');
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 8;
            $mail->setFrom((string) env('SMTP_FROM', ''), (string) env('SMTP_FROM_NAME', 'PKTechnologies Seguridad'));
            $mail->addAddress($destinatarioEmail, $usuarioAutenticado['nombre']);
            $mail->isHTML(true);
            $mail->Subject = 'Código de Verificación 2FA - PKTechnologies';

            $otpCellsHtml = '';
            for ($i = 0; $i < 6; $i++) {
                $d = htmlspecialchars($otp[$i] ?? '');
                $padLeft = ($i === 3) ? '12px' : '0';
                $padRight = ($i === 5) ? '0' : '4px';
                $otpCellsHtml .= "<td style=\"padding-left:{$padLeft};padding-right:{$padRight};\"><div style=\"width:42px;height:48px;line-height:48px;background:#0e1a30;border:1.8px solid #23324e;border-radius:10px;text-align:center;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:17px;font-weight:600;color:#e2e8f0;\">{$d}</div></td>";
            }
            $safeName = htmlspecialchars($usuarioAutenticado['nombre']);
            $mail->Body = "
<!DOCTYPE html><html lang=\"es\"><body style=\"margin:0;padding:0;background:#070b1a;\">
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background:#070b1a;padding:24px;\"><tr><td align=\"center\">
<table width=\"480\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"max-width:480px;width:100%;background:#141f35;border:1px solid rgba(255,255,255,0.07);border-radius:16px;overflow:hidden;\">
<tr><td style=\"padding:14px 20px;border-bottom:1px solid rgba(255,255,255,0.06);color:#fff;font-family:sans-serif;font-size:15px;font-weight:600;\">PKTechnologies</td></tr>
<tr><td align=\"center\" style=\"padding:28px 24px 20px;\">
<h1 style=\"color:#f1f5f9;font-family:sans-serif;font-size:22px;\">Código de Verificación</h1>
<p style=\"color:#94a3b8;font-family:sans-serif;font-size:13px;\">Hola <strong style=\"color:#e2e8f0;\">{$safeName}</strong>, tu código temporal es:</p>
<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:18px auto;\"><tr>{$otpCellsHtml}</tr></table>
<p style=\"color:#64748b;font-family:sans-serif;font-size:12px;\">Vencerá en <strong style=\"color:#cbd5e1;\">5 minutos</strong> • No lo compartas</p>
</td></tr></table></td></tr></table></body></html>";
            $mail->AltBody = 'Tu código de verificación de PKTechnologies es válido por 5 minutos. Revísalo en el correo HTML.';
            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            error_log('[MAIL] fallo de envío SMTP');
        }
    }

    // El OTP NUNCA se devuelve al cliente: solo via correo.
    json_out([
        'status'       => 'otp_required',
        'message'      => $mailSent
            ? 'Hemos enviado el código a su correo.'
            : 'Si no recibe el correo, revise spam o solicite un nuevo código.',
        'user_id'      => (int) $usuarioAutenticado['id'],
        'email_masked' => mask_email($destinatarioEmail),
    ]);
} catch (Exception $e) {
    error_log('[DB] login query failed');
    json_out(['status' => 'error', 'message' => 'Error del servidor. Intente más tarde.']);
}
