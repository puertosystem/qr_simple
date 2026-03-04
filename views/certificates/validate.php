<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Certificado - <?php echo htmlspecialchars($certificate['nombres_apellidos']); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo $baseUrl; ?>/images/logo/icono.png">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/validate.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-3xl w-full bg-white rounded-2xl shadow-xl overflow-hidden relative container-box">
        <!-- Barra superior de estado -->
        <div class="bg-green-600 h-2 w-full"></div>
        
        <!-- Marca de agua -->
       
        <div class="relative z-10 p-8 md:p-12">
            
            <!-- Encabezado -->
            <div class="text-center mb-10">
                <img src="<?php echo $baseUrl; ?>/images/logo/logo.png" alt="Logo Institucional" class="h-20 mx-auto mb-6 object-contain">
                
                <div class="inline-flex items-center gap-2 bg-green-100 text-green-700 px-6 py-2 rounded-full font-bold text-sm uppercase tracking-wider mb-4 shadow-sm border border-green-200">
                    <i class="fas fa-check-circle text-lg"></i> Certificado Válido
                </div>
                
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Resultado de Verificación</h1>
                <p class="text-gray-500 mt-2">El documento consultado es auténtico y se encuentra registrado en nuestro sistema.</p>
            </div>

            <!-- Información Principal -->
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 md:p-8 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                    
                    <div class="col-span-1 md:col-span-2 border-b border-gray-200 pb-4 mb-2">
                        <label class="block text-xs font-semibold text-gray-500 tracking-wider mb-1">Otorgado a</label>
                        <p class="text-lg md:text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-user-graduate text-green-600"></i>
                            <?php echo htmlspecialchars(mb_convert_case($certificate['nombres_apellidos'], MB_CASE_TITLE, "UTF-8")); ?>
                        </p>
                    </div>

                    <div class="col-span-1 md:col-span-2 mb-2">
                        <label class="block text-xs font-semibold text-gray-500 tracking-wider mb-1">Curso / Programa</label>
                        <p class="text-base md:text-lg font-bold text-indigo-700">
                            <?php echo htmlspecialchars(mb_convert_case($certificate['nombre_curso'], MB_CASE_TITLE, "UTF-8")); ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha de Inicio</label>
                        <p class="text-gray-800 font-medium">
                            <i class="far fa-calendar-alt text-gray-400 mr-1"></i>
                            <?php echo date('d/m/Y', strtotime($certificate['fecha_inicio'])); ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha de Finalización</label>
                        <p class="text-gray-800 font-medium">
                            <i class="far fa-calendar-check text-gray-400 mr-1"></i>
                            <?php echo date('d/m/Y', strtotime($certificate['fecha_fin'])); ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Duración</label>
                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($certificate['duracion']); ?></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Horas Académicas</label>
                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($certificate['horas_academicas']); ?> Horas</p>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Créditos</label>
                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($certificate['creditos_academicos']); ?></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Auspicios</label>
                        <div class="text-gray-800 font-medium text-sm">
                            <?php 
                            if (!empty($certificate['auspicios'])) {
                                $auspicios = explode('|', $certificate['auspicios']);
                                foreach ($auspicios as $auspicio) {
                                    echo '<div>' . htmlspecialchars(trim($auspicio)) . '</div>';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Metadatos de Verificación -->
            <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-500 border-t border-gray-100 pt-6 gap-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock text-gray-400"></i>
                    <span>Verificado el: <strong><?php date_default_timezone_set('America/Lima'); echo date('d/m/Y H:i:s'); ?></strong></span>
                </div>
                <div class="flex items-center gap-2 bg-gray-100 px-3 py-1 rounded text-xs font-mono">
                    <i class="fas fa-fingerprint text-gray-400"></i>
                    <span>ID: <?php echo htmlspecialchars($certificate['qr_codigo']); ?></span>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-4 no-print">
                <button onclick="window.print()" class="flex items-center justify-center gap-2 bg-gray-800 hover:bg-gray-900 text-white py-3 px-4 rounded-xl font-semibold transition-all transform hover:-translate-y-0.5 shadow-md">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                
                <!-- Botón Descargar -->
                <a href="<?php echo $baseUrl; ?>/index.php?page=download_certificate&code=<?php echo urlencode($code); ?>" class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-4 rounded-xl font-semibold transition-all transform hover:-translate-y-0.5 shadow-md">
                    <i class="fas fa-download"></i> Descargar PDF
                </a>
               

                <a href="https://psicoingenioconsultora.com" target="_blank" class="flex items-center justify-center gap-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 py-3 px-4 rounded-xl font-semibold transition-all transform hover:-translate-y-0.5 shadow-sm">
                    <i class="fas fa-home"></i> Ir al Inicio
                </a>
            </div>

        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-2 text-xs text-gray-500">
            <p>
                Copyright &copy; <?php echo date('Y'); ?> 
                <a href="https://www.linkedin.com/company/puertosystem/" target="_blank" class="text-indigo-600 hover:underline font-medium">Puerto System, S.A.</a>
            </p>
            <p>
                Desarrollado por: <a href="https://www.linkedin.com/in/norberto-ramirez/" target="_blank" class="text-gray-700 hover:text-indigo-600">Norberto Ramirez</a> & <a href="https://postsdigital.com/" target="_blank" class="text-gray-700 hover:text-indigo-600">POSTS Digital</a> - v1.0.8
            </p>
        </div>
    </div>

</body>
</html>
