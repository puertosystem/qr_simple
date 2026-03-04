<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuevo Participante - Módulo Certificados QR</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="assets/lte/plugins/fontawesome-free/css/all.min.css">
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
            <h1 class="m-0 text-dark">Nuevo Participante</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
              <li class="breadcrumb-item"><a href="index.php?page=participants">Participantes</a></li>
              <li class="breadcrumb-item active">Nuevo</li>
            </ol>
          </div>
        </div>
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
          <div class="col-md-6">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Alta individual de participante</h3>
                <span class="badge badge-light border ml-2">Registro individual</span>
              </div>
              <div class="card-body">
                <p class="text-muted">
                  Crea un participante y define en el mismo paso el curso al que se inscribe.
                </p>
                <form action="index.php?page=participants&view=create" method="post">
                  <input type="hidden" name="form_type" value="single">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="first_name">Nombres</label>
                      <input
                        type="text"
                        class="form-control"
                        id="first_name"
                        name="first_name"
                        placeholder="Ej.: María Fernanda"
                        value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                    <div class="form-group col-md-6">
                      <label for="last_name">Apellidos</label>
                      <input
                        type="text"
                        class="form-control"
                        id="last_name"
                        name="last_name"
                        placeholder="Ej.: Pérez Rodríguez"
                        value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-12">
                      <label for="email">Correo electrónico</label>
                      <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="Ej.: participante@correo.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                    <div class="form-group col-md-6">
                      <label for="document">Documento de identidad</label>
                      <input
                        type="text"
                        class="form-control"
                        id="document"
                        name="identity_document"
                        placeholder="DNI / CE / Pasaporte"
                        value="<?php echo isset($_POST['identity_document']) ? htmlspecialchars($_POST['identity_document'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                    <div class="form-group col-md-6">
                      <label for="phone">Teléfono opcional</label>
                      <input
                        type="text"
                        class="form-control"
                        id="phone"
                        name="phone"
                        placeholder="Ej.: 999 999 999"
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-12">
                      <label for="course_id">Curso o programa</label>
                      <div class="input-group mb-2">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input
                          type="text"
                          class="form-control"
                          id="course_search"
                          placeholder="Buscar curso por nombre o código"
                        >
                      </div>
                      <select id="course_id" name="course_id" class="form-control">
                        <option value="">Seleccionar curso</option>
                        <?php if (!empty($events)): ?>
                          <?php foreach ($events as $event): ?>
                            <?php
                              $value = $event['id'];
                              $label = $event['name'];
                              if (!empty($event['event_code'])) {
                                  $label .= ' (' . $event['event_code'] . ')';
                              }
                              $selected = isset($_POST['course_id']) && $_POST['course_id'] === $value ? 'selected' : '';
                            ?>
                            <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>>
                              <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </select>
                    </div>
                    <div class="form-group col-md-12">
                      <label for="enrollment_status">Estado de la matrícula</label>
                      <select id="enrollment_status" name="enrollment_status" class="form-control">
                        <?php
                          $currentStatus = isset($_POST['enrollment_status']) ? $_POST['enrollment_status'] : 'active';
                        ?>
                        <option value="active" <?php echo $currentStatus === 'active' ? 'selected' : ''; ?>>Activo</option>
                        <option value="completed" <?php echo $currentStatus === 'completed' ? 'selected' : ''; ?>>Completado</option>
                        <option value="pending" <?php echo $currentStatus === 'pending' ? 'selected' : ''; ?>>Pendiente de pago</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="notes">Observaciones internas</label>
                    <textarea
                      id="notes"
                      name="notes"
                      class="form-control"
                      rows="3"
                      placeholder="Ej.: Alumno que viene de campaña promocional..."
                    ><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                  </div>

                  <div class="text-right">
                    <button type="submit" class="btn btn-primary">
                      Guardar participante
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Carga masiva de participantes</h3>
                <span class="badge badge-light border ml-2">Registro masivo</span>
              </div>
              <div class="card-body">
                <p class="text-muted">
                  Importa una lista de personas y vincúlalas a un curso en bloque.
                </p>
                <form action="index.php?page=participants&view=create" method="post" enctype="multipart/form-data">
                  <input type="hidden" name="form_type" value="bulk">
                  <div class="form-row">
                    <div class="form-group col-md-12">
                      <label>Archivo de participantes</label>
                      <div class="border border-secondary rounded p-3 bg-light">
                        <p class="small mb-2">
                          Formato sugerido: CSV o Excel con columnas como:
                        </p>
                        <ul class="small mb-3">
                          <li><span class="font-weight-bold">first_name</span> (nombres)</li>
                          <li><span class="font-weight-bold">last_name</span> (apellidos)</li>
                          <li><span class="font-weight-bold">email</span></li>
                          <li><span class="font-weight-bold">identity_document</span></li>
                        </ul>
                        <div class="custom-file">
                          <input
                            type="file"
                            class="custom-file-input"
                            id="participants_file"
                            name="participants_file"
                            accept=".csv,text/csv"
                          >
                          <label class="custom-file-label" for="participants_file">Seleccionar archivo CSV</label>
                        </div>
                        <div class="mt-3">
                          <a href="index.php?page=participants&amp;action=download_template" class="btn btn-outline-secondary btn-sm">
                            Descargar plantilla CSV
                          </a>
                        </div>
                      </div>
                    </div>
                    <div class="form-group col-md-12">
                      <label for="bulk_course_id">Curso al que se inscriben todos</label>
                      <div class="input-group mb-2">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" id="bulk_course_search" placeholder="Buscar curso por nombre o código">
                      </div>
                      <select id="bulk_course_id" name="bulk_course_id" class="form-control">
                        <option value="">Seleccionar curso</option>
                        <?php if (!empty($events)): ?>
                          <?php foreach ($events as $event): ?>
                            <?php
                              $value = $event['id'];
                              $label = $event['name'];
                              if (!empty($event['event_code'])) {
                                  $label .= ' (' . $event['event_code'] . ')';
                              }
                            ?>
                            <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
                              <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </select>
                    </div>
                  </div>

                  <div class="text-right">
                    <button type="submit" class="btn btn-success">
                      Procesar carga masiva
                    </button>
                  </div>
                </form>
              </div>
            </div>
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

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
  function filterOptions(inputId, selectId) {
    var filter = $(inputId).val().toLowerCase();
    var hasMatch = false;
    $(selectId + ' option').each(function() {
      var text = $(this).text().toLowerCase();
      var val = $(this).val();
      if (val === '') return;
      if (text.indexOf(filter) > -1) {
        $(this).show();
        if (!hasMatch) {
            $(this).prop('selected', true);
            hasMatch = true;
        }
      } else {
        $(this).hide();
      }
    });
    if (!hasMatch) {
       $(selectId).val('');
    }
  }

  $('#course_search').on('keyup', function() {
    filterOptions('#course_search', '#course_id');
  });

  $('#bulk_course_search').on('keyup', function() {
    filterOptions('#bulk_course_search', '#bulk_course_id');
  });

  $('.custom-file-input').on('change', function() {
    var fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').addClass("selected").html(fileName);
  });
});
</script>
</body>
</html>
