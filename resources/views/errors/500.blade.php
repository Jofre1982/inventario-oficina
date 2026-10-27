<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error 500 · {{ config('app.name') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #1e1e2f; color: #fff; font-family: 'Segoe UI', Arial, sans-serif; }
        .box { max-width: 520px; padding: 32px; text-align: center; }
        h1 { font-size: 56px; margin: 0; color: #e14eca; }
        p { color: rgba(255,255,255,.8); line-height: 1.5; }
        a { display: inline-block; margin-top: 16px; color: #fff; background: #e14eca; padding: 10px 22px; border-radius: 4px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <h1>500</h1>
        <p>Ocurrió un error inesperado. El detalle quedó guardado en storage\\logs\\laravel.log.</p>
        <a href="/home">Ir al inicio</a>
    </div>
</body>
</html>
