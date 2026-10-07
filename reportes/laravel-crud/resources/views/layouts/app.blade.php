<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PKTechnologies - @yield('titulo', 'Usuarios')</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
        header { background: #1a73e8; color: #fff; padding: 12px 20px; }
        main { max-width: 800px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #eef3fd; }
        .ok { background: #d4edda; padding: 10px; border-radius: 4px; }
        .error { background: #f8d7da; padding: 10px; border-radius: 4px; }
        .btn { display: inline-block; padding: 8px 14px; background: #1a73e8; color: #fff; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-danger { background: #dc3545; }
        form.inline { display: inline; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
    </style>
</head>
<body>
    <header><strong>PKTechnologies</strong> — Módulo de Usuarios (PHP + Laravel)</header>
    <main>
        @if (session('ok'))
            <p class="ok">{{ session('ok') }}</p>
        @endif
        @if ($errors->any())
            <div class="error">
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('contenido')
    </main>
</body>
</html>
