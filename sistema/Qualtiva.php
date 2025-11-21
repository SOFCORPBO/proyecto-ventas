<?php


/**
 * APP Version
 *
 * @var string
 *
 */
define('MYLOTTERYAPP_VERSION', '2.0.1');
// Activar Desarrollo del proyecto
define('ENTORNO_DESARROLLO', false);
// Barra separadora
define('DS', DIRECTORY_SEPARATOR);
//Archivos Estaticos del Proyecto
define('ESTATICO', URLBASE.'estatico/');
// Directorio de las imgenes para las noticias del Proyecto
define('IMGNOTICIAS', ESTATICO.'img/noticias/');
// Directorio Root del Proyecto
define('__ROOT__', dirname(dirname(__FILE__)));
// Directorio del Sistema del Proyecto
define('SISTEMA', __ROOT__.DS.'sistema'.DS);
// Directorio de las Clases del Proyecto
define('CLASE', SISTEMA.DS.'clase'.DS);
// Directorio de los modulos del Proyecto
define('MODULO', SISTEMA.'modulo'.DS.'');
// Directorio de los archivos xlsx del Proyecto
define('EXCEL', SISTEMA.DS.'tmp'.DS.'excel'.DS.'');
// Prevenir que la mayoria de navegadores no puedan manejar con javascript a través del atributo "HttpOnly"
// ini_set('session.cookie_httponly', 1);
// Utilizar únicamente cookies para la propagación del identificador de sesión.
// ini_set('session.use_only_cookies', 1);
// Establecer la zona horaria predeterminada UTC.
// date_default_timezone_set(HORARIO);

// Directorios Importantes NO EDITAR DE AQUI EN ADELANTE
require_once (SISTEMA.'clase.php');

require_once (SISTEMA.'metodo.php');
require_once (SISTEMA.'Tema.Apps.php');
require_once (SISTEMA.'POO.php');