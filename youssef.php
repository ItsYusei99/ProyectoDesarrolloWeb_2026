<?php
// Configurar la zona horaria a México para mostrar la fecha exacta
date_default_timezone_set('America/Mexico_City');

// Formatear la fecha actual
$fecha_hoy = date('d/m/Y H:i:s');
$materia = "Desarrollo de Aplicaciones Web / Programación Web"; // Ajusta según el nombre exacto de tu asignatura
$semestre = "7° Semestre";                                    // Ajusta según tu semestre actual
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Información del Alumno</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        h2 {
            color: #1a73e8;
            margin-top: 0;
        }
        .info {
            margin-bottom: 15px;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Detalles de la Entrega</h2>
        <div class="info">
            <span class="label">Fecha de hoy:</span> <?php echo $fecha_hoy; ?>
        </div>
        <div class="info">
            <span class="label">Materia:</span> <?php echo $materia; ?>
        </div>
        <div class="info">
            <span class="label">Semestre:</span> <?php echo $semestre; ?>
        </div>
    </div>
</body>
</html>
