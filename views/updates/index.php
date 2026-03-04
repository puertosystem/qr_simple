<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Actualizaciones - Sistema de Certificados</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="assets/lte/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/lte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
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
  </nav>

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

                            <button type="button" class="btn btn-primary btn-lg mt-3" id="btn-check-update">
                                <i class="fas fa-cloud-download-alt mr-2"></i> Comprobar Actualización
                            </button>
                            
                            <div class="progress mt-4 d-none" id="update-progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
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

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 1.0.0
    </div>
    <strong>&copy; <?php echo date('Y'); ?> Sistema de Certificados.</strong>
  </footer>
</div>

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    $('#btn-check-update').click(function() {
        var btn = $(this);
        var originalText = btn.html();
        var resultArea = $('#check-result');
        var progressBar = $('#update-progress');
        var progressBarInner = progressBar.find('.progress-bar');

        // Reset UI
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Comprobando...');
        resultArea.addClass('d-none').removeClass('alert-success alert-warning alert-danger');
        progressBar.addClass('d-none');

        // Call check API
        $.ajax({
            url: 'index.php?page=updates&action=check',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html(originalText);
                resultArea.removeClass('d-none');

                if (response.update_available) {
                    resultArea.addClass('alert-warning');
                    resultArea.html(`
                        <h5><i class="icon fas fa-exclamation-triangle"></i> ¡Nueva versión disponible!</h5>
                        <p>Versión: <strong>${response.version}</strong> (${response.date})</p>
                        <p>${response.description}</p>
                        <button class="btn btn-success mt-2" onclick="startUpdate()"><i class="fas fa-download"></i> Descargar e Instalar</button>
                    `);
                } else {
                    resultArea.addClass('alert-success');
                    resultArea.html(`
                        <h5><i class="icon fas fa-check"></i> Sistema Actualizado</h5>
                        <p>${response.message}</p>
                    `);
                }
            },
            error: function() {
                btn.prop('disabled', false).html(originalText);
                resultArea.removeClass('d-none').addClass('alert-danger');
                resultArea.html('<h5><i class="icon fas fa-ban"></i> Error</h5><p>No se pudo conectar con el servidor de actualizaciones.</p>');
            }
        });
    });
});

function startUpdate() {
    if(!confirm('¿Estás seguro de iniciar la actualización? Se recomienda hacer una copia de seguridad antes.')) return;

    var btn = $('#btn-check-update');
    var resultArea = $('#check-result');
    var progressBar = $('#update-progress');
    var progressBarInner = progressBar.find('.progress-bar');

    btn.prop('disabled', true);
    resultArea.addClass('d-none');
    progressBar.removeClass('d-none');
    progressBarInner.css('width', '10%').text('Iniciando...');

    // Simulamos progreso
    var progress = 10;
    var interval = setInterval(function() {
        progress += 10;
        if(progress > 90) clearInterval(interval);
        progressBarInner.css('width', progress + '%').text(progress + '%');
    }, 500);

    $.ajax({
        url: 'index.php?page=updates&action=process',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            clearInterval(interval);
            progressBarInner.css('width', '100%').text('100%');
            
            setTimeout(function() {
                progressBar.addClass('d-none');
                resultArea.removeClass('d-none');
                
                if (response.status === 'success') {
                    resultArea.removeClass('alert-warning').addClass('alert-success');
                    resultArea.html('<h5><i class="icon fas fa-check"></i> ¡Actualización completada!</h5><p>El sistema se ha actualizado correctamente. Recarga la página.</p>');
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    resultArea.removeClass('alert-warning').addClass('alert-danger');
                    resultArea.html(`<h5><i class="icon fas fa-ban"></i> Error en la actualización</h5><p>${response.message}</p>`);
                    btn.prop('disabled', false);
                }
            }, 1000);
        },
        error: function() {
            clearInterval(interval);
            progressBar.addClass('d-none');
            resultArea.removeClass('d-none').addClass('alert-danger');
            resultArea.html('<h5><i class="icon fas fa-ban"></i> Error Crítico</h5><p>Falló el proceso de actualización.</p>');
            btn.prop('disabled', false);
        }
    });
}
</script>
</body>
</html>
