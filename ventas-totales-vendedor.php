<?php
session_start();
include('sistema/configuracion.php');

// Validar sesión
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

// === RANGO DE FECHAS (FILTRO) ===
$hoy = FechaActual();

// Desde / Hasta por GET o POST
$fecha_desde = isset($_REQUEST['fecha_desde']) && $_REQUEST['fecha_desde'] != '' 
    ? $_REQUEST['fecha_desde'] 
    : $hoy;

$fecha_hasta = isset($_REQUEST['fecha_hasta']) && $_REQUEST['fecha_hasta'] != '' 
    ? $_REQUEST['fecha_hasta'] 
    : $hoy;

// Vendedor opcional (0 = todos)
$id_vendedor_filtro = isset($_REQUEST['vendedor']) ? (int)$_REQUEST['vendedor'] : 0;

// === CARGAR LISTA DE VENDEDORES PARA EL SELECT ===
$VendedoresSql = $db->SQL("SELECT id, nombre, apellido1, apellido2 FROM vendedores WHERE habilitado='1' ORDER BY nombre");

// === CONSULTA PRINCIPAL SOBRE TABLA VENTAS ===
// Agrupamos por vendedor y calculamos:
// - total_bruto  = SUM(totalprecio)
// - total_comision = SUM(comision)
// - total_caja   = total_bruto - total_comision

$condVendedor = "";
if ($id_vendedor_filtro > 0) {
    $condVendedor = " AND v.vendedor = '{$id_vendedor_filtro}'";
}

$VentasTotalesSql = $db->SQL("
    SELECT 
        v.vendedor,
        ven.nombre   AS nombre,
        ven.apellido1 AS apellido1,
        ven.apellido2 AS apellido2,
        SUM(v.totalprecio) AS total_bruto,
        SUM(v.comision)    AS total_comision
    FROM ventas v
    LEFT JOIN vendedores ven ON ven.id = v.vendedor
    WHERE v.fecha >= '{$fecha_desde}'
      AND v.fecha <= '{$fecha_hasta}'
      {$condVendedor}
      AND v.habilitada = 1
    GROUP BY v.vendedor, ven.nombre, ven.apellido1, ven.apellido2
    ORDER BY total_bruto DESC
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Ventas Totales por Vendedor | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?php echo ESTATICO; ?>img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO; ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO; ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>
    <?php
// Menú según perfil
if($usuarioApp['id_perfil']==2){
    include (MODULO.'menu_vendedor.php');
}elseif($usuarioApp['id_perfil']==1){
    include (MODULO.'menu_admin.php');
}else{
    echo'<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
}
?>

    <div id="wrap">
        <div class="container">

            <!-- TÍTULO -->
            <div class="page-header" id="banner">
                <div class="row">
                    <div class="col-lg-8 col-md-7 col-sm-6">
                        <h1>Ventas Totales por Vendedor</h1>
                        <p class="lead">Resumen de ventas basadas en la tabla <code>ventas</code></p>
                    </div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="row">
                <div class="col-md-12">
                    <form class="form-inline" method="get" action="">
                        <div class="form-group">
                            <label>Desde: </label>
                            <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>"
                                class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Hasta: </label>
                            <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>"
                                class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Vendedor: </label>
                            <select name="vendedor" class="form-control">
                                <option value="0">-- Todos --</option>
                                <?php while($v = $VendedoresSql->fetch_assoc()): 
                                $nombreCompleto = trim($v['nombre'].' '.$v['apellido1'].' '.$v['apellido2']);
                            ?>
                                <option value="<?php echo $v['id']; ?>"
                                    <?php echo ($id_vendedor_filtro == $v['id'] ? 'selected' : ''); ?>>
                                    <?php echo $nombreCompleto; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i> Filtrar
                        </button>
                    </form>
                </div>
            </div>

            <hr>

            <!-- TABLA RESUMEN -->
            <div class="row">
                <div class="col-sm-12">
                    <table cellpadding="0" cellspacing="0" border="0"
                        class="table table-striped table-bordered table-condensed" id="tabla_ventas_vendedor">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Total Bruto</th>
                                <th>Total Comisión</th>
                                <th>Total Caja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                        $suma_bruto    = 0;
                        $suma_comision = 0;
                        $suma_caja     = 0;

                        while($row = $VentasTotalesSql->fetch_assoc()):
                            $total_bruto   = (float)$row['total_bruto'];
                            $total_comision= (float)$row['total_comision'];
                            $total_caja    = $total_bruto - $total_comision;

                            $suma_bruto    += $total_bruto;
                            $suma_comision += $total_comision;
                            $suma_caja     += $total_caja;

                            $nombreVendedor = trim(
                                ($row['nombre'] ?? '').' '.
                                ($row['apellido1'] ?? '').' '.
                                ($row['apellido2'] ?? '')
                            );
                            if ($nombreVendedor == '') {
                                $nombreVendedor = 'Sin nombre';
                            }
                        ?>
                            <tr>
                                <td><?php echo $nombreVendedor; ?></td>
                                <td>Bs <?php echo number_format($total_bruto, 2); ?></td>
                                <td>Bs <?php echo number_format($total_comision, 2); ?></td>
                                <td>Bs <?php echo number_format($total_caja, 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>TOTAL GENERAL</th>
                                <th>Bs <?php echo number_format($suma_bruto, 2); ?></th>
                                <th>Bs <?php echo number_format($suma_comision, 2); ?></th>
                                <th>Bs <?php echo number_format($suma_caja, 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>

    <?php include(MODULO.'Tema.JS.php'); ?>
    <script type="text/javascript" src="<?php echo ESTATICO; ?>js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="<?php echo ESTATICO; ?>js/dataTables.bootstrap.js"></script>
    <script type="text/javascript">
    $(document).ready(function() {
        $('#tabla_ventas_vendedor').dataTable({
            "order": [
                [1, 'desc']
            ]
        });
    });
    </script>

</body>

</html>