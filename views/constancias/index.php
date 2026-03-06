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
                                                    <a href="<?= htmlspecialchars($event['fondo_constancia']) ?>" target="_blank">Ver Fondo</a>
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
    <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
<script>
$(function() {
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
