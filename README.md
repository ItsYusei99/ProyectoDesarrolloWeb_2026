# PKTechnologies - Proyecto de Diseño de Aplicaciones Web

Este es mi proyecto de la materia de Diseño de aplicaciones web (7mo semestre, Tecmilenio campus Toluca, con el profe Bruno Reyes Valle).

Es un panel de administración con inicio de sesión en dos pasos: primero validas tu usuario y contraseña contra MySQL, y después te llega un código de 6 dígitos a tu correo que tienes que meter para entrar. El código dura 5 minutos y solo se puede usar una vez.

## Cómo lo tengo montado

Lo corro en una máquina virtual de Ubuntu que se llama `ubuntuserver` (la hice con multipass). Esta carpeta está montada directo en `/var/www/html` de la VM, entonces todo lo que edito aquí se refleja solo allá, no tengo que estar copiando archivos.

Para prenderla y ver el sitio:

```bash
multipass start ubuntuserver
multipass info ubuntuserver   # ahí sale la IP
```

Y en el navegador abres `http://192.168.252.3`. Si cambias algo, con recargar la página basta.

## Si lo quieres correr tú

1. Instala las dependencias con `composer install` (la carpeta `vendor/` no la subí al repo).
2. Copia `.env.example` a `.env` y pon tus datos de la base y del correo SMTP ahí.
3. La base se llama `auth_system`, la tabla es `usuarios`. El proyecto ya espera esas columnas (incluyendo `otp_code` y `otp_expires_at`).

## Usuarios de prueba

Los dejé para que el profe pueda calificar sin registrar nada:

- `admin / Password123`
- `admin / Admin2026!`

(Sí, los dos se llaman igual a propósito, en el Reporte 4 pedían soportar homónimos.)

## Qué hay en el repo

- `index.html`, `app.js`, `styles.css` → el login y la pantalla del código.
- `login.php` → revisa usuario/contraseña y manda el código por correo.
- `verificacion.php` → revisa el código y crea la sesión (`verify_otp.php` es solo un alias por compatibilidad).
- `inicio.php` → la página principal cuando ya entraste (`dashboard.php` solo redirige ahí).
- `logout.php`, `session.php`, `config.php` → sesiones y configuración.
- `reportes/` → mis reportes de la materia en Word.
- `figma/` → cosas del diseño.

Youssef Nabil Khalil Garcia — 3086048
