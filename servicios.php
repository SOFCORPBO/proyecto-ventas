<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Backend
include("clase/productos.clase.php");
$Servicio = new Servicio();

// Tipos y proveedores
$tiposServicio = $Servicio->obtenerTiposDeServicio();
$proveedores  = $db->SQL("SELECT id, nombre FROM proveedor WHERE habilitado = 1 ORDER BY nombre ASC");

// Filtros
$filtro_tipo        = $_GET['tipo'] ?? '';
$filtro_proveedor   = $_GET['proveedor'] ?? '';
$filtro_estado      = $_GET['estado'] ?? '1';
$filtro_precio_min  = $_GET['precio_min'] ?? 0;
$filtro_precio_max  = $_GET['precio_max'] ?? 5000;

// Condiciones de búsqueda
$cond = "WHERE p.habilitado = 1 AND p.precioventa BETWEEN {$filtro_precio_min} AND {$filtro_precio_max}";

if ($filtro_tipo != '') {
    $cond .= " AND p.tipo_servicio = '" . addslashes($filtro_tipo) . "' ";
}
if ($filtro_proveedor != '') {
    $cond .= " AND p.proveedor = '" . intval($filtro_proveedor) . "' ";
}
if ($filtro_estado != '') {
    $cond .= " AND p.habilitado = '" . intval($filtro_estado) . "' ";
}

// Paginación
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Consulta principal
$SQL = $db->SQL("
    SELECT p.*,
           pr.nombre AS proveedor_nombre,
           c.nombre AS categoria_nombre
    FROM producto p
    LEFT JOIN proveedor pr ON pr.id = p.proveedor
    LEFT JOIN categorias_servicios c ON c.id = p.categoria_id
    $cond
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $offset
");

// KPIs
$totalServicios = $db->SQL("SELECT COUNT(*) AS total FROM producto WHERE habilitado = 1")->fetch_assoc()['total'];
$comisionables  = $db->SQL("SELECT COUNT(*) AS comisionables FROM producto WHERE habilitado = 1 AND comision > 0")->fetch_assoc()['comisionables'];
$precioPromedio = $db->SQL("SELECT AVG(precioventa) AS promedio FROM producto WHERE habilitado = 1")->fetch_assoc()['promedio'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Catálogo de Servicios | <?php echo TITULO ?></title>

    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">

    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .kpi-card {
        background: #f7f7f7;
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }

    .kpi-card h3 {
        color: #007BFF;
    }

    .filter-panel {
        margin-bottom: 20px;
    }
    </style>
</head>

<body>

    <?php include(MODULO.'menu_admin.php'); ?>

    <div class="container">

        <div class="page-header">
            <h1>Catálogo de Servicios</h1>
            <p class="text-muted">Servicios disponibles para venta en POS.</p>

            <a href="productos.php" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> Nuevo Servicio
            </a>
            <div style="clear:both;"></div>
        </div>

        <!-- KPIs -->
        <div class="row">
            <div class="col-md-4">
                <div class="kpi-card">
                    <h4>Total Activos</h4>
                    <h3><?php echo $totalServicios; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <h4>Comisionables</h4>
                    <h3><?php echo $comisionables; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <h4>Precio Promedio</h4>
                    <h3>Bs <?php echo number_format($precioPromedio, 2); ?></h3>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <form method="get" class="panel panel-default filter-panel">
            <div class="panel-heading">Filtros</div>
            <div class="panel-body">
                <div class="row">

                    <div class="col-md-4">
                        <label>Tipo de Servicio</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($tiposServicio as $tipo): ?>
                            <option value="<?php echo $tipo; ?>"
                                <?php echo ($filtro_tipo == $tipo) ? 'selected' : ''; ?>>
                                <?php echo ucfirst(strtolower($tipo)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Proveedor</label>
                        <select name="proveedor" class="form-control">
                            <option value="">Todos</option>
                            <?php while($p = $proveedores->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>"
                                <?php echo ($filtro_proveedor == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo $p['nombre']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Rango de Precio</label>
                        <div class="row">
                            <div class="col-xs-6">
                                <input type="number" name="precio_min" class="form-control"
                                    value="<?php echo $filtro_precio_min; ?>" placeholder="Mínimo">
                            </div>
                            <div class="col-xs-6">
                                <input type="number" name="precio_max" class="form-control"
                                    value="<?php echo $filtro_precio_max; ?>" placeholder="Máximo">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 text-center" style="margin-top:15px;">
                        <button class="btn btn-default"><i class="fa fa-search"></i> Buscar</button>
                    </div>

                </div>
            </div>
        </form>

        <!-- TABLA -->
        <div class="table-responsive">
            <table class="table table-bordered" id="servicios">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Servicio</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Proveedor</th>
                        <th>Precio Venta</th>
                        <th>Precio Costo</th>
                        <th>IVA</th>
                        <th>Comisión</th>
                        <th>Boleto</th>
                        <th>Visa</th>
                        <th>Impuesto</th>
                        <th>Especificaciones</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($row = $SQL->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['codigo']; ?></td>
                        <td><?php echo $row['nombre']; ?></td>
                        <td><?php echo $row['tipo_servicio']; ?></td>
                        <td><?php echo $row['categoria_nombre'] ?: '-'; ?></td>
                        <td><?php echo $row['proveedor_nombre'] ?: '-'; ?></td>

                        <td>Bs <?php echo number_format($row['precioventa'], 2); ?></td>
                        <td>Bs <?php echo number_format($row['preciocosto'], 2); ?></td>

                        <td><?php echo number_format($row['iva'], 2); ?>%</td>
                        <td><?php echo number_format($row['comision'], 2); ?>%</td>

                        <td><?php echo ($row['requiere_boleto'] ? '<span class="label label-info">Sí</span>' : '<span class="label label-default">No</span>'); ?>
                        </td>
                        <td><?php echo ($row['requiere_visa'] ? '<span class="label label-info">Sí</span>' : '<span class="label label-default">No</span>'); ?>
                        </td>

                        <td><?php echo $row['impuesto']; ?>%</td>

                        <td><?php echo (strlen($row['especificaciones']) > 35) ? substr($row['especificaciones'], 0, 35).'...' : $row['especificaciones']; ?>
                        </td>

                        <td>
                            <?php echo ($row['habilitado'] == 1)
                            ? '<span class="label label-success">Activo</span>'
                            : '<span class="label label-danger">Inactivo</span>'; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $(document).ready(function() {
        $('#servicios').dataTable({
            "order": [
                [1, "asc"]
            ],
            "pageLength": 10,
            "language": {
                "lengthMenu": "Mostrar _MENU_ registros",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron servicios",
                "info": "Página _PAGE_ de _PAGES_",
                "infoEmpty": "Sin registros",
                "infoFiltered": "(filtrado de _MAX_ registros)"
            }
        });
    });
    </script>

</body>

</html>