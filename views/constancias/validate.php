<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Constancia - <?php echo htmlspecialchars($constancia['nombres'] . ' ' . $constancia['apellidos']); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo $baseUrl; ?>images/logo/icono.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .container-box { box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1); }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-3xl w-full bg-white rounded-2xl shadow-xl overflow-hidden relative container-box">
        <!-- Barra superior de estado -->
        <div class="bg-blue-600 h-2 w-full"></div>
        
        <div class="relative z-10 p-8 md:p-12">
            
            <!-- Encabezado -->
            <div class="text-center mb-10">
                <img src="<?php echo $baseUrl; ?>images/logo/logo.png" alt="Logo Institucional" class="h-20 mx-auto mb-6 object-contain">
                
                <div class="inline-flex items-center gap-2 bg-blue-100 text-blue-700 px-6 py-2 rounded-full font-bold text-sm uppercase tracking-wider mb-4 shadow-sm border border-blue-200">
                    <i class="fas fa-check-circle text-lg"></i> Constancia Válida
                </div>
                
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Resultado de Verificación</h1>
                <p class="text-gray-500 mt-2">La constancia consultada es auténtica y se encuentra registrada en nuestro sistema.</p>
            </div>

            <!-- Información Principal -->
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-6 md:p-8 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                    
                    <div class="col-span-1 md:col-span-2 border-b border-gray-200 pb-4 mb-2">
                        <label class="block text-xs font-semibold text-gray-500 tracking-wider mb-1">Otorgado a</label>
                        <p class="text-lg md:text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-user text-blue-600"></i>
                            <?php echo htmlspecialchars(mb_convert_case($constancia['nombres'] . ' ' . $constancia['apellidos'], MB_CASE_TITLE, "UTF-8")); ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-1 ml-6">
                            DNI/Documento: <?php echo htmlspecialchars($constancia['documento_identidad']); ?>
                        </p>
                    </div>

                    <div class="col-span-1 md:col-span-2 mb-2">
                        <label class="block text-xs font-semibold text-gray-500 tracking-wider mb-1">Evento / Curso</label>
                        <p class="text-base md:text-lg font-bold text-indigo-700">
                            <?php echo htmlspecialchars($constancia['evento_nombre']); ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha de Inicio</label>
                        <p class="text-gray-800 font-medium">
                            <i class="far fa-calendar-alt text-gray-400 mr-1"></i>
                            <?php echo date('d/m/Y', strtotime($constancia['fecha_inicio'])); ?>
                        </p>
                    </div>

                    <?php if (!empty($constancia['fecha_fin'])): ?>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha de Finalización</label>
                        <p class="text-gray-800 font-medium">
                            <i class="far fa-calendar-check text-gray-400 mr-1"></i>
                            <?php echo date('d/m/Y', strtotime($constancia['fecha_fin'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Código de Verificación</label>
                        <p class="font-mono text-gray-800 bg-gray-200 inline-block px-2 py-1 rounded text-sm">
                            <?php echo htmlspecialchars($constancia['codigo_verificacion']); ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha de Emisión</label>
                        <p class="text-gray-800 font-medium">
                            <?php echo date('d/m/Y H:i', strtotime($constancia['fecha_generacion'])); ?>
                        </p>
                    </div>
                    
                </div>
            </div>

        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-2 text-xs text-gray-500">
            <p>
                Copyright &copy; <?php echo date('Y'); ?> 
                <a href="https://www.linkedin.com/company/puertosystem/" target="_blank" class="text-indigo-600 hover:underline font-medium">Puerto System, S.A.</a>
            </p>
            <p>
                Desarrollado por: <a href="https://www.linkedin.com/in/norberto-ramirez/" target="_blank" class="text-gray-700 hover:text-indigo-600">Norberto Ramirez</a> & <a href="https://postsdigital.com/" target="_blank" class="text-gray-700 hover:text-indigo-600">POSTS Digital</a> - v<?php echo APP_VERSION; ?>
            </p>
        </div>
    </div>

</body>
</html>
