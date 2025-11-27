<?php
/*
|-------------------------------------------------------------
| FORMULARIO PARA REGISTRAR EL PAGO / FACTURA / RECIBO
|-------------------------------------------------------------
*/

$TotalSQL = $db->SQL("
    SELECT SUM(totalprecio) AS total 
    FROM cajatmp 
    WHERE vendedor='{$usuarioApp['id']}'
");
$Total = $TotalSQL->fetch_assoc();
$TotalPagar = $Total['total'] ?? 0;
?>

<!-- MODAL REGISTRAR VENTA -->
<div class="modal fade" id="RegistrarCompra" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <form method="post" action="" onsubmit="return ComprobarVenta();">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">
                        Registrar Venta – Total:
                        <strong class="text-success">$ <?php echo number_format($TotalPagar, 2); ?></strong>
                    </h4>
                </div>

                <div class="modal-body">
                    <div class="row">

                        <!-- CLIENTE -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cliente</label>
                                <select class="form-control" name="cliente" required>
                                    <?php foreach ($SelectorClientesArray as $c): ?>
                                    <option value="<?php echo $c['id']; ?>">
                                        <?php echo $c['nombre']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- ¿CON FACTURA? -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>¿Con factura?</label>
                                <select class="form-control" name="con_factura" required>
                                    <option value="1">Sí</option>
                                    <option value="0" selected>No</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <!-- MÉTODO DE PAGO -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Método de Pago</label>
                                <select class="form-control" name="metodo_pago" id="metodo_pago" required>
                                    <option value="EFECTIVO">Efectivo</option>
                                    <option value="TRANSFERENCIA">Transferencia</option>
                                    <option value="DEPOSITO">Depósito</option>
                                    <option value="TARJETA">Tarjeta</option>
                                </select>
                            </div>
                        </div>

                        <!-- CAJA -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Caja</label>
                                <select class="form-control" name="tipo_caja" required>
                                    <option value="CHICA">Caja Chica</option>
                                    <option value="GENERAL">Caja General</option>
                                </select>
                            </div>
                        </div>

                        <!-- BANCO SOLO SI NO ES EFECTIVO -->
                        <div class="col-md-4" id="banco_div" style="display:none;">
                            <div class="form-group">
                                <label>Banco</label>
                                <select class="form-control" name="id_banco">
                                    <?php foreach ($ListaBancosArray as $b): ?>
                                    <option value="<?php echo $b['id']; ?>">
                                        <?php echo $b['nombre']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <!-- OBSERVACIONES -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Observaciones de la venta</label>
                                <textarea class="form-control" name="observacion" rows="3"
                                    placeholder="Ej: Detalles del servicio, notas adicionales"></textarea>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Cerrar
                    </button>

                    <button type="submit" name="RegistrarVenta" class="btn btn-primary">
                        <i class="fa fa-check"></i> Procesar Venta
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- SCRIPT PARA MOSTRAR BANCO SI NO ES EFECTIVO -->
<script>
document.getElementById('metodo_pago').addEventListener('change', function() {
    var metodo = this.value;
    if (metodo === 'EFECTIVO') {
        document.getElementById('banco_div').style.display = 'none';
    } else {
        document.getElementById('banco_div').style.display = 'block';
    }
});
</script>