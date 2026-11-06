<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicio no disponible | Citas HSJ</title>
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">
    <main class="max-w-xl rounded-2xl bg-white p-8 shadow-sm border border-slate-200">
        <h1 class="text-2xl font-bold text-slate-900">Servicio temporalmente no disponible</h1>
        <p class="mt-3 text-slate-600">{{ $message }}</p>
        <p class="mt-4 text-sm text-slate-500">Código de soporte: <strong>{{ $reference }}</strong></p>
        <a class="mt-6 inline-flex rounded-lg bg-blue-700 px-4 py-2 text-white" href="{{ route('appointments.public.index') }}">Volver al inicio</a>
    </main>
</body>
</html>
