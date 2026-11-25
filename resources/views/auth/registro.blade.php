<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Crear cuenta | GotaVerde</title>
    <link rel="stylesheet" href="{{ asset('css/gotaverde.css') }}?v={{ filemtime(public_path('css/gotaverde.css')) }}">
</head>
<body class="gv-register">
    <div class="gv-register-layout">
        <div class="gv-register-photo" aria-hidden="true"></div>
        <main class="gv-register-main" id="contenido">
            <div class="gv-register-content">
                @include('partials.brand')

                <header class="gv-register-heading">
                    <h1>Crear cuenta</h1>
                    <p>Comienza a cuidar el agua de tu hogar.</p>
                </header>

                @include('partials.alerts')

                <form method="POST" action="{{ route('register.store') }}" data-limpiar-formulario>
                    @csrf
                    <div class="gv-field">
                        <label for="name">Nombre</label>
                        <div class="gv-register-input">
                            @include('partials.icon', ['icon' => 'user'])
                            <input id="name" name="name" autocomplete="name" maxlength="120" required value="{{ old('name') }}" placeholder="Ingresa tu nombre" @error('name') aria-invalid="true" @enderror>
                        </div>
                    </div>

                    <div class="gv-field">
                        <label for="email">Correo electrónico</label>
                        <div class="gv-register-input">
                            @include('partials.icon', ['icon' => 'mail'])
                            <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" placeholder="correo@ejemplo.com" @error('email') aria-invalid="true" @enderror>
                        </div>
                    </div>

                    <div class="gv-field">
                        <label for="password">Contraseña</label>
                        <div class="gv-register-input gv-register-password">
                            @include('partials.icon', ['icon' => 'lock'])
                            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required placeholder="Crea una contraseña" aria-describedby="registro-minimo" @error('password') aria-invalid="true" @enderror>
                            <button type="button" data-mostrar-clave aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false" hidden>@include('partials.icon', ['icon' => 'eye'])</button>
                        </div>
                        <small id="registro-minimo" class="gv-label">Mínimo 8 caracteres.</small>
                    </div>

                    <div class="gv-field">
                        <label for="password_confirmation">Confirmar contraseña</label>
                        <div class="gv-register-input gv-register-password">
                            @include('partials.icon', ['icon' => 'lock'])
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required placeholder="Repite tu contraseña" @error('password_confirmation') aria-invalid="true" @enderror>
                            <button type="button" data-mostrar-clave aria-controls="password_confirmation" aria-label="Mostrar confirmación de contraseña" aria-pressed="false" hidden>@include('partials.icon', ['icon' => 'eye'])</button>
                        </div>
                    </div>

                    <button class="gv-btn" type="submit">Crear cuenta</button>
                </form>

                <div class="gv-login-separator" aria-hidden="true">@include('partials.icon', ['icon' => 'leaf'])</div>
                <p class="gv-register-return">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
            </div>
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-mostrar-clave]').forEach((boton) => {
            const campo = document.getElementById(boton.getAttribute('aria-controls'));
            boton.hidden = false;
            boton.addEventListener('click', () => {
                const visible = campo.type === 'password';
                campo.type = visible ? 'text' : 'password';
                boton.setAttribute('aria-pressed', String(visible));
            });
        });
    </script>
</body>
</html>
