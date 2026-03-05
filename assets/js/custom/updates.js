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
    $('#btn-start-update').click(function() {
        $('#confirmUpdateModal').modal('hide');
        startUpdate();
    });
});

function confirmUpdate(version) {
    $('#modal-version-text').text(version);
    $('#confirmUpdateModal').modal('show');
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