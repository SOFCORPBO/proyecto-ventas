<?php
// Modal para crear / editar factura de proveedor
// Asume que $Proveedor está instanciado en la página que incluye este archivo.
$SelectorProveedores = $Proveedor->SelectorProveedores();
?>
<div class="modal fade" id="ModalFactura" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <form method="post" id="FormFactura">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-file-text-o"></i> Factura de Proveedor</h4>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="id_factura" id="id_factura">

                    <div class="form-group">
                        <label>Proveedor</label>
                        <select class="form-control" name="id_proveedor" id="id_proveedor" required>
                            <option value="">Seleccione...</option>
                            <?php while($pr = $SelectorProveedores->fetch_assoc()): ?>
                            <option value="<?= $pr['id'] ?>">
                                <?= $pr['nombre'] ?> (<?= $pr['tipo_proveedor'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>N° Factura</label>
                        <input type="text" class="form-control" name="nro_factura" id="nro_factura" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Emisión</label>
                        <input type="date" class="form-control" name="fecha_emision" id="fecha_emision" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Vencimiento</label>
                        <input type="date" class="form-control" name="fecha_vencimiento" id="fecha_vencimiento">
                    </div>

                    <div class="form-group">
                        <label>Monto Total (Bs)</label>
                        <input type="number" step="0.01" class="form-control" name="monto_total" id="monto_total"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Monto Pagado (Bs)</label>
                        <input type="number" step="0.01" class="form-control" name="monto_pagado" id="monto_pagado"
                            value="0">
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" name="estado" id="estado">
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="PARCIAL">PARCIAL</option>
                            <option value="PAGADA">PAGADA</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Observación</label>
                        <textarea class="form-control" name="observacion" id="observacion" rows="3"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Cerrar
                    </button>
                    <button type="submit" name="GuardarFactura" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>