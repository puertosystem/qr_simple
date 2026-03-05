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
            <h1 class="m-0 text-dark">Listado de cursos y certificados</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
              <li class="breadcrumb-item active">Certificados</li>
            </ol>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="callout callout-info py-3">
              <p class="mb-1">
                Busca un curso, revisa su información básica y genera los códigos QR de certificados para todas las personas inscritas en ese programa.
              </p>
              <span class="badge badge-primary mr-1">Validación: /aulavirtual/certificate/validate/(CODIGO QR)</span>
              <span class="badge badge-success mr-1">Generación de códigos QR activa</span>
            </div>
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
          <div class="col-lg-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Buscador de cursos</h3>
              </div>
              <div class="card-body">
                <form method="get" action="index.php">
                  <input type="hidden" name="page" value="certificates">
                  <div class="form-row align-items-end">
                    <div class="form-group col-md-6">
                      <label for="search_term">Buscar cursos</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input
                          type="text"
                          class="form-control"
                          id="search_term"
                          name="search_term"
                          placeholder="Buscar por nombre o código interno"
                          value="<?php echo isset($searchTerm) ? htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') : ''; ?>"
                        >
                      </div>
                    </div>
                    <div class="form-group col-md-3">
                      <label for="filter_type">Tipo de programa</label>
                      <select id="filter_type" name="filter_type" class="form-control">
                        <?php
                          $currentType = isset($filterType) ? $filterType : '';
                        ?>
                        <option value="">Todos</option>
                        <option value="curso" <?php echo $currentType === 'curso' ? 'selected' : ''; ?>>Cursos</option>
                        <option value="diplomado" <?php echo $currentType === 'diplomado' ? 'selected' : ''; ?>>Diplomados</option>
                        <option value="taller" <?php echo $currentType === 'taller' ? 'selected' : ''; ?>>Talleres</option>
                        <option value="webinar" <?php echo $currentType === 'webinar' ? 'selected' : ''; ?>>Webinars</option>
                      </select>
                    </div>
                    <div class="form-group col-md-3">
                      <label for="filter_state">Estado</label>
                      <select id="filter_state" name="filter_state" class="form-control">
                        <?php
                          $currentState = isset($filterState) ? $filterState : '';
                        ?>
                        <option value="">Todos</option>
                        <option value="active" <?php echo $currentState === 'active' ? 'selected' : ''; ?>>En curso o próximos</option>
                        <option value="completed" <?php echo $currentState === 'completed' ? 'selected' : ''; ?>>Finalizados</option>
                      </select>
                    </div>
                  </div>
                  <div class="text-right">
                    <button type="submit" class="btn btn-outline-primary">
                      Aplicar filtros
                    </button>
                  </div>
                </form>
                <p class="small text-muted mb-0">
                  El buscador aplica filtros sobre la tabla events usando nombre, código interno, tipo y estado.
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Listado de cursos</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                  <tr>
                    <th>Curso o programa</th>
                    <th>Fechas</th>
                    <th>Tipo y modalidad</th>
                    <th>Participantes inscritos</th>
                    <th>Validación QR</th>
                    <th>Estado</th>
                    <th class="text-right">Certificados</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                      <?php
                        $statusBadgeClass = 'badge-secondary';
                        $statusLabel = $course['status'] === 'completed' ? 'Finalizado' : 'En curso';
                        if ($course['status'] === 'active') {
                            $statusBadgeClass = 'badge-success';
                        }
                        $total = (int) $course['total_enrollments'];
                        $active = (int) $course['active_enrollments'];
                        $completed = (int) $course['completed_enrollments'];
                        // $certCount = (int) $course['certificates_count']; // Ya no se usa directamente para la vista

                        // Usar el conteo real de participantes SIN certificado (calculado en la consulta)
                        $withoutCert = isset($course['pending_qr_count']) ? (int) $course['pending_qr_count'] : 0;
                        
                        // Calcular "Con QR" basado en los participantes que SÍ tienen certificado (Total - Sin Certificado)
                        $withCert = $total - $withoutCert;
                        if ($withCert < 0) $withCert = 0;
                      ?>
                      <tr>
                        <td>
                          <strong>
                            <?php echo htmlspecialchars($course['name'], ENT_QUOTES, 'UTF-8'); ?>
                          </strong>
                          <p class="mb-0 small text-muted">
                            Código: <span class="font-weight-bold"><?php echo htmlspecialchars($course['event_code'], ENT_QUOTES, 'UTF-8'); ?></span>
                          </p>
                          <p class="mb-0 small text-muted">
                            ID curso: <span class="font-weight-bold"><?php echo htmlspecialchars($course['id'], ENT_QUOTES, 'UTF-8'); ?></span>
                          </p>
                        </td>
                        <td>
                          <p class="mb-0 small">
                            Inicio:
                            <?php echo $course['start_date'] ? date('d/m/Y', strtotime($course['start_date'])) : 'Sin definir'; ?>
                          </p>
                          <p class="mb-0 small">
                            Fin:
                            <?php echo $course['end_date'] ? date('d/m/Y', strtotime($course['end_date'])) : 'Sin definir'; ?>
                          </p>
                        </td>
                        <td>
                          <p class="mb-0 small">
                            Tipo: <?php echo htmlspecialchars($course['type_name'], ENT_QUOTES, 'UTF-8'); ?>
                          </p>
                          <p class="mb-0 small">
                            Modalidad: <?php echo $course['modality_name'] ? htmlspecialchars($course['modality_name'], ENT_QUOTES, 'UTF-8') : 'Sin definir'; ?>
                          </p>
                        </td>
                        <td>
                          <p class="mb-0 small font-weight-bold">
                            <?php echo $total; ?> participantes
                          </p>
                          <p class="mb-0 small text-muted">
                            Activos: <?php echo $active; ?> · Completados: <?php echo $completed; ?>
                          </p>
                        </td>
                        <td>
                          <p class="mb-0 small text-success">
                            <i class="fas fa-check-circle"></i> Con QR: <strong><?php echo $withCert; ?></strong>
                          </p>
                          <p class="mb-0 small text-danger">
                            <i class="fas fa-times-circle"></i> Sin QR: <strong><?php echo $withoutCert; ?></strong>
                          </p>
                        </td>
                        <td>
                          <span class="badge <?php echo $statusBadgeClass; ?>"><?php echo $statusLabel; ?></span>
                        </td>
                        <td class="text-right">
                          <form method="post" action="index.php?page=certificates" class="d-inline">
                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($course['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="generate_qr">
                            <button type="submit" class="btn btn-outline-primary btn-sm mb-2">
                              Generar QR
                            </button>
                          </form>
                          <form method="post" action="index.php?page=certificates" class="d-inline">
                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($course['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="download_pdfs">
                            <button type="submit" class="btn btn-outline-secondary btn-sm mb-2">
                              Descargar certificados
                            </button>
                          </form>
                          <p class="mb-0 small text-muted">
                            Genera códigos únicos por matrícula y los almacena en la tabla certificates.
                          </p>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted">
                        No se encontraron cursos con los filtros aplicados.
                      </td>
                    </tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
              <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
              <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                  <?php
                    $queryParams = $_GET;
                    unset($queryParams['p']);
                    $baseUrl = 'index.php?' . http_build_query($queryParams);
                    $currentPage = $pagination['current_page'];
                    $totalPages = $pagination['total_pages'];
                    
                    // Previous
                    if ($currentPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&p=' . ($currentPage - 1) . '">&laquo;</a></li>';
                    } else {
                        echo '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>';
                    }

                    // Pages
                    // Simple logic: show all or window. For now, simple window.
                    $start = max(1, $currentPage - 2);
                    $end = min($totalPages, $currentPage + 2);
                    
                    if ($start > 1) {
                         echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&p=1">1</a></li>';
                         if ($start > 2) {
                             echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                         }
                    }

                    for ($i = $start; $i <= $end; $i++) {
                        $activeClass = ($i === $currentPage) ? 'active' : '';
                        echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . $baseUrl . '&p=' . $i . '">' . $i . '</a></li>';
                    }

                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) {
                             echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                    }

                    // Next
                    if ($currentPage < $totalPages) {
                        echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&p=' . ($currentPage + 1) . '">&raquo;</a></li>';
                    } else {
                        echo '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>';
                    }
                  ?>
                </ul>
              </div>
              <?php endif; ?>
              <div class="card-footer">
                <p class="mb-1 small text-muted">
                  La tabla se alimenta desde events, event_enrollments y certificates, contando automáticamente participantes por curso.
                </p>
                <p class="mb-0 small text-muted">
                  La generación de certificados crea registros en la tabla certificates que almacenan el código QR por matrícula.
                </p>
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
</body>
</html>
