<?php
session_start();
define('acceso', true);

require_once('sistema/configuracion.php'); // CARGA clase.php y $usuario automáticamente
require_once('sistema/clase/proveedor.clase.php'); // SOLO una vez

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

/* ==========================
   Instancia
========================== */
$Proveedor = new Proveedor();

/* ==========================
   Acciones CRUD reales
========================== */
$Proveedor->CrearProveedor();
$Proveedor->EditarProveedor();
$Proveedor->ActivarProveedor();
$Proveedor->DesactivarProveedor();
$Proveedor->EliminarProveedor();

/* ==========================
   KPIs
========================== */
$KPI = $Proveedor->KPIs();

/* ==========================
   Listado proveedores
========================== */
$Lista = $Proveedor->ListarProveedores();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Gestión de Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

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

    .k2 {
        background: #4caf50;
    }

    .k3 {
        background: #f44336;
    }

    .k4 {
        background: #009688;
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
            <h1><i class="fa fa-truck"></i> Gestión de Proveedores</h1>

            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#ModalProveedor"
                onclick="NuevoProveedor()">
                <i class="fa fa-plus"></i> Nuevo Proveedor
            </button>
            <div style="clear:both;"></div>
        </div>

        <!-- =============================
         KPI PRINCIPALES
    ============================= -->
        <div class="row">
            <div class="col-sm-3">
                <div class="kpi-box k1">
                    <h2><?= $KPI['total'] ?></h2>
                    <small>Total Proveedores</small>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi-box k2">
                    <h2><?= $KPI['activos'] ?></h2>
                    <small>Activos</small>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi-box k3">
                    <h2><?= $KPI['inactivos'] ?></h2>
                    <small>Inactivos</small>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi-box k4">
                    <h2><?= number_format($KPI['deuda_total'],2) ?> Bs</h2>
                    <small>Deuda Total</small>
                </div>
            </div>
        </div>

        <!-- =============================
         TABLA PRINCIPAL
    ============================= -->
        <table class="table table-bordered table-striped" id="tabla_proveedores">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>Tipo</th>
                    <th>Contacto</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                    <th width="180">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $Lista->fetch_assoc()): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= $p['nombre'] ?></td>
                    <td><?= $p['tipo_proveedor'] ?></td>
                    <td><?= $p['contacto'] ?></td>
                    <td><?= $p['email'] ?></td>
                    <td><?= $p['telefono'] ?></td>
                    <td><strong><?= number_format($p['saldo_pendiente'],2) ?> Bs</strong></td>

                    <td>
                        <span class="label <?= $p['habilitado'] ? 'label-success':'label-danger' ?>">
                            <?= $p['habilitado'] ? 'Activo':'Inactivo' ?>
                        </span>
                    </td>

                    <td>
                        <!-- Editar -->
                        <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#ModalProveedor"
                            onclick='EditarProveedor(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <i class="fa fa-pencil"></i>
                        </button>

                        <!-- Activar / Desactivar -->
                        <form method="post" style="display:inline-block;">
                            <input type="hidden"
                                name="<?= $p['habilitado']==1?'DesactivarProveedor':'ActivarProveedor' ?>">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">

                            <button class="btn btn-<?= $p['habilitado']==1?'warning':'success' ?> btn-xs">
                                <i class="fa <?= $p['habilitado']==1?'fa-ban':'fa-check' ?>"></i>
                            </button>
                        </form>

                        <!-- Eliminar -->
                        <form method="post" style="display:inline-block;"
                            onsubmit="return confirm('¿Eliminar proveedor?');">

                            <input type="hidden" name="EliminarProveedor">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">

                            <button class="btn btn-danger btn-xs">
                                <i class="fa fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>

    <?php include("modal_proveedor.php"); ?>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_proveedores').dataTable();

    function NuevoProveedor() {
        document.getElementById("FormProveedor").reset();
        document.getElementById("id").value = "";
    }

    function EditarProveedor(p) {
        document.getElementById("id").value = p.id;
        document.getElementById("nombre").value = p.nombre;
        document.getElementById("telefono").value = p.telefono;
        document.getElementById("contacto").value = p.contacto;
        document.getElementById("email").value = p.email;
        document.getElementById("direccion").value = p.direccion;
        document.getElementById("tipo_proveedor").value = p.tipo_proveedor;
        document.getElementById("habilitado").checked = (p.habilitado == 1);
    }
    </script>

</body>

</html>