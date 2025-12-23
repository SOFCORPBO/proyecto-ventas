<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();

// Definir las constantes solo si no están definidas previamente
if (!defined('HOST')) define('HOST', '127.0.0.1');
if (!defined('USER')) define('USER', 'root');
if (!defined('PASSWORD')) define('PASSWORD', '');
if (!defined('PORT')) define('PORT', '3306');
if (!defined('DB')) define('DB', 'servicios_rahina');

if (!defined('LANGUAGE')) define('LANGUAGE', 'es');
if (!defined('TITULO')) define('TITULO', 'PUNTO DE VENTA RIHANA');
if (!defined('URLBASE')) define('URLBASE', 'http://localhost/Punto-de-venta-php-master/');
if (!defined('URLNOTIFICARVENTA')) define('URLNOTIFICARVENTA', '#');

if (!defined('MANTENIMIENTO')) define('MANTENIMIENTO', false);
if (!defined('HORARIO')) define('HORARIO', 'America/Ecuador');
if (!defined('GOOGLEANALYTICS')) define('GOOGLEANALYTICS', '');

require_once('Qualtiva.php');
?>