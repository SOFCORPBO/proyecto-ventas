<?php 
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Instancia clase cliente
$ClienteClase = new Cliente();

// Acciones
$ClienteClase->EliminarCliente();
$ClienteClase->ActivarCliente();
$ClienteClase->DesactivarCliente();

$ListaClientes = $ClienteClase->ListarClientes();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Clientes | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==2) include(MODULO.'menu_vendedor.php');
else include(MODULO.'menu_admin.php');
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header">
                <h1>Gestion Clientes</h1>
                <a href="nuevo-cliente.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Nuevo Cliente
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="tabla_clientes">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>CI / Pasaporte</th>
                            <th>Tipo Doc.</th>
                            <th>Nacionalidad</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Descuento</th>
                            <th>Estado</th>
                            <th width="240">Opciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $ListaClientes->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['nombre'] ?></td>
                            <td><?= $row['ci_pasaporte'] ?></td>
                            <td><?= $row['tipo_documento'] ?></td>
                            <td><?= $row['nacionalidad'] ?></td>
                            <td><?= $row['telefono'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td><?= $row['descuento'] ?>%</td>

                            <td>
                                <?php if ($row['habilitado']==1): ?>
                                <span class="label label-success">Activo</span>
                                <?php else: ?>
                                <span class="label label-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <!-- Expediente completo -->
                                <a href="cliente-expediente.php?id=<?= $row['id'] ?>" class="btn btn-info btn-xs"
                                    title="Expediente del cliente">
                                    <i class="fa fa-folder-open"></i>
                                </a>

                                <!-- Ver cliente -->
                                <a href="ver-cliente.php?id=<?= $row['id'] ?>" class="btn btn-default btn-xs"
                                    title="Ver Cliente">
                                    <i class="fa fa-user"></i>
                                </a>

                                <!-- Editar -->
                                <a href="editar-cliente.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-xs"
                                    title="Editar Cliente">
                                    <i class="fa fa-pencil"></i>
                                </a>

                                <!-- Activar / Desactivar -->
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <?php if ($row['habilitado']==1): ?>
                                    <button type="submit" name="DesactivarCliente" class="btn btn-warning btn-xs">
                                        <i class="fa fa-ban"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="submit" name="ActivarCliente" class="btn btn-success btn-xs">
                                        <i class="fa fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                </form>

                                <!-- Eliminar -->
                                <form method="post" style="display:inline-block;"
                                    onsubmit="return confirm('¿Eliminar este cliente?');">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="EliminarCliente" class="btn btn-danger btn-xs">
                                        <i class="fa fa-trash"></i>
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
    $(document).ready(function() {
        $('#tabla_clientes').dataTable({
            "scrollX": true
        });
    });
    </script>

</body>

</html>