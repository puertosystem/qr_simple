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
                        <h1>Crear Nuevo Evento</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php?page=constancias">Eventos</a></li>
                            <li class="breadcrumb-item active">Crear</li>
                        </ol>
                    </div>
                </div>
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
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Datos del Evento</h3>
                    </div>
                    <form action="index.php?page=constancias&action=store_event" method="post" enctype="multipart/form-data">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nombre del Evento</label>
                                <input type="text" name="nombre" class="form-control" required placeholder="Ej: Webinar de Seguridad">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fecha Inicio</label>
                                        <input type="date" name="fecha_inicio" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fecha Fin (Opcional)</label>
                                        <input type="date" name="fecha_fin" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Fondo de Constancia (Imagen)</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" name="fondo_constancia" class="custom-file-input" id="customFile" accept="image/*">
                                        <label class="custom-file-label" for="customFile">Elegir archivo</label>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Se recomienda una imagen en formato JPG o PNG de alta resolución.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Guardar Evento</button>
                            <a href="index.php?page=constancias" class="btn btn-default float-right">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
    <?php include __DIR__ . '/../partials/footer.php'; ?>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
<script src="assets/lte/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
$(function () {
  bsCustomFileInput.init();
});
</script>
</body>
</html>
