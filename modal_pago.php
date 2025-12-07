<?php
// Modal para crear / editar pago a proveedor
// Asume que $Proveedor está instanciado en la página que incluye este archivo.

$SelectorProveedoresPago = $Proveedor->SelectorProveedores();

// Listado de facturas (para seleccionar una factura opcionalmente)
global $db;
$FacturasSelect = $db->SQL("
    SELECT f.id, f.nro_factura, p.nombre AS proveedor
    FROM proveedor_factura f
    INNER JOIN proveedor p ON p.id=f.id_proveedor
    ORDER BY f.id DESC
");
?>
<div class="modal fade" id="ModalPago" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <form method="post" id="FormPago">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-money"></i> Pago a Proveedor</h4>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="id_pago" id="id_pago">

                    <div class="form-group">
                        <label>Proveedor</label>
                        <select class="form-control" name="id_proveedor" id="id_proveedor_pago" required>
                            <option value="">Seleccione...</option>
                            <?php while($pr = $SelectorProveedoresPago->fetch_assoc()): ?>
                            <option value="<?= $pr['id'] ?>">
                                <?= $pr['nombre'] ?> (<?= $pr['tipo_proveedor'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Factura Asociada (Opcional)</label>
                        <select class="form-control" name="id_factura" id="id_factura_pago">
                            <option value="">-- Sin factura específica --</option>
                            <?php while($ff = $FacturasSelect->fetch_assoc()): ?>
                            <option value="<?= $ff['id'] ?>">
                                <?= $ff['proveedor'] ?> - N° <?= $ff['nro_factura'] ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Pago</label>
                        <input type="date" class="form-control" name="fecha_pago" id="fecha_pago"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Monto (Bs)</label>
                        <input type="number" step="0.01" class="form-control" name="monto" id="monto" required>
                    </div>

                    <div class="form-group">
                        <label>Método de Pago</label>
                        <select class="form-control" name="metodo_pago" id="metodo_pago" required>
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                            <option value="DEPOSITO">Depósito</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Referencia / Observación</label>
                        <input type="text" class="form-control" name="referencia" id="referencia"
                            placeholder="N° de operación, detalle, etc.">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Cerrar
                    </button>
                    <button type="submit" name="GuardarPago" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar Pago
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>