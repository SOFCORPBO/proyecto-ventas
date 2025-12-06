<?php
session_start();
include('sistema/configuracion.php');
include('whatsapp.func.php'); // Asegúrate de tener aquí la función EnviarWhatsapp()

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

/* =====================================================
   FILTROS DEL PANEL DE ALERTAS
===================================================== */
$f = [
    'estado'    => $_GET['estado']    ?? '',
    'pais'      => $_GET['pais']      ?? '',
    'desde'     => $_GET['desde']     ?? '',
    'hasta'     => $_GET['hasta']     ?? '',
    'dias_max'  => $_GET['dias_max']  ?? 30, // por defecto 30 días
];

/* Sanitizar días máximos */
$f['dias_max'] = (int)$f['dias_max'];
if ($f['dias_max'] <= 0) $f['dias_max'] = 30;

/* =====================================================
   ACCIÓN: ENVIAR ALERTA WHATSAPP
===================================================== */
$mensaje_flash = '';
$tipo_mensaje  = 'info';

if (isset($_POST['EnviarAlertaWhatsApp'])) {

    $idTramite = (int)$_POST['id_tramite'];

    // Obtener trámite + cliente
    $TramSQL = $db->SQL("
        SELECT 
            t.*,
            c.nombre    AS cliente_nombre,
            c.telefono  AS cliente_telefono
        FROM tramites t
        LEFT JOIN cliente c ON c.id = t.id_cliente
        WHERE t.id = {$idTramite}
        LIMIT 1
    ");

    if ($TramSQL && $TramSQL->num_rows > 0) {
        $t = $TramSQL->fetch_assoc();

        if (!empty($t['cliente_telefono'])) {

            // Calcular días restantes
            $dias_restantes = null;
            if (!empty($t['fecha_vencimiento'])) {
                $hoy      = new DateTime(date('Y-m-d'));
                $vence    = new DateTime($t['fecha_vencimiento']);
                $diff     = $hoy->diff($vence);
                $dias_restantes = (int)$diff->format('%r%a'); // puede ser negativo
            }

            // Mensaje de alerta
            $mensaje = "⚠️ *ALERTA DE VENCIMIENTO DE VISA*\n";
            $mensaje .= "Cliente: *{$t['cliente_nombre']}*\n";
            $mensaje .= "Trámite: *{$t['tipo_tramite']}*\n";
            $mensaje .= "País destino: {$t['pais_destino']}\n";
            if (!empty($t['fecha_vencimiento'])) {
                $mensaje .= "Fecha de vencimiento: *{$t['fecha_vencimiento']}*\n";
            }
            if ($dias_restantes !== null) {
                $mensaje .= "Vence en: *{$dias_restantes} día(s)*\n";
            }
            $mensaje .= "\nPor favor contactarse con la agencia para renovación o regularización.";

            // ENVIAR WHATSAPP
            $resp = EnviarWhatsapp($t['cliente_telefono'], $mensaje);

            $mensaje_flash = "Alerta enviada por WhatsApp al cliente: {$t['cliente_nombre']}.";
            $tipo_mensaje  = 'success';

        } else {
            $mensaje_flash = "El cliente no tiene teléfono registrado. No se pudo enviar la alerta.";
            $tipo_mensaje  = 'danger';
        }

    } else {
        $mensaje_flash = "Trámite no encontrado.";
        $tipo_mensaje  = 'danger';
    }
}

/* =====================================================
   KPI PRINCIPALES DE ALERTAS
===================================================== */
$TotalProximos = $db->SQL("
    SELECT COUNT(*) AS total
    FROM tramites
    WHERE fecha_vencimiento IS NOT NULL
      AND estado IN ('PENDIENTE','EN_PROCESO')
      AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL {$f['dias_max']} DAY)
")->fetch_assoc()['total'];

$AlertasHoy = $db->SQL("
    SELECT COUNT(*) AS total
    FROM tramites
    WHERE fecha_vencimiento IS NOT NULL
      AND estado IN ('PENDIENTE','EN_PROCESO')
      AND DATE(fecha_vencimiento) = CURDATE()
")->fetch_assoc()['total'];

$Alertas7Dias = $db->SQL("
    SELECT COUNT(*) AS total
    FROM tramites
    WHERE fecha_vencimiento IS NOT NULL
      AND estado IN ('PENDIENTE','EN_PROCESO')
      AND fecha_vencimiento > CURDATE()
      AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->fetch_assoc()['total'];

$ClientesSinTelefono = $db->SQL("
    SELECT COUNT(DISTINCT c.id) AS total
    FROM tramites t
    INNER JOIN cliente c ON c.id = t.id_cliente
    WHERE t.fecha_vencimiento IS NOT NULL
      AND t.estado IN ('PENDIENTE','EN_PROCESO')
      AND (c.telefono IS NULL OR c.telefono = '' OR c.telefono = '-')
")->fetch_assoc()['total'];

/* =====================================================
   LISTADO DETALLADO DE TRÁMITES POR VENCER
===================================================== */
$where = "
    t.fecha_vencimiento IS NOT NULL
    AND t.estado IN ('PENDIENTE','EN_PROCESO')
    AND t.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL {$f['dias_max']} DAY)
";

if (!empty($f['estado'])) {
    $estado = $db->SQL("SELECT '{$f['estado']}' AS e")->fetch_assoc()['e']; // simple escape
    $where .= " AND t.estado = '{$estado}'";
}

if (!empty($f['pais'])) {
    $pais = $db->SQL("SELECT '{$f['pais']}' AS p")->fetch_assoc()['p'];
    $where .= " AND t.pais_destino LIKE '%{$pais}%'";
}

if (!empty($f['desde'])) {
    $where .= " AND DATE(t.fecha_vencimiento) >= '{$f['desde']}'";
}

if (!empty($f['hasta'])) {
    $where .= " AND DATE(t.fecha_vencimiento) <= '{$f['hasta']}'";
}

$AlertasSQL = $db->SQL("
    SELECT
        t.*,
        c.nombre   AS cliente_nombre,
        c.telefono AS cliente_telefono,
        DATEDIFF(t.fecha_vencimiento, CURDATE()) AS dias_restantes
    FROM tramites t
    LEFT JOIN cliente c ON c.id = t.id_cliente
    WHERE {$where}
    ORDER BY t.fecha_vencimiento ASC, t.id ASC
");

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Alertas de Vencimiento de Visas | <?= TITULO ?></title>

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

    .kpi-total {
        background: #3f51b5;
    }

    .kpi-hoy {
        background: #f44336;
    }

    .kpi-7d {
        background: #ff9800;
    }

    .kpi-sin-tel {
        background: #9e9e9e;
    }

    .label-dias-critico {
        background: #f44336;
    }

    .label-dias-pronto {
        background: #ff9800;
    }

    .label-dias-ok {
        background: #4caf50;
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
            <h1>Alertas de Vencimiento de Visas</h1>
            <p class="text-muted">
                Panel para monitorear trámites próximos a vencer y enviar notificaciones por WhatsApp.
            </p>
        </div>

        <?php if ($mensaje_flash): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>">
            <?= $mensaje_flash ?>
        </div>
        <?php endif; ?>

        <!-- =====================================
             KPIs PRINCIPALES
        ====================================== -->
        <div class="row">

            <div class="col-sm-3">
                <div class="kpi-box kpi-total">
                    <h2><?= (int)$TotalProximos ?></h2>
                    <small>Trámites que vencen en ≤ <?= (int)$f['dias_max'] ?> días</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi-hoy">
                    <h2><?= (int)$AlertasHoy ?></h2>
                    <small>Vencen Hoy</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi-7d">
                    <h2><?= (int)$Alertas7Dias ?></h2>
                    <small>Vencen en Próximos 7 días</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi-sin-tel">
                    <h2><?= (int)$ClientesSinTelefono ?></h2>
                    <small>Clientes sin teléfono registrado</small>
                </div>
            </div>

        </div>

        <!-- =====================================
             FILTROS
        ====================================== -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Filtros</strong></div>
            <div class="panel-body">

                <form method="GET" class="form-inline">

                    <label>Días máximos:</label>
                    <input type="number" name="dias_max" class="form-control" style="width:100px"
                        value="<?= (int)$f['dias_max'] ?>">

                    <label>Estado:</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="PENDIENTE" <?= $f['estado']=='PENDIENTE'?'selected':'' ?>>Pendiente</option>
                        <option value="EN_PROCESO" <?= $f['estado']=='EN_PROCESO'?'selected':'' ?>>En proceso</option>
                        <option value="FINALIZADO" <?= $f['estado']=='FINALIZADO'?'selected':'' ?>>Finalizado</option>
                        <option value="RECHAZADO" <?= $f['estado']=='RECHAZADO'?'selected':'' ?>>Rechazado</option>
                    </select>

                    <input type="text" name="pais" placeholder="País destino" class="form-control"
                        value="<?= htmlspecialchars($f['pais']) ?>">

                    <label>Vence desde:</label>
                    <input type="date" name="desde" class="form-control" value="<?= $f['desde'] ?>">

                    <label>Hasta:</label>
                    <input type="date" name="hasta" class="form-control" value="<?= $f['hasta'] ?>">

                    <button class="btn btn-primary">Aplicar</button>
                    <a href="alertas-visas.php" class="btn btn-default">Limpiar</a>

                </form>

            </div>
        </div>

        <!-- =====================================
             TABLA DE ALERTAS
        ====================================== -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Trámites Próximos a Vencer</strong></div>
            <div class="panel-body">

                <table class="table table-bordered table-striped" id="tabla_alertas_visas">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Tipo Trámite</th>
                            <th>País Destino</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Vencimiento</th>
                            <th>Días Restantes</th>
                            <th>Estado Trámite</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while($t = $AlertasSQL->fetch_assoc()): ?>
                        <?php
                            $dias = (int)$t['dias_restantes'];

                            if ($dias <= 0)          $classDias = 'label-dias-critico';
                            elseif ($dias <= 7)      $classDias = 'label-dias-pronto';
                            else                     $classDias = 'label-dias-ok';
                        ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><?= $t['cliente_nombre'] ?></td>
                            <td><?= $t['cliente_telefono'] ?: '-' ?></td>
                            <td><?= $t['tipo_tramite'] ?></td>
                            <td><?= $t['pais_destino'] ?></td>
                            <td><?= $t['fecha_inicio'] ?></td>
                            <td><?= $t['fecha_vencimiento'] ?></td>
                            <td>
                                <span class="label <?= $classDias ?>">
                                    <?= $dias ?> día(s)
                                </span>
                            </td>
                            <td>
                                <span class="label label-info"><?= $t['estado'] ?></span>
                            </td>
                            <td>
                                <!-- Ver trámite -->
                                <a href="tramites.php?id=<?= $t['id'] ?>" class="btn btn-xs btn-default"
                                    title="Ver detalle en módulo de trámites">
                                    <i class="fa fa-folder-open"></i>
                                </a>

                                <!-- Enviar WhatsApp -->
                                <form method="post" style="display:inline-block;"
                                    onsubmit="return confirm('¿Enviar alerta por WhatsApp a este cliente?');">
                                    <input type="hidden" name="id_tramite" value="<?= $t['id'] ?>">
                                    <button type="submit" name="EnviarAlertaWhatsApp" class="btn btn-xs btn-success"
                                        <?php if(empty($t['cliente_telefono'])) echo 'disabled'; ?>
                                        title="Enviar alerta por WhatsApp">
                                        <i class="fa fa-whatsapp"></i>
                                    </button>
                                </form>
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
    $('#tabla_alertas_visas').dataTable({
        "scrollX": true
    });
    </script>

</body>

</html>