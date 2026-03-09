<!DOCTYPE html>
<html lang="es">
<head>
  <?php include __DIR__ . '/../partials/head.php'; ?>
  <link rel="stylesheet" href="assets/lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
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

      <div class="mt-4 text-center text-muted" style="font-size: 0.8rem; border-top: 1px solid #eee; padding-top: 15px;">
          <p class="mb-1">
              Copyright &copy; <?php echo date('Y'); ?> 
              <a href="https://www.linkedin.com/company/puertosystem/" target="_blank">Puerto System, S.A.</a>
          </p>
          <p class="mb-0">
              Desarrollado por: <a href="https://www.linkedin.com/in/norberto-ramirez/" target="_blank">Norberto Ramirez</a> & <a href="https://postsdigital.com/" target="_blank">POSTS Digital</a> - v<?php echo APP_VERSION; ?>
          </p>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
