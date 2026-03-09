<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Constancia - No Encontrado</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo $baseUrl; ?>images/logo/icono.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden text-center p-8">
        <div class="mb-6 text-red-500">
            <i class="fas fa-times-circle text-6xl"></i>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Constancia No Encontrada</h1>
        <p class="text-gray-500 mb-6">El código de verificación proporcionado no corresponde a ninguna constancia válida en nuestro sistema.</p>
        
        <?php if (!empty($_GET['code'])): ?>
        <div class="bg-gray-100 p-3 rounded mb-6">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Código consultado</p>
            <p class="font-mono font-bold text-gray-700"><?php echo htmlspecialchars($_GET['code']); ?></p>
        </div>
        <?php endif; ?>

        <a href="<?php echo $baseUrl; ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full transition duration-300">
            Ir al Inicio
        </a>
    </div>

</body>
</html>
