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