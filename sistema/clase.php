<?php

/*
|--------------------------------------------------------------------------|
| Carga automática de Clases
|--------------------------------------------------------------------------|
*/
spl_autoload_register(function ($class_name) {
    $file = SISTEMA . 'clase' . DS . strtolower($class_name) . '.clase.php';

    error_log("Cargando clase: $class_name desde: $file");

    if (file_exists($file)) {
        require_once($file);

        // Si el archivo existe pero NO define la clase:
        if (!class_exists($class_name, false)) {
            error_log("Archivo cargado pero la clase '$class_name' no fue declarada en: $file");
        }
    } else {
        // Recomendación: NO cortar todo el sistema por una clase que quizá no se usa en esa pantalla
        error_log("Archivo de clase no encontrado: $file");
        // NO echo + exit aquí (te rompe módulos que no usan esa clase)
    }
});


// Instanciar clases de manera correcta
$db             = new Conexion();
$usuario        = new Usuario();
$enlace         = new Enlace();
$ClienteClase  = new Cliente();
$sistema        = new Sistema();
$Vendedor       = new Vendedor();
$notificacion   = new Notificacion();
$EstadoCuenta   = new EstadoCuenta();
$ProductosClase = new Productos();
$CajaDeVenta    = new Venta();  
$CotizacionClase = new Cotizacion();

// Verifica que la clase Venta esté correctamente definida
//$servicios     = new Servicios();  // Si no usas esta clase, no la declares

// Ejecutar algunas clases necesarias
$sistema->ReportarError();
?>