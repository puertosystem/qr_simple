<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestión de Usuarios - Módulo Certificados QR</title>
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
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Gestión de Usuarios</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
              <li class="breadcrumb-item active">Usuarios</li>
            </ol>
          </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($_GET['success']); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($_GET['error']); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
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
                <h3 class="card-title">Listado de Usuarios</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-create-user">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                  </button>
                </div>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Nombre</th>
                      <th>Usuario</th>
                      <th>Email</th>
                      <th>Rol</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($users)): ?>
                      <?php foreach ($users as $user): ?>
                      <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                          <div class="d-flex align-items-center">
                            <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : 'assets/lte/dist/img/default-150x150.png'; ?>" class="img-circle elevation-1 mr-2" alt="User Image" style="width: 32px; height: 32px; object-fit: cover;">
                            <?php echo htmlspecialchars($user['name']); ?>
                          </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                          <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-danger' : 'badge-info'; ?>">
                            <?php echo ucfirst($user['role']); ?>
                          </span>
                        </td>
                        <td>
                          <button type="button" class="btn btn-sm btn-warning" 
                                  data-toggle="modal" 
                                  data-target="#modal-edit-user"
                                  data-id="<?php echo $user['id']; ?>"
                                  data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                  data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                  data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                  data-role="<?php echo $user['role']; ?>"
                                  data-profile-image="<?php echo htmlspecialchars($user['profile_image'] ?? ''); ?>">
                            <i class="fas fa-edit"></i>
                          </button>
                          <?php if ($user['id'] != $_SESSION['user_id']): ?>
                          <a href="index.php?page=users&view=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                            <i class="fas fa-trash"></i>
                          </a>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="6" class="text-center">No hay usuarios registrados</td>
                      </tr>
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

  <!-- Modal Create User -->
  <div class="modal fade" id="modal-create-user">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Nuevo Usuario</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="post" action="index.php?page=users&view=create" enctype="multipart/form-data">
          <div class="modal-body">
            <div class="form-group">
              <label for="create_name">Nombre Completo</label>
              <input type="text" class="form-control" id="create_name" name="name" required>
            </div>
            <div class="form-group">
              <label for="create_username">Nombre de Usuario</label>
              <input type="text" class="form-control" id="create_username" name="username" required>
            </div>
            <div class="form-group">
              <label for="create_email">Email</label>
              <input type="email" class="form-control" id="create_email" name="email" required>
            </div>
            <div class="form-group">
              <label for="create_password">Contraseña</label>
              <input type="password" class="form-control" id="create_password" name="password" required>
            </div>
            <div class="form-group">
              <label for="create_role">Rol</label>
              <select class="form-control" id="create_role" name="role">
                <option value="user">Usuario</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
            <div class="form-group">
              <label for="create_profile_image">Avatar</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="create_profile_image" name="profile_image" accept="image/*">
                <label class="custom-file-label" for="create_profile_image">Elegir archivo</label>
              </div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit User -->
  <div class="modal fade" id="modal-edit-user">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Editar Usuario</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="form-edit-user" method="post" action="" enctype="multipart/form-data">
          <div class="modal-body">
            <div class="form-group">
              <label for="edit_name">Nombre Completo</label>
              <input type="text" class="form-control" id="edit_name" name="name" required>
            </div>
            <div class="form-group">
              <label for="edit_username">Nombre de Usuario</label>
              <input type="text" class="form-control" id="edit_username" name="username" required>
            </div>
            <div class="form-group">
              <label for="edit_email">Email</label>
              <input type="email" class="form-control" id="edit_email" name="email" required>
            </div>
            <div class="form-group">
              <label for="edit_password">Contraseña (Dejar en blanco para no cambiar)</label>
              <input type="password" class="form-control" id="edit_password" name="password">
            </div>
            <div class="form-group">
              <label for="edit_role">Rol</label>
              <select class="form-control" id="edit_role" name="role">
                <option value="user">Usuario</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
            <div class="form-group">
              <label for="edit_profile_image">Avatar</label>
              <div class="text-center mb-2">
                <img src="assets/lte/dist/img/default-150x150.png" id="edit_profile_image_preview" class="img-circle elevation-2" alt="Avatar actual" style="width: 80px; height: 80px; object-fit: cover;">
              </div>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="edit_profile_image" name="profile_image" accept="image/*">
                <label class="custom-file-label" for="edit_profile_image">Elegir archivo</label>
              </div>
              <small class="form-text text-muted">Dejar en blanco para mantener el avatar actual.</small>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Actualizar</button>
          </div>
        </form>
      </div>
    </div>
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
<script src="assets/lte/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>

<script>
  $(function() {
    bsCustomFileInput.init();

    $('#modal-edit-user').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var id = button.data('id');
      var name = button.data('name');
      var username = button.data('username');
      var email = button.data('email');
      var role = button.data('role');
      var profileImage = button.data('profile-image');

      var modal = $(this);
      modal.find('#edit_name').val(name);
      modal.find('#edit_username').val(username);
      modal.find('#edit_email').val(email);
      modal.find('#edit_role').val(role);
      modal.find('#edit_password').val(''); // Clear password field
      
      if (profileImage) {
        modal.find('#edit_profile_image_preview').attr('src', profileImage);
      } else {
        modal.find('#edit_profile_image_preview').attr('src', 'assets/lte/dist/img/default-150x150.png');
      }

      // Reset file input label
      modal.find('.custom-file-label').html('Elegir archivo');

      // Update form action
      modal.find('#form-edit-user').attr('action', 'index.php?page=users&view=edit&id=' + id);
    });
  });
</script>
</body>
</html>
