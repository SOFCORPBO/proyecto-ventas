<?php
// Usa $ProveedoresSQL que viene desde proveedor-facturas.php
?>
<div class="modal fade" id="ModalFacturaProveedor" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <form method="post" id="FormFacturaProveedor">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa fa-file-text-o"></i> Factura de Proveedor
                    </h4>
                </div>

                <div class="modal-body">

                    <!-- ID oculto para editar -->
                    <input type="hidden" name="id_factura" id="id_factura">

                    <div class="form-group">
                        <label>Proveedor</label>
                        <select name="id_proveedor" id="id_proveedor_factura" class="form-control" required>
                            <option value="">Seleccione proveedor...</option>
                            <?php
                            $ProveedoresSQL->data_seek(0);
                            while($p = $ProveedoresSQL->fetch_assoc()):
                            ?>
                            <option value="<?= $p['id'] ?>">
                                <?= $p['nombre'] ?> (<?= $p['tipo_proveedor'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Número de factura</label>
                        <input type="text" name="numero_factura" id="numero_factura" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Fecha de emisión</label>
                                <input type="date" name="fecha_emision" id="fecha_emision" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Fecha de vencimiento</label>
                                <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Monto total</label>
                        <div class="input-group">
                            <span class="input-group-addon">Bs</span>
                            <input type="number" step="0.01" min="0" name="monto_total" id="monto_total"
                                class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" id="observaciones_factura" class="form-control"
                            rows="3"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>

                    <!-- Coincide con proveedor-facturas.php -->
                    <button type="submit" name="GuardarFacturaProveedor" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>