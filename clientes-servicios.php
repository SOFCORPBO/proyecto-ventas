<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

/* ===============================
      VALIDAR CLIENTE
================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Cliente no válido.</div>";
    exit;
}

$idCliente = intval($_GET['id']);

/* ===============================
      OBTENER DATOS DEL CLIENTE
================================ */
$ClienteSql = $db->SQL("SELECT * FROM cliente WHERE id=$idCliente LIMIT 1");
$Cliente = $ClienteSql->fetch_assoc();

if (!$Cliente) {
    echo "<div class='alert alert-danger'>El cliente no existe.</div>";
    exit;
}

/* ===============================
      HISTORIAL DE SERVICIOS
================================ */
$ServiciosSql = $db->SQL("
    SELECT 
        f.id AS id_factura,
        f.fecha AS fecha_venta,
        fd.cantidad,
        fd.precio,
        fd.subtotal,
        fd.comision,
        fd.tipo_pago,
        p.nombre AS producto_nombre,
        u.usuario AS vendedor,
        f.con_factura,
        f.estado
    FROM factura f
    INNER JOIN factura_detalle fd ON fd.idfactura = f.id
    INNER JOIN producto p ON p.id = fd.producto
    LEFT JOIN usuario u ON u.id = f.vendedor
    WHERE f.cliente = '$idCliente'
    ORDER BY f.id DESC
");

/* ===============================
      KPI DEL CLIENTE
================================ */
$KpiSql = $db->SQL("
    SELECT 
        COUNT(*) AS total_servicios,
        SUM(total) AS monto_total,
        MAX(fecha) AS ultima_compra
    FROM factura
    WHERE cliente = '$idCliente'
");
$KPI = $KpiSql->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Servicios del Cliente | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .card-box {
        padding: 14px;
        border-radius: 10px;
        color: #fff;
        margin-bottom: 15px;
        text-align: center;
    }

    .card-blue {
        background: #2196F3;
    }

    .card-green {
        background: #4CAF50;
    }

    .card-orange {
        background: #FF9800;
    }
    </style>

</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==2) include(MODULO.'menu_vendedor.php');
else include(MODULO.'menu_admin.php');
?>

    <div class="container" id="wrap">

        <!-- HEADER -->
        <div class="page-header">
            <h1>Servicios de: <?= $Cliente['nombre'] ?></h1>
            <a href="clientes.php" class="btn btn-default pull-right">
                <i class="fa fa-arrow-left"></i> Volver
            </a>
            <div style="clear:both;"></div>
        </div>

        <!-- DATOS DEL CLIENTE -->
        <div class="well">
            <h4><i class="fa fa-user"></i> Datos del Cliente</h4>
            <p><b>Nombre:</b> <?= $Cliente['nombre'] ?></p>
            <p><b>CI / Pasaporte:</b> <?= $Cliente['ci_pasaporte'] ?></p>
            <p><b>Teléfono:</b> <?= $Cliente['telefono'] ?></p>
            <p><b>Email:</b> <?= $Cliente['email'] ?></p>
        </div>

        <!-- KPI -->
        <div class="row">

            <div class="col-sm-3">
                <div class="card-box card-blue">
                    <h4>Total Servicios</h4>
                    <h2><?= $KPI['total_servicios'] ?></h2>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="card-box card-green">
                    <h4>Monto Total</h4>
                    <h2><?= number_format($KPI['monto_total'],2) ?> Bs</h2>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="card-box card-orange">
                    <h4>Última Compra</h4>
                    <h3><?= $KPI['ultima_compra'] ?: 'N/A' ?></h3>
                </div>
            </div>

        </div>

        <!-- TABLA DE SERVICIOS -->
        <table id="tabla_servicios" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th># Factura</th>
                    <th>Fecha</th>
                    <th>Servicio</th>
                    <th>Cant.</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                    <th>Comisión</th>
                    <th>Pago</th>
                    <th>Con Factura</th>
                    <th>Vendedor</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php while($s = $ServiciosSql->fetch_assoc()): ?>
                <tr>

                    <td><?= $s['id_factura'] ?></td>
                    <td><?= $s['fecha_venta'] ?></td>
                    <td><?= $s['producto_nombre'] ?></td>
                    <td><?= $s['cantidad'] ?></td>
                    <td><?= number_format($s['precio'],2) ?></td>
                    <td><?= number_format($s['subtotal'],2) ?></td>
                    <td><?= number_format($s['comision'],2) ?></td>
                    <td><?= $s['tipo_pago'] ?></td>

                    <td>
                        <?= $s['con_factura'] == 1 ? '<span class="label label-success">Sí</span>' : '<span class="label label-default">No</span>' ?>
                    </td>

                    <td><?= $s['vendedor'] ?></td>

                    <td>
                        <span class="label label-info"><?= $s['estado'] ?></span>
                    </td>

                    <td>
                        <a href="ver-factura.php?id=<?= $s['id_factura'] ?>" class="btn btn-xs btn-primary">
                            <i class="fa fa-eye"></i>
                        </a>
                    </td>

                </tr>
                <?php endwhile; ?>
            </tbody>

        </table>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_servicios').dataTable();
    </script>

</body>

</html>