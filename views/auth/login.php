<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión - Módulo Certificados QR</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="assets/lte/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="assets/lte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="index.php"><b>Certificados</b> QR</a>
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Acceso al panel interno de certificados</p>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form action="index.php?page=login" method="post">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">
                Mantener sesión iniciada
              </label>
            </div>
          </div>
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
          </div>
        </div>
      </form>

      <div class="mt-4 mb-2">
        <p class="text-muted text-xs mb-0">
          Cada certificado generado incluirá un código QR que apunta a la URL pública de validación.
        </p>
      </div>
    </div>
  </div>
</div>

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>
</body>
</html>
