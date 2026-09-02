<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

$userId = intval($input["user_id"] ?? $_POST["user_id"] ?? 0);
$otp    = trim($input["otp"] ?? $_POST["otp"] ?? "");

if (!$userId || empty($otp)) {
    echo json_encode(["status" => "error", "message" => "Ingrese el código de 6 dígitos."]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=auth_system;charset=utf8mb4", "admin", "AdminPass2026!", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=auth_system;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $ex) {
        echo json_encode(["status" => "error", "message" => "Error de conexión BD."]);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT id, nombre, email, otp_code, otp_expires_at FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 1. Validar existencia de solicitud activa
if (!$user || empty($user["otp_code"])) {
    echo json_encode(["status" => "error", "message" => "Código no válido o utilizado previamente."]);
    exit;
}

// 2. Comprobar expiración
if (strtotime($user["otp_expires_at"]) < time()) {
    $pdo->prepare("UPDATE usuarios SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?")->execute([$userId]);
    echo json_encode(["status" => "error", "message" => "El código ha expirado."]);
    exit;
}

// 3. Comprobar coincidencia
if ($user["otp_code"] !== $otp) {
    echo json_encode(["status" => "error", "message" => "Código incorrecto."]);
    exit;
}

// 4. Invalidar código (consumo único)
$pdo->prepare("UPDATE usuarios SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?")->execute([$userId]);

// 5. Inicializar sesión PHP
$_SESSION["user_id"] = $user["id"];
$_SESSION["username"] = $user["nombre"];
$_SESSION["email"] = $user["email"];
$_SESSION["authenticated"] = true;

echo json_encode([
    "status" => "success",
    "message" => "Acceso permitido.",
    "redirect" => "inicio.php"
]);
