<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Cargar backend
include("clases/servicio.clase.php");
$Servicio = new Servicio();

// Obtener tipos de servicio y proveedores dinámicamente
$tiposServicio = $Servicio->obtenerTiposDeServicio();
$proveedores = $db->SQL("SELECT id, nombre FROM proveedor WHERE habilitado=1 ORDER BY nombre ASC");

// Filtros
$filtro_tipo      = $_GET['tipo'] ?? '';
$filtro_proveedor = $_GET['proveedor'] ?? '';
$filtro_estado    = $_GET['estado'] ?? '1'; // Activos
$filtro_precio_min = $_GET['precio_min'] ?? 0;
$filtro_precio_max = $_GET['precio_max'] ?? 5000; // Ejemplo de máximo

$cond = "WHERE p.habilitado = 1 AND p.precioventa BETWEEN $filtro_precio_min AND $filtro_precio_max";

if ($filtro_tipo != '') {
    $cond .= " AND p.tipo_servicio = '" . addslashes($filtro_tipo) . "' ";
}

if ($filtro_proveedor != '') {
    $cond .= " AND p.proveedor = '" . intval($filtro_proveedor) . "' ";
}

if ($filtro_estado != '') {
    $cond .= " AND p.habilitado = '" . intval($filtro_estado) . "' ";
}

$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$SQL = $db->SQL("SELECT p.*, pr.nombre AS proveedor_nombre FROM producto p LEFT JOIN proveedor pr ON pr.id = p.proveedor $cond ORDER BY p.id DESC LIMIT $limit OFFSET $offset");

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
    <style>
        .table-img {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
        }
        .kpi-card {
            background-color: #f7f7f7;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .kpi-card h3 {
            margin-top: 10px;
            font-size: 24px;
            color: #007BFF;
        }
        .filter-panel {
            margin-bottom: 30px;
        }
        .panel-heading {
            font-weight: bold;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <?php include(MODULO.'menu_admin.php'); ?>

    <div class="container">
        <div class="page-header">
            <h1>Catálogo de Servicios</h1>
            <p class="text-muted">Servicios disponibles para venta en POS.</p>

            <a href="servicio-nuevo.php" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> Nuevo Servicio
            </a>
            <div style="clear:both;"></div>
        </div>

        <!-- KPIs -->
        <div class="row">
            <div class="col-md-4">
                <div class="kpi-card">
                    <h4>Total de Servicios Activos</h4>
                    <h3><?php echo $totalServicios; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <h4>Servicios Comisionables</h4>
                    <h3><?php echo $comisionables; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <h4>Precio Venta Promedio</h4>
                    <h3>Bs <?php echo number_format($precioPromedio, 2); ?></h3>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="get" class="panel panel-default filter-panel">
            <div class="panel-heading">
                <strong>Filtros</strong>
            </div>
            <div class="panel-body">
                <div class="row">
                    <!-- Tipo -->
                    <div class="col-md-4">
                        <label>Tipo de Servicio</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($tiposServicio as $tipo): ?>
                                <option value="<?php echo $tipo; ?>" <?php if($filtro_tipo==$tipo) echo 'selected'; ?>>
                                    <?php echo ucfirst(strtolower($tipo)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Proveedor -->
                    <div class="col-md-4">
                        <label>Proveedor</label>
                        <select name="proveedor" class="form-control">
                            <option value="">Todos</option>
                            <?php while($p = $proveedores->fetch_assoc()): ?>
                                <option value="<?php echo $p['id']; ?>" <?php if($filtro_proveedor==$p['id']) echo 'selected'; ?>>
                                    <?php echo $p['nombre']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Precio -->
                    <div class="col-md-4">
                        <label>Rango de Precio</label>
                        <input type="number" name="precio_min" class="form-control" placeholder="Mínimo" value="<?php echo $filtro_precio_min; ?>">
                        <input type="number" name="precio_max" class="form-control" placeholder="Máximo" value="<?php echo $filtro_precio_max; ?>">
                    </div>

                    <!-- Botón -->
                    <div class="col-md-12 text-center">
                        <button class="btn btn-default">
                            <i class="fa fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Tabla de Servicios -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>Listado de Servicios</strong>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table id="tablaServicios" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>IMG</th>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Precio Venta</th>
                                <th>Proveedor</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($s = $SQL->fetch_assoc()): ?>
                            <tr>
                                <td><img src="uploads/servicios/<?php echo $s['imagen']; ?>" class="table-img"></td>
                                <td><?php echo $s['nombre']; ?></td>
                                <td><?php echo $s['codigo']; ?></td>
                                <td><?php echo $s['tipo_servicio']; ?></td>
                                <td>Bs <?php echo number_format($s['precioventa'], 2); ?></td>
                                <td><?php echo $s['proveedor_nombre'] ?: '-'; ?></td>
                                <td>
                                    <a href="servicio-editar.php?id=<?php echo $s['id']; ?>" class="btn btn-info btn-xs">
                                        <i class="fa fa-pencil"></i> Editar
                                    </a>
                                    <a href="servicio-eliminar.php?id=<?php echo $s['id']; ?>" class="btn btn-danger btn-xs">
                                        <i class="fa fa-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $(document).ready(function() {
        $('#tablaServicios').dataTable({
            "order": [[1, "asc"]],
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
