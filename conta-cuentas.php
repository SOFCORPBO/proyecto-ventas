<?php
session_start();
include("sistema/configuracion.php");
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$params = $_GET;
unset($params['tab']);
$params = array_merge(['tab'=>'cuentas'], $params);

header("Location: contabilidad.php?".http_build_query($params));
exit;
