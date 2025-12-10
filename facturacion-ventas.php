<?php
session_start();
define('acceso', true);

include("sistema/configuracion.php");
include("sistema/clase/facturacion_ventas.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Facturacion = new FacturacionVentas();

/* ============================================================
   📌 IDENTIFICAR USUARIO FACTURADOR (RESPONSABLE)
============================================================ */
$usuario_facturador = isset($usuarioApp['id'])
    ? (int)$usuarioApp['id']
    : (isset($usuarioApp['id_usuario']) ? (int)$usuarioApp['id_usuario'] : 0);

/* ============================================================
   📌 ACCIONES: MARCAR COMO FACTURADA / SIN FACTURA
============================================================ */
if (isset($_POST['AccionFactura']) && isset($_POST['id_venta'])) {

    $idVenta = (int)$_POST['id_venta'];
    $accion  = $_POST['AccionFactura'];

    if ($accion === 'FACTURAR') {

        // En esta primera versión usamos datos mínimos.
        // Más adelante podemos abrir un modal para capturar NIT / Razón / Nº.
        $Facturacion->MarcarComoFacturada($idVenta, [
            'usuario_factura' => $usuario_facturador
        ]);

        echo '<div class="alert alert-success">
                <i class="fa fa-check"></i> La venta fue marcada como <strong>FACTURADA</strong>.
              </div>
              <meta http-equiv="refresh" content="1;url=facturacion-ventas.php">';
        exit;

    } elseif ($accion === 'SIN_FACTURA') {

        $Facturacion->MarcarSinFactura($idVenta);

        echo '<div class="alert alert-warning">
                <i class="fa fa-info-circle"></i> La venta fue marcada como <strong>Sin factura</strong>.
              </div>
              <meta http-equiv="refresh" content="1;url=facturacion-ventas.php">';
        exit;
    }
}

/* ============================================================
   📌 FILTROS
============================================================ */
$hoy = date('Y-m-d');

$filtros = [
    'desde'       => $_GET['desde']       ?? $hoy,
    'hasta'       => $_GET['hasta']       ?? $hoy,
    'con_factura' => $_GET['con_factura'] ?? '',
    'cliente_id'  => $_GET['cliente_id']  ?? '',
    'metodo_pago' => $_GET['metodo_pago'] ?? '',
    'anulada'     => $_GET['anulada']     ?? ''
];

/* ============================================================
   📌 KPI DE FACTURACIÓN
============================================================ */
$KPI = $Facturacion->KPIs(
    $filtros['desde'] ?: null,
    $filtros['hasta'] ?: null
);

/* ============================================================
   📌 LISTADO DE VENTAS (SEGÚN FILTROS)
============================================================ */
$VentasSQL = $Facturacion->ListarVentas($filtros);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Facturación de Ventas | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
    .kpi-box {
        padding: 18px;
        color: #fff;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
    }

    .k1 {
        background: #3f51b5;
    }

    /* Total ventas */
    .k2 {
        background: #4caf50;
    }

    /* Con factura */
    .k3 {
        background: #ff9800;
    }

    /* Sin factura */
    .k4 {
        background: #009688;
    }

    /* Monto facturado */
    .k5 {
        background: #607d8b;
    }

    /* Monto no facturado */

    .badge-facturada {
        background: #4caf50;
    }

    .badge-sin-factura {
        background: #f44336;
    }

    .badge-anulada {
        background: #9e9e9e;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>Facturación de Ventas</h1>
            <p class="text-muted">
                Panel de control para ventas <strong>facturadas</strong> y <strong>no facturadas</strong>,
                con indicadores y filtros contables.
            </p>
        </div>

        <!-- =============================
         KPI PRINCIPALES
    ============================== -->
        <div class="row">

            <div class="col-sm-2">
                <div class="kpi-box k1">
                    <h2><?= (int)$KPI['total'] ?></h2>
                    <small>Total de Ventas</small>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="kpi-box k2">
                    <h2><?= (int)$KPI['facturadas'] ?></h2>
                    <small>Con factura</small>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="kpi-box k3">
                    <h2><?= (int)$KPI['no_facturadas'] ?></h2>
                    <small>Sin factura</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box k4">
                    <h2><?= number_format($KPI['ventas_facturadas'] ?? 0, 2) ?> Bs</h2>
                    <small>Monto facturado</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box k5">
                    <h2><?= number_format($KPI['ventas_no_facturadas'] ?? 0, 2) ?> Bs</h2>
                    <small>Monto no facturado</small>
                </div>
            </div>

        </div>

        <!-- =============================
         FILTROS
    ============================== -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>Filtros de Búsqueda</strong>
            </div>
            <div class="panel-body">

                <form method="get" class="form-inline">

                    <label>Desde:&nbsp;</label>
                    <input type="date" name="desde" value="<?= htmlspecialchars($filtros['desde']) ?>"
                        class="form-control">

                    <label>&nbsp;Hasta:&nbsp;</label>
                    <input type="date" name="hasta" value="<?= htmlspecialchars($filtros['hasta']) ?>"
                        class="form-control">

                    <label>&nbsp;Factura:&nbsp;</label>
                    <select name="con_factura" class="form-control">
                        <option value="">Todas</option>
                        <option value="1" <?= $filtros['con_factura']==='1'?'selected':'' ?>>Con factura</option>
                        <option value="0" <?= $filtros['con_factura']==='0'?'selected':'' ?>>Sin factura</option>
                    </select>

                    <label>&nbsp;Método pago:&nbsp;</label>
                    <select name="metodo_pago" class="form-control">
                        <option value="">Todos</option>
                        <option value="EFECTIVO" <?= $filtros['metodo_pago']==='EFECTIVO'?'selected':'' ?>>Efectivo
                        </option>
                        <option value="TRANSFERENCIA" <?= $filtros['metodo_pago']==='TRANSFERENCIA'?'selected':'' ?>>
                            Transferencia</option>
                        <option value="DEPOSITO" <?= $filtros['metodo_pago']==='DEPOSITO'?'selected':'' ?>>Depósito
                        </option>
                        <option value="TARJETA" <?= $filtros['metodo_pago']==='TARJETA'?'selected':'' ?>>Tarjeta
                        </option>
                    </select>

                    <label>&nbsp;Anulada:&nbsp;</label>
                    <select name="anulada" class="form-control">
                        <option value="">Todas</option>
                        <option value="0" <?= $filtros['anulada']==='0'?'selected':'' ?>>No anuladas</option>
                        <option value="1" <?= $filtros['anulada']==='1'?'selected':'' ?>>Anuladas</option>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-filter"></i> Aplicar
                    </button>

                    <a href="facturacion-ventas.php" class="btn btn-default">
                        Limpiar
                    </a>

                </form>

            </div>
        </div>

        <!-- =============================
         TABLA PRINCIPAL DE VENTAS
    ============================== -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>Listado de Ventas (Facturación)</strong>
            </div>

            <div class="panel-body">

                <table class="table table-bordered table-striped" id="tabla_facturacion">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha / Hora</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Método Pago</th>
                            <th>Monto</th>
                            <th>Estado Factura</th>
                            <th>Usuario Facturación</th>
                            <th>Anulada</th>
                            <th width="160">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while($v = $VentasSQL->fetch_assoc()): ?>
                        <?php
                            $esFacturada = (int)$v['con_factura'] === 1;
                            $esAnulada   = isset($v['anulada']) ? (int)$v['anulada'] === 1 : false;
                        ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= $v['fecha'] . ' ' . $v['hora'] ?></td>
                            <td><?= $v['cliente_nombre'] ?></td>
                            <td><?= $v['servicio_nombre'] ?></td>
                            <td><?= $v['metodo_pago'] ?></td>
                            <td><strong><?= number_format($v['totalprecio'], 2) ?> Bs</strong></td>

                            <td>
                                <?php if ($esFacturada): ?>
                                <span class="badge badge-facturada">Con factura</span><br>
                                <small><?= $v['nro_comprobante'] ?: 'S/N' ?></small>
                                <?php else: ?>
                                <span class="badge badge-sin-factura">Sin factura</span>
                                <?php endif; ?>
                            </td>

                            <td><?= $v['usuario_facturado'] ?: '-' ?></td>

                            <td>
                                <?php if ($esAnulada): ?>
                                <span class="badge badge-anulada">Anulada</span>
                                <?php else: ?>
                                <span class="label label-success">Vigente</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!$esAnulada): ?>

                                <?php if (!$esFacturada): ?>
                                <!-- Marcar como FACTURADA -->
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="AccionFactura" value="FACTURAR">
                                    <input type="hidden" name="id_venta" value="<?= $v['id'] ?>">
                                    <button class="btn btn-success btn-xs" title="Marcar como facturada">
                                        <i class="fa fa-check"></i> Facturar
                                    </button>
                                </form>
                                <?php else: ?>
                                <!-- Marcar como SIN FACTURA -->
                                <form method="post" style="display:inline-block;"
                                    onsubmit="return confirm('¿Marcar esta venta como SIN FACTURA?');">
                                    <input type="hidden" name="AccionFactura" value="SIN_FACTURA">
                                    <input type="hidden" name="id_venta" value="<?= $v['id'] ?>">
                                    <button class="btn btn-warning btn-xs" title="Marcar como sin factura">
                                        <i class="fa fa-ban"></i> Sin factura
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php else: ?>
                                <em class="text-muted">Sin acciones</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>

            </div>
        </div>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_facturacion').dataTable({
        "order": [
            [0, "desc"]
        ]
    });
    </script>

</body>

</html>