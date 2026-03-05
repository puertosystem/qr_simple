<!DOCTYPE html>
<html lang="es">
<head>
  <?php include __DIR__ . '/../partials/head.php'; ?>
  <link rel="stylesheet" href="assets/lte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
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
            <h1 class="m-0 text-dark">Cursos y programas</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
              <li class="breadcrumb-item active">Cursos</li>
            </ol>
          </div>
        </div>       
        <?php if (!empty($successMessage)): ?>
        <div class="row">
          <div class="col-md-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
        <div class="row">
          <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Listado de Cursos</h3>
                <div class="card-tools">
                  <form action="index.php" method="get">
                    <input type="hidden" name="page" value="courses">
                    <div class="input-group input-group-sm" style="width: 250px;">
                      <input type="text" name="q" class="form-control float-right" placeholder="Buscar..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                      <div class="input-group-append">
                        <button type="submit" class="btn btn-default">
                          <i class="fas fa-search"></i>
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Código</th>
                      <th>Nombre</th>
                      <th>Modalidad</th>
                      <th>Participantes</th>
                      <th>QR</th>
                      <th>Inicio</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($coursesData['data'])): ?>
                      <tr>
                        <td colspan="7" class="text-center">No se encontraron cursos.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($coursesData['data'] as $row): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['event_code']); ?></td>
                          <td><?php echo htmlspecialchars($row['name']); ?></td>
                          <td>
                            <strong>Tipo:</strong> <?php echo htmlspecialchars($row['type_name']); ?><br>
                            <strong>Modalidad:</strong> <?php echo htmlspecialchars($row['modality_name']); ?>
                          </td>
                          <td>
                            <strong><?php echo $row['total_participants'] ?? 0; ?> participantes</strong><br>
                            <small class="text-muted">
                                Activos: <?php echo $row['active_participants'] ?? 0; ?> · 
                                Completados: <?php echo $row['completed_participants'] ?? 0; ?>
                            </small>
                          </td>
                          <td>
                            <span class="text-success"><i class="fas fa-check-circle"></i> Con QR: <strong><?php echo $row['with_qr'] ?? 0; ?></strong></span><br>
                            <span class="text-danger"><i class="fas fa-times-circle"></i> Sin QR: <strong><?php echo $row['without_qr'] ?? 0; ?></strong></span>
                          </td>
                          <td><?php echo $row['start_date'] ? date('d/m/Y', strtotime($row['start_date'])) : '-'; ?></td>
                          <td>
                            <?php
                              $statusLabels = [
                                'active' => '<span class="badge badge-success">Activo</span>',
                                'inactive' => '<span class="badge badge-secondary">Inactivo</span>',
                                'finished' => '<span class="badge badge-primary">Finalizado</span>'
                              ];
                              echo $statusLabels[$row['status']] ?? $row['status'];
                            ?>
                          </td>
                          <td>
                            <?php
                              $bgFile = $row['certificate_background_filename'];
                              $bgUrl = '';
                              if ($bgFile) {
                                  // Check local images/plantilla (primary storage)
                                  if (file_exists(__DIR__ . '/../../images/plantilla/' . $bgFile)) {
                                      $bgUrl = 'images/plantilla/' . $bgFile;
                                  } 
                              }
                            ?>
                            <?php if ($bgUrl): ?>
                              <button type="button" class="btn btn-sm btn-secondary btn-view-background" 
                                      data-bg="<?php echo htmlspecialchars($bgUrl); ?>" 
                                      title="Ver Fondo del Certificado">
                                <i class="fas fa-image"></i>
                              </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-info btn-edit-course" 
                                    data-id="<?php echo $row['id']; ?>">
                              <i class="fas fa-edit"></i> Editar
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
              <div class="card-footer clearfix">
                <div class="float-left">
                    <a href="index.php?page=courses&view=create" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Curso
                    </a>
                </div>
                <?php
                  $totalPages = $coursesData['totalPages'];
                  $currentPage = $coursesData['page'];
                  $q = isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '';
                ?>
                <ul class="pagination pagination-sm m-0 float-right">
                  <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="index.php?page=courses&p=<?php echo $currentPage - 1; ?><?php echo $q; ?>">&laquo;</a>
                  </li>
                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $currentPage == $i ? 'active' : ''; ?>">
                      <a class="page-link" href="index.php?page=courses&p=<?php echo $i; ?><?php echo $q; ?>"><?php echo $i; ?></a>
                    </li>
                  <?php endfor; ?>
                  <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="index.php?page=courses&p=<?php echo $currentPage + 1; ?><?php echo $q; ?>">&raquo;</a>
                  </li>
                </ul>
              </div>
            </div>
            <!-- /.card -->
          </div>
        </div>
      </div>
    </section>
  </div>

  <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>

<!-- Modal Ver Fondo -->
<div class="modal fade" id="modalViewBackground" tabindex="-1" role="dialog" aria-labelledby="modalViewBackgroundLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalViewBackgroundLabel">Fondo del Certificado</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="previewBackgroundImage" class="img-fluid" alt="Fondo del Certificado">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Curso -->
<div class="modal fade" id="modalEditCourse" tabindex="-1" role="dialog" aria-labelledby="modalEditCourseLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditCourseLabel">Editar Curso</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="formEditCourse" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" id="edit_course_id" name="id">
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_event_name">Nombre del Curso</label>
                <input type="text" class="form-control" id="edit_event_name" name="event_name" required>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="edit_event_code">Código Interno</label>
                <input type="text" class="form-control" id="edit_event_code" name="event_code">
              </div>
            </div>
             <div class="col-md-3">
              <div class="form-group">
                <label for="edit_max_capacity">Capacidad Máxima</label>
                <input type="number" class="form-control" id="edit_max_capacity" name="max_capacity">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_event_type">Tipo de Programa</label>
                <select class="form-control" id="edit_event_type" name="event_type" required>
                  <option value="">Seleccione...</option>
                  <?php foreach ($eventTypes as $type): ?>
                    <option value="<?php echo htmlspecialchars($type['code']); ?>">
                        <?php echo htmlspecialchars($type['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_event_modality">Modalidad</label>
                <select class="form-control" id="edit_event_modality" name="event_modality" required>
                  <option value="">Seleccione...</option>
                  <?php foreach ($eventModalities as $modality): ?>
                    <option value="<?php echo htmlspecialchars($modality['code']); ?>">
                        <?php echo htmlspecialchars($modality['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="edit_start_date">Fecha Inicio</label>
                <input type="date" class="form-control" id="edit_start_date" name="start_date">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="edit_end_date">Fecha Fin</label>
                <input type="date" class="form-control" id="edit_end_date" name="end_date">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="edit_total_hours">Horas Académicas</label>
                <input type="number" class="form-control" id="edit_total_hours" name="total_hours">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="edit_credits">Créditos</label>
                <input type="number" class="form-control" id="edit_credits" name="credits" step="0.1">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="edit_description">Descripción</label>
            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6">
                 <div class="form-group">
                    <label>Auspicios</label>
                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: 4px;">
                        <?php foreach ($auspices as $auspice): ?>
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input edit-auspice-checkbox" type="checkbox" 
                                id="edit_auspice_<?php echo $auspice['id']; ?>" 
                                name="auspices[]" 
                                value="<?php echo $auspice['id']; ?>">
                            <label for="edit_auspice_<?php echo $auspice['id']; ?>" class="custom-control-label">
                                <?php echo htmlspecialchars($auspice['name']); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                 </div>
            </div>
             <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_certificate_background">Fondo del Certificado (Opcional)</label>
                    <div class="input-group">
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" id="edit_certificate_background" name="certificate_background" accept=".jpg,.jpeg,.png">
                        <label class="custom-file-label" for="edit_certificate_background">Elegir archivo</label>
                      </div>
                    </div>
                    <small class="form-text text-muted">Subir solo si desea reemplazar el fondo actual (JPG, PNG).</small>
                    
                    <!-- Preview y Eliminación -->
                    <div id="edit_background_preview_container" class="mt-2" style="display:none;">
                        <label>Fondo Actual:</label>
                        <div class="d-flex align-items-center">
                            <img id="edit_background_preview" src="" class="img-thumbnail" style="max-height: 100px;">
                            <button type="button" class="btn btn-sm btn-danger ml-2" id="btn_delete_background" title="Eliminar fondo">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <input type="hidden" id="delete_background" name="delete_background" value="0">
                    </div>
                </div>
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

<?php include __DIR__ . '/../partials/scripts.php'; ?>
<script src="assets/lte/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="assets/lte/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

<script>
$(document).ready(function() {
    bsCustomFileInput.init();

    // Ver Fondo
    $('.btn-view-background').on('click', function() {
        var bgUrl = $(this).data('bg');
        $('#previewBackgroundImage').attr('src', bgUrl);
        $('#modalViewBackground').modal('show');
    });

    // Abrir modal y cargar datos
    $('.btn-edit-course').on('click', function() {
        var courseId = $(this).data('id');
        
        // Limpiar formulario
        $('#formEditCourse')[0].reset();
        $('.edit-auspice-checkbox').prop('checked', false);
        $('.custom-file-label').html('Elegir archivo');
        $('#edit_background_preview_container').hide();
        $('#delete_background').val('0');
        
        // Cargar datos via AJAX
        $.ajax({
            url: 'index.php',
            type: 'GET',
            data: {
                page: 'courses',
                action: 'get_course',
                id: courseId
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error
                    });
                } else {
                    $('#edit_course_id').val(response.id);
                    $('#edit_event_name').val(response.name);
                    $('#edit_event_code').val(response.event_code || response.internal_code); // Handle potential alias
                    $('#edit_max_capacity').val(response.max_capacity);
                    $('#edit_event_type').val(response.event_type_code);
                    $('#edit_event_modality').val(response.event_modality_code);
                    $('#edit_start_date').val(response.start_date);
                    $('#edit_end_date').val(response.end_date);
                    $('#edit_total_hours').val(response.total_hours);
                    $('#edit_credits').val(response.credits);
                    $('#edit_description').val(response.description);
                    
                    // Check auspices
                    if (response.auspice_ids && Array.isArray(response.auspice_ids)) {
                        response.auspice_ids.forEach(function(auspiceId) {
                            $('#edit_auspice_' + auspiceId).prop('checked', true);
                        });
                    }

                    // Background Preview
                    if (response.certificate_background_filename) {
                        var bgFile = response.certificate_background_filename;
                        var localPath = 'images/plantilla/' + bgFile;
                        
                        $('#edit_background_preview').attr('src', localPath);
                        $('#edit_background_preview_container').show();
                    }
                    
                    $('#modalEditCourse').modal('show');
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cargar los datos del curso.'
                });
            }
        });
    });

    // Eliminar fondo (visual)
    $('#btn_delete_background').on('click', function() {
        $('#edit_background_preview_container').hide();
        $('#delete_background').val('1');
        $('#edit_certificate_background').val(''); // Clear file input
        $('.custom-file-label').html('Elegir archivo');
    });

    // Guardar cambios
    $('#formEditCourse').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: 'index.php?page=courses&action=update_course',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message
                    }).then(() => {
                        $('#modalEditCourse').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Error al actualizar.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud.'
                });
            }
        });
    });
});
</script>
</body>
</html>