# PKTechnologies - ProyectoDiseñoWeb (copia sincronizada con Ubuntu)

Esta carpeta es una copia directa de `/var/www/html` (y `/var/www/misitio.local/public_html` via symlink) del servidor Ubuntu `ubuntuserver` (192.168.252.3).

**Montaje activo:**
- Host: `/Users/yuseigarcia/WEB/ProyectoDiseñoWeb_Server` 
- VM: `ubuntuserver:/var/www/html` (y symlink `/var/www/misitio.local/public_html -> /var/www/html`)
- Tipo: `classic` sshfs via `multipass mount`

**Uso en VS Code:**
```
code /Users/yuseigarcia/WEB/ProyectoDiseñoWeb_Server
```
Cualquier edición aquí se refleja instantáneamente en Ubuntu (sin necesidad de `multipass transfer`). Para ver cambios en `http://192.168.252.3` o `http://misitio.local` basta guardar y recargar (ya con `styles.css?v=20250901e` y headers no-cache).

**Sincronización dual futura:**
El asistente editará simultáneamente:
- Este directorio (montado) → auto-sync a Ubuntu
- Copia de respaldo `/Users/yuseigarcia/WEB/ProyectoDiseñoWeb/` (via rsync)

Para forzar sinc manual:
```bash
/Users/yuseigarcia/WEB/sync_dual.sh
```

**Credenciales de prueba (solo entorno académico):**
- `admin / Password123` → jodidroks@gmail.com
- `admin / Admin2026!` → soporte@tecnosoluciones.com

**Seguridad (mejoras aplicadas):**
- Secretos (BD y SMTP) en `.env` (no versionado). Copiar `.env.example` a `.env` y rellenar.
- Hashes bcrypt con migración automática desde SHA-256+salt legado.
- 2FA: OTP de 6 dígitos, 5 min, un solo uso, comparación `hash_equals`, límite 5 intentos + bloqueo 15 min.
- Sesiones: `session_regenerate_id`, timeout 30 min inactividad, cookies `HttpOnly` + `SameSite=Lax`, logout POST con CSRF (GET por compatibilidad).
- Endpoints: `login.php` / `verificacion.php` (`verify_otp.php` es alias). Panel: `inicio.php` (`dashboard.php` redirige ahí).
- Headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` en `.htaccess`.
- `vendor/` no se versiona: desplegar con `composer install`.
- Reportes universitarios en `reportes/`.

**VM:**
- `multipass start ubuntuserver`
- `multipass info ubuntuserver` (ver mounts)
- `multipass exec ubuntuserver -- sudo systemctl reload apache2`
