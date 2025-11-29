<?php

/*
|--------------------------------------------------------------------------|
| Carga autom�tica Clases
|--------------------------------------------------------------------------|
*/
spl_autoload_register(function ($NombreClase) {
    $ruta = SISTEMA . 'clase' . DS . strtolower($NombreClase) . '.clase.php';

    if (file_exists($ruta)) {
        require_once($ruta);
    } else {
        // Debug opcional:
        // echo "No se encontró la clase: $ruta";
    }
});


//Instanciar Clases
$db				= new Conexion();
$usuario		= new Usuario();
$enlace			= new Enlace();
$ClientesClase  = new Clientes();
$sistema		= new Sistema();
$Vendedor		= new Vendedor();
$notificacion	= new Notificacion();
$EstadoCuenta	= new EstadoCuenta();
$ProductosClase	= new Productos();
$CajaDeVenta	= new Venta();
//$servicios = new Servicios();

// Ejecutar Algunas Clases
$sistema->ReportarError();
?>