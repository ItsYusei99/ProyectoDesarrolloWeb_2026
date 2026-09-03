<?php
require_once __DIR__ . '/session.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PKTechnologies - Menú Principal</title>
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
            <h1 class="card-title">Menú Principal</h1>
            <p class="card-subtitle">Autenticación de dos pasos completada con éxito</p>

            <nav class="menu-grid">
                <a href="usuarios.php" class="menu-card">
                    <span class="menu-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </span>
                    <span class="menu-title">Usuarios</span>
                    <span class="menu-desc">Crear, consultar, modificar y eliminar</span>
                </a>
                <a href="perfil.php" class="menu-card">
                    <span class="menu-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <span class="menu-title">Perfil</span>
                    <span class="menu-desc">Ver los datos de tu cuenta</span>
                </a>
                <a href="logout.php" class="menu-card">
                    <span class="menu-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </span>
                    <span class="menu-title">Cerrar sesión</span>
                    <span class="menu-desc">Salir del sistema</span>
                </a>
            </nav>
        </section>
    </main>
</body>
</html>
