<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Constancia</title>
    <link rel="stylesheet" href="assets/lte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .register-box {
            margin-top: 50px;
        }
    </style>
</head>
<body class="hold-transition register-page">
<div class="register-box">
    <div class="register-logo">
        <a href="#"><b>Sistema de</b> Constancias</a>
    </div>

    <div class="card">
        <div class="card-body register-card-body">
            <?php if (isset($selectedEvent) && $selectedEvent): ?>
                <h5 class="text-center mb-3 text-primary font-weight-bold"><?= htmlspecialchars($selectedEvent['nombre']) ?></h5>
            <?php endif; ?>
            
            <p class="login-box-msg">Regístrate para obtener tu constancia</p>

            <?php if (isset($_SESSION['public_success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['public_success'] ?>
                </div>
                <?php unset($_SESSION['public_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['public_error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['public_error'] ?>
                </div>
                <?php unset($_SESSION['public_error']); ?>
            <?php endif; ?>

            <form action="index.php?page=constancias&action=register_lead" method="post">
                <?php if (isset($selectedEvent) && $selectedEvent): ?>
                    <input type="hidden" name="evento_id" value="<?= $selectedEvent['id'] ?>">
                <?php endif; ?>
                <div class="input-group mb-3">
                    <input type="text" name="nombres" class="form-control" placeholder="Nombres completos" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="text" name="apellidos" class="form-control" placeholder="Apellidos completos" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="text" name="documento_identidad" class="form-control" placeholder="Documento de Identidad (DNI/Cédula)" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-id-card"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Correo Electrónico (Opcional)">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="text" name="celular" class="form-control" placeholder="Celular (Opcional)">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-phone"></span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">Registrarme</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>
</body>
</html>
