<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Mantenimiento · Estación Radial</title>
    <style>
        :root { color-scheme: light; font-family: Inter, system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: radial-gradient(circle at top, #fff 0, #f4f5f8 60%, #e8eaf0 100%); color: #17191d; }
        main { width: min(620px, 100%); padding: clamp(28px, 6vw, 56px); border-radius: 28px; background: #fff; box-shadow: 0 28px 80px rgba(18, 22, 33, .14); border-top: 6px solid #c91725; }
        small { color: #c91725; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { font-size: clamp(2rem, 6vw, 3.5rem); margin: 12px 0; line-height: 1; }
        p { color: #5d6472; line-height: 1.7; }
    </style>
</head>
<body>
<main>
    <small>Estación Radial</small>
    <h1>Volvemos pronto</h1>
    <p>{{ $settings['message'] }}</p>
    @if ($settings['return_at'])
        <p><strong>Retorno estimado:</strong> {{ $settings['return_at'] }}</p>
    @endif
</main>
</body>
</html>
