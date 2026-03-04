<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Licencia y Versión - Módulo Certificados QR</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="assets/lte/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/lte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
  </nav>

  <?php include __DIR__ . '/../partials/sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Información del Sistema</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
              <li class="breadcrumb-item active">Ajustes</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-6">
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Detalles de Licencia</h3>
              </div>
              <div class="card-body">
                <div class="text-center mb-4">
                  <i class="fas fa-certificate fa-3x text-success"></i>
                  <h3 class="mt-2"><?php echo $licenseData['productname'] ?? 'Producto Desconocido'; ?></h3>
                  <p class="text-muted">Versión <?php echo $licenseData['version'] ?? '1.0.0'; ?></p>
                </div>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Estado</b> 
                    <a class="float-right">
                        <?php if (($licenseData['status'] ?? '') === 'Active'): ?>
                            <span class="badge badge-success">Activa</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactiva</span>
                        <?php endif; ?>
                    </a>
                  </li>
                  <li class="list-group-item">
                    <b>Registrado a</b> <a class="float-right"><?php echo $licenseData['registeredname'] ?? 'N/A'; ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Dominio Válido</b> <a class="float-right"><?php echo $licenseData['validdomain'] ?? 'N/A'; ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Próximo Vencimiento</b> <a class="float-right"><?php echo $licenseData['nextduedate'] ?? 'N/A'; ?></a>
                  </li>
                </ul>

                <a href="#" class="btn btn-primary btn-block"><b>Verificar Licencia Ahora</b></a>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">Acerca del Sistema</h3>
              </div>
              <div class="card-body">
                <strong><i class="fas fa-book mr-1"></i> Descripción</strong>
                <p class="text-muted">
                  Sistema integral para la gestión de cursos, participantes y emisión de certificados digitales con validación QR.
                </p>
                <hr>
                <strong><i class="fas fa-code mr-1"></i> Desarrollado por</strong>
                <p class="text-muted">Equipo de Desarrollo</p>
                <hr>
                <strong><i class="far fa-file-alt mr-1"></i> Frameworks</strong>
                <p class="text-muted">PHP Nativo, AdminLTE 3, TCPDF, PHPQRCode</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
  
  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> <?php echo $licenseData['version'] ?? '1.0.0'; ?>
    </div>
    <strong>&copy; <?php echo date('Y'); ?> Sistema de Certificados.</strong>
  </footer>
</div>

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>
</body>
</html>
