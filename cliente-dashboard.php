<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$ClienteClase = new Cliente();

/* =====================================================
   FILTROS DEL DASHBOARD
===================================================== */
$f = [
    'nombre'        => $_GET['nombre']        ?? '',
    'nacionalidad'  => $_GET['nacionalidad']  ?? '',
    'estado'        => $_GET['estado']        ?? '',
    'desde'         => $_GET['desde']         ?? '',
    'hasta'         => $_GET['hasta']         ?? '',
    'min_tramites'  => $_GET['min_tramites']  ?? 0,
    'min_servicios' => $_GET['min_servicios'] ?? 0,
];

/* =====================================================
   LISTADO GLOBAL CLIENTES + KPI DE CONSUMO
===================================================== */
$ClientesSQL = $db->SQL("
    SELECT 
        c.*,
        (SELECT COUNT(*) FROM tramites t WHERE t.id_cliente=c.id) AS tramites,
        (SELECT COUNT(*) FROM ventas v WHERE v.cliente=c.id AND v.habilitada=1) AS servicios,
        (SELECT SUM(v.totalprecio) FROM ventas v WHERE v.cliente=c.id AND v.habilitada=1) AS monto_total,
        (SELECT COUNT(*) FROM cotizacion ct WHERE ct.id_cliente=c.id) AS cotizaciones
    FROM cliente c
    WHERE 1=1
        ".($f['nombre'] ? "AND c.nombre LIKE '%{$f['nombre']}%'" : "")."
        ".($f['nacionalidad'] ? "AND c.nacionalidad LIKE '%{$f['nacionalidad']}%'" : "")."
        ".($f['estado'] !== '' ? "AND c.habilitado='{$f['estado']}'" : "")."
    HAVING 
        tramites >= {$f['min_tramites']}
        AND servicios >= {$f['min_servicios']}
    ORDER BY c.id DESC
");

/* =====================================================
   KPI PRINCIPALES
===================================================== */
$TotalClientes     = $db->SQL("SELECT COUNT(*) total FROM cliente")->fetch_assoc()['total'];
$ClientesActivos   = $db->SQL("SELECT COUNT(*) total FROM cliente WHERE habilitado=1")->fetch_assoc()['total'];
$ClientesInactivos = $db->SQL("SELECT COUNT(*) total FROM cliente WHERE habilitado=0")->fetch_assoc()['total'];

$TramitesActivos   = $db->SQL("
    SELECT COUNT(*) total 
    FROM tramites 
    WHERE estado IN ('PENDIENTE','EN_PROCESO')
")->fetch_assoc()['total'];

$TramitesPorVencer = $db->SQL("
    SELECT COUNT(*) total 
    FROM tramites 
    WHERE fecha_vencimiento <= DATE_ADD(NOW(), INTERVAL 10 DAY)
      AND estado IN ('PENDIENTE','EN_PROCESO')
")->fetch_assoc()['total'];

/* =====================================================
   KPI DE VENTAS DE CLIENTES
===================================================== */
$TotalFacturado = $db->SQL("
    SELECT SUM(totalprecio) AS total 
    FROM ventas 
    WHERE habilitada=1
")->fetch_assoc()['total'] ?? 0;

$TicketPromedio = $db->SQL("
    SELECT AVG(totalprecio) AS promedio
    FROM ventas 
    WHERE habilitada=1
")->fetch_assoc()['promedio'] ?? 0;

/* ---- Cliente con mayor consumo ---- */
$MayorConsumoSQL = $db->SQL("
    SELECT 
        c.nombre AS cliente,
        SUM(v.totalprecio) AS total_consumo
    FROM ventas v
    INNER JOIN cliente c ON c.id = v.cliente
    WHERE v.habilitada = 1
    GROUP BY v.cliente
    ORDER BY total_consumo DESC
    LIMIT 1
");

if ($MayorConsumoSQL->num_rows > 0) {
    $Mayor = $MayorConsumoSQL->fetch_assoc();
    $ClienteMayorCompra = $Mayor['cliente'];
    $MontoMayorCompra = number_format($Mayor['total_consumo'], 2);
} else {
    $ClienteMayorCompra = "N/A";
    $MontoMayorCompra = "0.00";
}

/* =====================================================
   TOP 10 SERVICIOS MÁS ADQUIRIDOS POR CLIENTES
===================================================== */
$TopServiciosSQL = $db->SQL("
    SELECT 
        p.nombre AS servicio,
        SUM(v.cantidad) AS total_cantidad,
        SUM(v.totalprecio) AS total_monto
    FROM ventas v
    LEFT JOIN producto p ON p.id=v.producto
    WHERE v.habilitada=1
    GROUP BY v.producto
    ORDER BY total_cantidad DESC
    LIMIT 10
");

/* =====================================================
   TOP 10 CLIENTES CON MAYOR CONSUMO
===================================================== */
$TopClientesSQL = $db->SQL("
    SELECT 
        c.nombre AS cliente,
        COUNT(v.id) AS total_servicios,
        SUM(v.totalprecio) AS total_gastado
    FROM ventas v
    LEFT JOIN cliente c ON c.id=v.cliente
    WHERE v.habilitada=1
    GROUP BY v.cliente
    ORDER BY total_gastado DESC
    LIMIT 10
");

/* =====================================================
   HISTORIAL GLOBAL DE VENTAS REALIZADAS POR CLIENTES
===================================================== */
$VentasSQL = $db->SQL("
    SELECT 
        v.*,
        c.nombre AS cliente_nombre,
        p.nombre AS servicio_nombre,
        p.tipo_servicio
    FROM ventas v
    LEFT JOIN cliente c ON c.id=v.cliente
    LEFT JOIN producto p ON p.id=v.producto
    WHERE v.habilitada = 1
    ORDER BY v.id DESC
");

/* =====================================================
   INGRESOS MENSUALES (GRÁFICO)
===================================================== */
$IngresosMensualesSQL = $db->SQL("
    SELECT 
        DATE_FORMAT(v.fecha, '%Y-%m') AS mes,
        SUM(v.totalprecio) AS total_mensual
    FROM ventas v
    WHERE v.habilitada=1
    GROUP BY mes
    ORDER BY mes ASC
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Dashboard de Clientes | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .kpi-box {
        padding: 15px;
        border-radius: 6px;
        color: #fff;
        margin-bottom: 15px;
        text-align: center;
    }

    .kpi1 {
        background: #3f51b5;
    }

    .kpi2 {
        background: #4caf50;
    }

    .kpi3 {
        background: #f44336;
    }

    .kpi4 {
        background: #009688;
    }

    .kpi5 {
        background: #ff9800;
    }

    .kpi6 {
        background: #673ab7;
    }

    .kpi7 {
        background: #e91e63;
    }

    .kpi8 {
        background: #9c27b0;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>Dashboard General de Clientes</h1>
            <small class="text-muted">Análisis completo del comportamiento de clientes y servicios adquiridos.</small>
        </div>

        <!-- ===========================
         KPI PRINCIPALES
    ============================ -->
        <div class="row">

            <div class="col-sm-2">
                <div class="kpi-box kpi1">
                    <h2><?= $TotalClientes ?></h2><small>Total Clientes</small>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="kpi-box kpi2">
                    <h2><?= $ClientesActivos ?></h2><small>Activos</small>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="kpi-box kpi3">
                    <h2><?= $ClientesInactivos ?></h2><small>Inactivos</small>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi-box kpi4">
                    <h2><?= $TramitesActivos ?></h2><small>Trámites Activos</small>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi-box kpi5">
                    <h2><?= $TramitesPorVencer ?></h2><small>Trámites por Vencer</small>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="kpi-box kpi6">
                    <h2><?= number_format($TotalFacturado,2) ?> Bs</h2><small>Total Facturado</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="kpi-box kpi7">
                    <h2><?= number_format($TicketPromedio,2) ?> Bs</h2><small>Ticket Promedio</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="kpi-box kpi8">
                    <h2><?= $ClienteMayorCompra ?></h2>
                    <small>Cliente con Mayor Consumo</small><br>
                    <strong><?= $MontoMayorCompra ?> Bs</strong>
                </div>
            </div>

        </div>

        <!-- ===========================
         FILTROS
    ============================ -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Filtros de Clientes</strong></div>
            <div class="panel-body">

                <form method="GET" class="form-inline">

                    <input type="text" name="nombre" placeholder="Nombre" class="form-control"
                        value="<?= $f['nombre'] ?>">
                    <input type="text" name="nacionalidad" placeholder="Nacionalidad" class="form-control"
                        value="<?= $f['nacionalidad'] ?>">

                    <select name="estado" class="form-control">
                        <option value="">Estado</option>
                        <option value="1" <?= $f['estado']==='1'?'selected':'' ?>>Activos</option>
                        <option value="0" <?= $f['estado']==='0'?'selected':'' ?>>Inactivos</option>
                    </select>

                    <label>Desde:</label>
                    <input type="date" name="desde" value="<?= $f['desde'] ?>" class="form-control">

                    <label>Hasta:</label>
                    <input type="date" name="hasta" value="<?= $f['hasta'] ?>" class="form-control">

                    <input type="number" name="min_tramites" placeholder="Min. Trámites" class="form-control"
                        value="<?= $f['min_tramites'] ?>">
                    <input type="number" name="min_servicios" placeholder="Min. Servicios" class="form-control"
                        value="<?= $f['min_servicios'] ?>">

                    <button class="btn btn-primary">Aplicar</button>
                    <a href="clientes-dashboard.php" class="btn btn-default">Limpiar</a>

                </form>
            </div>
        </div>

        <!-- ===========================
         TABLA PRINCIPAL CLIENTES
    ============================ -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Clientes — Resultado del Análisis</strong></div>

            <div class="panel-body">
                <table class="table table-bordered table-striped" id="tabla_dashboard_clientes">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>CI</th>
                            <th>Nacionalidad</th>
                            <th>Teléfono</th>
                            <th>Trámites</th>
                            <th>Servicios</th>
                            <th>Monto Total</th>
                            <th>Estado</th>
                            <th>Expediente</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while($c = $ClientesSQL->fetch_assoc()): ?>
                        <tr>
                            <td><?= $c['nombre'] ?></td>
                            <td><?= $c['ci_pasaporte'] ?></td>
                            <td><?= $c['nacionalidad'] ?></td>
                            <td><?= $c['telefono'] ?></td>

                            <td><span class="label label-primary"><?= $c['tramites'] ?></span></td>
                            <td><span class="label label-success"><?= $c['servicios'] ?></span></td>
                            <td><strong><?= number_format($c['monto_total'] ?? 0,2) ?> Bs</strong></td>

                            <td>
                                <?php if($c['habilitado']==1): ?>
                                <span class="label label-success">Activo</span>
                                <?php else: ?>
                                <span class="label label-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="cliente-expediente.php?id=<?= $c['id'] ?>" class="btn btn-info btn-xs">
                                    <i class="fa fa-folder-open"></i> Ver
                                </a>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===========================
         VENTAS REALIZADAS POR CLIENTES
    ============================ -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Ventas de Servicios Realizadas</strong></div>

            <div class="panel-body">
                <table class="table table-bordered table-striped" id="tabla_ventas_clientes">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Método Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($v = $VentasSQL->fetch_assoc()): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= $v['fecha'] ?> <?= $v['hora'] ?></td>
                            <td><?= $v['cliente_nombre'] ?></td>
                            <td><?= $v['servicio_nombre'] ?></td>
                            <td><span class="label label-info"><?= $v['tipo_servicio'] ?></span></td>
                            <td><?= $v['cantidad'] ?></td>
                            <td><strong><?= number_format($v['totalprecio'],2) ?> Bs</strong></td>
                            <td><?= $v['metodo_pago'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===========================
         TOP 10 CLIENTES
    ============================ -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Top 10 Clientes con Mayor Consumo</strong></div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Servicios Comprados</th>
                        <th>Total Gastado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($t = $TopClientesSQL->fetch_assoc()): ?>
                    <tr>
                        <td><?= $t['cliente'] ?></td>
                        <td><?= $t['total_servicios'] ?></td>
                        <td><strong><?= number_format($t['total_gastado'],2) ?> Bs</strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- ===========================
         TOP 10 SERVICIOS MÁS COMPRADOS
    ============================ -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Top 10 Servicios Más Vendidos</strong></div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Cantidad Vendida</th>
                        <th>Total Generado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($s = $TopServiciosSQL->fetch_assoc()): ?>
                    <tr>
                        <td><?= $s['servicio'] ?></td>
                        <td><?= $s['total_cantidad'] ?></td>
                        <td><strong><?= number_format($s['total_monto'],2) ?> Bs</strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- ===========================
         GRÁFICO INGRESOS MENSUALES
    ============================ -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Ingresos Mensuales</strong></div>
            <div class="panel-body">
                <canvas id="chart_ingresos" height="100"></canvas>
            </div>
        </div>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_dashboard_clientes').dataTable();
    $('#tabla_ventas_clientes').dataTable();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    var ctx = document.getElementById('chart_ingresos').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
            $IngresosMensualesSQL->data_seek(0);
            while($m = $IngresosMensualesSQL->fetch_assoc()):
                echo "'".$m['mes']."',";
            endwhile;
            ?>
            ],
            datasets: [{
                label: "Ingresos Mensuales",
                backgroundColor: "#3f51b5",
                data: [
                    <?php 
                $IngresosMensualesSQL->data_seek(0);
                while($m = $IngresosMensualesSQL->fetch_assoc()):
                    echo $m['total_mensual'].",";
                endwhile;
                ?>
                ]
            }]
        }
    });
    </script>

</body>

</html>