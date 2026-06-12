<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Inesperado - Mercalocal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full text-center">
        <div class="w-24 h-24 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2">¡Ups! Algo salió mal.</h1>
        <p class="text-gray-600 mb-8">
            Hemos tenido un problema inesperado procesando tu petición. Nuestros técnicos ya han sido notificados.
        </p>
        <a href="<?= BASE_URL ?>/" class="inline-block bg-primary hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-xl shadow-sm transition-colors">
            Volver al inicio
        </a>
    </div>
</body>
</html>
