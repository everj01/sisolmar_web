<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documento no encontrado</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 40px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            max-width: 420px;
            width: 90%;
        }
        .icon {
            width: 72px; height: 72px;
            background: #FFF3E0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
        }
        h2 {
            color: #1a1a2e;
            font-size: 20px;
            margin-bottom: 12px;
        }
        p {
            color: #888;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-block;
            background: #1a1a2e;
            color: #fff;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .btn:hover { opacity: 0.85; color: #fff; }
        .code {
            display: inline-block;
            background: #f4f6f9;
            color: #555;
            border-radius: 6px;
            padding: 2px 10px;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📄</div>
        <h2>Documento no encontrado</h2>
        <p>{!! $mensaje !!}</p>
      
        <br>
        <span class="code">Error 404</span>
    </div>
</body>
</html>