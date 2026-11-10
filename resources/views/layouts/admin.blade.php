<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plan Anual de Compras - Dilocar</title>

    
    <link rel="stylesheet" href="{{ url('plugins/source-sans-pro/source-sans-pro.css') }}">
    
    <link rel="stylesheet" href="{{ url('plugins/fontawesome-free/css/all.min.css') }}">
    
    <link rel="stylesheet" href="{{ url('dist/css/adminlte.min.css') }}">
    
    <link rel="stylesheet" href="{{ url('plugins/bootstrap-icons/bootstrap-icons.min.css') }}">
    
    <script src="{{ url('plugins/jquery/jquery.min.js') }}"></script>

    
    <script src="{{ url('plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>

    
    <link rel="stylesheet" href="{{ url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ url('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ url('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">

    <style @cspNonce>
        .wrapper {
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
        }
        .content-wrapper {
            flex: 1 0 auto !important;
            height: auto !important;
            min-height: calc(100vh - 7.5rem) !important;
            padding-bottom: 1.5rem;
        }
        .main-footer {
            flex-shrink: 0;
        }
    </style>

    @stack('styles')
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        {{-- Navbar --}}
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            {{-- Left navbar links --}}
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>

                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{url('dashboard/') }}" class="nav-link">Sistema Plan Anual de Compras - DIRECCIÓN DE
                        LOGÍSTICA</a>
                </li>
            </ul>

            {{-- Right navbar links --}}
            <ul class="navbar-nav ml-auto">
                {{-- Navbar Search --}}


                {{-- Messages Dropdown Menu --}}

                {{-- Notifications Dropdown Menu --}}

                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                        <i class="fas fa-th-large"></i>
                    </a>
                </li>
            </ul>
        </nav>
        {{-- /.navbar --}}

        {{-- Main Sidebar Container --}}
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            {{-- Brand Logo --}}
            <a href="{{ url('dashboard/') }}" class="brand-link">
                <img src="{{ url('dist/img/carabineros.png') }}" alt="Logo Carabineros" class="brand-image elevation-3"
                    style="width: 30px; height: auto;">
                <span class="brand-text font-weight-light">Pac-Dilocar</span>
            </a>



            {{-- Sidebar --}}
            <div class="sidebar">
                {{-- Sidebar user panel (optional) --}}
                <div class="user-panel mt-3 pb-3 mb-3 d-flex flex-column align-items-center text-center">
                    {{-- 
                    <div class="mb-2">
                        @if(session('foto_perfil'))
                            <img src="{{ session('foto_perfil') }}"
                                 alt="Foto de perfil"
                                 class="img-circle elevation-2"
                                 style="width:70px; height:70px; object-fit:cover;">
                        @else
                            <div class="img-circle elevation-2 d-flex align-items-center justify-content-center"
                                 style="width:70px; height:70px; background-color:#4a5568;">
                                <i class="fas fa-user" style="font-size:36px; color:#cbd5e0;"></i>
                            </div>
                        @endif
                    </div>
                    --}}
                    <div class="info">
                        <h4 style="font-size: 14px; color:aliceblue; margin-bottom: 0;">Perfil de Usuario:</h4>
                        <a href="#" class="d-block" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                            {{ Auth::user()->getRoleNames()->first() }}</a>
                        @if(session('grado'))
                            <a href="#" class="d-block" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                {{ session('grado') }}</a>
                        @endif
                        <a href="#" class="d-block" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                            {{ session('nombre_completo', Auth::user()->name) }}</a>
                        <a href="#" class="d-block" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                            {{ session('dotacion', optional(Auth::user()->departamento)->detalle) }}</a>
                    </div>
                </div>


                {{-- Sidebar Menu --}}
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        {{-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library --}}


                        @can('MENU PERSONAL AUTORIZADO')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-person-fill-check"></i>
                                    <p>
                                        Personal autorizado
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ url('admin/usuarios/create') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ingresar personal</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('admin/usuarios') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listados de personal</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan
                        
                        {{--  
                        @can('MENU USUARIOS DEL SISTEMA')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-people"></i>
                                    <p>
                                        Usuarios del sistema
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    {{--
                                <li class="nav-item">
                                    <a href="{{ url('funcionarios/create') }}" class="nav-link active">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Creación de Usuarios</p>
                                    </a>
                                </li>
                                
                                    <li class="nav-item">
                                        <a href="{{ url('funcionarios/') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listados de Usuarios</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

--}}

                        @can('MENU AUTENTIFICATIC')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-person-fill-check"></i>
                                    <p>
                                        Personal Autentificatic
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('registerUser.form') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ingresar personal</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('autenti.funcionarios') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Funcionarios Autentificatic</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('admin/usuarios') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Usuarios del sistema PAC</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('MENU ROLES')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-file-person"></i>
                                    <p>
                                        Roles
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    {{-- <li class="nav-item">
                                    <a href="{{ url('roles/create') }}" class="nav-link active">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Creación de Roles</p>
                                    </a>
                                </li>
                                --}}
                                    <li class="nav-item">
                                        <a href="{{ url('roles/') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listados de Roles</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('MENU PERMISOS')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-clipboard-check"></i>
                                    <p>
                                        Permisos
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    {{--
                                <li class="nav-item">
                                    <a href="{{ url('permisos/create') }}" class="nav-link active">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Creación de Permisos</p>
                                    </a>
                                </li>
                                --}}
                                    <li class="nav-item">
                                        <a href="{{ url('permisos/') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listados de Permisos</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('MENU PLAN ANUAL DE COMPRAS')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-journal-text"></i>
                                    <p>
                                        Plan Anual de Compras
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    {{--
                                <li class="nav-item">
                                    <a href="{{ url('pac/create') }}" class="nav-link active">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ingreso PAC Repartición</p>
                                    </a>
                                </li>
                            --}}
                                    <li class="nav-item">
                                        <a href="{{ url('/pac') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado de Registros PAC</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('MENU LICITACIONES')
                            {{-- licitacion --}}
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-card-checklist"></i>
                                    <p>
                                        Licitaciones
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ url('modalidad') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado de Licitaciones</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                        @endcan

                        @can('MENU ORDENES DE COMPRA')
                            {{-- Ordenn de compras --}}
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-cart-check"></i>
                                    <p>
                                        Ordenes de Compras
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ url('ordenes') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado de O/C</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                        @endcan





                        
                        @can('MENU PRESUPUESTO')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-people"></i>
                                    <p>
                                        Presupuesto
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">                                   
                                    <li class="nav-item">
                                        <a href="{{ url('presupuesto/') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listados de Presupuestos</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan


                        @canany(['MENU CONFIGURACION', 'GRADOS DEL PERSONAL', 'DEPARTAMENTOS', 'ESPECIES O SERVICIOS', 'CLASIFICADOR', 'DESCRIPCION CLASIFICADOR', 'UNIDAD DE COMPRAS', 'ESTADO DEL REGISTRO', 'MODALIDAD DE COMPRA', 'ESTADO DE LICITACION', 'ESTADO DE COMPRAS', 'FUENTE DE FINANCIAMIENTO'])
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas bi bi-gear"></i>
                                    <p>
                                        Configuración
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('GRADOS DEL PERSONAL')
                                        <li class="nav-item">
                                            <a href="{{ url('grados/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Grados del personal</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('DEPARTAMENTOS')
                                        <li class="nav-item">
                                            <a href="{{ url('departamentos/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Departamentos</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('ESPECIES O SERVICIOS')
                                        <li class="nav-item">
                                            <a href="{{ url('especies/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Especies o Servicios</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('CLASIFICADOR')
                                        <li class="nav-item">
                                            <a href="{{ url('clasificador/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Clasificador</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('DESCRIPCION CLASIFICADOR')
                                        <li class="nav-item">
                                            <a href="{{ url('codigos/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Descripcion/clasificador</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('UNIDAD DE COMPRAS')
                                        <li class="nav-item">
                                            <a href="{{ url('unidadcompra/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Unidad de Compras</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('ESTADO DEL REGISTRO')
                                        <li class="nav-item">
                                            <a href="{{ url('estados/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Estado del Registro</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('MODALIDAD DE COMPRA')
                                        <li class="nav-item">
                                            <a href="{{ url('tipodecompra/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Modalidad de Compra</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('ESTADO DE LICITACION')
                                        <li class="nav-item">
                                            <a href="{{ url('estadolicitacion/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Estado de Licitación</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('ESTADO DE COMPRAS')
                                        <li class="nav-item">
                                            <a href="{{ url('estadocompras/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Estado de Compras</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('FUENTE DE FINANCIAMIENTO')
                                        <li class="nav-item">
                                            <a href="{{ url('fuentefinanciamiento/') }}" class="nav-link active">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Fuente de Financiamiento</p>
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany

                        {{-- Menú Dashboard --}}
                        @can('MENU DASHBOARD')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon bi bi-database-up"></i>
                                    <p style="margin-left: 5px;">
                                        Dashboard
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ url('dashboard/') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ver Detalles</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                        @endcan
                        {{-- Menú Reportes --}}
                        @can('MENU REPORTES')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon bi bi-clipboard2-pulse"></i>
                                    <p style="margin-left: 5px;">
                                        Reportes
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ url('reporte') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ver Detalles</p>
                                        </a>
                                    </li>
                                      @can('DASHBOARD REPORTE')
                                     <li class="nav-item">
                                        <a href="{{ url('reporte/dashboard') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ver Dashboard</p>
                                        </a>
                                    </li>
                                    @endcan

                                </ul>
                            </li>
                        @endcan

                        @can('MENU BITACORA')
                            <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon bi bi-clipboard2-pulse"></i>
                                    <p style="margin-left: 5px;">
                                        Bitacora
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ url('bitacora') }}" class="nav-link active">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ver Detalles</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                        @endcan



                        <li class="nav-item" style="font-size: 14px; color:aliceblue; margin-bottom: 0;">
                            <a href="#" id="logout-link"
                                class="nav-link" style="background-color: rgb(212, 23, 23)">
                                <i class="nav-icon fas bi bi-door-closed-fill"></i>
                                <p>
                                    Cerrar sesión
                                </p>
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                style="display: none;">
                                @csrf
                            </form>


                        </li>
                    </ul>
                </nav>
                {{-- /.sidebar-menu --}}
            </div>
            {{-- /.sidebar --}}
        </aside>

        @if (($message = Session::get('mensaje')) && ($icono = Session::get('icono')))
            <div id="flash-notification"
                 data-icon="{{ $icono }}"
                 data-message="{{ e($message) }}"
                 style="display:none;"></div>
        @endif
        <div class="content-wrapper">
            <div class="container-fluid" style="font-size: 14px; color:rgb(12, 13, 14); margin-bottom: 0;">
                @yield('content')


            </div>
        </div>

        {{-- Control Sidebar --}}
        <aside class="control-sidebar control-sidebar-dark">
            {{-- Add the sidebar's content here --}}
            <div class="p-3">
                <h5>Pac Dilocar</h5>
                <p></p>
            </div>
        </aside>
        {{-- /.control-sidebar --}}

        {{-- Main Footer --}}
        <footer class="main-footer">
            {{-- To the right --}}
            <div class="float-right d-none d-sm-inline"
                style="font-size: 14px; color:rgb(107, 111, 116); margin-bottom: 0;">
                Versión 1.0.
            </div>
            {{-- Default to the left --}}
            @can('MENU FOOTER')
            <strong style="font-size: 14px; color:rgb(107, 111, 116); margin-bottom: 0;">Diseñado por Suboficial (Sec.)
                Rivera - Asesoría Técnica - Dirección de Logística &copy; Diciembre - 2025 <a
                    href="{{ url('/admin') }}">"Sistema Registro y Control "Plan Anual de Compras"</a></strong>.
              @endcan      
        </footer>
    </div>
    {{-- ./wrapper --}}

    


    
    <script src="{{ url('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>


    
    <script src="{{ url('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ url('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ url('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ url('plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ url('plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ url('plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ url('plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ url('plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ url('plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ url('plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ url('plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>



    {{-- scripts --}}
    <script src="{{ url('dist/js/adminlte.min.js') }}"></script>
    <script src="{{ url('js/flash.js') }}"></script>
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', function () {
            var logoutLink = document.getElementById('logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById('logout-form').submit();
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
