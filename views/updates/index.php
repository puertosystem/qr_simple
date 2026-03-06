<!DOCTYPE html>
<html lang="es">
<head>
  <?php include __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <?php include __DIR__ . '/../partials/sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Actualizaciones del Sistema</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Actualizaciones</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <div class="row">
                <!-- Panel de Estado -->
                <div class="col-md-8">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-sync-alt mr-1"></i>
                                Estado de la Actualización
                            </h3>
                        </div>
                        <div class="card-body text-center" id="update-status-area">
                            <h4 class="text-primary mb-3">Tu versión actual: <span class="badge badge-info"><?php echo $currentVersion; ?></span></h4>
                            
                            <div id="check-result" class="alert alert-light border d-none">
                                <!-- Mensajes de actualización -->
                            </div>

                            <?php if (isset($dbUpdateAvailable) && $dbUpdateAvailable): ?>
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <h5><i class="icon fas fa-exclamation-triangle"></i> ¡Atención!</h5>
                                Se ha detectado una actualización de base de datos pendiente.
                                <hr>
                                <button type="button" class="btn btn-warning btn-block font-weight-bold" onclick="applyDbUpdate(this)">
                                    <i class="fas fa-database mr-2"></i> Actualizar Base de Datos Ahora
                                </button>
                                <div id="db-update-result" class="mt-2"></div>
                            </div>
                            <?php endif; ?>

                            <button type="button" class="btn btn-primary btn-lg mt-3" id="btn-check-update">
                                <i class="fas fa-cloud-download-alt mr-2"></i> Comprobar Actualización
                            </button>
                            
                            <div class="progress mt-4 d-none" id="update-progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal de Confirmación -->
                <div class="modal fade" id="confirmUpdateModal" tabindex="-1" role="dialog" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="confirmUpdateModalLabel">Confirmar Actualización</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <p>¿Estás seguro de que deseas instalar la versión <strong id="modal-version-text"></strong>?</p>
                        <p class="text-danger"><small><i class="fas fa-exclamation-triangle"></i> Se recomienda encarecidamente realizar una copia de seguridad de la base de datos antes de continuar.</small></p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btn-start-update">Sí, Instalar</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Panel de Requisitos -->
                <div class="col-md-4">
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Requisitos del Sistema
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Requisito</th>
                                        <th style="width: 40px">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requirements as $req): ?>
                                    <tr>
                                        <td>
                                            <?php echo $req['name']; ?>
                                            <small class="d-block text-muted"><?php echo $req['current']; ?></small>
                                        </td>
                                        <td>
                                            <?php if ($req['status']): ?>
                                                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"><i class="fas fa-times"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-muted">
                            <small>Estos requisitos son necesarios para la actualización automática.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Logs (Opcional/Oculto inicialmente) -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-secondary collapsed-card">
                        <div class="card-header">
                            <h3 class="card-title">Registro de Cambios (Changelog)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Versión 1.0.0 - Lanzamiento Inicial</p>
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
<script src="assets/js/custom/updates.js"></script>
</body>
</html>
