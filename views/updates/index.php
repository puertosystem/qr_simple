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

  <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>

<?php include __DIR__ . '/../partials/scripts.php'; ?>

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

                if (response.status === 'success') {
                    if (response.update_available) {
                        resultArea.addClass('alert-warning');
                        resultArea.html(`
                            <h5><i class="icon fas fa-exclamation-triangle"></i> ¡Nueva versión disponible!</h5>
                            <p>Versión: <strong>${response.version}</strong> (${response.date})</p>
                            <p>${response.description}</p>
                            <button class="btn btn-success mt-2" onclick="confirmUpdate('${response.version}')"><i class="fas fa-download"></i> Descargar e Instalar</button>
                        `);
                    } else {
                        resultArea.addClass('alert-success');
                        resultArea.html(`
                            <h5><i class="icon fas fa-check"></i> Sistema Actualizado</h5>
                            <p>${response.message}</p>
                        `);
                    }
                } else {
                    // Mostrar mensaje de error del servidor
                    resultArea.removeClass('d-none').addClass('alert-danger');
                    resultArea.html(`<h5><i class="icon fas fa-ban"></i> Error</h5><p>${response.message}</p>`);
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html(originalText);
                resultArea.removeClass('d-none').addClass('alert-danger');
                resultArea.html(`<h5><i class="icon fas fa-ban"></i> Error de Conexión</h5><p>No se pudo conectar con el servidor. Código: ${xhr.status}. Detalles: ${error}</p>`);
            }
        });
    });
});

function confirmUpdate(version) {
    if (confirm('¿Estás seguro de que deseas instalar la versión ' + version + '? Se recomienda hacer una copia de seguridad de la base de datos antes de continuar.')) {
        startUpdate();
    }
}

function startUpdate() {
    var btn = $('#btn-check-update');
    var resultArea = $('#check-result');
    var progressBar = $('#update-progress');
    var progressBarInner = progressBar.find('.progress-bar');

    // UI Update
    btn.prop('disabled', true).addClass('d-none');
    resultArea.html('<div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div> <span class="ml-2">Descargando e instalando actualización... Por favor espere.</span>');
    progressBar.removeClass('d-none');
    progressBarInner.css('width', '50%');

    $.ajax({
        url: 'index.php?page=updates&action=process',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            progressBarInner.css('width', '100%');
            
            if (response.status === 'success') {
                resultArea.removeClass('alert-warning').addClass('alert-success');
                resultArea.html(`
                    <h5><i class="icon fas fa-check"></i> ¡Actualización Completada!</h5>
                    <p>${response.message}</p>
                    <p>El sistema se recargará en 5 segundos...</p>
                `);
                setTimeout(function() {
                    location.reload();
                }, 5000);
            } else {
                resultArea.removeClass('alert-warning').addClass('alert-danger');
                resultArea.html(`
                    <h5><i class="icon fas fa-ban"></i> Error en la Actualización</h5>
                    <p>${response.message}</p>
                    <button class="btn btn-primary mt-2" onclick="location.reload()">Reintentar</button>
                `);
                btn.prop('disabled', false).removeClass('d-none');
            }
        },
        error: function(xhr, status, error) {
            progressBar.addClass('d-none');
            resultArea.removeClass('alert-warning').addClass('alert-danger');
            resultArea.html(`
                <h5><i class="icon fas fa-ban"></i> Error Fatal</h5>
                <p>Ocurrió un error inesperado durante la actualización. Por favor contacte a soporte.</p>
                <p>Detalles: ${xhr.status} - ${error}</p>
            `);
            btn.prop('disabled', false).removeClass('d-none');
        }
    });
}
</script>
</body>
</html>
