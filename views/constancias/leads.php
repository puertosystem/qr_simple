<?php include __DIR__ . '/../partials/head.php'; ?>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Participantes Registrados (Leads)</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                            <li class="breadcrumb-item active">Participantes</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Registros</h3>
                        <div class="card-tools">
                            <form action="index.php" method="get" class="form-inline">
                                <input type="hidden" name="page" value="constancias">
                                <input type="hidden" name="view" value="leads">
                                
                                <select name="evento_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                    <option value="">Todos los eventos</option>
                                    <?php foreach ($events as $event): ?>
                                        <option value="<?= $event['id'] ?>" <?= (isset($_GET['evento_id']) && $_GET['evento_id'] == $event['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($event['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input type="text" name="q" class="form-control float-right" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Documento</th>
                                    <th>Evento</th>
                                    <th>Celular</th>
                                    <th>Estado</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leads)): ?>
                                    <tr><td colspan="8" class="text-center">No se encontraron registros.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($leads as $lead): ?>
                                        <tr>
                                            <td><?= $lead['id'] ?></td>
                                            <td><?= htmlspecialchars($lead['nombres']) ?></td>
                                            <td><?= htmlspecialchars($lead['apellidos']) ?></td>
                                            <td><?= htmlspecialchars($lead['documento_identidad']) ?></td>
                                            <td>
                                                <?php if (!empty($lead['evento_nombre'])): ?>
                                                    <?= htmlspecialchars($lead['evento_nombre']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted text-xs">Sin evento asignado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($lead['celular']) ?></td>
                                            <td>
                                                <?php 
                                                $descargas = $lead['num_descargas'] ?? 0;
                                                $constanciaId = $lead['constancia_id'] ?? null;
                                                
                                                if ($descargas > 0) {
                                                    echo '<span class="badge badge-success">Descargada (' . $descargas . ')</span>';
                                                } elseif ($constanciaId) {
                                                    echo '<span class="badge badge-warning">Generada (0)</span>';
                                                } else {
                                                    echo '<span class="badge badge-secondary">Solo registro</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?= $lead['fecha_registro'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer clearfix">
                        <?php 
                        $queryParams = $_GET;
                        unset($queryParams['p']);
                        $queryString = http_build_query($queryParams);
                        ?>
                        <ul class="pagination pagination-sm m-0 float-right">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?<?= $queryString ?>&p=<?= $page - 1 ?>">&laquo;</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?<?= $queryString ?>&p=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?<?= $queryString ?>&p=<?= $page + 1 ?>">&raquo;</a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
    <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
