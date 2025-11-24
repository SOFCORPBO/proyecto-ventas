<div class="navbar navbar-default navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <div class="navbar-brand">
                <a href="<?php echo URLBASE ?>" class="navbar-brand">
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

                <!-- POS -->
                <li class="menu"><a href="index.php">POS VENTA</a></li>

                <!-- KARDEX -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Kardex <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>kardex">Kardex General</a></li>
                        <li><a href="<?php echo URLBASE ?>kardex-por-producto">Kardex Por Servicios</a></li>
                    </ul>
                </li>

                <!-- SERVICIOS -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Servicios <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>productos">Catálogo de Servicios</a></li>
                        <li><a href="<?php echo URLBASE ?>nuevo-producto">Nuevo Servicio</a></li>
                        <li><a href="<?php echo URLBASE ?>proveedores">Proveedores</a></li>
                        <li><a href="<?php echo URLBASE ?>impuestos">Impuestos</a></li>
                    </ul>
                </li>

                <!-- CLIENTES -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Clientes <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>clientes">Registro de Clientes</a></li>
                        <li><a href="<?php echo URLBASE ?>nuevo-cliente">Nuevo Cliente</a></li>
                    </ul>
                </li>

                <!-- VENTAS -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Venta de Servicios <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>registro-de-ventas">Registro de Ventas</a></li>
                        <li><a href="<?php echo URLBASE ?>ventas-totales-vendedor">Ventas Totales por Vendedor</a></li>
                        <li><a href="<?php echo URLBASE ?>venta-bruta-usuarios">Venta Bruta por Día</a></li>
                        <li><a href="<?php echo URLBASE ?>resumen">Resumen</a></li>
                    </ul>
                </li>

                <!-- USUARIOS -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Usuarios <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>vendedores">Vendedores y Usuarios</a></li>
                        <li><a href="<?php echo URLBASE ?>nuevo-vendedor">Agregar Nuevo Vendedor</a></li>
                    </ul>
                </li>

                <!-- SISTEMA -->
                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Sistema<span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>cajas">Cajas del Sistema</a></li>
                        <li><a href="<?php echo URLBASE ?>ajuste-sistema">Ajustes de la Aplicación</a></li>
                    </ul>
                </li>

            </ul>

            <ul class="nav navbar-nav navbar-right">
                <?php include(MODULO."notificaciones-inventario.php");?>

                <li class="dropdown menu">
                    <a class="dropdown-toggle" data-toggle="dropdown">Cuenta <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo URLBASE ?>cerrar-sesion">Cerrar Sesión</a></li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</div>