<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Participantes / Alumnos - Módulo Certificados QR</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="assets/lte/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/lte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
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
            <h1 class="m-0 text-dark">Participantes y matrículas</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
              <li class="breadcrumb-item active">Participantes</li>
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
                <h3 class="card-title">Listado de Participantes</h3>
                <div class="card-tools">
                  <form action="index.php" method="get">
                    <input type="hidden" name="page" value="participants">
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
                      <th>Participante</th>
                      <th>Documento</th>
                      <th>Curso</th>
                      <th>Estado</th>
                      <th>Fecha Registro</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($participantsData['data'])): ?>
                      <tr>
                        <td colspan="6" class="text-center">No se encontraron registros.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($participantsData['data'] as $row): ?>
                        <tr>
                          <td>
                            <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                          </td>
                          <td><?php echo htmlspecialchars($row['identity_document']); ?></td>
                          <td>
                            <?php
                              $courseName = $row['course_name'];
                              $maxLen = 70;
                              if (function_exists('mb_strlen')) {
                                  if (mb_strlen($courseName, 'UTF-8') > $maxLen) {
                                      $courseName = mb_substr($courseName, 0, $maxLen - 3, 'UTF-8') . '...';
                                  }
                              } else {
                                  if (strlen($courseName) > $maxLen) {
                                      $courseName = substr($courseName, 0, $maxLen - 3) . '...';
                                  }
                              }
                              echo htmlspecialchars($courseName);
                            ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($row['event_code']); ?></small>
                          </td>
                          <td>
                            <?php
                              $statusLabels = [
                                'active' => '<span class="badge badge-success">Activo</span>',
                                'completed' => '<span class="badge badge-primary">Completado</span>',
                                'pending' => '<span class="badge badge-warning">Pendiente</span>'
                              ];
                              echo $statusLabels[$row['enrollment_status']] ?? $row['enrollment_status'];
                            ?>
                          </td>
                          <td><?php echo date('d/m/Y H:i', strtotime($row['enrollment_date'])); ?></td>
                          <td>
                            <button type="button" class="btn btn-sm btn-info btn-edit-participant" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-enrollment-id="<?php echo $row['enrollment_id']; ?>">
                              <i class="fas fa-edit"></i> Editar
                            </button>
                            <button type="button" class="btn btn-sm btn-primary btn-manage-courses" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>">
                              <i class="fas fa-graduation-cap"></i> Cursos
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
                    <a href="index.php?page=participants&view=create" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Participante
                    </a>
                </div>
                <?php
                  $totalPages = $participantsData['totalPages'];
                  $currentPage = $participantsData['page'];
                  $q = isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '';
                ?>
                <ul class="pagination pagination-sm m-0 float-right">
                  <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="index.php?page=participants&p=<?php echo $currentPage - 1; ?><?php echo $q; ?>">&laquo;</a>
                  </li>
                  
                  <?php
                  $range = 2; // Rango de páginas a mostrar alrededor de la actual
                  for ($i = 1; $i <= $totalPages; $i++): 
                    // Mostrar primera, última, y rango alrededor de la actual
                    if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)):
                  ?>
                    <li class="page-item <?php echo $currentPage == $i ? 'active' : ''; ?>">
                      <a class="page-link" href="index.php?page=participants&p=<?php echo $i; ?><?php echo $q; ?>"><?php echo $i; ?></a>
                    </li>
                  <?php 
                    // Mostrar puntos suspensivos si hay hueco
                    elseif ($i == $currentPage - $range - 1 || $i == $currentPage + $range + 1): 
                  ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                  <?php endif; ?>
                  <?php endfor; ?>

                  <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="index.php?page=participants&p=<?php echo $currentPage + 1; ?><?php echo $q; ?>">&raquo;</a>
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

  <footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
      Vistas preparadas para futura integración con PostgreSQL y QR.
    </div>
    <span>Módulo de Certificados QR dentro de aulavirtual, etapa de maquetación.</span>
  </footer>

  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<!-- Modal Editar Participante -->
<div class="modal fade" id="modalEditParticipant" tabindex="-1" role="dialog" aria-labelledby="modalEditParticipantLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditParticipantLabel">Editar Participante</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="formEditParticipant">
        <div class="modal-body">
          <input type="hidden" id="edit_participant_id" name="participant_id">
          <input type="hidden" id="edit_enrollment_id" name="enrollment_id">
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_first_name">Nombres</label>
                <input type="text" class="form-control" id="edit_first_name" name="first_name">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_last_name">Apellidos</label>
                <input type="text" class="form-control" id="edit_last_name" name="last_name">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_email">Email</label>
                <input type="email" class="form-control" id="edit_email" name="email">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_identity_document">Documento de Identidad</label>
                <input type="text" class="form-control" id="edit_identity_document" name="identity_document">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_phone">Teléfono</label>
                <input type="text" class="form-control" id="edit_phone" name="phone">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_enrollment_status">Estado de Matrícula</label>
                <select class="form-control" id="edit_enrollment_status" name="enrollment_status">
                  <option value="active">Activo</option>
                  <option value="completed">Completado</option>
                  <option value="pending">Pendiente</option>
                  <option value="cancelled">Cancelado</option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="edit_notes">Notas</label>
            <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
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

<!-- Modal Gestionar Cursos -->
<div class="modal fade" id="modal-manage-courses">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Gestionar Cursos: <span id="modal-participant-name"></span></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="manage-participant-id">
        
        <h5>Cursos Matriculados</h5>
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Código</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="enrolled-courses-list">
                    <!-- Loaded via AJAX -->
                </tbody>
            </table>
        </div>

        <hr>

        <h5>Matricular en Nuevo Curso</h5>
        <div class="form-group">
            <div class="input-group">
                <input type="text" class="form-control" id="search-available-course" placeholder="Buscar curso por nombre o código...">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="btn-search-course">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="list-group" id="available-courses-list" style="max-height: 200px; overflow-y: auto;">
            <!-- Loaded via AJAX -->
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // --- NUEVO: Gestión de Cursos ---
    $('.btn-manage-courses').click(function() {
        var participantId = $(this).data('id');
        var participantName = $(this).data('name');
        
        $('#manage-participant-id').val(participantId);
        $('#modal-participant-name').text(participantName);
        $('#enrolled-courses-list').html('<tr><td colspan="4" class="text-center">Cargando...</td></tr>');
        $('#available-courses-list').html('');
        $('#search-available-course').val('');
        
        $('#modal-manage-courses').modal('show');
        
        loadEnrolledCourses(participantId);
    });

    function loadEnrolledCourses(participantId) {
        $.ajax({
            url: 'index.php',
            method: 'GET',
            data: { page: 'participants', action: 'get_participant_courses', participant_id: participantId },
            dataType: 'json',
            success: function(response) {
                var html = '';
                if (response.error) {
                    html = '<tr><td colspan="4" class="text-danger">' + response.error + '</td></tr>';
                } else if (response.length === 0) {
                    html = '<tr><td colspan="4" class="text-center">No hay cursos matriculados.</td></tr>';
                } else {
                    response.forEach(function(course) {
                        html += '<tr>';
                        html += '<td>' + course.course_name + '</td>';
                        html += '<td>' + course.event_code + '</td>';
                        html += '<td>' + course.status + '</td>';
                        html += '<td>' + course.created_at + '</td>';
                        html += '<td>';
                        if (course.certificate_id) {
                            html += '<form method="post" action="index.php?page=certificates" class="d-inline" target="_blank">';
                            html += '<input type="hidden" name="certificate_id" value="' + course.certificate_id + '">';
                            html += '<input type="hidden" name="action" value="download_individual_pdf">';
                            html += '<button type="submit" class="btn btn-xs btn-primary" title="Descargar Certificado">';
                            html += '<i class="fas fa-file-download"></i>';
                            html += '</button>';
                            html += '</form>';
                        } else {
                            html += '<span class="text-muted" title="Sin certificado"><i class="fas fa-minus-circle"></i></span>';
                        }
                        html += '</td>';
                        html += '</tr>';
                    });
                }
                $('#enrolled-courses-list').html(html);
            },
            error: function() {
                $('#enrolled-courses-list').html('<tr><td colspan="4" class="text-danger">Error al cargar cursos.</td></tr>');
            }
        });
    }

    var searchTimeout;
    $('#search-available-course').on('input', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val();
        var participantId = $('#manage-participant-id').val();
        
        searchTimeout = setTimeout(function() {
            if (query.length >= 3) {
                searchAvailableCourses(participantId, query);
            } else {
                 $('#available-courses-list').html('');
            }
        }, 500);
    });
    
    $('#btn-search-course').click(function() {
        var query = $('#search-available-course').val();
        var participantId = $('#manage-participant-id').val();
        searchAvailableCourses(participantId, query);
    });

    function searchAvailableCourses(participantId, query) {
        $('#available-courses-list').html('<div class="text-center p-2">Buscando...</div>');
        
        $.ajax({
            url: 'index.php',
            method: 'GET',
            data: { page: 'participants', action: 'search_available_courses', participant_id: participantId, q: query },
            dataType: 'json',
            success: function(response) {
                var html = '';
                if (response.error) {
                    html = '<div class="text-danger p-2">' + response.error + '</div>';
                } else if (response.length === 0) {
                    html = '<div class="text-muted p-2">No se encontraron cursos disponibles.</div>';
                } else {
                    response.forEach(function(course) {
                        html += '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center btn-enroll-course" data-id="' + course.id + '">';
                        html += '<div><strong>' + course.event_code + '</strong> - ' + course.name + ' <span class="badge badge-info">' + (course.type_name || '') + '</span></div>';
                        html += '<span class="badge badge-primary badge-pill"><i class="fas fa-plus"></i> Matricular</span>';
                        html += '</button>';
                    });
                }
                $('#available-courses-list').html(html);
            },
            error: function() {
                $('#available-courses-list').html('<div class="text-danger p-2">Error al buscar cursos.</div>');
            }
        });
    }

    $(document).on('click', '.btn-enroll-course', function() {
        var courseId = $(this).data('id');
        var participantId = $('#manage-participant-id').val();
        var btn = $(this);
        var badge = btn.find('.badge-pill');
        var originalBadgeHtml = badge.html();
        
        // Usar SweetAlert2 para confirmación
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¿Deseas matricular al participante en este curso?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, matricular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true);
                badge.html('<i class="fas fa-spinner fa-spin"></i> Matriculando...');
                
                $.ajax({
                    url: 'index.php?page=participants&action=enroll_participant',
                    method: 'POST',
                    data: { participant_id: participantId, course_id: courseId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Matriculado!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadEnrolledCourses(participantId);
                            // Recargar lista de disponibles para actualizar estado (o quitar el curso matriculado)
                            searchAvailableCourses(participantId, $('#search-available-course').val());
                        } else {
                            Swal.fire(
                                'Error',
                                response.error || 'Desconocido',
                                'error'
                            );
                            btn.prop('disabled', false);
                            badge.html('<i class="fas fa-redo"></i> Reintentar');
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error',
                            'Error de conexión al matricular.',
                            'error'
                        );
                        btn.prop('disabled', false);
                        badge.html('<i class="fas fa-redo"></i> Reintentar');
                    }
                });
            }
        });
    });

    // Abrir modal y cargar datos
    $('.btn-edit-participant').on('click', function() {
        var participantId = $(this).data('id');
        var enrollmentId = $(this).data('enrollment-id');
        
        // Limpiar formulario
        $('#formEditParticipant')[0].reset();
        
        // Cargar datos via AJAX
        $.ajax({
            url: 'index.php',
            type: 'GET',
            data: {
                page: 'participants',
                action: 'get_participant',
                id: participantId,
                enrollment_id: enrollmentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    Swal.fire('Error', response.error, 'error');
                } else {
                    $('#edit_participant_id').val(response.id);
                    $('#edit_enrollment_id').val(response.enrollment_id);
                    $('#edit_first_name').val(response.first_name);
                    $('#edit_last_name').val(response.last_name);
                    $('#edit_email').val(response.email);
                    $('#edit_identity_document').val(response.identity_document);
                    $('#edit_phone').val(response.phone);
                    $('#edit_notes').val(response.notes);
                    $('#edit_enrollment_status').val(response.enrollment_status);
                    
                    $('#modalEditParticipant').modal('show');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error al cargar los datos del participante.', 'error');
            }
        });
    });

    // Guardar cambios
    $('#formEditParticipant').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: '¿Guardar cambios?',
            text: "¿Estás seguro de actualizar los datos del participante?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?page=participants&action=update_participant',
                    type: 'POST',
                    data: $('#formEditParticipant').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Actualizado!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                $('#modalEditParticipant').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.error || 'Error al actualizar.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
