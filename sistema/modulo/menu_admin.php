<div class="navbar navbar-default navbar-fixed-top">
    <div class="container">

        <div class="navbar-header">
            <div class="navbar-brand">
                <a href="<?php echo URLBASE ?>index.php" class="navbar-brand">
                    <img src="<?php echo ESTATICO ?>img/store.png" alt="Logo <?php echo TITULO ?>" width="40px" />
                </a>
            </div>
            <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>

        <div class="navbar-collapse collapse" id="navbar-main">
            <ul class="nav navbar-nav">

                <li><a href="<?php echo URLBASE; ?>panel-admin.php">
                        <i class="fa fa-area-chart"></i> Panel Administrativo
                    </a></li>


                <!-- POS -->
                <li class="menu"><a href="<?php echo URLBASE ?>index.php">POS VENTA</a></li>

                <!-- KARDEX -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown"> CLIENTES Y EXPEDIENTES <span
                            class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>cliente-dashboard.php">Dashboard Cliente</a></li>
                        <li><a href="<?php echo URLBASE ?>cliente.php">Gestion Clientes </a></li>
                        <li><a href="<?php echo URLBASE ?>tramites.php">Tramites</a></li>
                        <li><a href="<?php echo URLBASE ?>cotizacion-kamban.php">Cotizaciones </a></li>
                        <li><a href="<?php echo URLBASE ?>alertas-visa.php">Alertas </a></li>

                    </ul>


                </li>
                <!-- PROVEEDORES -->
                <!-- PROVEEDORES -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-truck"></i> Proveedores <span class="caret"></span>
                    </a>

                    <ul class="dropdown-menu">

                        <!-- DASHBOARD -->
                        <li>
                            <a href="<?php echo URLBASE ?>proveedores-dashboard.php">
                                <i class="fa fa-area-chart"></i> Dashboard General
                            </a>
                        </li>

                        <!-- GESTIÓN PRINCIPAL -->
                        <li>
                            <a href="<?php echo URLBASE ?>proveedores.php">
                                <i class="fa fa-users"></i> Gestión de Proveedores
                            </a>
                        </li>

                        <li role="separator" class="divider"></li>

                        <!-- FACTURAS -->
                        <li>
                            <a href="<?php echo URLBASE ?>proveedor-facturas.php">
                                <i class="fa fa-file-text-o"></i> Facturas Recibidas
                            </a>
                        </li>

                        <!-- PAGOS -->
                        <li>
                            <a href="<?php echo URLBASE ?>proveedor-pagos.php">
                                <i class="fa fa-money"></i> Pagos Realizados
                            </a>
                        </li>

                        <!-- DEUDAS -->
                        <li>
                            <a href="<?php echo URLBASE ?>proveedor-deudas.php">
                                <i class="fa fa-exclamation-circle"></i> Deudas Pendientes
                            </a>
                        </li>

                        <!-- HISTORIAL / ESTADO DE CUENTA -->
                        <li>
                            <a href="<?php echo URLBASE ?>proveedor-historial.php">
                                <i class="fa fa-book"></i> Historial Financiero
                            </a>
                        </li>

                    </ul>
                </li>

                <div class="navbar navbar-default navbar-fixed-top">
                    <div class="container">

                        <div class="navbar-header">
                            <div class="navbar-brand">
                                <a href="<?php echo URLBASE ?>index.php" class="navbar-brand">
                                    <img src="<?php echo ESTATICO ?>img/store.png" alt="Logo <?php echo TITULO ?>"
                                        width="40px" />
                                </a>
                            </div>
                            <button class="navbar-toggle" type="button" data-toggle="collapse"
                                data-target="#navbar-main">
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                            </button>
                        </div>

                        <div class="navbar-collapse collapse" id="navbar-main">
                            <ul class="nav navbar-nav">

                                <!-- PANEL ADMIN -->
                                <li>
                                    <a href="<?php echo URLBASE; ?>panel-admin.php">
                                        <i class="fa fa-area-chart"></i> Panel Administrativo
                                    </a>
                                </li>

                                <!-- POS -->
                                <li class="menu">
                                    <a href="<?php echo URLBASE ?>index.php">
                                        <i class="fa fa-desktop"></i> POS VENTA
                                    </a>
                                </li>

                                <!-- CLIENTES Y EXPEDIENTES -->
                                <li class="dropdown menu">
                                    <a class="dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-folder-open"></i> CLIENTES Y EXPEDIENTES <span
                                            class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="<?php echo URLBASE ?>cliente-dashboard.php">
                                                <i class="fa fa-area-chart"></i> Dashboard Cliente
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>cliente.php">
                                                <i class="fa fa-users"></i> Gestión Clientes
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>tramites.php">
                                                <i class="fa fa-briefcase"></i> Trámites
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>cotizacion-kamban.php">
                                                <i class="fa fa-columns"></i> Cotizaciones (Kanban)
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>alertas-visa.php">
                                                <i class="fa fa-bell"></i> Alertas de Visas
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <!-- PROVEEDORES -->
                                <li class="dropdown menu">
                                    <a class="dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-truck"></i> Proveedores <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">

                                        <li>
                                            <a href="<?php echo URLBASE ?>proveedores-dashboard.php">
                                                <i class="fa fa-area-chart"></i> Dashboard Proveedores
                                            </a>
                                        </li>

                                        <li>
                                            <a href="<?php echo URLBASE ?>proveedores.php">
                                                <i class="fa fa-users"></i> Gestión de Proveedores
                                            </a>
                                        </li>

                                        <li role="separator" class="divider"></li>

                                        <li>
                                            <a href="<?php echo URLBASE ?>proveedor_factura.php">
                                                <i class="fa fa-file-text-o"></i> Facturas Recibidas
                                            </a>
                                        </li>

                                        <li>
                                            <a href="<?php echo URLBASE ?>proveedor-pagos.php">
                                                <i class="fa fa-money"></i> Pagos a Proveedores
                                            </a>
                                        </li>

                                        <li>
                                            <a href="<?php echo URLBASE ?>proveedor-deudas.php">
                                                <i class="fa fa-exclamation-circle"></i> Deudas y Saldos
                                            </a>
                                        </li>

                                        <li>
                                            <a href="<?php echo URLBASE ?>proveedor-historial.php">
                                                <i class="fa fa-book"></i> Historial Financiero
                                            </a>
                                        </li>

                                    </ul>
                                </li>

                                <!-- SERVICIOS -->
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                        <i class="glyphicon glyphicon-list-alt"></i> Servicios <span
                                            class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">

                                        <li>
                                            <a href="<?php echo URLBASE; ?>servicios.php">
                                                <i class="glyphicon glyphicon-tags"></i> Catálogo de Servicios
                                            </a>
                                        </li>

                                        <li role="separator" class="divider"></li>

                                        <li>
                                            <a href="<?php echo URLBASE; ?>productos.php">
                                                <i class="glyphicon glyphicon-cog"></i> Configuración adicional
                                                (Opcional)
                                            </a>
                                        </li>

                                    </ul>
                                </li>

                                <!-- CLIENTES (ACCESO RÁPIDO) -->
                                <li class="dropdown menu">
                                    <a class="dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-user"></i> Clientes <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="<?php echo URLBASE ?>clientes.php">
                                                <i class="fa fa-list"></i> Registro de Clientes
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>nuevo-cliente.php">
                                                <i class="fa fa-plus"></i> Nuevo Cliente
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <!-- VENTAS -->
                                <li class="dropdown menu">
                                    <a class="dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-shopping-cart"></i> Venta de Servicios <span
                                            class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="<?php echo URLBASE ?>registro-de-ventas.php">
                                                <i class="fa fa-list-alt"></i> Registro de Ventas
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>ventas-totales-vendedor.php">
                                                <i class="fa fa-user-circle"></i> Ventas Totales por Vendedor
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>venta-bruta-usuarios.php">
                                                <i class="fa fa-line-chart"></i> Venta Bruta por Día
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>resumen.php">
                                                <i class="fa fa-pie-chart"></i> Resumen General
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <!-- FACTURACIÓN & CONTABILIDAD -->
                                <li class="dropdown menu">
                                    <a class="dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-file-text-o"></i> Facturación & Contabilidad <span
                                            class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">

                                        <!-- FACTURACIÓN -->
                                        <li class="dropdown-header">Facturación</li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>facturacion-dashboard.php">
                                                <i class="fa fa-area-chart"></i> Dashboard de Facturación
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>facturacion-ventas.php">
                                                <i class="fa fa-check-square-o"></i> Ventas Facturadas / No Facturadas
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>facturacion-reportes.php">
                                                <i class="fa fa-file-pdf-o"></i> Reportes de Facturación
                                            </a>
                                        </li>

                                        <li role="separator" class="divider"></li>

                                        <!-- IMPUESTOS -->
                                        <li class="dropdown-header">Impuestos</li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>impuestos-iva-it.php">
                                                <i class="fa fa-percent"></i> Control de IVA / IT
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>impuestos-reportes.php">
                                                <i class="fa fa-bar-chart"></i> Reportes Tributarios
                                            </a>
                                        </li>

                                        <li role="separator" class="divider"></li>

                                        <!-- CONTABILIDAD -->
                                        <li class="dropdown-header">Contabilidad</li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>conta-cuentas.php">
                                                <i class="fa fa-sitemap"></i> Plan de Cuentas
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>conta-diario.php">
                                                <i class="fa fa-book"></i> Libro Diario
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>conta-mayor.php">
                                                <i class="fa fa-bookmark"></i> Libro Mayor
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>conta-reportes.php">
                                                <i class="fa fa-balance-scale"></i> Estados Financieros
                                            </a>
                                        </li>

                                    </ul>
                                </li>

                                <!-- USUARIOS -->
                                <li class="dropdown menu">
                                    <a class="dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-users"></i> Usuarios <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="<?php echo URLBASE ?>vendedores.php">
                                                <i class="fa fa-user-circle"></i> Vendedores y Usuarios
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>nuevo-vendedor.php">
                                                <i class="fa fa-user-plus"></i> Agregar Nuevo Vendedor
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <!-- SISTEMA -->
                                <li class="dropdown menu">
                                    <a class="dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-cogs"></i> Sistema <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">

                                        <!-- Caja del Sistema (base original POS) -->
                                        <li>
                                            <a href="<?php echo URLBASE ?>cajas.php">
                                                <i class="fa fa-archive"></i> Caja del Sistema
                                            </a>
                                        </li>

                                        <!-- NUEVA: Caja Chica -->
                                        <li>
                                            <a href="<?php echo URLBASE ?>caja-chica.php">
                                                <i class="fa fa-briefcase"></i> Caja Chica
                                            </a>
                                        </li>

                                        <!-- NUEVA: Caja General -->
                                        <li>
                                            <a href="<?php echo URLBASE ?>caja-general.php">
                                                <i class="fa fa-university"></i> Caja General
                                            </a>
                                        </li>

                                        <li>
                                            <a href="<?php echo URLBASE; ?>panel-cajas.php">
                                                <i class="fa fa-area-chart"></i> Panel de Cajas
                                            </a>
                                        </li>

                                        <!-- Ajustes -->
                                        <li role="separator" class="divider"></li>
                                        <li>
                                            <a href="<?php echo URLBASE ?>ajuste-sistema.php">
                                                <i class="fa fa-sliders"></i> Ajustes de la Aplicación
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                            </ul>

                            <!-- DERECHA -->
                            <ul class="nav navbar-nav navbar-right">

                                <!-- Notificaciones -->
                                <?php include(MODULO."notificaciones-inventario.php"); ?>

                                <li class="dropdown menu">
                                    <a class="dropdown-toggle" data-toggle="dropdown">
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
                </div>

                <!-- FIX para que los dropdown no se queden pegados -->
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    $('.dropdown-menu a').on('click', function() {
                        $('.dropdown.open').removeClass('open');
                        $('.dropdown').removeClass('open');
                    });
                });
                </script>


                <!-- SERVICIOS -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="glyphicon glyphicon-list-alt"></i> Servicios <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">

                        <li>
                            <a href="<?php echo URLBASE; ?>servicios.php">
                                <i class="glyphicon glyphicon-tags"></i> Catálogo de Servicios
                            </a>
                        </li>

                        <li role="separator" class="divider"></li>

                        <li>
                            <a href="<?php echo URLBASE; ?>productos.php">
                                <i class="glyphicon glyphicon-cog"></i> Configuración adicional (Opcional)
                            </a>
                        </li>

                    </ul>
                </li>

                <!-- CLIENTES -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Clientes <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>clientes.php">Registro de Clientes</a></li>
                        <li><a href="<?php echo URLBASE ?>nuevo-cliente.php">Nuevo Cliente</a></li>
                    </ul>
                </li>

                <!-- VENTAS -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Venta de Servicios <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>registro-de-ventas.php">Registro de Ventas</a></li>
                        <li><a href="<?php echo URLBASE ?>ventas-totales-vendedor.php">Ventas Totales por Vendedor</a>
                        </li>
                        <li><a href="<?php echo URLBASE ?>venta-bruta-usuarios.php">Venta Bruta por Día</a></li>
                        <li><a href="<?php echo URLBASE ?>resumen.php">Resumen</a></li>
                    </ul>
                </li>

                <!-- USUARIOS -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Usuarios <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>vendedores.php">Vendedores y Usuarios</a></li>
                        <li><a href="<?php echo URLBASE ?>nuevo-vendedor.php">Agregar Nuevo Vendedor</a></li>
                    </ul>
                </li>

                <!-- SISTEMA -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Sistema <span class="caret"></span></a>
                    <ul class="dropdown-menu">

                        <!-- Caja del Sistema (base original POS) -->
                        <li><a href="<?php echo URLBASE ?>cajas.php">Caja del Sistema</a></li>

                        <!-- NUEVA: Caja Chica -->
                        <li><a href="<?php echo URLBASE ?>caja-chica.php">Caja Chica</a></li>

                        <!-- NUEVA: Caja General -->
                        <li><a href="<?php echo URLBASE ?>caja-general.php">Caja General</a></li>

                        <li>
                            <a href="<?php echo URLBASE; ?>panel-cajas.php">
                                <i class="fa fa-area-chart"></i> Panel de Cajas
                            </a>
                        </li>

                        <!-- Ajustes -->
                        <li><a href="<?php echo URLBASE ?>ajuste-sistema.php">Ajustes de la Aplicación</a></li>
                    </ul>
                </li>

            </ul>

            <!-- DERECHA -->
            <ul class="nav navbar-nav navbar-right">

                <!-- Notificaciones -->
                <?php include(MODULO."notificaciones-inventario.php"); ?>

                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Cuenta <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>cerrar-sesion.php">Cerrar Sesión</a></li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</div>

<!-- FIX para que los dropdown no se queden pegados -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    $('.dropdown-menu a').on('click', function() {
        $('.dropdown.open').removeClass('open');
        $('.dropdown').removeClass('open');
    });
});
</script>