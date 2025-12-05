<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();

define('HOST',      '127.0.0.1');
define('USER',      'root');
define('PASSWORD',  '');
define('PORT',      '3306');
define('DB',        'servicios_rahina');

define('LANGUAGE',  'es');
define('TITULO',    'PUNTO DE VENTA RIHANA');
define('URLBASE', 'http://localhost/Punto-de-venta-php-master/');
define('URLNOTIFICARVENTA', '#');

define('MANTENIMIENTO', false);
define('HORARIO', 'America/Ecuador');
define('GOOGLEANALYTICS', '');

require_once ('Qualtiva.php');