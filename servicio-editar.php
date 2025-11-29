<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

include("clases/servicio.clase.php");
$Servicios = new Servicio();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: servicios.php");
    exit;
}

$servicio = $Servicios->Obtener($id);
if (!$servicio) {
    echo "<p>Servicio no encontrado.</p>";
    echo '<a href="servicios.php">Volver</a>';
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Proveedores
$ProveedoresSQL = $db->SQL("SELECT id, nombre FROM proveedor WHERE habilitado=1 ORDER BY nombre ASC");

if (isset($_POST['GuardarServicio'])) {

    // Mantener imagen actual por defecto
    $imagen = $servicio['imagen'];

    // Si se sube una nueva, la reemplazamos (la clase puede encargarse de borrar la anterior si quieres)
    if (!empty($_FILES['imagen']['name'])) {
        $imgSubida = $Servicios->SubirImagen($_FILES['imagen']);
        if ($imgSubida) {
            $imagen = $imgSubida;
        }
    }

    $data = [
        'nombre'        => $_POST['nombre'],
        'codigo'        => $_POST['codigo'],
        'tipo_servicio' => $_POST['tipo_servicio'],
        'proveedor'     => $_POST['proveedor'],
        'preciocosto'   => $_POST['preciocosto'],
        'precioventa'   => $_POST['precioventa'],
        'comision'      => $_POST['comision'],
        'descripcion'   => $_POST['descripcion'],
        'imagen'        => $imagen
    ];

    $ok = $Servicios->Actualizar($id, $data);

    if ($ok) {
        $mensaje = "Servicio actualizado correctamente.";
        $tipo_mensaje = "success";
        // Recargar datos
        $servicio = $Servicios->Obtener($id);
    } else {
        $mensaje = "Error al actualizar el servicio.";
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editar Servicio | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .panel-heading h3 {
        margin: 0;
    }

    .preview-img {
        width: 140px;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ddd;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 2) include(MODULO.'menu_vendedor.php');
elseif ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
?>

    <div class="container">

        <div class="page-header">
            <h1>Editar Servicio</h1>
            <p class="text-muted"><?php echo htmlspecialchars($servicio['nombre']); ?></p>
        </div>

        <?php if ($mensaje != ''): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="panel panel-default">
            <div class="panel-heading">
                <h3>Datos del Servicio</h3>
            </div>

            <div class="panel-body">

                <div class="row">

                    <!-- Nombre -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre del Servicio *</label>
                            <input type="text" name="nombre" class="form-control"
                                value="<?php echo htmlspecialchars($servicio['nombre']); ?>" required>
                        </div>
                    </div>

                    <!-- Código -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Código *</label>
                            <input type="text" name="codigo" class="form-control"
                                value="<?php echo htmlspecialchars($servicio['codigo']); ?>" required>
                        </div>
                    </div>

                    <!-- Tipo -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipo de Servicio *</label>
                            <select name="tipo_servicio" class="form-control" required>
                                <?php
                            $tipos = ['PASAJE'=>'Pasaje','PAQUETE'=>'Paquete','SEGURO'=>'Seguro','TRAMITE'=>'Trámite','OTRO'=>'Otro'];
                            foreach ($tipos as $val => $txt):
                            ?>
                                <option value="<?php echo $val; ?>"
                                    <?php if($servicio['tipo_servicio']==$val) echo 'selected'; ?>>
                                    <?php echo $txt; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div><!-- /.row -->

                <div class="row">

                    <!-- Proveedor -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Proveedor *</label>
                            <select name="proveedor" class="form-control" required>
                                <option value="">Seleccione proveedor...</option>
                                <?php while($p = $ProveedoresSQL->fetch_assoc()): ?>
                                <option value="<?php echo $p['id']; ?>"
                                    <?php if($servicio['proveedor']==$p['id']) echo 'selected'; ?>>
                                    <?php echo $p['nombre']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Precio Costo -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Precio Costo</label>
                            <input type="number" step="0.01" name="preciocosto" class="form-control"
                                value="<?php echo htmlspecialchars($servicio['preciocosto']); ?>">
                        </div>
                    </div>

                    <!-- Precio Venta -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Precio Venta *</label>
                            <input type="number" step="0.01" name="precioventa" class="form-control"
                                value="<?php echo htmlspecialchars($servicio['precioventa']); ?>" required>
                        </div>
                    </div>

                </div><!-- /.row -->

                <div class="row">

                    <!-- Comisión -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Comisión (Bs)</label>
                            <input type="number" step="0.01" name="comision" class="form-control"
                                value="<?php echo htmlspecialchars($servicio['comision']); ?>">
                        </div>
                    </div>

                    <!-- Imagen -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Cambiar Imagen</label>
                            <input type="file" name="imagen" class="form-control" accept="image/*"
                                onchange="vistaPrevia(this);">
                            <br>
                            <?php if (!empty($servicio['imagen'])): ?>
                            <img id="preview" class="preview-img"
                                src="uploads/servicios/<?php echo $servicio['imagen']; ?>">
                            <?php else: ?>
                            <img id="preview" class="preview-img" style="display:none;">
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /.row -->

                <!-- Descripción -->
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3"><?php
                    echo htmlspecialchars($servicio['descripcion']);
                ?></textarea>
                </div>

            </div><!-- /.panel-body -->

            <div class="panel-footer">
                <a href="servicios.php" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Volver
                </a>

                <button type="submit" name="GuardarServicio" class="btn btn-primary pull-right">
                    <i class="fa fa-save"></i> Guardar Cambios
                </button>

                <div style="clear:both;"></div>
            </div>

        </form>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script>
    function vistaPrevia(input) {
        const preview = document.getElementById('preview');
        if (input.files && input.files[0]) {
            preview.style.display = "block";
            preview.src = URL.createObjectURL(input.files[0]);
        }
    }
    </script>

</body>

</html>