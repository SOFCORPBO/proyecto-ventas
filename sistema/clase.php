<?php

/*
|--------------------------------------------------------------------------|
| Carga automática de Clases
|--------------------------------------------------------------------------|
*/
spl_autoload_register(function ($class_name) {
    // Ruta completa del archivo de clase
    $file = SISTEMA . 'clase' . DS . strtolower($class_name) . '.clase.php';

    // Depuración: Verificar la ruta que se está cargando
    // Para producción, usa error_log en lugar de var_dump
    error_log("Cargando clase: $file");  // Graba el mensaje en el archivo de logs

    if (file_exists($file)) {
        require_once($file);
    } else {
        // Si el archivo no existe, mostramos un mensaje claro de error
        echo "Archivo no encontrado: $file";
        exit;  // Detener ejecución si no se encuentra la clase
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