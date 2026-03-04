<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Auspicios - Módulo Certificados QR</title>
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
  </nav>

  <?php include __DIR__ . '/../partials/sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Gestión de Auspicios</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
              <li class="breadcrumb-item active">Auspicios</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Listado de Auspicios</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAuspice" id="btnNewAuspice">
                    <i class="fas fa-plus"></i> Nuevo Auspicio
                  </button>
                </div>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Nombre</th>
                      <th>Código</th>
                      <th>Logo</th>
                      <th>Sitio Web</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($auspices)): ?>
                      <tr>
                        <td colspan="6" class="text-center">No se encontraron registros.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($auspices as $row): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['name']); ?></td>
                          <td><?php echo htmlspecialchars($row['code'] ?? '-'); ?></td>
                          <td>
                            <?php if (!empty($row['logo_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['logo_url']); ?>" alt="Logo" style="height: 30px;">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (!empty($row['website_url'])): ?>
                                <a href="<?php echo htmlspecialchars($row['website_url']); ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($row['active']): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <button type="button" class="btn btn-sm btn-info btn-edit-auspice" 
                                    data-id="<?php echo $row['id']; ?>">
                              <i class="fas fa-edit"></i> Editar
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-delete-auspice" 
                                    data-id="<?php echo $row['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($row['name']); ?>">
                              <i class="fas fa-trash"></i> Eliminar
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
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
      Gestión de Auspicios
    </div>
    <span>Módulo de Certificados QR.</span>
  </footer>

  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<!-- Modal Auspicio -->
<div class="modal fade" id="modalAuspice" tabindex="-1" role="dialog" aria-labelledby="modalAuspiceLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAuspiceLabel">Nuevo Auspicio</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="formAuspice" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" id="auspice_id" name="id">
          <input type="hidden" id="delete_logo" name="delete_logo" value="0">
          <div class="form-group">
            <label for="auspice_name">Nombre</label>
            <input type="text" class="form-control" id="auspice_name" name="name" required>
          </div>
          <div class="form-group">
            <label for="auspice_code">Código (Opcional)</label>
            <input type="text" class="form-control" id="auspice_code" name="code">
          </div>
          <div class="form-group">
            <label for="auspice_logo">Logo (Imagen)</label>
            <div class="input-group">
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="auspice_logo_file" name="logo_file" accept="image/*">
                <label class="custom-file-label" for="auspice_logo_file">Seleccionar archivo</label>
              </div>
            </div>
            <small class="form-text text-muted">Formatos permitidos: JPG, PNG. Se guardará en images/auspicio</small>
            <input type="hidden" id="auspice_logo" name="logo_url">
            <div id="current_logo_preview" class="mt-2" style="display:none;">
                <p>Logo actual:</p>
                <img src="" id="img_preview" style="max-height: 50px;">
                <button type="button" class="btn btn-sm btn-danger ml-2" id="btn_delete_logo" title="Eliminar logo">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
          </div>
          <div class="form-group">
            <label for="auspice_website">Sitio Web</label>
            <input type="text" class="form-control" id="auspice_website" name="website_url">
          </div>
          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="auspice_active" name="active" value="1" checked>
              <label class="custom-control-label" for="auspice_active">Activo</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>
<script src="assets/lte/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

<script>
$(document).ready(function() {
    bsCustomFileInput.init();

    // Nuevo Auspicio
    $('#btnNewAuspice').on('click', function() {
        $('#modalAuspiceLabel').text('Nuevo Auspicio');
        $('#formAuspice')[0].reset();
        $('#auspice_id').val('');
        $('#auspice_logo').val('');
        $('#delete_logo').val('0');
        $('#current_logo_preview').hide();
        $('.custom-file-label').text('Seleccionar archivo');
        $('#auspice_active').prop('checked', true);
    });

    // Delete logo button
    $('#btn_delete_logo').on('click', function() {
        $('#delete_logo').val('1');
        $('#current_logo_preview').hide();
        $('#auspice_logo').val(''); // Clear the hidden url holder
    });

    // Custom file input label update
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });

    // Editar Auspicio
    $('.btn-edit-auspice').on('click', function() {
        var id = $(this).data('id');
        $('#modalAuspiceLabel').text('Editar Auspicio');
        $('#delete_logo').val('0');
        
        $.ajax({
            url: 'index.php?page=auspices&action=get&id=' + id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    $('#auspice_id').val(response.id);
                    $('#auspice_name').val(response.name);
                    $('#auspice_code').val(response.code);
                    $('#auspice_logo').val(response.logo_url);
                    
                    if (response.logo_url) {
                        $('#img_preview').attr('src', response.logo_url);
                        $('#current_logo_preview').show();
                    } else {
                        $('#current_logo_preview').hide();
                    }
                    
                    $('.custom-file-label').text('Seleccionar archivo');

                    $('#auspice_website').val(response.website_url);
                    $('#auspice_active').prop('checked', response.active == 1);
                    $('#modalAuspice').modal('show');
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cargar datos.'
                });
            }
        });
    });

    // Guardar Auspicio
    $('#formAuspice').on('submit', function(e) {
        e.preventDefault();
        var id = $('#auspice_id').val();
        var action = id ? 'update' : 'create';
        
        var formData = new FormData(this);
        
        $.ajax({
            url: 'index.php?page=auspices&action=' + action,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar.'
                });
            }
        });
    });

    // Eliminar Auspicio
    $('.btn-delete-auspice').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Vas a eliminar el auspicio "' + name + '"',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?page=auspices&action=delete',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Eliminado!',
                                response.message,
                                'success'
                            ).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.error,
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Error al eliminar.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
