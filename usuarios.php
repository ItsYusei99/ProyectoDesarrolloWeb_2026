<?php
require_once __DIR__ . '/session.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PKTechnologies - Usuarios</title>
    <link rel="stylesheet" href="styles.css?v=20250902_nodes">
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <div class="brand-logo">PK</div>
            <span class="brand-name">PKTechnologies</span>
        </div>
        <div class="topbar-actions">
            <a href="inicio.php" class="btn-logout">Menú</a>
            <a href="logout.php" class="btn-logout">Cerrar sesión</a>
        </div>
    </header>

    <main class="main-container">
        <section class="card wide-card">
            <h1 class="card-title">Administración de Usuarios</h1>
            <p class="card-subtitle">Crear, consultar, modificar y eliminar usuarios</p>

            <div id="user-alert" class="alert hidden"></div>

            <div class="table-toolbar">
                <button type="button" id="btn-new" class="btn-signin btn-small">
                    <span>+ Nuevo usuario</span>
                </button>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="users-body">
                        <tr><td colspan="5">Cargando…</td></tr>
                    </tbody>
                </table>
            </div>

            <form id="user-form" class="hidden" novalidate>
                <h2 class="form-subtitle" id="user-form-title">Nuevo usuario</h2>
                <input type="hidden" id="user-id">
                <div class="form-group">
                    <label for="user-nombre">Usuario</label>
                    <div class="input-wrapper">
                        <input type="text" id="user-nombre" placeholder="Nombre de usuario" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label for="user-email">Correo</label>
                    <div class="input-wrapper">
                        <input type="email" id="user-email" placeholder="correo@ejemplo.com" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label for="user-password">Contraseña <small id="pass-hint">(mínimo 8 caracteres, letras y números)</small></label>
                    <div class="input-wrapper">
                        <input type="password" id="user-password" placeholder="Vacío = conservar la actual" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-signin btn-small"><span>Guardar</span></button>
                    <button type="button" id="btn-cancel" class="link-button">Cancelar</button>
                </div>
            </form>
        </section>
    </main>

    <script>
        window.CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
        window.MY_USER_ID = <?php echo json_encode((int) ($_SESSION['user_id'] ?? 0)); ?>;
    </script>
    <script src="usuarios.js?v=1"></script>
</body>
</html>
