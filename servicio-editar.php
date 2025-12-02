<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// -----------------------------
// VALIDAR ID
// -----------------------------
if (!isset($_GET['id'])) {
    echo '<meta http-equiv="refresh" content="0;url=servicios.php">';
    exit;
}

$idServicio = (int)$_GET['id'];

// -----------------------------
// OBTENER SERVICIO
// -----------------------------
$ServicioSQL = $db->SQL("
    SELECT * FROM producto 
    WHERE id = {$idServicio} 
    LIMIT 1
");

if ($ServicioSQL->num_rows == 0) {
    echo '<div class="alert alert-danger">Servicio no encontrado.</div>';
    exit;
}

$S = $ServicioSQL->fetch_assoc();

// -----------------------------
// OBTENER PROVEEDORES
// -----------------------------
$Proveedores = $db->SQL("
    SELECT id, nombre 
    FROM proveedor 
    WHERE habilitado = 1 
    ORDER BY nombre ASC
");

// -----------------------------
// OBTENER CATEGORÍAS
// -----------------------------
$Categorias = $db->SQL("
    SELECT id, nombre 
    FROM categorias_servicios 
    ORDER BY nombre ASC
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editar Servicio | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/font-awesome.min.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .panel-custom {
        background: #fff;
        padding: 25px;
        border-radius: 6px;
        border: 1px solid #ddd;
        margin-top: 15px;
    }

    h3.section-title {
        margin-top: 30px;
        font-weight: bold;
        color: #555;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 2) {
    include(MODULO . 'menu_vendedor.php');
} elseif ($usuarioApp['id_perfil'] == 1) {
    include(MODULO . 'menu_admin.php');
}
?>

    <div class="container">

        <div class="page-header">
            <h1><i class="fa fa-pencil"></i> Editar Servicio</h1>

            <a href="servicios.php" class="btn btn-default pull-right">
                <i class="fa fa-arrow-left"></i> Volver al catálogo
            </a>

            <div class="clearfix"></div>
        </div>

        <!-- PROCESAR FORMULARIO -->
        <?php $ProductosClase->EditarServicio(); ?>

        <div class="panel panel-custom">

            <form class="form-horizontal" method="post">

                <input type="hidden" name="IdServicio" value="<?php echo $S['id']; ?>">

                <!-- =============================
                 DATOS BÁSICOS
            ==============================-->
                <h3 class="section-title">Datos Básicos</h3>

                <!-- Nombre -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Nombre del Servicio</label>
                    <div class="col-md-9">
                        <input type="text" name="Nombre" class="form-control" value="<?php echo $S['nombre']; ?>"
                            required>
                    </div>
                </div>

                <!-- Código -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Código</label>
                    <div class="col-md-9">
                        <input type="text" name="Codigo" class="form-control" value="<?php echo $S['codigo']; ?>"
                            required>
                    </div>
                </div>

                <!-- Tipo de Servicio -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Tipo de Servicio</label>
                    <div class="col-md-9">
                        <select name="TipoServicio" class="form-control">
                            <?php
                        $tipos = ['PASAJE','PAQUETE','SEGURO','TRAMITE','OTRO'];
                        foreach ($tipos as $t):
                        ?>
                            <option value="<?php echo $t; ?>" <?php if ($S['tipo_servicio'] == $t) echo 'selected'; ?>>
                                <?php echo ucfirst(strtolower($t)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Categoría -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Categoría</label>
                    <div class="col-md-9">
                        <select name="Categoria" class="form-control">
                            <option value="">Sin categoría</option>
                            <?php while ($c = $Categorias->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>"
                                <?php if ($S['categoria_id'] == $c['id']) echo 'selected'; ?>>
                                <?php echo $c['nombre']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Proveedor -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Proveedor</label>
                    <div class="col-md-9">
                        <select name="Proveedor" class="form-control">
                            <?php while ($p = $Proveedores->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>"
                                <?php if ($S['proveedor'] == $p['id']) echo 'selected'; ?>>
                                <?php echo $p['nombre']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- =============================
                 PRECIOS Y COMISIONES
            ==============================-->
                <h3 class="section-title">Precios y Comisión</h3>

                <!-- Precio Costo -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Precio Costo</label>
                    <div class="col-md-9">
                        <input type="number" step="0.01" name="PrecioCosto" class="form-control"
                            value="<?php echo $S['preciocosto']; ?>">
                    </div>
                </div>

                <!-- Precio Venta -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Precio Venta</label>
                    <div class="col-md-9">
                        <input type="number" step="0.01" name="PrecioVenta" required class="form-control"
                            value="<?php echo $S['precioventa']; ?>">
                    </div>
                </div>

                <!-- IVA -->
                <div class="form-group">
                    <label class="col-md-3 control-label">IVA (%)</label>
                    <div class="col-md-9">
                        <input type="number" step="0.01" name="IVA" class="form-control"
                            value="<?php echo $S['iva']; ?>">
                    </div>
                </div>

                <!-- Comisión -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Comisión (Bs)</label>
                    <div class="col-md-9">
                        <input type="number" step="0.01" name="Comision" class="form-control"
                            value="<?php echo $S['comision']; ?>">
                    </div>
                </div>

                <!-- Es Comisionable -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Es Comisionable</label>
                    <div class="col-md-9">
                        <select name="EsComisionable" class="form-control">
                            <option value="1" <?php if ($S['es_comisionable']) echo 'selected'; ?>>Sí</option>
                            <option value="0" <?php if (!$S['es_comisionable']) echo 'selected'; ?>>No</option>
                        </select>
                    </div>
                </div>

                <!-- =============================
                 REQUISITOS
            ==============================-->
                <h3 class="section-title">Requisitos</h3>

                <!-- Requiere Boleto -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Requiere Boleto</label>
                    <div class="col-md-9">
                        <select name="RequiereBoleto" class="form-control">
                            <option value="1" <?php if ($S['requiere_boleto']) echo 'selected'; ?>>Sí</option>
                            <option value="0" <?php if (!$S['requiere_boleto']) echo 'selected'; ?>>No</option>
                        </select>
                    </div>
                </div>

                <!-- Requiere Visa -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Requiere Visa</label>
                    <div class="col-md-9">
                        <select name="RequiereVisa" class="form-control">
                            <option value="1" <?php if ($S['requiere_visa']) echo 'selected'; ?>>Sí</option>
                            <option value="0" <?php if (!$S['requiere_visa']) echo 'selected'; ?>>No</option>
                        </select>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Descripción</label>
                    <div class="col-md-9">
                        <textarea class="form-control" name="DescripcionServicio"
                            rows="4"><?php echo $S['descripcion']; ?></textarea>
                    </div>
                </div>

                <!-- Especificaciones -->
                <div class="form-group">
                    <label class="col-md-3 control-label">Especificaciones</label>
                    <div class="col-md-9">
                        <textarea class="form-control" name="Especificaciones"
                            rows="3"><?php echo $S['especificaciones']; ?></textarea>
                    </div>
                </div>

                <hr>

                <!-- Botones -->
                <div class="form-group">
                    <div class="col-md-12 text-right">
                        <button type="submit" name="EditarServicio" class="btn btn-primary">
                            <i class="fa fa-save"></i> Guardar Cambios
                        </button>

                        <a href="servicios.php" class="btn btn-default">
                            Cancelar
                        </a>
                    </div>
                </div>

            </form>

        </div>

    </div>

    <?php include(MODULO . 'footer.php'); ?>
    <?php include(MODULO . 'Tema.JS.php'); ?>

</body>

</html>