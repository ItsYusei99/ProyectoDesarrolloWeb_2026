<?php
// users_api.php — CRUD de usuarios protegido por sesión (módulo Usuarios).
// POST JSON: {action: list|create|update|delete, csrf_token, ...}
// Nunca devuelve hashes ni códigos; toda acción mutante exige CSRF.

declare(strict_types=1);

require_once __DIR__ . '/session.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    json_out(['status' => 'error', 'message' => 'Sesión no válida. Inicie sesión.']);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_POST['action'] ?? 'list';

// CSRF en toda acción que modifica datos
if (in_array($action, ['create', 'update', 'delete'], true)) {
    $sent = (string) ($input['csrf_token'] ?? $_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        json_out(['status' => 'error', 'message' => 'Token de seguridad no válido. Recargue la página.']);
    }
}

try {
    $pdo = db();
} catch (Exception $e) {
    error_log('[DB] users_api connection failed');
    json_out(['status' => 'error', 'message' => 'Error del servidor. Intente más tarde.']);
}

// READ — Consultar usuarios (sin secretos)
if ($action === 'list') {
    $stmt = $pdo->query('SELECT id, nombre, email, created_at FROM usuarios ORDER BY id ASC');
    json_out(['status' => 'success', 'users' => $stmt->fetchAll()]);
}

// CREATE — Crear usuario (bcrypt, correo único, política de contraseña)
if ($action === 'create') {
    $nombre = trim((string) ($input['nombre'] ?? ''));
    $email  = trim((string) ($input['email'] ?? ''));
    $pass   = (string) ($input['password'] ?? '');
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
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_out(['status' => 'error', 'message' => 'Ese correo ya está registrado.']);
    }
    $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash, salt) VALUES (?, ?, ?, NULL)')
        ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT)]);
    json_out(['status' => 'success', 'message' => 'Usuario creado.', 'id' => (int) $pdo->lastInsertId()]);
}

// UPDATE — Modificar usuario (nombre/correo; contraseña solo si se envía)
if ($action === 'update') {
    $id     = (int) ($input['id'] ?? 0);
    $nombre = trim((string) ($input['nombre'] ?? ''));
    $email  = trim((string) ($input['email'] ?? ''));
    $pass   = (string) ($input['password'] ?? '');
    if (!is_string($pass)) $pass = '';

    if ($id <= 0) {
        json_out(['status' => 'error', 'message' => 'ID no válido.']);
    }
    if ($nombre === '' || mb_strlen($nombre) > 100) {
        json_out(['status' => 'error', 'message' => 'Ingrese un nombre de usuario válido.']);
    }
    if (!valid_email($email)) {
        json_out(['status' => 'error', 'message' => 'Ingrese un correo válido.']);
    }
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_out(['status' => 'error', 'message' => 'El usuario no existe.']);
    }
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1');
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        json_out(['status' => 'error', 'message' => 'Ese correo ya lo usa otro usuario.']);
    }
    if ($pass !== '') {
        if (!valid_password($pass)) {
            json_out(['status' => 'error', 'message' => 'La contraseña debe tener mínimo 8 caracteres, letras y números.']);
        }
        $pdo->prepare('UPDATE usuarios SET nombre = ?, email = ?, password_hash = ?, salt = NULL WHERE id = ?')
            ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), $id]);
    } else {
        $pdo->prepare('UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?')
            ->execute([$nombre, $email, $id]);
    }
    // Si editó su propio nombre/correo, refrescar la sesión
    if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
        $_SESSION['username'] = $nombre;
        $_SESSION['email'] = $email;
    }
    json_out(['status' => 'success', 'message' => 'Usuario actualizado.']);
}

// DELETE — Eliminar usuario (nunca a sí mismo)
if ($action === 'delete') {
    $id = (int) ($input['id'] ?? 0);
    if ($id <= 0) {
        json_out(['status' => 'error', 'message' => 'ID no válido.']);
    }
    if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
        json_out(['status' => 'error', 'message' => 'No puede eliminar su propio usuario.']);
    }
    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_out(['status' => 'error', 'message' => 'El usuario no existe.']);
    }
    json_out(['status' => 'success', 'message' => 'Usuario eliminado.']);
}

json_out(['status' => 'error', 'message' => 'Acción no válida.']);
