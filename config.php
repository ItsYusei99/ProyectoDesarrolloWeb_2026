<?php
// config.php — Carga .env, conexión PDO y constantes de seguridad.
// No expone secretos: todos salen de .env (ver .env.example).

declare(strict_types=1);

const OTP_TTL_SECONDS      = 300;  // 5 minutos (login 2FA)
const RESET_TTL_SECONDS    = 900;  // 15 minutos (recuperación y registro)
const OTP_LENGTH           = 6;
const MAX_LOGIN_ATTEMPTS   = 5;    // intentos fallidos antes de bloqueo
const LOGIN_LOCKOUT_SECS   = 900;  // 15 minutos
const MAX_OTP_ATTEMPTS     = 5;
const SESSION_LIFETIME     = 1800; // 30 minutos de inactividad

function load_env(string $path): void {
    if (!is_readable($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if (strlen($val) >= 2 && (
            ($val[0] === '"' && substr($val, -1) === '"') ||
            ($val[0] === "'" && substr($val, -1) === "'")
        )) {
            $val = substr($val, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

load_env(__DIR__ . '/.env');

function env(string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v !== false) return $v;
    return $_ENV[$key] ?? $default;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST', 'localhost') . ';dbname=' . env('DB_NAME', 'auth_system') . ';charset=utf8mb4',
        (string) env('DB_USER', 'admin'),
        (string) env('DB_PASS', ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    return $pdo;
}

function json_out(array $data): void {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

// --- Rate limiting genérico en tabla login_attempts(identifier, ip) ---
function rate_key(string $identifier): array {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return [$identifier, substr($ip, 0, 45)];
}

function is_locked(PDO $pdo, string $identifier, int $max, int $lockSecs): bool {
    [$ident, $ip] = rate_key($identifier);
    $stmt = $pdo->prepare('SELECT attempts, last_attempt FROM login_attempts WHERE identifier = ? AND ip = ?');
    $stmt->execute([$ident, $ip]);
    $row = $stmt->fetch();
    if (!$row) return false;
    if ((int) $row['attempts'] < $max) return false;
    return (time() - strtotime($row['last_attempt'])) < $lockSecs;
}

function register_failure(PDO $pdo, string $identifier): void {
    [$ident, $ip] = rate_key($identifier);
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('SELECT id, attempts FROM login_attempts WHERE identifier = ? AND ip = ?');
    $stmt->execute([$ident, $ip]);
    $row = $stmt->fetch();
    if ($row) {
        $pdo->prepare('UPDATE login_attempts SET attempts = attempts + 1, last_attempt = ? WHERE id = ?')
            ->execute([$now, $row['id']]);
    } else {
        $pdo->prepare('INSERT INTO login_attempts (identifier, ip, attempts, last_attempt) VALUES (?, ?, 1, ?)')
            ->execute([$ident, $ip, $now]);
    }
}

function clear_failures(PDO $pdo, string $identifier): void {
    [$ident, $ip] = rate_key($identifier);
    $pdo->prepare('DELETE FROM login_attempts WHERE identifier = ? AND ip = ?')->execute([$ident, $ip]);
}

function mask_email(string $email): string {
    $parts = explode('@', $email);
    $user = $parts[0] ?? '';
    $domain = $parts[1] ?? '';
    return substr($user, 0, 3) . '•••@' . $domain;
}

function is_bcrypt_hash(string $hash): bool {
    return str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$')
        || str_starts_with($hash, '$argon2i$') || str_starts_with($hash, '$argon2id$');
}

// Política mínima: 8+ caracteres con al menos una letra y un número.
function valid_password(string $p): bool {
    return strlen($p) >= 8
        && preg_match('/[A-Za-z]/', $p)
        && preg_match('/[0-9]/', $p);
}

function valid_email(string $e): bool {
    return filter_var($e, FILTER_VALIDATE_EMAIL) !== false && strlen($e) <= 150;
}

// Envía un correo con código de 6 dígitos usando la plantilla oscura del proyecto.
// Devuelve true si el SMTP aceptó el envío. Nunca incluye el código en logs.
function send_code_mail(string $toEmail, string $toName, string $subject, string $code, string $title, string $subtitle): bool {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) return false;
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
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;

        $cells = '';
        for ($i = 0; $i < 6; $i++) {
            $d = htmlspecialchars($code[$i] ?? '');
            $padLeft = ($i === 3) ? '12px' : '0';
            $padRight = ($i === 5) ? '0' : '4px';
            $cells .= "<td style=\"padding-left:{$padLeft};padding-right:{$padRight};\"><div style=\"width:42px;height:48px;line-height:48px;background:#0e1a30;border:1.8px solid #23324e;border-radius:10px;text-align:center;font-family:ui-monospace,Menlo,monospace;font-size:17px;font-weight:600;color:#e2e8f0;\">{$d}</div></td>";
        }
        $safeName = htmlspecialchars($toName);
        $safeTitle = htmlspecialchars($title);
        $safeSub = htmlspecialchars($subtitle);
        $mail->Body = "
<!DOCTYPE html><html lang=\"es\"><body style=\"margin:0;padding:0;background:#070b1a;\">
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background:#070b1a;padding:24px;\"><tr><td align=\"center\">
<table width=\"480\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"max-width:480px;width:100%;background:#141f35;border:1px solid rgba(255,255,255,0.07);border-radius:16px;overflow:hidden;\">
<tr><td style=\"padding:14px 20px;border-bottom:1px solid rgba(255,255,255,0.06);color:#fff;font-family:sans-serif;font-size:15px;font-weight:600;\">PKTechnologies</td></tr>
<tr><td align=\"center\" style=\"padding:28px 24px 20px;\">
<h1 style=\"color:#f1f5f9;font-family:sans-serif;font-size:22px;\">{$safeTitle}</h1>
<p style=\"color:#94a3b8;font-family:sans-serif;font-size:13px;\">Hola <strong style=\"color:#e2e8f0;\">{$safeName}</strong>, {$safeSub}</p>
<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:18px auto;\"><tr>{$cells}</tr></table>
<p style=\"color:#64748b;font-family:sans-serif;font-size:12px;\">Vencerá en <strong style=\"color:#cbd5e1;\">15 minutos</strong> • No lo compartas</p>
</td></tr></table></td></tr></table></body></html>";
        $mail->AltBody = $title . ' de PKTechnologies. Revisa el correo HTML para ver tu código (válido 15 minutos).';
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[MAIL] fallo de envío SMTP');
        return false;
    }
}
