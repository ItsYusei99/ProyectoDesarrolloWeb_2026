<?php
require_once __DIR__ . '/session.php';
require_auth();

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, nombre, email, created_at FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $me = $stmt->fetch();
} catch (Exception $e) {
    $me = null;
}
if (!$me) {
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PKTechnologies - Mi Perfil</title>
    <link rel="stylesheet" href="styles.css?v=20250902_nodes">
</head>
<body>
    <?php $activePage = 'perfil'; require __DIR__ . '/header.php'; ?>

    <main class="main-container">
        <section class="card welcome-card">
            <div class="icon-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <h1 class="card-title">Mi Perfil</h1>
            <p class="card-subtitle">Datos de tu cuenta</p>

            <div class="info-panel">
                <div class="info-row">
                    <span class="info-label">ID de Usuario</span>
                    <span class="info-value">#<?php echo htmlspecialchars($me['id']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Usuario</span>
                    <span class="info-value"><?php echo htmlspecialchars($me['nombre']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Correo</span>
                    <span class="info-value"><?php echo htmlspecialchars($me['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Miembro desde</span>
                    <span class="info-value"><?php echo htmlspecialchars($me['created_at']); ?></span>
                </div>
            </div>

            <div class="welcome-actions">
                <a href="inicio.php" class="btn-signin" style="text-decoration:none;"><span>Volver al menú</span></a>
            </div>
        </section>
    </main>
</body>
</html>
