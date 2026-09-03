<?php
// config.php — Carga .env, conexión PDO y constantes de seguridad.
// No expone secretos: todos salen de .env (ver .env.example).

declare(strict_types=1);

const OTP_TTL_SECONDS      = 300;  // 5 minutos
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
