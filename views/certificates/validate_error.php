<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Error'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo isset($baseUrl) ? rtrim($baseUrl, '/') : '/qr_simple'; ?>/images/logo/icono.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-md w-full text-center border-t-4 <?php echo $borderColor ?? 'border-red-500'; ?>">
        <div class="<?php echo $iconColor ?? 'text-red-500'; ?> text-5xl mb-4"><i class="fas <?php echo $iconClass ?? 'fa-exclamation-triangle'; ?>"></i></div>
        <h1 class="text-xl font-bold text-gray-800 mb-2"><?php echo $errorTitle ?? 'Error'; ?></h1>
        <p class="text-gray-600 mb-6"><?php echo $errorMessage ?? 'Ocurrió un error inesperado.'; ?></p>
        <a href="https://psicoingenioconsultora.com" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition">Volver al Inicio</a>
    </div>
</body>
</html>
