<?php
// dashboard.php — Alias de compatibilidad: el panel canónico es inicio.php.
// Se conserva la URL antigua redirigiendo con sesión validada.
declare(strict_types=1);
require_once __DIR__ . '/session.php';
require_auth();
header('Location: inicio.php');
exit;
