<?php
require_once __DIR__ . '/../../config/database.php';

$participantsCount = 0;
$coursesCount = 0;
$certificatesCount = 0;
$enrollmentsCount = 0;

try {
    $pdo = Database::getConnection();

    $participantsCount = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $coursesCount = (int) $pdo->query('SELECT COUNT(*) FROM cursos')->fetchColumn();
    $certificatesCount = (int) $pdo->query('SELECT COUNT(*) FROM certificados')->fetchColumn();
    $enrollmentsCount = (int) $pdo->query('SELECT COUNT(*) FROM curso_estudiantes')->fetchColumn();
} catch (Throwable $e) {
    error_log('Error obteniendo estadísticas para el dashboard QR: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Módulo Certificados QR - Rebagliati Diplomados</title>
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
      <li class="nav-item d-none d-sm-inline-block">
        <a href="index.php" class="nav-link">Inicio</a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="index.php?page=logout" class="nav-link">Cerrar sesión</a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
    </ul>
  </nav>

  <?php include __DIR__ . '/../partials/sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Módulo de Certificados QR</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active">Inicio</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="row">
              <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                  <div class="inner">
                    <h3><?php echo number_format($participantsCount, 0, ',', '.'); ?></h3>
                    <p>Participantes registrados</p>
                  </div>
                  <div class="icon">
                    <i class="fas fa-user-friends"></i>
                  </div>
                  <a href="index.php?page=participants" class="small-box-footer">
                    Ver participantes <i class="fas fa-arrow-circle-right"></i>
                  </a>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                  <div class="inner">
                    <h3><?php echo number_format($coursesCount, 0, ',', '.'); ?></h3>
                    <p>Cursos creados</p>
                  </div>
                  <div class="icon">
                    <i class="fas fa-book"></i>
                  </div>
                  <a href="index.php?page=courses" class="small-box-footer">
                    Ver cursos <i class="fas fa-arrow-circle-right"></i>
                  </a>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                  <div class="inner">
                    <h3><?php echo number_format($certificatesCount, 0, ',', '.'); ?></h3>
                    <p>Certificados QR emitidos</p>
                  </div>
                  <div class="icon">
                    <i class="fas fa-qrcode"></i>
                  </div>
                  <a href="index.php?page=certificates" class="small-box-footer">
                    Ir a certificados <i class="fas fa-arrow-circle-right"></i>
                  </a>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                  <div class="inner">
                    <h3><?php echo number_format($enrollmentsCount, 0, ',', '.'); ?></h3>
                    <p>Matrículas registradas</p>
                  </div>
                  <div class="icon">
                    <i class="fas fa-user-check"></i>
                  </div>
                  <a href="index.php?page=participants" class="small-box-footer">
                    Ver matrículas <i class="fas fa-arrow-circle-right"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
      Vistas preparadas para futura integración con PostgreSQL y QR.
    </div>
    <span>Módulo de Certificados QR dentro de aulavirtual, etapa de maquetación.</span>
  </footer>

  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>
</body>
</html>
