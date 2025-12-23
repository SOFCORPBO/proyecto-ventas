<?php
// ============================
// MENU ADMIN - SIDEBAR IZQUIERDO (Bootstrap 3 + jQuery)
// Admin: id_perfil = 1
// ============================

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$current = basename($_SERVER['PHP_SELF']);

function is_active($file, $current) {
    return ($file === $current) ? 'active' : '';
}
function is_open_group(array $files, $current){
    return in_array($current, $files, true) ? 'in' : '';
}
function is_aria_expanded(array $files, $current){
    return in_array($current, $files, true) ? 'true' : 'false';
}

$esAdmin = isset($usuarioApp['id_perfil']) ? ((int)$usuarioApp['id_perfil'] === 1)
        : (isset($_SESSION['id_perfil']) && (int)$_SESSION['id_perfil'] === 1);

// Grupos para auto-abrir cuando estás dentro
$grpChat        = ['chat.php','chat-config.php'];
$grpClientes    = ['cliente-dashboard.php','cliente.php','tramites.php','cotizacion-kamban.php','alertas-visa.php','clientes.php','nuevo-cliente.php'];
$grpProveedores = ['proveedores-dashboard.php','proveedores.php','proveedor-facturas.php','proveedor-pagos.php','proveedor-deudas.php','proveedor-historial.php'];
$grpServicios   = ['servicios.php','productos.php'];
$grpVentas      = ['registro-de-ventas.php','ventas-totales-vendedor.php','venta-bruta-usuarios.php','resumen.php'];
$grpFact        = ['facturacion-dashboard.php','facturacion-ventas.php','facturacion-reportes.php','impuestos-iva-it.php','impuestos-reportes.php','conta-cuentas.php','conta-diario.php','conta-mayor.php','conta-reportes.php'];
$grpUsuarios    = ['vendedores.php','nuevo-vendedor.php'];
$grpSistema     = ['cajas.php','caja-chica.php','caja-general.php','panel-cajas.php','ajuste-sistema.php'];
?>

<style>
/* ===== Layout base ===== */
body {
    padding-top: 50px;
    /* topbar */
}

@media (min-width:768px) {
    body {
        padding-left: 260px;
        /* sidebar visible */
        transition: padding-left .25s ease;
    }

    body.sidebar-collapsed {
        padding-left: 0;
        /* sidebar oculto */
    }
}

/* ===== Topbar ===== */
.topbar.navbar {
    z-index: 1040;
}

.topbar .navbar-brand {
    padding: 5px 15px;
}

/* ===== Sidebar ===== */
.sidebar {
    position: fixed;
    top: 50px;
    left: 0;
    width: 260px;
    height: calc(100vh - 50px);
    overflow-y: auto;
    overflow-x: hidden;
    background: #f8f8f8;
    border-right: 1px solid #e7e7e7;
    z-index: 1030;
    padding: 10px 0;
    transform: translateX(0);
    transition: transform .25s ease;
}

body.sidebar-collapsed .sidebar {
    transform: translateX(-260px);
}

/* Links */
.sidebar .nav>li>a {
    padding: 10px 15px;
    color: #333;
}

.sidebar .nav>li>a:hover,
.sidebar .nav>li.active>a {
    background: #e7e7e7;
}

.sidebar .submenu {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar .submenu li a {
    padding: 8px 15px 8px 35px;
    display: block;
    color: #444;
}

.sidebar .submenu li a:hover {
    background: #eee;
}

.sidebar .section-title {
    padding: 10px 15px;
    font-size: 11px;
    text-transform: uppercase;
    color: #888;
    letter-spacing: .5px;
}

/* ===== Mobile offcanvas ===== */
@media (max-width:767px) {
    body {
        padding-left: 0;
    }

    .sidebar {
        transform: translateX(-260px);
    }

    body.sidebar-open .sidebar {
        transform: translateX(0);
    }

    .sidebar-backdrop {
        display: none;
    }

    body.sidebar-open .sidebar-backdrop {
        display: block;
        position: fixed;
        top: 50px;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, .35);
        z-index: 1020;
    }
}
</style>

<!-- ========================= TOPBAR ========================= -->
<div class="navbar navbar-default navbar-fixed-top topbar">
    <div class="container-fluid">

        <div class="navbar-header">
            <!-- Toggle sidebar: móvil y escritorio -->
            <button type="button" class="navbar-toggle collapsed" id="btnSidebarToggle"
                style="float:left; margin-left:10px;">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <a href="<?php echo URLBASE ?>index.php" class="navbar-brand">
                <img src="<?php echo ESTATICO ?>img/store.png" alt="Logo <?php echo TITULO ?>" style="height:40px;">
            </a>
        </div>

        <ul class="nav navbar-nav navbar-right" style="margin-right:10px;">
            <?php if (defined('MODULO')) { include(MODULO."notificaciones-inventario.php"); } ?>

            <li class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                    <i class="fa fa-user-circle"></i> Cuenta <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="<?php echo URLBASE ?>cerrar-sesion.php">
                            <i class="fa fa-sign-out"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

    </div>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- ========================= SIDEBAR ========================= -->
<aside class="sidebar" id="sidebar">
    <ul class="nav nav-pills nav-stacked">

        <li class="section-title">Principal</li>
        <li class="<?php echo is_active('panel-admin.php', $current); ?>">
            <a href="<?php echo URLBASE; ?>panel-admin.php"><i class="fa fa-area-chart"></i> Panel Administrativo</a>
        </li>
        <li class="<?php echo is_active('index.php', $current); ?>">
            <a href="<?php echo URLBASE ?>index.php"><i class="fa fa-desktop"></i> POS Venta</a>
        </li>

        <li class="section-title">Comunicación</li>
        <?php $chatOpen = is_open_group($grpChat,$current); $chatAria = is_aria_expanded($grpChat,$current); ?>
        <li>
            <a href="#mChat" data-toggle="collapse" aria-expanded="<?php echo $chatAria; ?>">
                <i class="fa fa-comments"></i> WhatsApp / Chat <span class="caret"></span>
            </a>
            <ul class="submenu collapse <?php echo $chatOpen; ?>" id="mChat">
                <li class="<?php echo is_active('chat.php', $current); ?>">
                    <a href="<?php echo URLBASE ?>chat-instancias.php"><i class="fa fa-inbox"></i> Bandeja WhatsApp</a>
                </li>
                <?php if ($esAdmin): ?>
                <li class="<?php echo is_active('chat-config.php', $current); ?>">
                    <a href="<?php echo URLBASE ?>chat-config.php"><i class="fa fa-cog"></i> Configuración WhatsApp</a>
                </li>
                <?php endif; ?>
            </ul>
        </li>

        <li class="section-title">Clientes & Trámites</li>
        <?php $cliOpen = is_open_group($grpClientes,$current); $cliAria = is_aria_expanded($grpClientes,$current); ?>
        <li>
            <a href="#mClientes" data-toggle="collapse" aria-expanded="<?php echo $cliAria; ?>">
                <i class="fa fa-folder-open"></i> Clientes y Expedientes <span class="caret"></span>
            </a>
            <ul class="submenu collapse <?php echo $cliOpen; ?>" id="mClientes">
                <li><a href="<?php echo URLBASE ?>cliente-dashboard.php"><i class="fa fa-area-chart"></i> Dashboard
                        Cliente</a></li>
                <li><a href="<?php echo URLBASE ?>cliente.php"><i class="fa fa-users"></i> Gestión Clientes</a></li>
                <li><a href="<?php echo URLBASE ?>tramites.php"><i class="fa fa-briefcase"></i> Trámites</a></li>
                <li><a href="<?php echo URLBASE ?>cotizacion-kamban.php"><i class="fa fa-columns"></i> Cotizaciones
                        (Kanban)</a></li>
                <li><a href="<?php echo URLBASE ?>alertas-visa.php"><i class="fa fa-bell"></i> Alertas de Visas</a></li>
                <li style="border-top:1px solid #e7e7e7; margin:6px 0;"></li>
                <li><a href="<?php echo URLBASE ?>clientes.php"><i class="fa fa-list"></i> Registro de Clientes</a></li>
                <li><a href="<?php echo URLBASE ?>nuevo-cliente.php"><i class="fa fa-plus"></i> Nuevo Cliente</a></li>
            </ul>
        </li>

        <li class="section-title">Catálogos</li>
        <?php $srvOpen = is_open_group($grpServicios,$current); $srvAria = is_aria_expanded($grpServicios,$current); ?>
        <li>
            <a href="#mServicios" data-toggle="collapse" aria-expanded="<?php echo $srvAria; ?>">
                <i class="glyphicon glyphicon-list-alt"></i> Servicios <span class="caret"></span>
            </a>
            <ul class="submenu collapse <?php echo $srvOpen; ?>" id="mServicios">
                <li><a href="<?php echo URLBASE ?>servicios.php"><i class="glyphicon glyphicon-tags"></i> Catálogo de
                        Servicios</a></li>
                <li><a href="<?php echo URLBASE ?>productos.php"><i class="glyphicon glyphicon-cog"></i> Configuración
                        adicional</a></li>
            </ul>
        </li>

        <li class="section-title">Operación</li>
        <?php $venOpen = is_open_group($grpVentas,$current); $venAria = is_aria_expanded($grpVentas,$current); ?>
        <li>
            <a href="#mVentas" data-toggle="collapse" aria-expanded="<?php echo $venAria; ?>">
                <i class="fa fa-shopping-cart"></i> Venta de Servicios <span class="caret"></span>
            </a>
            <ul class="submenu collapse <?php echo $venOpen; ?>" id="mVentas">
                <li><a href="<?php echo URLBASE ?>registro-de-ventas.php"><i class="fa fa-list-alt"></i> Registro de
                        Ventas</a></li>
                <li><a href="<?php echo URLBASE ?>ventas-totales-vendedor.php"><i class="fa fa-user-circle"></i> Ventas
                        Totales por Vendedor</a></li>
                <li><a href="<?php echo URLBASE ?>venta-bruta-usuarios.php"><i class="fa fa-line-chart"></i> Venta Bruta
                        por Día</a></li>
                <li><a href="<?php echo URLBASE ?>resumen.php"><i class="fa fa-pie-chart"></i> Resumen General</a></li>
            </ul>
        </li>

        <li class="section-title">Proveedores</li>
        <?php $provOpen = is_open_group($grpProveedores,$current); $provAria = is_aria_expanded($grpProveedores,$current); ?>
        <li>
            <a href="#mProveedores" data-toggle="collapse" aria-expanded="<?php echo $provAria; ?>">
                <i class="fa fa-truck"></i> Proveedores <span class="caret"></span>
            </a>
            <ul class="submenu collapse <?php echo $provOpen; ?>" id="mProveedores">
                <li><a href="<?php echo URLBASE ?>proveedores-dashboard.php"><i class="fa fa-area-chart"></i> Dashboard
                        Proveedores</a></li>
                <li><a href="<?php echo URLBASE ?>proveedores.php"><i class="fa fa-users"></i> Gestión de
                        Proveedores</a></li>
                <li style="border-top:1px solid #e7e7e7; margin:6px 0;"></li>
                <li><a href="<?php echo URLBASE ?>proveedor-facturas.php"><i class="fa fa-file-text-o"></i> Facturas
                        Recibidas</a></li>
                <li><a href="<?php echo URLBASE ?>proveedor-pagos.php"><i class="fa fa-money"></i> Pagos a
                        Proveedores</a></li>
                <li><a href="<?php echo URLBASE ?>proveedor-deudas.php"><i class="fa fa-exclamation-circle"></i> Deudas
                        y Saldos</a></li>
                <li><a href="<?php echo URLBASE ?>proveedor-historial.php"><i class="fa fa-book"></i> Historial
                        Financiero</a></li>
            </ul>
        </li>

        <li class="section-title">Finanzas</li>
        <?php $facOpen = is_open_group($grpFact,$current); $facAria = is_aria_expanded($grpFact,$current); ?>
        <li>
            <a href="#mFact" data-toggle="collapse" aria-expanded="<?php echo $facAria; ?>">
                <i class="fa fa-file-text-o"></i> Facturación & Contabilidad <span class="caret"></span>
            </a>
            <ul class="submenu collapse <?php echo $facOpen; ?>" id="mFact">
                <li><a href="<?php echo URLBASE ?>facturacion-dashboard.php"><i class="fa fa-area-chart"></i> Dashboard
                        Facturación</a></li>
                <li><a href="<?php echo URLBASE ?>facturacion-ventas.php"><i class="fa fa-check-square-o"></i> Ventas
                        Facturadas / No</a></li>
                <li><a href="<?php echo URLBASE ?>facturacion-reportes.php"><i class="fa fa-file-pdf-o"></i> Reportes
                        Facturación</a></li>
                <li style="border-top:1px solid #e7e7e7; margin:6px 0;"></li>
                <li><a href="<?php echo URLBASE ?>impuestos-iva-it.php"><i class="fa fa-percent"></i> IVA / IT</a></li>
                <li><a href="<?php echo URLBASE ?>impuestos-reportes.php"><i class="fa fa-bar-chart"></i> Reportes
                        Tributarios</a></li>
                <li style="border-top:1px solid #e7e7e7; margin:6px 0;"></li>
                <li><a href="<?php echo URLBASE ?>contabilidad.php"><i class="fa fa-sitemap"></i> Plan de Cuentas</a>
                </li>
                <li><a href="<?php echo URLBASE ?>conta-diario.php"><i class="fa fa-book"></i> Libro Diario</a></li>
                <li><a href="<?php echo URLBASE ?>conta-mayor.php"><i class="fa fa-bookmark"></i> Libro Mayor</a></li>
                <li><a href="<?php echo URLBASE ?>conta-reportes.php"><i class="fa fa-balance-scale"></i> Estados
                        Financieros</a></li>
            </ul>
        </li>

        <li class="section-title">Administración</li>
        <?php $usrOpen = is_open_group($grpUsuarios,$current); $usrAria = is_aria_expanded($grpUsuarios,$current); ?>
        <li>
            <a href="#mUsuarios" data-toggle="collapse" aria-expanded="<?php echo $usrAria; ?>">
                <i class="fa fa-users"></i> Usuarios <span class="caret"></span>
            </a>
            <ul class="submenu collapse <?php echo $usrOpen; ?>" id="mUsuarios">
                <li><a href="<?php echo URLBASE ?>vendedores.php"><i class="fa fa-user-circle"></i> Vendedores y
                        Usuarios</a></li>
                <li><a href="<?php echo URLBASE ?>nuevo-vendedor.php"><i class="fa fa-user-plus"></i> Agregar
                        Usuario</a></li>
            </ul>
        </li>

        <li class="section-title">Sistema</li>
        <?php $sysOpen = is_open_group($grpSistema,$current); $sysAria = is_aria_expanded($grpSistema,$current); ?>
        <li>
            <a href="#mSistema" data-toggle="collapse" aria-expanded="<?php echo $sysAria; ?>">
                <i class="fa fa-cogs"></i> Sistema <span class="caret"></span>
            </a>
            <ul class="submenu collapse <?php echo $sysOpen; ?>" id="mSistema">
                <li><a href="<?php echo URLBASE ?>cajas.php"><i class="fa fa-archive"></i> Caja del Sistema</a></li>
                <li><a href="<?php echo URLBASE ?>caja-chica.php"><i class="fa fa-briefcase"></i> Caja Chica</a></li>
                <li><a href="<?php echo URLBASE ?>caja-general.php"><i class="fa fa-university"></i> Caja General</a>
                </li>
                <li><a href="<?php echo URLBASE ?>panel-cajas.php"><i class="fa fa-area-chart"></i> Panel de Cajas</a>
                </li>
                <li style="border-top:1px solid #e7e7e7; margin:6px 0;"></li>
                <li><a href="<?php echo URLBASE ?>ajuste-sistema.php"><i class="fa fa-sliders"></i> Ajustes de la
                        Aplicación</a></li>
            </ul>
        </li>

    </ul>
</aside>

<script>
(function() {
    var btn = document.getElementById('btnSidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');

    function isMobile() {
        return window.innerWidth < 768;
    }

    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
        if (!isMobile()) {
            document.body.classList.add('sidebar-collapsed');
        }
    }

    function toggleSidebar() {
        if (isMobile()) {
            document.body.classList.toggle('sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
        }
    }

    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function() {
            document.body.classList.remove('sidebar-open');
        });
    }

    // Acordeón: abre uno y cierra los demás
    if (window.jQuery) {
        $('.sidebar .collapse').on('show.bs.collapse', function() {
            $('.sidebar .collapse.in').not(this).collapse('hide');
        });

        // En móvil: al click en link final, cerrar
        $('.sidebar a').on('click', function() {
            if (isMobile()) {
                document.body.classList.remove('sidebar-open');
            }
        });
    }
})();
</script>