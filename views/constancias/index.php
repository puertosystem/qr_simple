<!DOCTYPE html>
<html lang="es">
<head>
  <?php include __DIR__ . '/../partials/head.php'; ?>
  <link rel="stylesheet" href="assets/lte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <style>
      /* Fix SweetAlert2 z-index issue with Bootstrap modals */
      .swal2-container {
          z-index: 2000 !important;
      }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Eventos de Constancias</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                            <li class="breadcrumb-item active">Eventos</li>
                        </ol>
                    </div>
                </div>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Lista de Eventos</h3>
                        <div class="card-tools">
                            <a href="index.php?page=constancias&view=create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuevo Evento
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Fechas</th>
                                    <th>Fondo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($events)): ?>
                                    <tr><td colspan="6" class="text-center">No hay eventos registrados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td><?= $event['id'] ?></td>
                                            <td><?= htmlspecialchars($event['nombre']) ?></td>
                                            <td>
                                                <?= $event['fecha_inicio'] ?>
                                                <?= $event['fecha_fin'] ? ' - ' . $event['fecha_fin'] : '' ?>
                                            </td>
                                            <td>
                                                <?php if ($event['fondo_constancia']): ?>
                                                    <button type="button" class="btn btn-sm btn-secondary btn-view-background" data-bg="<?= htmlspecialchars($event['fondo_constancia']) ?>" title="Ver Fondo">
                                                        <i class="fas fa-image"></i> Ver Fondo
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin fondo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $event['activo'] ? 'success' : 'danger' ?>">
                                                    <?= $event['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary btn-edit-event" data-id="<?= $event['id'] ?>" title="Editar Evento">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="index.php?page=constancias&action=toggle_status&id=<?= $event['id'] ?>" class="btn btn-sm btn-<?= $event['activo'] ? 'warning' : 'success' ?>" title="<?= $event['activo'] ? 'Desactivar' : 'Activar' ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-info btn-copy-url" data-id="<?= $event['id'] ?>" title="Copiar URL del formulario">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal Editar Evento -->
    <div class="modal fade" id="modalEditEvent" tabindex="-1" role="dialog" aria-labelledby="modalEditEventLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditEventLabel">Editar Evento</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="formEditEvent" action="index.php?page=constancias&action=update_event" method="post" enctype="multipart/form-data">
            <div class="modal-body">
              <input type="hidden" id="edit_event_id" name="id">
              
              <div class="form-group">
                <label for="edit_nombre">Nombre del Evento</label>
                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="edit_fecha_inicio">Fecha Inicio</label>
                    <input type="date" class="form-control" id="edit_fecha_inicio" name="fecha_inicio" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="edit_fecha_fin">Fecha Fin</label>
                    <input type="date" class="form-control" id="edit_fecha_fin" name="fecha_fin">
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="edit_fondo_constancia">Fondo de Constancia (Opcional)</label>
                <div class="input-group">
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" id="edit_fondo_constancia" name="fondo_constancia" accept="image/*">
                    <label class="custom-file-label" for="edit_fondo_constancia">Elegir archivo</label>
                  </div>
                </div>
                <small class="form-text text-muted">Dejar en blanco para mantener el fondo actual.</small>
                
                <!-- Preview y Eliminación (Adapted from courses logic) -->
                <div id="current_fondo_preview_container" class="mt-2" style="display:none;">
                    <label>Fondo Actual:</label>
                    <div class="d-flex align-items-center">
                        <img id="current_fondo_preview" src="" class="img-thumbnail" style="max-height: 100px;">
                        <button type="button" class="btn btn-sm btn-danger ml-2 btn-delete-fondo" title="Eliminar fondo">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <input type="hidden" id="delete_fondo" name="delete_fondo" value="0">
                </div>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Ver Fondo -->
    <div class="modal fade" id="modalViewBackground" tabindex="-1" role="dialog" aria-labelledby="modalViewBackgroundLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalViewBackgroundLabel">Fondo de Constancia</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body text-center">
            <img src="" id="previewBackgroundImage" class="img-fluid" alt="Fondo de Constancia">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
<script src="assets/lte/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="assets/lte/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
$(function() {
    bsCustomFileInput.init();

    // Ver Fondo
    $('.btn-view-background').on('click', function() {
        var bgUrl = $(this).data('bg');
        $('#previewBackgroundImage').attr('src', bgUrl);
        $('#modalViewBackground').modal('show');
    });

    // Editar Evento
    $('.btn-edit-event').on('click', function() {
        var eventId = $(this).data('id');
        
        // Reset form
        $('#formEditEvent')[0].reset();
        $('.custom-file-label').html('Elegir archivo');
        $('#current_fondo_preview_container').hide();
        $('#delete_fondo').val('0');
        
        // Fetch event details
        $.ajax({
            url: 'index.php',
            type: 'GET',
            data: {
                page: 'constancias',
                action: 'get_event_details',
                id: eventId
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert(response.error);
                    return;
                }
                
                $('#edit_event_id').val(response.id);
                $('#edit_nombre').val(response.nombre);
                $('#edit_fecha_inicio').val(response.fecha_inicio);
                $('#edit_fecha_fin').val(response.fecha_fin);
                
                if (response.fondo_constancia) {
                    $('#current_fondo_preview').attr('src', response.fondo_constancia);
                    $('#current_fondo_preview_container').show();
                } else {
                    $('#current_fondo_preview_container').hide();
                }
                
                $('#modalEditEvent').modal('show');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching event details:', error);
                alert('Error al cargar los datos del evento.');
            }
        });
    });

    // Delete fondo handler
    $('.btn-delete-fondo').on('click', function() {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¿Deseas eliminar la imagen de fondo actual?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#current_fondo_preview_container').hide();
                $('#delete_fondo').val('1');
                $('#edit_fondo_constancia').val(''); // Clear file input
                $('.custom-file-label').html('Elegir archivo');
            }
        });
    });

    // Reset delete flag if user selects a new file
    $('#edit_fondo_constancia').on('change', function() {
        if (this.files && this.files.length > 0) {
            $('#delete_fondo').val('0');
            $('#current_fondo_preview_container').hide();
        }
    });

    $('.btn-copy-url').on('click', function() {
        var id = $(this).data('id');
        // Construir la URL base limpia (sin parámetros)
        var path = window.location.pathname;
        // Si termina en index.php, lo usamos tal cual. Si es carpeta raíz, agregamos index.php por seguridad.
        if (!path.endsWith('index.php')) {
            path = path.replace(/\/$/, "") + '/index.php';
        }
        
        var baseUrl = window.location.origin + path;
        var url = baseUrl + '?page=constancias&view=public&event_id=' + id;
        
        navigator.clipboard.writeText(url).then(function() {
            // Usar Toast de AdminLTE si está disponible
            if (typeof $(document).Toasts === 'function') {
                $(document).Toasts('create', {
                    title: 'Enlace Copiado',
                    class: 'bg-success', 
                    body: 'La URL del formulario se ha copiado al portapapeles.',
                    autohide: true,
                    delay: 3000,
                    icon: 'fas fa-check'
                });
            } else {
                alert('URL copiada: ' + url);
            }
        }, function(err) {
            console.error('Error al copiar: ', err);
            prompt('Copia esta URL manualmente:', url);
        });
    });
});
</script>
</body>
</html>
