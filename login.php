<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
$nombre   = trim($input["username"] ?? $_POST["username"] ?? "");
$password = trim($input["password"] ?? $_POST["password"] ?? "");
if ($nombre === "" || $password === "") {
    echo json_encode(["status" => "error", "message" => "Por favor, ingrese usuario y contraseña."]);
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
        echo json_encode(["status" => "error", "message" => "Error BD: " . $ex->getMessage()]);
        exit;
    }
}
try {
    $stmt = $pdo->prepare("SELECT id, nombre, email, salt, password_hash FROM usuarios WHERE nombre = ?");
    $stmt->execute([$nombre]);
    $cuentas = $stmt->fetchAll();
    $usuarioAutenticado = null;
    foreach ($cuentas as $cuenta) {
        $hashCalculado = hash("sha256", $password . $cuenta["salt"]);
        if (hash_equals($cuenta["password_hash"], $hashCalculado)) {
            $usuarioAutenticado = $cuenta;
            break;
        }
    }
    if (!$usuarioAutenticado) {
        echo json_encode(["status" => "error", "message" => "Usuario o contraseña incorrectos."]);
        exit;
    }
    $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    $expiracion = date("Y-m-d H:i:s", strtotime("+5 minutes"));
    $upd = $pdo->prepare("UPDATE usuarios SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
    $upd->execute([$otp, $expiracion, $usuarioAutenticado["id"]]);
    $destinatarioEmail = $usuarioAutenticado["email"];
    error_log("[OTP] user=$nombre email=$destinatarioEmail otp=$otp ip=" . ($_SERVER["REMOTE_ADDR"] ?? ""));
    $mailSent = false;
    $mailError = "";
    $autoloadPaths = [
        __DIR__ . "/vendor/autoload.php",
        "/var/www/html/vendor/autoload.php",
        "/var/www/misitio.local/public_html/vendor/autoload.php"
    ];
    $autoload = null;
    foreach ($autoloadPaths as $p) { if (file_exists($p)) { $autoload = $p; break; } }
    if ($autoload) {
        require_once $autoload;
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = "smtp.gmail.com";
            $mail->SMTPAuth   = true;
            $mail->Username   = "yyyushclash@gmail.com";
            $mail->Password   = "zlmv sgnn gufv zryv";
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = "UTF-8";
            $mail->Timeout    = 8;
            $mail->setFrom("yyyushclash@gmail.com", "PKTechnologies Seguridad");
            $mail->addAddress($destinatarioEmail, $usuarioAutenticado["nombre"]);
            $mail->isHTML(true);
            $mail->Subject = "Código de Verificación 2FA - PKTechnologies";
            // Genera celdas OTP estilo 6 inputs dark (match con .otp-cell)
            $otpCellsHtml = "";
            for ($i = 0; $i < 6; $i++) {
                $d = htmlspecialchars($otp[$i] ?? "");
                $padLeft = ($i === 3) ? "12px" : "0";
                $padRight = ($i === 5) ? "0" : "4px";
                $otpCellsHtml .= "<td style=\"padding-left:{$padLeft};padding-right:{$padRight};\"><div style=\"width:42px;height:48px;line-height:48px;background:#0e1a30;border:1.8px solid #23324e;border-radius:10px;text-align:center;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:17px;font-weight:600;color:#e2e8f0;box-shadow:inset 0 1px 2px rgba(0,0,0,0.25);\">{$d}</div></td>";
            }
            $safeName = htmlspecialchars($usuarioAutenticado["nombre"]);
            $mail->Body    = "
<!DOCTYPE html>
<html lang=\"es\">
<body style=\"margin:0;padding:0;background:#070b1a;\">
  <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background:#070b1a;padding:24px;\">
    <tr><td align=\"center\">
      <table width=\"480\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"max-width:480px;width:100%;background:linear-gradient(180deg,#1a2641 0%,#141f35 100%);border:1px solid rgba(255,255,255,0.07);border-radius:16px;overflow:hidden;box-shadow:0 20px 60px -15px rgba(0,0,0,0.6);\">
        <tr><td style=\"background:rgba(15,23,42,0.9);padding:14px 20px;border-bottom:1px solid rgba(255,255,255,0.06);\">
          <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>
            <td style=\"width:32px;height:32px;background:linear-gradient(135deg,#2563eb,#1e40af);border-radius:6px;text-align:center;color:#ffffff;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:13px;font-weight:700;line-height:32px;\">PK</td>
            <td style=\"padding-left:10px;color:#ffffff;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:15px;font-weight:600;letter-spacing:-0.2px;\">PKTechnologies</td>
          </tr></table>
        </td></tr>
        <tr><td align=\"center\" style=\"padding:28px 24px 20px;\">
          <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td style=\"width:58px;height:58px;background:linear-gradient(135deg,#2563eb,#1e40af);border-radius:50%;text-align:center;line-height:58px;color:#ffffff;font-size:22px;font-weight:700;box-shadow:0 8px 24px rgba(37,99,235,0.35);\">&#10003;</td></tr></table>
          <h1 style=\"margin:16px 0 6px;color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:22px;font-weight:650;letter-spacing:-0.3px;\">Código de Verificación</h1>
          <p style=\"margin:0 0 4px;color:#94a3b8;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:13px;\">Hola <strong style=\"color:#e2e8f0;\">{$safeName}</strong>, tu código temporal es:</p>
          <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:18px auto;\"><tr>{$otpCellsHtml}</tr></table>
          <p style=\"margin:0;color:#64748b;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:12px;text-align:center;\">Este código vencerá en <strong style=\"color:#cbd5e1;\">5 minutos</strong> &bull; No lo compartas con nadie</p>
        </td></tr>
        <tr><td style=\"padding:14px 20px;background:rgba(255,255,255,0.02);border-top:1px solid rgba(255,255,255,0.06);text-align:center;\">
          <p style=\"margin:0;color:#64748b;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:11px;line-height:14px;\">Si no solicitaste este código, puedes ignorar este correo.<br>PKTechnologies &bull; Sistema de Autenticación 2FA &bull; <span style=\"color:#475569;\">{$destinatarioEmail}</span></p>
        </td></tr>
      </table>
      <p style=\"margin:12px 0 0;color:#334155;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:10px;text-align:center;\">Este es un correo automático, por favor no respondas.</p>
    </td></tr>
  </table>
</body>
</html>
            ";
            $mail->AltBody = "Tu código de verificación de PKTechnologies es: $otp (Válido por 5 minutos).";
            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            $mailError = $mail->ErrorInfo;
            error_log("[MAIL FAIL] to $destinatarioEmail Error: $mailError OTP:$otp");
        }
    } else {
        $mailError = "autoload no encontrado";
    }
    $parts = explode("@", $destinatarioEmail);
    $emailMasked = substr($parts[0], 0, 3) . "•••@" . ($parts[1] ?? "gmail.com");
    echo json_encode([
        "status" => "otp_required",
        "message" => $mailSent ? "Hemos enviado el código a su correo." : "Código generado (Aviso SMTP: " . $mailError . ") Revise spam o use código: $otp",
        "user_id" => $usuarioAutenticado["id"],
        "email_masked" => $emailMasked,
        "debug_otp" => $otp,
        "mail_sent" => $mailSent
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Error SQL: " . $e->getMessage()]);
}
