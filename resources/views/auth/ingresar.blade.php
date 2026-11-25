<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Iniciar sesión | GotaVerde</title>
    <link rel="stylesheet" href="{{ asset('css/gotaverde.css') }}?v={{ filemtime(public_path('css/gotaverde.css')) }}">
</head>
<body class="gv-login">
    <div class="gv-login-layout">
        <aside class="gv-login-photo" aria-label="Bienvenida a GotaVerde">
            @include('partials.brand')
            <div class="gv-login-welcome">
                <p class="gv-login-welcome-title">Bienvenido de nuevo</p>
                <p>Continúa cuidando el agua de tu hogar y construye hábitos más sostenibles junto con tu familia.</p>
            </div>
            <dl class="gv-login-facts">
                <div>
                    @include('partials.icon', ['icon' => 'drop'])
                    <dt>Consumo promedio por persona al día en la CDMX</dt>
                    <dd>311 L</dd>
                </div>
                <div>
                    @include('partials.icon', ['icon' => 'leaf'])
                    <dt>Uso responsable recomendado</dt>
                    <dd>96 L</dd>
                </div>
            </dl>
        </aside>

        <main class="gv-login-main" id="contenido">
            <section class="gv-login-card" aria-labelledby="titular-ingresar">
                <div class="gv-login-content">
                    <h1 id="titular-ingresar">Iniciar sesión</h1>
                    <p class="gv-sub">Ingresa tus datos para acceder a tu cuenta.</p>
                    @include('partials.alerts')

                    <form method="POST" action="{{ route('login.store') }}" data-limpiar-formulario>
                        @csrf
                        <div class="gv-field">
                            <label for="email">Correo electrónico</label>
                            <div class="gv-login-input">
                                @include('partials.icon', ['icon' => 'mail'])
                                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror>
                            </div>
                        </div>
                        <div class="gv-field">
                            <label for="password">Contraseña</label>
                            <div class="gv-login-input gv-login-password">
                                @include('partials.icon', ['icon' => 'lock'])
                                <input id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
                                <button type="button" data-login-mostrar-clave aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false" hidden>@include('partials.icon', ['icon' => 'eye'])</button>
                            </div>
                        </div>
                        <div class="gv-login-links">
                            <label><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Recordarme</label>
                            <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                        </div>
                        <button class="gv-btn" type="submit">Iniciar sesión</button>
                    </form>

                    <div class="gv-login-separator" aria-hidden="true">@include('partials.icon', ['icon' => 'leaf'])</div>
                    <p class="gv-login-signup">¿No tienes cuenta?</p>
                    <a class="gv-btn alt" href="{{ route('register') }}">Crear una cuenta</a>
                </div>
            </section>
        </main>
    </div>
    <script>
        const botonClave = document.querySelector('[data-login-mostrar-clave]');
        const campoClave = document.getElementById('password');

        botonClave.hidden = false;
        botonClave.addEventListener('click', () => {
            const visible = campoClave.type === 'password';
            campoClave.type = visible ? 'text' : 'password';
            botonClave.setAttribute('aria-pressed', String(visible));
        });
    </script>
</body>
</html>
