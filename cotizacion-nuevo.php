<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Instancia segura (por si no está en clase.php)
if (!isset($CotizacionClase)) {
    $CotizacionClase = new Cotizacion();
}

// Listar clientes
$ClientesSQL = $db->SQL("
    SELECT id, nombre 
    FROM cliente 
    WHERE habilitado = 1 
    ORDER BY nombre ASC
");

// Listar servicios/productos
$ServiciosSQL = $db->SQL("
    SELECT id, nombre, tipo_servicio, precioventa 
    FROM producto 
    WHERE habilitado = 1 
    ORDER BY nombre ASC
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Nueva Cotización | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/font-awesome.min.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .panel-custom {
        background: #fff;
        border-radius: 6px;
        padding: 20px;
        border: 1px solid #ddd;
        margin-top: 15px;
    }

    .table-items th,
    .table-items td {
        vertical-align: middle !important;
    }

    .badge-tipo {
        font-size: 11px;
        text-transform: capitalize;
    }
    </style>
</head>

<body>

    <?php
// Menú
if ($usuarioApp['id_perfil'] == 2) {
    include(MODULO . 'menu_vendedor.php');
} elseif ($usuarioApp['id_perfil'] == 1) {
    include(MODULO . 'menu_admin.php');
} else {
    echo '<meta http-equiv="refresh" content="0;url=' . URLBASE . 'cerrar-sesion"/>';
}
?>

    <div class="container">

        <div class="page-header">
            <h1><i class="fa fa-file-text-o"></i> Nueva Cotización</h1>
            <p class="text-muted">Registra una cotización para servicios (pasajes, paquetes, seguros, trámites, etc.).
            </p>
        </div>

        <?php
    // Procesar creación
    $CotizacionClase->CrearCotizacion();
    ?>

        <div class="panel panel-custom">
            <form method="post" class="form-horizontal">

                <!------------------- DATOS GENERALES ------------------->
                <h4><i class="fa fa-user"></i> Datos del Cliente</h4>
                <hr>

                <!-- Cliente -->
                <div class="form-group">
                    <label class="col-md-2 control-label">Cliente</label>
                    <div class="col-md-6">
                        <select name="cliente" class="form-control" required>
                            <option value="">Seleccione un cliente...</option>
                            <?php while ($c = $ClientesSQL->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo $c['nombre']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Moneda y Validez -->
                <div class="form-group">
                    <label class="col-md-2 control-label">Moneda</label>
                    <div class="col-md-3">
                        <select name="moneda" class="form-control">
                            <option value="BOB">Bolivianos (BOB)</option>
                            <option value="USD">Dólares (USD)</option>
                        </select>
                    </div>

                    <label class="col-md-2 control-label">Validez (días)</label>
                    <div class="col-md-2">
                        <input type="number" name="validez" class="form-control" value="7" min="1">
                    </div>
                </div>

                <!-- Fechas -->
                <div class="form-group">
                    <label class="col-md-2 control-label">Fecha inicio</label>
                    <div class="col-md-3">
                        <input type="date" name="fecha_inicio" class="form-control"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <label class="col-md-2 control-label">Fecha entrega</label>
                    <div class="col-md-3">
                        <input type="date" name="fecha_entrega" class="form-control"
                            value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                </div>

                <!-- Observación -->
                <div class="form-group">
                    <label class="col-md-2 control-label">Observación</label>
                    <div class="col-md-8">
                        <textarea name="observacion" rows="3" class="form-control"
                            placeholder="Notas generales de la cotización (condiciones, formas de pago, etc.)"></textarea>
                    </div>
                </div>

                <!------------------- SERVICIOS ------------------->
                <h4><i class="fa fa-suitcase"></i> Servicios</h4>
                <hr>

                <div class="table-responsive">
                    <table class="table table-bordered table-items" id="tablaItems">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Servicio</th>
                                <th style="width: 15%;">Tipo</th>
                                <th style="width: 15%;">Precio</th>
                                <th style="width: 15%;">Cantidad</th>
                                <th style="width: 15%;">Subtotal</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <tr>
                                <td>
                                    <select name="servicio[]" class="form-control servicio-select" required>
                                        <option value="">Seleccione servicio...</option>
                                        <?php
                                // Reposicionar puntero
                                $ServiciosSQL->data_seek(0);
                                while ($s = $ServiciosSQL->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $s['id']; ?>"
                                            data-tipo="<?php echo $s['tipo_servicio']; ?>"
                                            data-precio="<?php echo $s['precioventa']; ?>">
                                            <?php echo $s['nombre']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td class="col-tipo">
                                    <span class="badge badge-tipo label-info">-</span>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="precio[]"
                                        class="form-control precio-item" placeholder="0.00" required>
                                </td>
                                <td>
                                    <input type="number" step="1" min="1" name="cantidad[]"
                                        class="form-control cantidad-item" value="1" required>
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control subtotal-item" value="0.00">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-xs btn-remove-line">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Total estimado:</strong></td>
                                <td>
                                    <input type="text" id="totalGeneral" class="form-control" readonly value="0.00">
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-right">
                    <button type="button" id="btnAgregarLinea" class="btn btn-default">
                        <i class="fa fa-plus"></i> Agregar línea
                    </button>
                </div>

                <hr>

                <!-- BOTONES -->
                <div class="form-group">
                    <div class="col-md-12 text-right">
                        <button type="submit" name="CrearCotizacion" class="btn btn-primary">
                            <i class="fa fa-save"></i> Guardar Cotización
                        </button>
                        <a href="cotizaciones-kanban.php" class="btn btn-default">
                            Cancelar
                        </a>
                    </div>
                </div>

            </form>
        </div>

    </div>

    <?php include(MODULO . 'footer.php'); ?>
    <?php include(MODULO . 'Tema.JS.php'); ?>

    <script src="<?php echo ESTATICO; ?>js/jquery.min.js"></script>
    <script>
    function recalcularFila($row) {
        var precio = parseFloat($row.find('.precio-item').val()) || 0;
        var cant = parseFloat($row.find('.cantidad-item').val()) || 0;
        var sub = precio * cant;
        $row.find('.subtotal-item').val(sub.toFixed(2));
    }

    function recalcularTotal() {
        var total = 0;
        $('#items-body .subtotal-item').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#totalGeneral').val(total.toFixed(2));
    }

    $(document).on('change', '.servicio-select', function() {
        var $row = $(this).closest('tr');
        var tipo = $(this).find(':selected').data('tipo') || '-';
        var precio = $(this).find(':selected').data('precio') || 0;

        $row.find('.col-tipo .badge-tipo').text(tipo.toLowerCase());
        if (!$row.find('.precio-item').val()) {
            $row.find('.precio-item').val(precio);
        }
        recalcularFila($row);
        recalcularTotal();
    });

    $(document).on('keyup change', '.precio-item, .cantidad-item', function() {
        var $row = $(this).closest('tr');
        recalcularFila($row);
        recalcularTotal();
    });

    $('#btnAgregarLinea').on('click', function() {
        var $last = $('#items-body tr:last');
        var $new = $last.clone();

        $new.find('select, input').each(function() {
            $(this).val('');
        });
        $new.find('.cantidad-item').val('1');
        $new.find('.col-tipo .badge-tipo').text('-');
        $new.find('.subtotal-item').val('0.00');

        $('#items-body').append($new);
    });

    $(document).on('click', '.btn-remove-line', function() {
        if ($('#items-body tr').length <= 1) return;
        $(this).closest('tr').remove();
        recalcularTotal();
    });

    // Calcular total inicial
    $(function() {
        $('#items-body tr').each(function() {
            recalcularFila($(this));
        });
        recalcularTotal();
    });
    </script>

</body>

</html>