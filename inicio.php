<?php
session_start();
if (!isset($_SESSION["authenticated"]) || $_SESSION["authenticated"] !== true) {
    header("Location: index.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PKTechnologies - Inicio</title>
    <link rel="stylesheet" href="styles.css?v=20250902_nodes">
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <div class="brand-logo">PK</div>
            <span class="brand-name">PKTechnologies</span>
        </div>
        <div class="topbar-actions">
            <span class="user-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <?php echo htmlspecialchars($_SESSION["username"]); ?>
            </span>
            <a href="logout.php" class="btn-logout">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Cerrar sesión
            </a>
        </div>
    </header>

    <main class="main-container">
        <canvas id="bg-particles" aria-hidden="true"></canvas>
        <section class="card welcome-card">
            <div class="icon-circle icon-success">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h1 class="card-title">Bienvenido al Sistema</h1>
            <p class="card-subtitle">Autenticación de dos pasos completada con éxito</p>

            <div class="info-panel">
                <div class="info-row">
                    <span class="info-label">ID de Usuario</span>
                    <span class="info-value">#<?php echo htmlspecialchars($_SESSION["user_id"]); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Usuario</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Correo</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SESSION["email"]); ?></span>
                </div>
            </div>

            <div class="welcome-actions">
                <a href="logout.php" class="btn-signin" style="text-decoration:none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </section>
    </main>
</body>
</html>
