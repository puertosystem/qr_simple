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
                            <form action="index.php" method="get">
                                <input type="hidden" name="page" value="constancias">
                                <input type="hidden" name="view" value="leads">
                                <div class="input-group input-group-sm" style="width: 250px;">
                                    <input type="text" name="q" class="form-control float-right" placeholder="Buscar por nombre o DNI..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
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
                                    <th>Email</th>
                                    <th>Celular</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leads)): ?>
                                    <tr><td colspan="7" class="text-center">No se encontraron registros.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($leads as $lead): ?>
                                        <tr>
                                            <td><?= $lead['id'] ?></td>
                                            <td><?= htmlspecialchars($lead['nombres']) ?></td>
                                            <td><?= htmlspecialchars($lead['apellidos']) ?></td>
                                            <td><?= htmlspecialchars($lead['documento_identidad']) ?></td>
                                            <td><?= htmlspecialchars($lead['email']) ?></td>
                                            <td><?= htmlspecialchars($lead['celular']) ?></td>
                                            <td><?= $lead['fecha_registro'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer clearfix">
                        <ul class="pagination pagination-sm m-0 float-right">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=constancias&view=leads&p=<?= $i ?>&q=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
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
