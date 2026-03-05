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

  <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>

<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
