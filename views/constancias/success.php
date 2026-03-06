<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Exitoso</title>
    <link rel="stylesheet" href="assets/lte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .success-box { margin-top: 50px; }
    </style>
</head>
<body class="hold-transition login-page">
<div class="success-box" style="width: 600px; max-width: 90%;">
    <div class="card card-outline card-success">
        <div class="card-header text-center">
            <a href="#" class="h1"><b>Sistema de</b> Constancias</a>
        </div>
        <div class="card-body">
            <div class="text-center mb-4">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                <h3 class="mt-2">¡Registro Exitoso!</h3>
            </div>
            
            <p class="login-box-msg">Estimado/a <strong><?= htmlspecialchars($datos['nombres'] . ' ' . $datos['apellidos']) ?></strong>, su registro ha sido completado.</p>

            <div class="callout callout-success">
                <h5>Datos Registrados:</h5>
                <p class="mb-0"><strong>Documento:</strong> <?= htmlspecialchars($datos['documento_identidad']) ?></p>
                <?php if (!empty($datos['email'])): ?>
                <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($datos['email']) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($evento): ?>
                <div class="text-center mt-4">
                    <p>Evento: <strong><?= htmlspecialchars($evento['nombre']) ?></strong></p>
                    <form method="post" action="index.php?page=constancias" target="_blank">
                        <input type="hidden" name="id" value="<?= $datos['id'] ?>">
                        <input type="hidden" name="action" value="download">
                        <button type="submit" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-download"></i> Descargar Constancia
                        </button>
                    </form>
                    <small class="text-muted mt-2 d-block">La constancia se generará automáticamente.</small>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    No hay eventos activos para generar constancia en este momento.
                </div>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <a href="index.php?page=constancias&view=public">Volver al inicio</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
