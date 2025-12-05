<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

if (!isset($usuarioApp)) {
    echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
    exit;
}

date_default_timezone_set(HORARIO);

/* ======================================================
|   RANGO DE FECHAS (PARA GRÁFICOS Y KPIs)
====================================================== */
$hoy = date('Y-m-d');
$primerDiaMes = date('Y-m-01');

$desde = isset($_GET['desde']) && $_GET['desde'] != '' ? $_GET['desde'] : $primerDiaMes;
$hasta = isset($_GET['hasta']) && $_GET['hasta'] != '' ? $_GET['hasta'] : $hoy;

/* ======================================================
|   KPIs PRINCIPALES
====================================================== */

/* Saldo actual Caja General */
$saldo_caja_general = 0.00;
$SaldoGenSQL = $db->SQL("SELECT saldo_caja FROM caja_general_movimientos ORDER BY id DESC LIMIT 1");
if ($SaldoGenSQL && $SaldoGenSQL->num_rows > 0) {
    $saldo_caja_general = (float)$SaldoGenSQL->fetch_assoc()['saldo_caja'];
} else {
    $CajaGenSQL = $db->SQL("
        SELECT monto 
        FROM caja 
        WHERE tipo_caja='GENERAL' AND habilitado=1
        ORDER BY id DESC LIMIT 1
    ");
    if ($CajaGenSQL && $CajaGenSQL->num_rows > 0) {
        $saldo_caja_general = (float)$CajaGenSQL->fetch_assoc()['monto'];
    }
}

/* Saldo actual Caja Chica */
$saldo_caja_chica = 0.00;
$SaldoChicaSQL = $db->SQL("SELECT saldo_resultante FROM caja_chica_movimientos ORDER BY id DESC LIMIT 1");
if ($SaldoChicaSQL && $SaldoChicaSQL->num_rows > 0) {
    $saldo_caja_chica = (float)$SaldoChicaSQL->fetch_assoc()['saldo_resultante'];
}

/* Ingresos / Egresos Caja General en rango */
$ing_gen = 0; $egr_gen = 0;
$TotGenSQL = $db->SQL("
    SELECT 
        SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE 0 END) AS total_ing,
        SUM(CASE WHEN tipo='EGRESO' THEN monto ELSE 0 END) AS total_egr
    FROM caja_general_movimientos
    WHERE fecha BETWEEN '{$desde}' AND '{$hasta}'
");
if ($TotGenSQL && $TotGenSQL->num_rows > 0) {
    $row = $TotGenSQL->fetch_assoc();
    $ing_gen = (float)$row['total_ing'];
    $egr_gen = (float)$row['total_egr'];
}

/* Ingresos / Egresos Caja Chica en rango */
$ing_chica = 0; $egr_chica = 0;
$TotChicaSQL = $db->SQL("
    SELECT 
        SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE 0 END) AS total_ing,
        SUM(CASE WHEN tipo='EGRESO' THEN monto ELSE 0 END) AS total_egr
    FROM caja_chica_movimientos
    WHERE fecha BETWEEN '{$desde}' AND '{$hasta}'
");
if ($TotChicaSQL && $TotChicaSQL->num_rows > 0) {
    $row = $TotChicaSQL->fetch_assoc();
    $ing_chica = (float)$row['total_ing'];
    $egr_chica = (float)$row['total_egr'];
}

/* Ventas de servicios (tabla ventas) en el rango */
$total_ventas = 0; 
$total_comision = 0;
$VentasSQL = $db->SQL("
    SELECT 
        SUM(totalprecio) AS total_ventas,
        SUM(comision)    AS total_comision
    FROM ventas
    WHERE STR_TO_DATE(fecha,'%d-%m-%Y') BETWEEN '{$desde}' AND '{$hasta}'
");
if ($VentasSQL && $VentasSQL->num_rows > 0) {
    $row = $VentasSQL->fetch_assoc();
    $total_ventas = (float)$row['total_ventas'];
    $total_comision = (float)$row['total_comision'];
}

/* ======================================================
|   SERIES PARA GRÁFICOS (CAJA GENERAL)
====================================================== */

/* Serie 1: Línea – Movimiento neto diario Caja General */
$cg_labels = [];
$cg_data_neto = [];

$SerieGenSQL = $db->SQL("
    SELECT fecha,
           SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END) AS neto
    FROM caja_general_movimientos
    WHERE fecha BETWEEN '{$desde}' AND '{$hasta}'
    GROUP BY fecha
    ORDER BY fecha
");
if ($SerieGenSQL) {
    while ($r = $SerieGenSQL->fetch_assoc()) {
        $cg_labels[]   = $r['fecha'];
        $cg_data_neto[] = (float)$r['neto'];
    }
}

/* Serie 2: Barra – Ingresos vs Egresos diarios (Caja General) */
$cg_labels_ie = [];
$cg_ingresos = [];
$cg_egresos = [];

$SerieGenIESQL = $db->SQL("
    SELECT fecha,
           SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE 0 END) AS ingresos,
           SUM(CASE WHEN tipo='EGRESO' THEN monto ELSE 0 END) AS egresos
    FROM caja_general_movimientos
    WHERE fecha BETWEEN '{$desde}' AND '{$hasta}'
    GROUP BY fecha
    ORDER BY fecha
");
if ($SerieGenIESQL) {
    while ($r = $SerieGenIESQL->fetch_assoc()) {
        $cg_labels_ie[] = $r['fecha'];
        $cg_ingresos[]  = (float)$r['ingresos'];
        $cg_egresos[]   = (float)$r['egresos'];
    }
}

/* Serie 3: Pie – Métodos de pago (Caja General) */
$mp_labels = [];
$mp_data = [];

$MetodosSQL = $db->SQL("
    SELECT metodo_pago, SUM(monto) AS total
    FROM caja_general_movimientos
    WHERE fecha BETWEEN '{$desde}' AND '{$hasta}'
    GROUP BY metodo_pago
");
if ($MetodosSQL) {
    while ($r = $MetodosSQL->fetch_assoc()) {
        $mp_labels[] = $r['metodo_pago'];
        $mp_data[]   = (float)$r['total'];
    }
}

/* ======================================================
|   SERIES PARA GRÁFICOS (CAJA CHICA Y VENTAS)
====================================================== */

/* Serie 4: Línea – Movimiento neto diario Caja Chica */
$cc_labels = [];
$cc_data_neto = [];

$SerieChicaSQL = $db->SQL("
    SELECT fecha,
           SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END) AS neto
    FROM caja_chica_movimientos
    WHERE fecha BETWEEN '{$desde}' AND '{$hasta}'
    GROUP BY fecha
    ORDER BY fecha
");
if ($SerieChicaSQL) {
    while ($r = $SerieChicaSQL->fetch_assoc()) {
        $cc_labels[]   = $r['fecha'];
        $cc_data_neto[] = (float)$r['neto'];
    }
}

/* Serie 5: Barra – Ventas por día (tabla ventas) */
$ventas_labels = [];
$ventas_data   = [];

$VentasDiaSQL = $db->SQL("
    SELECT 
        DATE_FORMAT(STR_TO_DATE(fecha,'%d-%m-%Y'),'%Y-%m-%d') AS f,
        SUM(totalprecio) AS total
    FROM ventas
    WHERE STR_TO_DATE(fecha,'%d-%m-%Y') BETWEEN '{$desde}' AND '{$hasta}'
    GROUP BY f
    ORDER BY f
");
if ($VentasDiaSQL) {
    while ($r = $VentasDiaSQL->fetch_assoc()) {
        $ventas_labels[] = $r['f'];
        $ventas_data[]   = (float)$r['total'];
    }
}

/* Serie 6: Doughnut – Distribución saldos General vs Chica */
$dist_labels = ['Caja General','Caja Chica'];
$dist_data   = [$saldo_caja_general, $saldo_caja_chica];

/* ======================================================
|   JSON PARA JAVASCRIPT
====================================================== */
$js_cg_labels       = json_encode($cg_labels);
$js_cg_data_neto    = json_encode($cg_data_neto);
$js_cg_labels_ie    = json_encode($cg_labels_ie);
$js_cg_ingresos     = json_encode($cg_ingresos);
$js_cg_egresos      = json_encode($cg_egresos);
$js_mp_labels       = json_encode($mp_labels);
$js_mp_data         = json_encode($mp_data);
$js_cc_labels       = json_encode($cc_labels);
$js_cc_data_neto    = json_encode($cc_data_neto);
$js_ventas_labels   = json_encode($ventas_labels);
$js_ventas_data     = json_encode($ventas_data);
$js_dist_labels     = json_encode($dist_labels);
$js_dist_data       = json_encode($dist_data);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Panel de Cajas | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    .kpi-box {
        padding: 15px;
        border-radius: 4px;
        color: #fff;
        margin-bottom: 15px;
    }

    .kpi-blue {
        background: #337ab7;
    }

    .kpi-green {
        background: #5cb85c;
    }

    .kpi-red {
        background: #d9534f;
    }

    .kpi-yellow {
        background: #f0ad4e;
    }

    .kpi-purple {
        background: #5bc0de;
    }

    canvas {
        max-height: 280px;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 2) include(MODULO.'menu_vendedor.php');
elseif ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header">
                <div class="row">
                    <div class="col-md-8">
                        <h1>Panel de Cajas</h1>
                        <p class="text-muted">Visión consolidada de Caja General, Caja Chica y Ventas de Servicios.</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <form class="form-inline" method="get" style="margin-top:20px;">
                            <label>Desde:&nbsp;</label>
                            <input type="date" name="desde" class="form-control"
                                value="<?php echo htmlspecialchars($desde); ?>">
                            <label>&nbsp;Hasta:&nbsp;</label>
                            <input type="date" name="hasta" class="form-control"
                                value="<?php echo htmlspecialchars($hasta); ?>">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row">
                <div class="col-md-3">
                    <div class="kpi-box kpi-blue">
                        <h4>Saldo Caja General</h4>
                        <h3>Bs <?php echo number_format($saldo_caja_general,2); ?></h3>
                        <small>Último saldo registrado</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-box kpi-green">
                        <h4>Saldo Caja Chica</h4>
                        <h3>Bs <?php echo number_format($saldo_caja_chica,2); ?></h3>
                        <small>Último saldo registrado</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-box kpi-purple">
                        <h4>Ventas de Servicios</h4>
                        <h3>Bs <?php echo number_format($total_ventas,2); ?></h3>
                        <small>Rango: <?php echo $desde.' a '.$hasta; ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-box kpi-yellow">
                        <h4>Comisiones Calculadas</h4>
                        <h3>Bs <?php echo number_format($total_comision,2); ?></h3>
                        <small>Tabla ventas / campo comision</small>
                    </div>
                </div>
            </div>

            <!-- Fila 1 de gráficos -->
            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">Caja General – Movimiento Neto Diario</div>
                        <div class="panel-body">
                            <canvas id="cgNetoChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">Caja General – Ingresos vs Egresos</div>
                        <div class="panel-body">
                            <canvas id="cgIEChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">Caja General – Métodos de Pago</div>
                        <div class="panel-body">
                            <canvas id="metodosChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila 2 de gráficos -->
            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-info">
                        <div class="panel-heading">Caja Chica – Movimiento Neto Diario</div>
                        <div class="panel-body">
                            <canvas id="ccNetoChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="panel panel-info">
                        <div class="panel-heading">Ventas de Servicios por Día</div>
                        <div class="panel-body">
                            <canvas id="ventasChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="panel panel-info">
                        <div class="panel-heading">Distribución de Saldos – General vs Chica</div>
                        <div class="panel-body">
                            <canvas id="distChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script>
    (function() {
        // Datos desde PHP
        const cgLabels = <?php echo $js_cg_labels; ?>;
        const cgNeto = <?php echo $js_cg_data_neto; ?>;
        const cgIELabels = <?php echo $js_cg_labels_ie; ?>;
        const cgIngresos = <?php echo $js_cg_ingresos; ?>;
        const cgEgresos = <?php echo $js_cg_egresos; ?>;
        const mpLabels = <?php echo $js_mp_labels; ?>;
        const mpData = <?php echo $js_mp_data; ?>;
        const ccLabels = <?php echo $js_cc_labels; ?>;
        const ccNeto = <?php echo $js_cc_data_neto; ?>;
        const ventasLabels = <?php echo $js_ventas_labels; ?>;
        const ventasData = <?php echo $js_ventas_data; ?>;
        const distLabels = <?php echo $js_dist_labels; ?>;
        const distData = <?php echo $js_dist_data; ?>;

        // 1) Caja General – Neto diario (linea)
        new Chart(document.getElementById('cgNetoChart'), {
            type: 'line',
            data: {
                labels: cgLabels,
                datasets: [{
                    label: 'Neto (Bs)',
                    data: cgNeto,
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 2) Caja General – Ingresos vs Egresos (barras)
        new Chart(document.getElementById('cgIEChart'), {
            type: 'bar',
            data: {
                labels: cgIELabels,
                datasets: [{
                        label: 'Ingresos',
                        data: cgIngresos,
                        borderWidth: 1
                    },
                    {
                        label: 'Egresos',
                        data: cgEgresos,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 3) Métodos de pago (pie)
        new Chart(document.getElementById('metodosChart'), {
            type: 'pie',
            data: {
                labels: mpLabels,
                datasets: [{
                    data: mpData
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 4) Caja Chica – Neto diario (línea)
        new Chart(document.getElementById('ccNetoChart'), {
            type: 'line',
            data: {
                labels: ccLabels,
                datasets: [{
                    label: 'Neto (Bs)',
                    data: ccNeto,
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 5) Ventas por día (barras)
        new Chart(document.getElementById('ventasChart'), {
            type: 'bar',
            data: {
                labels: ventasLabels,
                datasets: [{
                    label: 'Total ventas (Bs)',
                    data: ventasData,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 6) Distribución de saldos (doughnut)
        new Chart(document.getElementById('distChart'), {
            type: 'doughnut',
            data: {
                labels: distLabels,
                datasets: [{
                    data: distData
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

    })();
    </script>

</body>

</html>