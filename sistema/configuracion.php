<?php
// ⭐ ACTIVAR TODOS LOS ERRORES ⭐
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();

/**
 |-------------------------------------------
 |  CONFIGURACION BASE DE DATOS
 |-------------------------------------------
 */
define('HOST',      '127.0.0.1');
define('USER',      'root');
define('PASSWORD',  '');
define('PORT',      '3306');
define('DB',        'stockdev');

/**
 |-------------------------------------------
 |  CONFIGURACION IDIOMA
 |-------------------------------------------
 */
define('LANGUAGE',  'es');

/**
 |-------------------------------------------
 |  Datos de la Aplicación
 |-------------------------------------------
 */
define('TITULO',    'StockApp');

/**
 |-------------------------------------------
 |  CONFIGURACION DIRECCIONES
 |-------------------------------------------
 */
define('URLBASE', '/Punto-de-venta-php-master/');

define('URLNOTIFICARVENTA', '#');

/**
 |-------------------------------------------
 |  Estado Mantenimiento
 |-------------------------------------------
 */
define('MANTENIMIENTO', false);

/**
 |-------------------------------------------
 | ESTABLECER LA ZONA HORARIA PREDETERMINADA
 |-------------------------------------------
 */
define('HORARIO', 'America/Ecuador');
define('GOOGLEANALYTICS',        '');

/**
 |--------------------------------------------
 | CARGA NUCLEO DE LA APLICACION
 |--------------------------------------------
 */
require_once ('Qualtiva.php');