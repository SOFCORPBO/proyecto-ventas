<!-- Modal de Facturación -->
<div class="modal fade" id="ModalFacturarVenta" tabindex="-1" role="dialog" aria-labelledby="ModalFacturarVentaLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" id="FormFacturarVenta">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="ModalFacturarVentaLabel">
                        <i class="fa fa-file-text-o"></i> Facturar Venta
                    </h4>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id_venta" id="id_venta_factura">

                    <!-- Datos del cliente -->
                    <div class="form-group">
                        <label>Cliente</label>
                        <input type="text" class="form-control" id="cliente_factura" disabled>
                    </div>

                    <!-- NIT / Razón Social -->
                    <div class="form-group">
                        <label>NIT</label>
                        <input type="text" name="nit" id="nit_factura" class="form-control"
                            placeholder="NIT del cliente" required>
                    </div>

                    <div class="form-group">
                        <label>Razón Social</label>
                        <input type="text" name="razon_social" id="razon_social_factura" class="form-control"
                            placeholder="Razón Social del cliente" required>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Número de Comprobante</label>
                                <input type="text" name="nro_comprobante" id="nro_comprobante_factura"
                                    class="form-control" placeholder="Número de comprobante" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Fecha de Emisión</label>
                                <input type="date" name="fecha_emision" id="fecha_emision_factura" class="form-control"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Calcular impuestos -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>IVA (13%)</label>
                                <input type="number" name="iva_monto" id="iva_monto_factura" class="form-control"
                                    step="0.01" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Impuesto IT (3%)</label>
                                <input type="number" name="impuesto_monto" id="impuesto_monto_factura"
                                    class="form-control" step="0.01" min="0" value="0" required>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" id="observaciones_factura" class="form-control" rows="3"
                            placeholder="Detalles adicionales de la factura"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="submit" name="FacturarVenta" class="btn btn-success">
                        <i class="fa fa-check"></i> Marcar como Facturada
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Script de validación y carga de datos -->
<script>
// Abrir modal para facturar venta
function FacturarVenta(venta) {
    document.getElementById('id_venta_factura').value = venta.id;
    document.getElementById('cliente_factura').value = venta.cliente_nombre;

    // Rellenar campos de la venta
    document.getElementById('nit_factura').value = venta.nit || '';
    document.getElementById('razon_social_factura').value = venta.razon_social || '';
    document.getElementById('nro_comprobante_factura').value = venta.nro_comprobante || '';
    document.getElementById('fecha_emision_factura').value = venta.fecha_emision || '<?= date('Y-m-d') ?>';

    // Calcula impuestos automáticamente si no están en la venta
    if (venta.iva_monto === 0) {
        document.getElementById('iva_monto_factura').value = (venta.totalprecio * 0.13).toFixed(2);
    } else {
        document.getElementById('iva_monto_factura').value = venta.iva_monto;
    }

    if (venta.impuesto_monto === 0) {
        document.getElementById('impuesto_monto_factura').value = (venta.totalprecio * 0.03).toFixed(2);
    } else {
        document.getElementById('impuesto_monto_factura').value = venta.impuesto_monto;
    }

    $('#ModalFacturarVenta').modal('show');
}
</script>