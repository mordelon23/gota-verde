<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') | GotaVerde</title>
    <link rel="stylesheet" href="{{ asset('css/gotaverde.css') }}">
</head>
<body>
    <main class="gv-error-page">
        @include('partials.brand')
        <section class="gv-card gv-auth-form">
            @yield('contenido')
        </section>
    </main>
</body>
</html>
