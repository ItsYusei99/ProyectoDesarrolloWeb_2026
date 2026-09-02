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

**Credenciales de prueba:**
- `admin / Password123` → jodidroks@gmail.com
- `admin / Admin2026!` → soporte@tecnosoluciones.com

**VM:**
- `multipass start ubuntuserver`
- `multipass info ubuntuserver` (ver mounts)
- `multipass exec ubuntuserver -- sudo systemctl reload apache2`
