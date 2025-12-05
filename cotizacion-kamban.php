<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Instancia segura
if (!isset($CotizacionClase)) {
    $CotizacionClase = new Cotizacion();
}

$cn = $db->Conectar(); // <--- Conexión real mysqli

/* ============================================================
   CAMBIO RÁPIDO DE ETAPA
   ============================================================ */
$CotizacionClase->CambiarEtapaRapida();

/* ============================================================
   RESUMEN PARA KPIs
   ============================================================ */
$resumen = $CotizacionClase->ResumenEtapas();

/* ============================================================
   COLUMNAS KANBAN
   ============================================================ */
$columnas = [
    'Pendientes' => ['NUEVO','CONTACTO'],
    'En proceso' => ['PROPUESTA ENVIADA','EN NEGOCIACIÓN','CASI CERRADO'],
    'Finalizados' => ['GANADO'],
    'Rechazados' => ['PERDIDO']
];

/* ============================================================
   FUNCIÓN: OBTENER COTIZACIONES POR GRUPO DE ETAPAS
   ============================================================ */
function cotPorEtapas($db, $etapas)
{
    $cn = $db->Conectar();

    $in = array_map(function($e) use ($cn) {
        return "'" . $cn->real_escape_string($e) . "'";
    }, $etapas);

    $str = implode(",", $in);

    return $db->SQL("
        SELECT 
            c.*,
            cli.nombre AS cliente,
            (SELECT p.nombre 
             FROM cotizacion_detalle cd 
             INNER JOIN producto p ON p.id = cd.id_producto
             WHERE cd.id_cotizacion=c.id LIMIT 1
            ) AS servicio,
            (SELECT p.tipo_servicio 
             FROM cotizacion_detalle cd 
             INNER JOIN producto p ON p.id = cd.id_producto
             WHERE cd.id_cotizacion=c.id LIMIT 1
            ) AS tipo_servicio
        FROM cotizacion c
        LEFT JOIN cliente cli ON cli.id = c.id_cliente
        WHERE c.etapa IN ($str)
        ORDER BY c.fecha DESC, c.hora DESC
    ");
}

/* ============================================================
   FILTROS TABLA GENERAL
   ============================================================ */
$f_vendedor = $_GET['f_vendedor'] ?? 0;
$f_tipo     = $_GET['f_tipo'] ?? "";
$f_estado   = $_GET['f_estado'] ?? "";

$cond = " WHERE 1 ";

if ($f_vendedor > 0) {
    $cond .= " AND c.usuario = ".intval($f_vendedor)." ";
}

if ($f_estado !== "") {
    $estadoEsc = $cn->real_escape_string($f_estado);
    $cond .= " AND c.estado = '{$estadoEsc}' ";
}

if ($f_tipo !== "") {
    $tipoEsc = $cn->real_escape_string($f_tipo);
    $cond .= "
        AND EXISTS (
            SELECT 1 
            FROM cotizacion_detalle cd
            INNER JOIN producto p ON p.id = cd.id_producto
            WHERE cd.id_cotizacion = c.id
              AND p.tipo_servicio = '{$tipoEsc}'
        )
    ";
}

/* ============================================================
   LISTADO GENERAL
   ============================================================ */
$ListadoSQL = $db->SQL("
    SELECT 
        c.*,
        cli.nombre AS cliente,
        u.usuario AS vendedor,
        (
            SELECT GROUP_CONCAT(DISTINCT p.tipo_servicio SEPARATOR ', ')
            FROM cotizacion_detalle cd
            INNER JOIN producto p ON p.id = cd.id_producto
            WHERE cd.id_cotizacion = c.id
        ) AS tipos_servicio
    FROM cotizacion c
    LEFT JOIN cliente cli ON cli.id = c.id_cliente
    LEFT JOIN usuario u   ON u.id = c.usuario
    {$cond}
    ORDER BY c.fecha DESC, c.hora DESC
");

/* ============================================================
   LISTADO VENDEDORES / TIPOS
   ============================================================ */
$Vende = $db->SQL("SELECT id, usuario FROM usuario WHERE habilitado=1 ORDER BY usuario ASC");

$Tipos = $db->SQL("
    SELECT DISTINCT tipo_servicio 
    FROM producto 
    WHERE tipo_servicio IS NOT NULL AND tipo_servicio <> ''
    ORDER BY tipo_servicio ASC
");

/* ============================================================
   FUNCIONES PROBABILIDAD
   ============================================================ */
function calcularProbabilidadFinal($etapa, $manual)
{
    $manual = (int)$manual;

    if ($manual > 0) {
        return min(max($manual, 0), 100);
    }

    $auto = [
        'NUEVO'              => 10,
        'CONTACTO'           => 20,
        'PROPUESTA ENVIADA'  => 40,
        'EN NEGOCIACIÓN'     => 60,
        'CASI CERRADO'       => 90,
        'GANADO'             => 100,
        'PERDIDO'            => 0
    ];

    return $auto[$etapa] ?? 0;
}

function colorProbabilidadLabel($prob)
{
    if ($prob > 60) return 'label-success';
    if ($prob > 30) return 'label-warning';
    return 'label-danger';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Cotizaciones | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .kpi-card {
        padding: 15px;
        border-radius: 6px;
        color: #fff;
        text-align: center;
        margin-bottom: 15px;
    }

    .kpi-total {
        background: #607D8B;
    }

    .kpi-pend {
        background: #FF9800;
    }

    .kpi-proc {
        background: #2196F3;
    }

    .kpi-ganados {
        background: #4CAF50;
    }

    .kpi-perdidos {
        background: #F44336;
    }

    .kanban-col {
        background: #f7f7f7;
        border-radius: 6px;
        padding: 10px;
        min-height: 250px;
        border: 1px solid #ddd;
        margin-bottom: 20px;
    }

    .kanban-card {
        background: #fff;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
    }

    .panel-listado {
        background: #fff;
        border-radius: 6px;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 30px;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==2)
    include(MODULO.'menu_vendedor.php');
else
    include(MODULO.'menu_admin.php');
?>

    <div class="container">

        <div class="page-header">
            <h1><i class="fa fa-trello"></i> Cotizaciones</h1>
            <a href="cotizacion-nuevo.php" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> Nueva Cotización
            </a>
            <div class="clearfix"></div>
            <p class="text-muted">
                Tablero Kanban + listado con filtros avanzados (por fecha, montos, etapa, probabilidad, vendedor).
            </p>
        </div>

        <!-- ========================== KPIS ========================== -->
        <div class="row">
            <div class="col-sm-3">
                <div class="kpi-card kpi-total">
                    <h4>Total</h4>
                    <h2><?php echo $resumen['total']; ?></h2>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi-card kpi-pend">
                    <h4>Pendientes</h4>
                    <h2><?php echo $resumen['pendientes']; ?></h2>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi-card kpi-proc">
                    <h4>En proceso</h4>
                    <h2><?php echo $resumen['proceso']; ?></h2>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi-card kpi-ganados">
                    <h4>Ganados</h4>
                    <h2><?php echo $resumen['ganados']; ?></h2>
                </div>
            </div>
        </div>

        <!-- ========================== KANBAN ========================== -->
        <div class="row">
            <?php foreach($columnas as $titulo=>$stages): ?>
            <div class="col-md-3">
                <h4><?php echo $titulo; ?></h4>
                <div class="kanban-col">

                    <?php 
            $cards = cotPorEtapas($db, $stages);
            if ($cards->num_rows == 0) echo "<p class='text-muted'>Sin registros</p>";

            while($c = $cards->fetch_assoc()):
                $prob = calcularProbabilidadFinal($c['etapa'], $c['probabilidad']);
                $color = colorProbabilidadLabel($prob);
            ?>
                    <div class="kanban-card">
                        <strong><?php echo $c['codigo']; ?></strong>
                        <small><i class="fa fa-user"></i> <?php echo $c['cliente']; ?></small>

                        <?php if($c['servicio']): ?>
                        <small><i class="fa fa-suitcase"></i> <?php echo $c['servicio']; ?></small>
                        <?php endif; ?>

                        <small><i class="fa fa-calendar"></i> <?php echo $c['fecha']." ".$c['hora']; ?></small>
                        <small><span class="label label-info"><?php echo $c['etapa']; ?></span></small>

                        <div style="margin-top:5px;">
                            <small class="label <?php echo $color; ?>">Prob: <?php echo $prob; ?>%</small>
                        </div>

                        <div class="text-right" style="margin-top:5px;">
                            <a href="cotizacion-detalle.php?id=<?php echo $c['id']; ?>" class="btn btn-xs btn-default">
                                <i class="fa fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ========================== TABLA GENERAL ========================== -->
        <div class="panel-listado">

            <h4><i class="fa fa-table"></i> Listado general de cotizaciones</h4>
            <hr>

            <table id="tablaCot" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Tipos</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Prob</th>
                        <th>Estado</th>
                        <th>Etapa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>

                    <?php 
            while($r = $ListadoSQL->fetch_assoc()):
                $prob = calcularProbabilidadFinal($r['etapa'], $r['probabilidad']);
                $color = colorProbabilidadLabel($prob);
            ?>
                    <tr>
                        <td><?php echo $r['id']; ?></td>
                        <td><?php echo $r['codigo']; ?></td>
                        <td><?php echo $r['cliente']; ?></td>
                        <td><?php echo $r['vendedor']; ?></td>
                        <td><?php echo $r['tipos_servicio']; ?></td>
                        <td><?php echo $r['fecha']." ".$r['hora']; ?></td>
                        <td><?php echo number_format($r['total'],2)." ".$r['moneda']; ?></td>
                        <td><span class="label <?php echo $color; ?>"><?php echo $prob; ?>%</span></td>
                        <td><?php echo $r['estado']; ?></td>
                        <td><?php echo $r['etapa']; ?></td>
                        <td>
                            <a href="cotizacion-detalle.php?id=<?php echo $r['id']; ?>" class="btn btn-xs btn-default">
                                <i class="fa fa-eye"></i>
                            </a>
                            <a href="cotizacion-editar.php?id=<?php echo $r['id']; ?>" class="btn btn-xs btn-primary">
                                <i class="fa fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>

        </div>

    </div>

    <?php include(MODULO.'footer.php'); ?>

    <script src="<?php echo ESTATICO; ?>js/jquery.min.js"></script>
    <script src="<?php echo ESTATICO; ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO; ?>js/dataTables.bootstrap.js"></script>

    <script>
    $(function() {
        $('#tablaCot').DataTable({
            pageLength: 10,
            order: [
                [0, 'desc']
            ],
            language: {
                lengthMenu: "Mostrar _MENU_",
                search: "Buscar:",
                zeroRecords: "No se encontraron resultados",
                info: "Mostrando _START_ a _END_ de _TOTAL_",
                paginate: {
                    next: "Siguiente",
                    previous: "Anterior"
                }
            }
        });
    });
    </script>

</body>

</html>