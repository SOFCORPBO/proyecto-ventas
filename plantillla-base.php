<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php');?>

    <style>
    /* Contenedor principal del contenido (para que no se “pegue”) */
    .main-content {
        padding: 15px;
    }

    /* Evita que el header meta margen extra arriba cuando hay navbar fixed */
    .page-header {
        margin-top: 0;
    }
    </style>
</head>

<body>

    <?php
// Menú según perfil
if($usuarioApp['id_perfil'] == 2){
    include(MODULO.'menu_vendedor.php');
}elseif($usuarioApp['id_perfil'] == 1){
    include(MODULO.'menu_admin.php'); // aquí ya va el sidebar + topbar y contiene Chat
}else{
    echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
    exit;
}
?>

    <!-- CONTENIDO PRINCIPAL -->
    <div id="wrap">
        <!-- IMPORTANTE: usar container-fluid para que el contenido no quede estrecho con sidebar -->
        <div class="container-fluid main-content">

            <!-- Header general (si lo quieres en todas las páginas) -->
            <div class="page-header" id="banner">
                <div class="row">
                    <div class="col-lg-8 col-md-7 col-sm-6">
                        <h1 style="margin-top:0;"><?php echo TITULO ?></h1>
                        <p class="lead">Desarrollo para aplicaciones web</p>
                    </div>
                </div>
            </div>

            <?php
        /*
          Aquí normalmente va el contenido de cada página.
          Si tus páginas incluyen plantilla-base.php como “layout”, lo ideal es migrar a:
          - plantilla-header.php (abre HTML + menú + abre main-content)
          - plantilla-footer.php (cierra main-content + footer + JS)
          Para poder inyectar contenido en medio.
        */
        ?>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>

    <?php include(MODULO.'Tema.JS.php');?>

</body>

</html>