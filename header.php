<?php
// header.php — Barra superior compartida: Menú, Usuarios, Perfil + usuario + salir.
// Requiere: $activePage ('menu' | 'usuarios' | 'perfil') y sesión iniciada.
$navItems = [
    'menu'     => ['inicio.php', 'Menú'],
    'usuarios' => ['usuarios.php', 'Usuarios'],
    'perfil'   => ['perfil.php', 'Perfil'],
];
$active = $activePage ?? '';
?>
<header class="topbar">
    <div class="brand">
        <div class="brand-logo">PK</div>
        <span class="brand-name">PKTechnologies</span>
    </div>
    <nav class="topnav" aria-label="Navegación principal">
        <?php foreach ($navItems as $key => [$href, $label]): ?>
            <a href="<?php echo $href; ?>" class="<?php echo $key === $active ? 'active' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="topbar-actions">
        <span class="user-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
        </span>
        <a href="logout.php" class="btn-logout">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Cerrar sesión
        </a>
    </div>
</header>
<script>
// Si el navegador restaura esta página desde su caché al dar "atrás",
// recargarla para que el servidor valide la sesión (si se cerró, manda al login).
window.addEventListener("pageshow", function (e) { if (e.persisted) window.location.reload(); });
</script>
