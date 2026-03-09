<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (isset($selectedEvent) && $selectedEvent) ? htmlspecialchars($selectedEvent['nombre']) : 'Registro de Constancia' ?></title>
    <link rel="icon" type="image/png" href="images/logo/icono.png">
    
    <!-- Using AdminLTE (BS4) as base -->
    <link rel="stylesheet" href="assets/lte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/constancias-public.css">
</head>
<body>

  <header class="hero">
    <img src="assets/img/fondo/IMG_6102.PNG" class="hero-bg" alt="Fondo" style="width: 100%; height: 100vh; object-fit: cover;">
    <div class="hero-overlay"></div>
    
    <div class="container hero-content">
      <div class="row align-items-center" style="min-height: 100vh;">
        
        <!-- Form Column (Moved to Left) -->
        <div class="col-lg-4 col-md-6 ml-5">
            <div class="bg-white p-4 rounded-4 shadow-lg my-5">
                <h4 class="font-weight-bold text-primary mb-4 text-center">Registro de Participantes</h4>
                
                <!-- Messages -->
                <?php if (isset($_SESSION['public_success'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['public_success'] ?>
                    </div>
                    <?php unset($_SESSION['public_success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['public_error'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['public_error'] ?>
                    </div>
                    <?php unset($_SESSION['public_error']); ?>
                <?php endif; ?>

                <?php if (isset($selectedEvent) && $selectedEvent): ?>
                    <div class="alert alert-info text-center mb-4">
                        <h6 class="mb-0 font-weight-bold">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <?= htmlspecialchars($selectedEvent['nombre']) ?>
                        </h6>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form action="index.php?page=constancias&action=register_lead" method="post" id="registerForm">
                    
                    <?php if (isset($selectedEvent) && $selectedEvent): ?>
                        <input type="hidden" name="evento_id" value="<?= $selectedEvent['id'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="evento_id" id="evento_id_hidden" value="">
                        <div class="form-group mb-3">
                            <label>Seleccione el Evento:</label>
                            <select class="form-control soft" name="evento_id_select" id="evento_id_select" onchange="updateEventId(this)" required>
                                <option value="">-- Seleccione un evento --</option>
                                <?php if (isset($events) && !empty($events)): ?>
                                    <?php foreach ($events as $evt): ?>
                                        <option value="<?= $evt['id'] ?>"><?= htmlspecialchars($evt['nombre']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <label>Nombres:</label>
                        <input type="text" name="nombres" class="form-control soft uppercase-input" placeholder="Nombres completos" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Apellidos:</label>
                        <input type="text" name="apellidos" class="form-control soft uppercase-input" placeholder="Apellidos completos" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Documento de Identidad:</label>
                        <input type="text" name="documento_identidad" class="form-control soft" placeholder="DNI/Cédula" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Correo Electrónico:</label>
                        <input type="email" name="email" class="form-control soft" placeholder="ejemplo@correo.com" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Celular:</label>
                        <input type="text" name="celular" class="form-control soft" placeholder="999..." maxlength="15" required>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block btn-lg rounded-pill shadow-sm">Registrarme</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
      </div>
      
      <!-- Footer Credits (Outside Form) -->
      <div class="position-absolute w-100 text-center text-white" style="bottom: 20px; left: 0; z-index: 3;">
        <p class="mb-0" style="font-size: 0.8rem; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">
            Copyright &copy; <?php echo date('Y'); ?> <a href="https://www.linkedin.com/company/puertosystem/" target="_blank" class="text-white font-weight-bold">Puerto System, S.A.</a> | 
            Desarrollado por: <a href="https://www.linkedin.com/in/norberto-ramirez/" target="_blank" class="text-white font-weight-bold">Norberto Ramirez</a> & <a href="https://postsdigital.com/" target="_blank" class="text-white font-weight-bold">POSTS Digital</a> - v<?php echo APP_VERSION; ?>
        </p>
      </div>
    </div>
  </header>

<script src="assets/lte/plugins/jquery/jquery.min.js"></script>
<script src="assets/lte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/lte/dist/js/adminlte.min.js"></script>
<script src="assets/js/constancias-public.js"></script>
</body>
</html>