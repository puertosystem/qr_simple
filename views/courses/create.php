<!DOCTYPE html>
<html lang="es">
<head>
  <?php include __DIR__ . '/../partials/head.php'; ?>
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
            <h1 class="m-0 text-dark">Nuevo Curso</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
              <li class="breadcrumb-item"><a href="index.php?page=courses">Cursos</a></li>
              <li class="breadcrumb-item active">Nuevo</li>
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
          <div class="col-lg-6">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Crear curso o programa</h3>
                <span class="badge badge-light border ml-2">Alta individual</span>
              </div>
              <div class="card-body">
                <p class="text-muted">
                  Define los datos académicos y operativos para un nuevo curso.
                </p>
                <form action="index.php?page=courses&view=create" method="post" enctype="multipart/form-data">
                  <div class="form-group">
                    <label for="event_name">Nombre del curso o programa</label>
                    <input
                      type="text"
                      class="form-control"
                      id="event_name"
                      name="event_name"
                      placeholder="Ej.: Diplomado en Enfermería Neonatal"
                      value="<?php echo isset($_POST['event_name']) ? htmlspecialchars($_POST['event_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="event_type">Tipo de programa</label>
                      <select id="event_type" name="event_type" class="form-control">
                        <option value="">Seleccionar tipo</option>
                        <?php foreach ($eventTypes as $row): ?>
                          <?php
                            $value = $row['code'];
                            $label = $row['name'];
                            $selected = isset($_POST['event_type']) && $_POST['event_type'] === $value ? 'selected' : '';
                          ?>
                          <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <small class="form-text text-muted">Se asociará con la tabla event_types.</small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="event_modality">Modalidad</label>
                      <select id="event_modality" name="event_modality" class="form-control">
                        <option value="">Seleccionar modalidad</option>
                        <?php foreach ($eventModalities as $row): ?>
                          <?php
                            $value = $row['code'];
                            $label = $row['name'];
                            $selected = isset($_POST['event_modality']) && $_POST['event_modality'] === $value ? 'selected' : '';
                          ?>
                          <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <small class="form-text text-muted">Relacionado con event_modalities.</small>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="auspices">Auspicios</label>
                    <select id="auspices" name="auspices[]" class="form-control" multiple size="5">
                      <?php foreach ($auspices as $row): ?>
                        <?php
                          $value = $row['id'];
                          $label = $row['name'];
                          $selectedAuspices = $_POST['auspices'] ?? [];
                          $selected = in_array($value, $selectedAuspices) ? 'selected' : '';
                        ?>
                        <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>>
                          <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Mantén presionado Ctrl (Windows) o Command (Mac) para seleccionar varios.</small>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="start_date">Fecha de inicio</label>
                      <input
                        type="date"
                        class="form-control"
                        id="start_date"
                        name="start_date"
                        value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                    <div class="form-group col-md-4">
                      <label for="end_date">Fecha de fin</label>
                      <input
                        type="date"
                        class="form-control"
                        id="end_date"
                        name="end_date"
                        value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                    <div class="form-group col-md-4">
                      <label for="total_hours">Horas académicas</label>
                      <input
                        type="number"
                        class="form-control"
                        id="total_hours"
                        name="total_hours"
                        min="0"
                        placeholder="Ej.: 120"
                        value="<?php echo isset($_POST['total_hours']) ? htmlspecialchars($_POST['total_hours'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="credits">Créditos</label>
                      <input
                        type="number"
                        class="form-control"
                        id="credits"
                        name="credits"
                        min="0"
                        placeholder="Ej.: 24"
                        value="<?php echo isset($_POST['credits']) ? htmlspecialchars($_POST['credits'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                    <div class="form-group col-md-4">
                      <label for="event_code">Código interno</label>
                      <input
                        type="text"
                        class="form-control"
                        id="event_code"
                        name="event_code"
                        placeholder="Ej.: CUR-3FA9C1B2 (si se deja vacío se genera automáticamente)"
                        value="<?php echo isset($_POST['event_code']) ? htmlspecialchars($_POST['event_code'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                      <small class="form-text text-muted">
                        Si lo dejas vacío, se generará automáticamente según el tipo de programa (CUR-, DIP-, TAL-, WEB-).
                      </small>
                    </div>
                    <div class="form-group col-md-4">
                      <label for="max_capacity">Capacidad máxima</label>
                      <input
                        type="number"
                        class="form-control"
                        id="max_capacity"
                        name="max_capacity"
                        min="0"
                        placeholder="Opcional"
                        value="<?php echo isset($_POST['max_capacity']) ? htmlspecialchars($_POST['max_capacity'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                      >
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="description">Descripción breve</label>
                    <textarea
                      id="description"
                      name="description"
                      class="form-control"
                      rows="3"
                      placeholder="Resumen del curso que luego podrá mostrarse en la web pública."
                    ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                  </div>

                  <hr>

                  <div class="form-group">
                    <label for="certificate_background">Fondo del certificado</label>
                    <div class="border border-secondary rounded p-3 bg-light">
                      <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-white border d-flex align-items-center justify-content-center mr-3" style="width:40px;height:40px;">
                          <i class="fas fa-image text-primary"></i>
                        </div>
                        <div>
                          <p class="mb-1 small font-weight-bold">Imagen de fondo del certificado</p>
                          <p class="mb-0 small text-muted">
                            Selecciona el archivo que se usará como diseño base del certificado para este curso.
                          </p>
                        </div>
                      </div>
                      <div class="custom-file mb-2">
                        <input
                          type="file"
                          class="custom-file-input"
                          id="certificate_background"
                          name="certificate_background"
                          accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        >
                        <label class="custom-file-label" for="certificate_background">Seleccionar imagen (JPG o PNG)</label>
                      </div>
                      <p class="mb-0 small text-muted">
                        Esta imagen se almacenará en el servidor y se utilizará como fondo al generar los certificados en PDF.
                      </p>
                    </div>
                  </div>

                  <div class="alert alert-secondary">
                    <p class="mb-0 small">
                      Este formulario crea un registro en la tabla events de la base de datos qr_certificados.
                    </p>
                  </div>

                  <div class="text-right">
                    <button type="submit" class="btn btn-primary">
                      Guardar curso
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Carga masiva de cursos</h3>
                <span class="badge badge-light border ml-2">Alta masiva</span>
              </div>
              <div class="card-body">
                <p class="text-muted">
                  Registra varios cursos en bloque a partir de un archivo estructurado.
                </p>
                <form>
                  <div class="form-group">
                    <label>Archivo de cursos</label>
                    <div class="border border-secondary rounded p-3 bg-light">
                      <p class="small mb-2">
                        Formato sugerido: CSV o Excel con columnas como:
                      </p>
                      <ul class="small mb-3">
                        <li>name (nombre del curso)</li>
                        <li>event_type_code (curso, diplomado...)</li>
                        <li>modality_code (virtual, presencial...)</li>
                        <li>start_date y end_date</li>
                        <li>total_hours y credits</li>
                        <li>event_code (código interno)</li>
                        <li>certificate_background_filename (nombre del archivo de fondo)</li>
                      </ul>
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" id="courses_file">
                        <label class="custom-file-label" for="courses_file">Seleccionar archivo</label>
                      </div>
                      <p class="small text-muted mt-2 mb-0">
                        No se procesa todavía; esta sección es solo de diseño.
                      </p>
                    </div>
                  </div>

                  <div class="alert alert-secondary">
                    <p class="mb-1 small font-weight-bold">Notas de implementación futura</p>
                    <p class="mb-1 small">
                      El backend interpretará el archivo y creará registros en event_types, event_modalities y events con sus respectivos ID cuando corresponda.
                    </p>
                    <p class="mb-0 small">
                      Se podrán mostrar resúmenes de cuántos cursos se crearán, detectando duplicados por event_code antes de confirmar la importación.
                    </p>
                  </div>

                  <div class="text-right">
                    <button type="button" class="btn btn-primary">
                      Previsualizar e importar cursos
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

  <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>

<?php include __DIR__ . '/../partials/scripts.php'; ?>
<script>
  (function() {
    var input = document.getElementById('certificate_background');
    if (input) {
      input.addEventListener('change', function (e) {
        var fileName = e.target.files && e.target.files[0] ? e.target.files[0].name : '';
        var label = document.querySelector('label[for="certificate_background"].custom-file-label');
        if (label && fileName) {
          label.textContent = fileName;
        }
      });
    }
  })();
</script>
</body>
</html>