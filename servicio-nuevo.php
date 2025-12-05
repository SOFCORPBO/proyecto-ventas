<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Cargar backend
include("clases/servicio.clase.php");
$Servicio = new Servicio();

// Configuración dinámica
$tiposServicio = $Servicio->obtenerTiposDeServicio();
$proveedores = $db->SQL("SELECT id, nombre FROM proveedor WHERE habilitado=1 ORDER BY nombre ASC");

if ($_POST) {
    $nombre = $_POST['nombre'];
    $codigo = $_POST['codigo'];
    $tipo_servicio = $_POST['tipo_servicio'];
    $proveedor = $_POST['proveedor'];
    $precio_costo = $_POST['precio_costo'];
    $precio_venta = $_POST['precio_venta'];
    $comision = $_POST['comision'];

    // Validaciones
    if (!in_array($tipo_servicio, $tiposServicio)) {
        echo "El tipo de servicio no es válido.";
        exit;
    }

    $Servicio->crearServicio($nombre, $codigo, $tipo_servicio, $proveedor, $precio_costo, $precio_venta, $comision);
    header("Location: servicios.php");  // Redirige al listado de servicios
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nuevo Servicio | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/font-awesome.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>
<body>

    <?php include(MODULO.'menu_admin.php'); ?>

    <div class="container">
        <div class="page-header">
            <h1>Nuevo Servicio</h1>
        </div>

        <form method="post">
            <div class="form-group">
                <label>Nombre del Servicio</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Código del Servicio</label>
                <input type="text" name="codigo" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Tipo de Servicio</label>
                <select name="tipo_servicio" class="form-control" required>
                    <?php foreach ($tiposServicio as $tipo): ?>
                        <option value="<?php echo $tipo; ?>"><?php echo ucfirst(strtolower($tipo)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Proveedor</label>
                <select name="proveedor" class="form-control">
                    <option value="">Seleccionar Proveedor</option>
                    <?php while ($p = $proveedores->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Precio Costo</label>
                <input type="number" name="precio_costo" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Precio Venta</label>
                <input type="number" name="precio_venta" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Comisión</label>
                <input type="number" name="comision" class="form-control">
            </div>

            <button type="submit" class="btn btn-success">Crear Servicio</button>
        </form>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>
</html>
