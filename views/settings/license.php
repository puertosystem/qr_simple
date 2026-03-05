<!DOCTYPE html>
<html lang="es">
<head>
  <?php include __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

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
  
  <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>

<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
