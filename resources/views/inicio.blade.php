<!doctype html>
<html lang="es">

<head>
    <title>Inicio de sesión</title>

    {{-- estilos --}}
    <link href="{{ asset('plugins/bootstrap5/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('plugins/fontawesome5/css/all.min.css') }}" rel="stylesheet">

    <style @cspNonce>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #E3F2FD;
        }

        .card-login {
            max-width: 850px;
            margin: auto;
            border-radius: 15px;
        }

        .logo-text {
            color: #f8f9fa;
            font-size: 28px;
            font-weight: bold;
        }

        .custom-input {
            border-radius: 10px;
            padding: 12px;
        }

        .btn-login {
            background-color: #02183A;
            color: #fff;
            border-radius: 10px;
            font-size: 16px;
            padding: 12px;
            width: 100%;
            transition: 0.3s;
        }

        .btn-login:hover {
            background-color: #042a63;
        }

        .bg-left {
            background-color: #042a63;
            border-top-left-radius: 15px;
            border-bottom-left-radius: 15px;
        }

        .bg-left img {
            width: 150px;
        }

        .fondo-app {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("{{ asset('AutentificaTic/images/fondo.png') }}") no-repeat center center fixed;
            background-size: cover;
            overflow: hidden;
        }

        .contenedor-centro {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
            max-width: 600px;
            padding: 0 20px;
            color: white;
        }
    </style>

    <script @cspNonce>
        document.addEventListener("keydown", function(e) {
            if (e.key === "F12") e.preventDefault();
            if (e.ctrlKey && e.shiftKey && e.key === "I") e.preventDefault();
            if (e.ctrlKey && e.shiftKey && e.key === "C") e.preventDefault();
            if (e.ctrlKey && e.key === "U") e.preventDefault();
            if (e.ctrlKey && e.key === "S") e.preventDefault();
        });

        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
    </script>

    {{-- scripts --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script> --}}
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script @cspNonce>
        var access_token = '';

        function iniciar_sesion() {
            document.getElementById('btnSesion').disabled = true;

            axios.post('http://autentificaticapi.carabineros.cl/api/auth/login', {
                rut: document.getElementById('rut_funcionario').value,
                password: document.getElementById('clave_intranet').value,
                website: 'http://pacdilocar.carabineros.cl/'
            }).then(response => {

                access_token = response.data.success.access_token;

                // Insertar token y rut en los inputs ocultos para enviarlos al backend Laravel (el backend emitirá la cookie HttpOnly segura)
                document.getElementById('accessToken').value = access_token;
                document.getElementById('rutHidden').value = document.getElementById('rut_funcionario').value;

                // Mandar formulario al controlador Laravel
                document.getElementById('formLogin').action = "{{ route('login.api') }}";
                document.getElementById('formLogin').submit();

            }).catch(error => {

                if (error.response?.data?.errors) {
                    alert(error.response.data.errors.rut ?? error.response.data.errors.password ??
                        "Error de autenticación");
                } else {
                    alert("Error inesperado al autenticar");
                }

                window.location.reload();
            });
        }

        // Mostrar contraseña
        function hideOrShowPassword() {
            let pass = document.getElementById("clave_intranet");
            pass.type = pass.type === "password" ? "text" : "password";
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('btnSesion').addEventListener('click', iniciar_sesion);
            document.getElementById('ver').addEventListener('change', hideOrShowPassword);
        });
    </script>

</head>

<body>

    <div class="container">
        <div class="row vh-100 d-flex align-items-center">
            <div class="col-md-8 mx-auto">

                <div class="card shadow card-login">
                    <div class="row g-0">
                        <div
                            class="col-md-6 bg-left d-flex flex-column justify-content-center align-items-center text-center">
                            <img src="{{ asset('AutentificaTic/images/carabineros.png') }}" alt="Logo">
                            <h2 class="text-white mt-3">Sistema de Control del Plan Anual de Compras</h2>
                        </div>
                        <div class="col-md-6 p-4">
                            <form id="formLogin" name="form1" method="POST" action="{{ route('login.api') }}">
                                @csrf
                                <div class="fondo-app">
                                    <div class="contenedor-centro">
                                        <h3 class="logo-text mb-3">DIRECCIÓN DE LOGÍSTICA</h3>
                                        <p class="logo-text mb-4">Sistema Plan Anual de Compras</p>
                                         <img src="{{ asset('AutentificaTic/images/carabineros.png') }}" class="logo"
                                            height="100px"; width="100px">


                                        <h4 class="logo-text mb-3">Inicio de Sesión</h4>

                                        <div class="mb-3">
                                            <label for="rut_funcionario" class="form-label fw-bold">RUT</label>
                                            <input type="text" id="rut_funcionario" name="rut_funcionario_visible"
                                                class="form-control custom-input"
                                                placeholder="Ej: Sin punto ni guión 12345678K" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="clave_intranet" class="form-label fw-bold">Contraseña</label>
                                            <input type="password" id="clave_intranet" name="clave_intranet"
                                                class="form-control custom-input"
                                                placeholder="Ingrese su clave Intranet" required>
                                        </div>

                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="ver"
                                                >
                                            <label class="form-check-label" for="ver">Mostrar contraseña</label>
                                        </div>
                                        <input type="hidden" name="rut_funcionario" id="rutHidden">
                                        <input type="hidden" name="accessToken" id="accessToken">

                                        @if ($errors->any())
                                        <div class="alert alert-danger mb-3" style="border-radius: 10px;">
                                            {{ $errors->first('msg') ?? $errors->first() }}
                                        </div>
                                        @endif

                                        @if (session('info'))
                                        <div class="alert alert-warning mb-3" style="border-radius: 10px;">
                                            {{ session('info') }}
                                        </div>
                                        @endif

                                        <button type="button" id="btnSesion" class="btn-login">
                                            Iniciar Sesión
                                        </button>
                                        <br>
                                        <br>

                                        <div style="float: left;">
                                            <a href="http://autentificatic.carabineros.cl/password/reset"
                                                style="width: 50%; color: white;">¿Olvidaste tu contraseña?</a>
                                        </div>

                                        <div style="float: right;">
                                            <a href="http://autentificatic.carabineros.cl/register"
                                                style="width: 50%; color: white;">Registrate en autentificatic</a>
                                        </div>

                                        <div style="clear: both; padding-bottom: 15px;"></div>
                                        <p style="color: black; font-size: 15px;"><strong>Diseñado por ARM - Asesoría
                                                Técnica Dilocar - Mesa de ayuda IP-26407</strong></p>
                                        <br>
                                        <br>
                                        <p style="color: rgb(121, 45, 45); font-size: 15px;"><strong>Sistema con
                                                validación AUTENTIFICATIC</strong></p>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>
