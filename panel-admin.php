<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

if (!isset($usuarioApp)) {
    echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
    exit;
}

date_default_timezone_set(HORARIO);

/* ============================================================
|  FUNCIÓN AYUDA: LIMPIAR VALOR (básico)
============================================================ */
function v($key, $default = '') {
    return isset($_REQUEST[$key]) ? trim($_REQUEST[$key]) : $default;
}

/* ============================================================
|  MODO AJAX: DEVOLVER JSON PARA GRÁFICAS / KPIs / TABLAS
============================================================ */
if (isset($_GET['accion']) && $_GET['accion'] === 'load_data') {
    header('Content-Type: application/json; charset=utf-8');

    // ----------- FILTROS GLOBALES -------------
    $desde        = v('desde', date('Y-m-01'));
    $hasta        = v('hasta', date('Y-m-d'));
    $tipo_dato    = v('tipo_dato', 'VENTAS');
    $metodo_pago  = v('metodo_pago', '');
    $id_banco     = (int)v('id_banco', 0);
    $id_cliente   = (int)v('id_cliente', 0);
    $id_vendedor  = (int)v('id_vendedor', 0);
    $tipo_servicio= v('tipo_servicio', '');
    $estado_tram  = v('estado_tramite', '');
    $destino      = v('destino', '');

    // NOTA: asumimos que en las NUEVAS ventas la fecha se guarda en formato YYYY-mm-dd
    // Si tu campo ventas.fecha está en otro formato, ajusta STR_TO_DATE() según tu caso.

    /* ========================================================
    |   ARMAR WHERE PARA VENTAS
    ========================================================= */
    $whereVentas = "1=1";

    // Rango de fechas por fecha de venta
    $whereVentas .= " AND v.fecha >= '{$desde}' AND v.fecha <= '{$hasta}'";

    if ($metodo_pago !== '') {
        $metodo_pago_sql = $db->SQL->real_escape_string($metodo_pago);
        $whereVentas .= " AND v.metodo_pago = '{$metodo_pago_sql}'";
    }
    if ($id_banco > 0) {
        $whereVentas .= " AND v.id_banco = '{$id_banco}'";
    }
    if ($id_cliente > 0) {
        $whereVentas .= " AND v.cliente = '{$id_cliente}'";
    }
    if ($id_vendedor > 0) {
        $whereVentas .= " AND v.vendedor = '{$id_vendedor}'";
    }
    if ($tipo_servicio !== '') {
        $tipo_servicio_sql = $db->SQL->real_escape_string($tipo_servicio);
        $whereVentas .= " AND p.tipo_servicio = '{$tipo_servicio_sql}'";
    }

    /* ========================================================
    |   KPI – VENTAS (día / mes / año / ticket / top servicio)
    ========================================================= */
    $hoy      = date('Y-m-d');
    $mes_ini  = date('Y-m-01');
    $anio_ini = date('Y-01-01');

    // Total día
    $SqlDia = $db->SQL("
        SELECT SUM(v.totalprecio) AS total
        FROM ventas v
        WHERE v.fecha = '{$hoy}'
    ");
    $kpi_ventas_dia = (float)($SqlDia->fetch_assoc()['total'] ?? 0);

    // Total mes
    $SqlMes = $db->SQL("
        SELECT SUM(v.totalprecio) AS total
        FROM ventas v
        WHERE v.fecha >= '{$mes_ini}' AND v.fecha <= '{$hoy}'
    ");
    $kpi_ventas_mes = (float)($SqlMes->fetch_assoc()['total'] ?? 0);

    // Total año
    $SqlAnio = $db->SQL("
        SELECT SUM(v.totalprecio) AS total
        FROM ventas v
        WHERE v.fecha >= '{$anio_ini}' AND v.fecha <= '{$hoy}'
    ");
    $kpi_ventas_anio = (float)($SqlAnio->fetch_assoc()['total'] ?? 0);

    // Ticket promedio en rango filtrado
    $SqlTicket = $db->SQL("
        SELECT SUM(v.totalprecio) AS total, COUNT(DISTINCT v.idfactura) AS cant
        FROM ventas v
        INNER JOIN producto p ON p.id = v.producto
        WHERE {$whereVentas}
    ");
    $rowTicket = $SqlTicket->fetch_assoc();
    $totalTicket = (float)($rowTicket['total'] ?? 0);
    $cantFacturas = (int)($rowTicket['cant'] ?? 0);
    $kpi_ticket_promedio = ($cantFacturas > 0) ? $totalTicket / $cantFacturas : 0;

    // Servicio más vendido (por cantidad)
    $SqlTopServ = $db->SQL("
        SELECT p.nombre, SUM(v.cantidad) AS cant
        FROM ventas v
        INNER JOIN producto p ON p.id = v.producto
        WHERE {$whereVentas}
        GROUP BY p.id
        ORDER BY cant DESC
        LIMIT 1
    ");
    $top_servicio = null;
    if ($SqlTopServ && $SqlTopServ->num_rows > 0) {
        $r = $SqlTopServ->fetch_assoc();
        $top_servicio = [
            'nombre' => $r['nombre'],
            'cantidad' => (int)$r['cant']
        ];
    }

    /* ========================================================
    |   KPI – CAJAS / BANCOS
    ========================================================= */
    // SALDO CAJA GENERAL (último saldo_caja)
    $SaldoGeneralSQL = $db->SQL("
        SELECT saldo_caja 
        FROM caja_general_movimientos
        ORDER BY id DESC
        LIMIT 1
    ");
    $saldo_caja_general = 0;
    if ($SaldoGeneralSQL && $SaldoGeneralSQL->num_rows > 0) {
        $saldo_caja_general = (float)$SaldoGeneralSQL->fetch_assoc()['saldo_caja'];
    }

    // SALDO CAJA CHICA (último saldo_resultante)
    $SaldoChicaSQL = $db->SQL("
        SELECT saldo_resultante 
        FROM caja_chica_movimientos
        ORDER BY id DESC
        LIMIT 1
    ");
    $saldo_caja_chica = 0;
    if ($SaldoChicaSQL && $SaldoChicaSQL->num_rows > 0) {
        $saldo_caja_chica = (float)$SaldoChicaSQL->fetch_assoc()['saldo_resultante'];
    }

    // SALDO BANCOS TOTAL
    $SaldoBancosSQL = $db->SQL("
        SELECT 
            b.id,
            b.nombre,
            (b.saldo_inicial +
             COALESCE(SUM(
                 CASE WHEN bm.tipo='INGRESO' THEN bm.monto ELSE -bm.monto END
             ),0)
            ) AS saldo
        FROM bancos b
        LEFT JOIN banco_movimientos bm ON bm.id_banco = b.id
        GROUP BY b.id
    ");
    $saldo_bancos_total = 0;
    $bancos_list = [];
    if ($SaldoBancosSQL) {
        while ($b = $SaldoBancosSQL->fetch_assoc()) {
            $saldo_bancos_total += (float)$b['saldo'];
            $bancos_list[] = [
                'id'    => (int)$b['id'],
                'nombre'=> $b['nombre'],
                'saldo' => (float)$b['saldo']
            ];
        }
    }

    $saldo_global_consolidado = $saldo_caja_general + $saldo_caja_chica + $saldo_bancos_total;

    // FLUJO FINANCIERO NETO (caja_general en rango)
    $SqlFlujo = $db->SQL("
        SELECT 
            SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE 0 END) AS ing,
            SUM(CASE WHEN tipo='EGRESO' THEN monto ELSE 0 END) AS egr
        FROM caja_general_movimientos
        WHERE fecha >= '{$desde}' AND fecha <= '{$hasta}'
    ");
    $frow = $SqlFlujo->fetch_assoc();
    $flujo_ing = (float)($frow['ing'] ?? 0);
    $flujo_egr = (float)($frow['egr'] ?? 0);
    $flujo_neto = $flujo_ing - $flujo_egr;

    /* ========================================================
    |   KPI OPERATIVO (TRÁMITES, CLIENTES NUEVOS, etc.)
    ========================================================= */
    // Trámites creados en rango
    $SqlTramites = $db->SQL("
        SELECT 
            SUM(CASE WHEN estado='PENDIENTE' OR estado='EN_PROCESO' THEN 1 ELSE 0 END) AS en_proceso,
            SUM(CASE WHEN estado='FINALIZADO' THEN 1 ELSE 0 END) AS finalizados,
            SUM(CASE WHEN estado='RECHAZADO' THEN 1 ELSE 0 END) AS rechazados,
            COUNT(*) AS total
        FROM tramites
        WHERE fecha_inicio >= '{$desde}' AND fecha_inicio <= '{$hasta}'
    ");
    $tramRow = $SqlTramites->fetch_assoc();
    $tram_total      = (int)($tramRow['total'] ?? 0);
    $tram_en_proceso = (int)($tramRow['en_proceso'] ?? 0);
    $tram_finalizados= (int)($tramRow['finalizados'] ?? 0);
    $tram_rechazados = (int)($tramRow['rechazados'] ?? 0);

    // Clientes nuevos en rango (fecha_nacimiento NO siempre es fecha alta, solo ejemplo)
    $SqlClientesNuevos = $db->SQL("
        SELECT COUNT(*) AS total
        FROM cliente
        WHERE fecha_nacimiento >= '{$desde}' AND fecha_nacimiento <= '{$hasta}'
    ");
    $clientes_nuevos = (int)($SqlClientesNuevos->fetch_assoc()['total'] ?? 0);

    /* ========================================================
    |   GRÁFICAS – VENTAS
    ========================================================= */

    // A) Ventas diarias en el rango (línea)
    $VentasDiariasSQL = $db->SQL("
        SELECT v.fecha, SUM(v.totalprecio) AS total
        FROM ventas v
        INNER JOIN producto p ON p.id = v.producto
        WHERE {$whereVentas}
        GROUP BY v.fecha
        ORDER BY v.fecha ASC
    ");
    $ventas_diarias_labels = [];
    $ventas_diarias_data   = [];
    while ($vd = $VentasDiariasSQL->fetch_assoc()) {
        $ventas_diarias_labels[] = $vd['fecha'];
        $ventas_diarias_data[]   = (float)$vd['total'];
    }

    // B) Ventas por tipo de servicio (dona)
    $VentasTipoSQL = $db->SQL("
        SELECT p.tipo_servicio, SUM(v.totalprecio) AS total
        FROM ventas v
        INNER JOIN producto p ON p.id = v.producto
        WHERE {$whereVentas}
        GROUP BY p.tipo_servicio
        ORDER BY total DESC
    ");
    $ventas_tipo_labels = [];
    $ventas_tipo_data   = [];
    while ($vt = $VentasTipoSQL->fetch_assoc()) {
        $ventas_tipo_labels[] = $vt['tipo_servicio'] ?: 'SIN TIPO';
        $ventas_tipo_data[]   = (float)$vt['total'];
    }

    /* ========================================================
    |   GRÁFICAS – CAJA GENERAL (INGRESOS vs EGRESOS)
    ========================================================= */
    $CajaGeSQL = $db->SQL("
        SELECT fecha,
               SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE 0 END) AS ing,
               SUM(CASE WHEN tipo='EGRESO' THEN monto ELSE 0 END) AS egr
        FROM caja_general_movimientos
        WHERE fecha >= '{$desde}' AND fecha <= '{$hasta}'
        GROUP BY fecha
        ORDER BY fecha ASC
    ");
    $caja_labels = [];
    $caja_ing_data = [];
    $caja_egr_data = [];
    while ($cg = $CajaGeSQL->fetch_assoc()) {
        $caja_labels[]    = $cg['fecha'];
        $caja_ing_data[]  = (float)$cg['ing'];
        $caja_egr_data[]  = (float)$cg['egr'];
    }

    /* ========================================================
    |   GRÁFICAS – BANCOS (Saldos por Banco)
    ========================================================= */
    $bancos_labels = [];
    $bancos_data   = [];
    foreach ($bancos_list as $b) {
        $bancos_labels[] = $b['nombre'];
        $bancos_data[]   = $b['saldo'];
    }

    /* ========================================================
    |   TABLA – VENTAS DETALLADAS (para DataTables)
    ========================================================= */
    $VentasDetSQL = $db->SQL("
        SELECT 
            v.idfactura,
            v.fecha,
            v.hora,
            v.totalprecio,
            v.metodo_pago,
            v.nro_comprobante,
            c.nombre   AS cliente_nombre,
            p.nombre   AS producto_nombre,
            v.cantidad,
            v.vendedor
        FROM ventas v
        INNER JOIN producto p ON p.id = v.producto
        LEFT JOIN cliente c    ON c.id = v.cliente
        WHERE {$whereVentas}
        ORDER BY v.fecha DESC, v.hora DESC
        LIMIT 500
    ");
    $ventas_detalle = [];
    while ($vd = $VentasDetSQL->fetch_assoc()) {
        $ventas_detalle[] = [
            'idfactura'        => (int)$vd['idfactura'],
            'fecha'            => $vd['fecha'],
            'hora'             => $vd['hora'],
            'cliente'          => $vd['cliente_nombre'] ?: 'Cliente '.$vd['cliente_nombre'],
            'producto'         => $vd['producto_nombre'],
            'cantidad'         => (int)$vd['cantidad'],
            'totalprecio'      => (float)$vd['totalprecio'],
            'metodo_pago'      => $vd['metodo_pago'],
            'nro_comprobante'  => $vd['nro_comprobante'],
        ];
    }

    /* ========================================================
    |   RESPUESTA JSON
    ========================================================= */
    echo json_encode([
        'kpi' => [
            'ventas_dia'        => $kpi_ventas_dia,
            'ventas_mes'        => $kpi_ventas_mes,
            'ventas_anio'       => $kpi_ventas_anio,
            'ticket_promedio'   => $kpi_ticket_promedio,
            'top_servicio'      => $top_servicio,
            'saldo_caja_general'=> $saldo_caja_general,
            'saldo_caja_chica'  => $saldo_caja_chica,
            'saldo_bancos_total'=> $saldo_bancos_total,
            'saldo_global'      => $saldo_global_consolidado,
            'flujo_neto'        => $flujo_neto,
            'tram_total'        => $tram_total,
            'tram_en_proceso'   => $tram_en_proceso,
            'tram_finalizados'  => $tram_finalizados,
            'tram_rechazados'   => $tram_rechazados,
            'clientes_nuevos'   => $clientes_nuevos,
        ],
        'charts' => [
            'ventas_diarias' => [
                'labels' => $ventas_diarias_labels,
                'data'   => $ventas_diarias_data
            ],
            'ventas_tipo' => [
                'labels' => $ventas_tipo_labels,
                'data'   => $ventas_tipo_data
            ],
            'caja_general' => [
                'labels' => $caja_labels,
                'ingresos' => $caja_ing_data,
                'egresos'  => $caja_egr_data
            ],
            'bancos' => [
                'labels' => $bancos_labels,
                'data'   => $bancos_data
            ]
        ],
        'tables' => [
            'ventas_detalle' => $ventas_detalle
        ]
    ]);
    exit;
}

/* ===================================================================
|   MODO HTML – PANEL ADMIN CON GRÁFICAS, KPIs Y FILTROS
=================================================================== */

// Listas para filtros (clientes, vendedores, bancos, etc.)
$ClientesSQL = $db->SQL("SELECT id, nombre FROM cliente WHERE habilitado=1 ORDER BY nombre ASC");
$VendedoresSQL = $db->SQL("SELECT id, nombre, apellido1 FROM vendedores WHERE habilitado=1 ORDER BY nombre ASC");
$BancosSQL = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Panel Administrativo | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <!-- Chart.js 4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    .kpi-card {
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 8px;
        color: #fff;
    }

    .kpi-title {
        font-size: 14px;
        text-transform: uppercase;
        opacity: .8;
    }

    .kpi-value {
        font-size: 20px;
        font-weight: bold;
    }

    .kpi-sub {
        font-size: 12px;
        opacity: .9;
    }

    .kpi-ventas {
        background: #2563eb;
    }

    .kpi-finanzas {
        background: #16a34a;
    }

    .kpi-operativo {
        background: #dc2626;
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
                <h1>Panel Administrativo</h1>
                <p class="lead">Dashboard de Ventas, Cajas, Bancos y Operaciones (Agencia de Viajes)</p>
            </div>

            <!-- =================== FILTROS GLOBALES =================== -->
            <div class="panel panel-default">
                <div class="panel-heading"><strong>Filtros Globales</strong></div>
                <div class="panel-body">

                    <form id="formFiltros" class="form-inline">

                        <label>Desde:&nbsp;</label>
                        <input type="date" name="desde" id="f_desde" class="form-control"
                            value="<?php echo date('Y-m-01'); ?>">

                        <label>&nbsp;Hasta:&nbsp;</label>
                        <input type="date" name="hasta" id="f_hasta" class="form-control"
                            value="<?php echo date('Y-m-d'); ?>">

                        <label>&nbsp;Tipo:&nbsp;</label>
                        <select name="tipo_dato" id="f_tipo_dato" class="form-control">
                            <option value="VENTAS">Ventas</option>
                            <option value="CAJAS">Cajas</option>
                            <option value="BANCOS">Bancos</option>
                            <option value="SERVICIOS">Servicios</option>
                            <option value="TRAMITES">Trámites</option>
                            <option value="RESERVAS">Reservas</option>
                            <option value="VENDEDORES">Vendedores</option>
                        </select>

                        <label>&nbsp;Método de Pago:&nbsp;</label>
                        <select name="metodo_pago" id="f_metodo_pago" class="form-control">
                            <option value="">Todos</option>
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                            <option value="DEPOSITO">Depósito</option>
                            <option value="TARJETA">Tarjeta</option>
                        </select>

                        <label>&nbsp;Banco:&nbsp;</label>
                        <select name="id_banco" id="f_banco" class="form-control">
                            <option value="0">Todos</option>
                            <?php while ($b = $BancosSQL->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo $b['nombre']; ?></option>
                            <?php endwhile; ?>
                        </select>

                        <br><br>

                        <label>Cliente:&nbsp;</label>
                        <select name="id_cliente" id="f_cliente" class="form-control">
                            <option value="0">Todos</option>
                            <?php while ($c = $ClientesSQL->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                            <?php endwhile; ?>
                        </select>

                        <label>&nbsp;Vendedor:&nbsp;</label>
                        <select name="id_vendedor" id="f_vendedor" class="form-control">
                            <option value="0">Todos</option>
                            <?php while ($v = $VendedoresSQL->fetch_assoc()): ?>
                            <option value="<?php echo $v['id']; ?>">
                                <?php echo $v['nombre'].' '.$v['apellido1']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <label>&nbsp;Tipo de Servicio:&nbsp;</label>
                        <select name="tipo_servicio" id="f_tipo_servicio" class="form-control">
                            <option value="">Todos</option>
                            <option value="PASAJE">Pasaje</option>
                            <option value="PAQUETE">Paquete</option>
                            <option value="SEGURO">Seguro</option>
                            <option value="TRAMITE">Trámite</option>
                            <option value="OTRO">Otro</option>
                        </select>

                        <button type="button" id="btnAplicarFiltros" class="btn btn-primary">
                            Aplicar filtros
                        </button>

                    </form>

                </div>
            </div>

            <!-- =================== KPI PRINCIPALES =================== -->
            <div class="row">

                <!-- Ventas -->
                <div class="col-md-4">
                    <div class="kpi-card kpi-ventas">
                        <div class="kpi-title">Ventas del día</div>
                        <div class="kpi-value" id="kpi_ventas_dia">Bs 0.00</div>

                        <div class="kpi-sub">
                            Mes: <span id="kpi_ventas_mes">Bs 0.00</span><br>
                            Año: <span id="kpi_ventas_anio">Bs 0.00</span><br>
                            Ticket Promedio: <span id="kpi_ticket_promedio">Bs 0.00</span><br>
                            Top servicio: <span id="kpi_top_servicio">-</span>
                        </div>
                    </div>
                </div>

                <!-- Finanzas -->
                <div class="col-md-4">
                    <div class="kpi-card kpi-finanzas">
                        <div class="kpi-title">Finanzas</div>
                        <div class="kpi-value" id="kpi_saldo_global">Bs 0.00</div>

                        <div class="kpi-sub">
                            Caja General: <span id="kpi_saldo_caja_general">Bs 0.00</span><br>
                            Caja Chica: <span id="kpi_saldo_caja_chica">Bs 0.00</span><br>
                            Bancos: <span id="kpi_saldo_bancos">Bs 0.00</span><br>
                            Flujo neto periodo: <span id="kpi_flujo_neto">Bs 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Operativo -->
                <div class="col-md-4">
                    <div class="kpi-card kpi-operativo">
                        <div class="kpi-title">Operativo</div>
                        <div class="kpi-value" id="kpi_tram_total">0 trámites</div>

                        <div class="kpi-sub">
                            En proceso: <span id="kpi_tram_en_proceso">0</span> |
                            Finalizados: <span id="kpi_tram_finalizados">0</span> |
                            Rechazados: <span id="kpi_tram_rechazados">0</span><br>
                            Clientes nuevos: <span id="kpi_clientes_nuevos">0</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- =================== GRÁFICAS PRINCIPALES =================== -->
            <div class="row">

                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Ventas diarias (clic en barra = filtra por fecha)</strong>
                        </div>
                        <div class="panel-body">
                            <canvas id="chartVentasDiarias" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Ventas por tipo de servicio (clic = filtra tipo)</strong>
                        </div>
                        <div class="panel-body">
                            <canvas id="chartVentasTipo" height="200"></canvas>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row">

                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Caja General – Ingresos vs Egresos</strong>
                        </div>
                        <div class="panel-body">
                            <canvas id="chartCajaGeneral" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Saldos por Banco</strong>
                        </div>
                        <div class="panel-body">
                            <canvas id="chartBancos" height="200"></canvas>
                        </div>
                    </div>
                </div>

            </div>

            <!-- =================== TABLA DETALLE VENTAS =================== -->
            <div class="panel panel-default">
                <div class="panel-heading"><strong>Ventas Detalladas (según filtros / clics)</strong></div>
                <div class="panel-body">

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tablaVentasDetalle">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Cliente</th>
                                    <th>Servicio</th>
                                    <th>Cantidad</th>
                                    <th>Método</th>
                                    <th>Nro Comp.</th>
                                    <th>Total (Bs)</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    let chartVentasDiarias = null;
    let chartVentasTipo = null;
    let chartCajaGeneral = null;
    let chartBancos = null;
    let tablaVentasDetalle = null;

    /* ====================================================
     *  CARGAR DATOS VIA AJAX
     * ==================================================== */
    function loadDashboardData(extraParams = {}) {
        const form = document.getElementById('formFiltros');
        const formData = new FormData(form);

        for (const k in extraParams) {
            formData.set(k, extraParams[k]);
        }

        const params = new URLSearchParams(formData);
        params.append('accion', 'load_data');

        fetch('panel-cajas.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                renderKPIs(data.kpi);
                renderCharts(data.charts);
                renderVentasTable(data.tables.ventas_detalle);
            })
            .catch(err => {
                console.error(err);
                alert('Error cargando datos del panel.');
            });
    }

    /* ====================================================
     *  RENDER KPI
     * ==================================================== */
    function renderKPIs(kpi) {
        const fmt = (v) => 'Bs ' + parseFloat(v || 0).toFixed(2);

        document.getElementById('kpi_ventas_dia').innerText = fmt(kpi.ventas_dia);
        document.getElementById('kpi_ventas_mes').innerText = fmt(kpi.ventas_mes);
        document.getElementById('kpi_ventas_anio').innerText = fmt(kpi.ventas_anio);
        document.getElementById('kpi_ticket_promedio').innerText = fmt(kpi.ticket_promedio);

        let topText = '-';
        if (kpi.top_servicio && kpi.top_servicio.nombre) {
            topText = kpi.top_servicio.nombre + ' (' + kpi.top_servicio.cantidad + ')';
        }
        document.getElementById('kpi_top_servicio').innerText = topText;

        document.getElementById('kpi_saldo_caja_general').innerText = fmt(kpi.saldo_caja_general);
        document.getElementById('kpi_saldo_caja_chica').innerText = fmt(kpi.saldo_caja_chica);
        document.getElementById('kpi_saldo_bancos').innerText = fmt(kpi.saldo_bancos_total);
        document.getElementById('kpi_saldo_global').innerText = fmt(kpi.saldo_global);
        document.getElementById('kpi_flujo_neto').innerText = fmt(kpi.flujo_neto);

        document.getElementById('kpi_tram_total').innerText = (kpi.tram_total || 0) + ' trámites';
        document.getElementById('kpi_tram_en_proceso').innerText = kpi.tram_en_proceso || 0;
        document.getElementById('kpi_tram_finalizados').innerText = kpi.tram_finalizados || 0;
        document.getElementById('kpi_tram_rechazados').innerText = kpi.tram_rechazados || 0;
        document.getElementById('kpi_clientes_nuevos').innerText = kpi.clientes_nuevos || 0;
    }

    /* ====================================================
     *  RENDER GRÁFICAS
     * ==================================================== */
    function renderCharts(charts) {
        const ctxVentasDiarias = document.getElementById('chartVentasDiarias').getContext('2d');
        const ctxVentasTipo = document.getElementById('chartVentasTipo').getContext('2d');
        const ctxCajaGeneral = document.getElementById('chartCajaGeneral').getContext('2d');
        const ctxBancos = document.getElementById('chartBancos').getContext('2d');

        if (chartVentasDiarias) chartVentasDiarias.destroy();
        if (chartVentasTipo) chartVentasTipo.destroy();
        if (chartCajaGeneral) chartCajaGeneral.destroy();
        if (chartBancos) chartBancos.destroy();

        // Ventas Diarias (Línea)
        chartVentasDiarias = new Chart(ctxVentasDiarias, {
            type: 'line',
            data: {
                labels: charts.ventas_diarias.labels,
                datasets: [{
                    label: 'Ventas',
                    data: charts.ventas_diarias.data,
                    fill: false,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                onClick: function(evt, active) {
                    if (active.length > 0) {
                        const index = active[0].index;
                        const fecha = charts.ventas_diarias.labels[index];
                        // Filtrar por esa fecha en los filtros globales
                        document.getElementById('f_desde').value = fecha;
                        document.getElementById('f_hasta').value = fecha;
                        loadDashboardData();
                    }
                }
            }
        });

        // Ventas por Tipo de Servicio (Dona)
        chartVentasTipo = new Chart(ctxVentasTipo, {
            type: 'doughnut',
            data: {
                labels: charts.ventas_tipo.labels,
                datasets: [{
                    label: 'Ventas por tipo',
                    data: charts.ventas_tipo.data
                }]
            },
            options: {
                responsive: true,
                onClick: function(evt, active) {
                    if (active.length > 0) {
                        const index = active[0].index;
                        const tipo_servicio = charts.ventas_tipo.labels[index];
                        document.getElementById('f_tipo_servicio').value = tipo_servicio;
                        loadDashboardData();
                    }
                }
            }
        });

        // Caja General Ingresos vs Egresos
        chartCajaGeneral = new Chart(ctxCajaGeneral, {
            type: 'bar',
            data: {
                labels: charts.caja_general.labels,
                datasets: [{
                        label: 'Ingresos',
                        data: charts.caja_general.ingresos
                    },
                    {
                        label: 'Egresos',
                        data: charts.caja_general.egresos
                    }
                ]
            },
            options: {
                responsive: true
            }
        });

        // Saldos por Banco
        chartBancos = new Chart(ctxBancos, {
            type: 'bar',
            data: {
                labels: charts.bancos.labels,
                datasets: [{
                    label: 'Saldo',
                    data: charts.bancos.data
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                onClick: function(evt, active) {
                    if (active.length > 0) {
                        const index = active[0].index;
                        const bancoNombre = charts.bancos.labels[index];
                        // No tenemos ID aquí, pero podrías mapearlo.
                        // Por ahora solo aplicamos nombre como referencia visual.
                        alert('Click en banco: ' + bancoNombre);
                    }
                }
            }
        });
    }

    /* ====================================================
     *  TABLA VENTAS DETALLE (DataTables)
     * ==================================================== */
    function renderVentasTable(rows) {
        if (!tablaVentasDetalle) {
            tablaVentasDetalle = $('#tablaVentasDetalle').DataTable({
                "order": [
                    [1, "desc"]
                ],
                "pageLength": 10,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
                }
            });
        }
        tablaVentasDetalle.clear();

        rows.forEach(function(r) {
            tablaVentasDetalle.row.add([
                r.idfactura,
                r.fecha,
                r.hora,
                r.cliente,
                r.producto,
                r.cantidad,
                r.metodo_pago,
                r.nro_comprobante || '-',
                parseFloat(r.totalprecio).toFixed(2)
            ]);
        });

        tablaVentasDetalle.draw();
    }

    /* ====================================================
     *  EVENTOS
     * ==================================================== */
    document.getElementById('btnAplicarFiltros').addEventListener('click', function() {
        loadDashboardData();
    });

    // Primera carga
    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardData();
    });
    </script>

</body>

</html>