<?php
/* ============================================================
   ARCHIVO: modales-facturacion.php
   SISTEMA: Punto de Venta / Agencia de Viajes
   MÓDULO: Facturación y Contabilidad
============================================================ */
?>

<!-- ============================================================
     MODAL PARA FACTURAR UNA VENTA
============================================================ -->
<div class="modal fade" id="modalFacturacion" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md">

        <div class="modal-content">

            <div class="modal-header bg-primary" style="color:#fff;">
                <button class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-file-text-o"></i> Procesar Facturación
                </h4>
            </div>

            <form id="formFacturar" method="POST">

                <div class="modal-body">

                    <input type="hidden" name="accion" value="crearFactura">
                    <input type="hidden" name="id_venta" id="fact_id_venta">
                    <input type="hidden" name="subtotal" id="fact_subtotal">

                    <div class="alert alert-info">
                        <strong>Venta Seleccionada</strong><br>
                        <span id="fact_mensaje"></span>
                    </div>

                    <div class="form-group">
                        <label>Subtotal (Bs)</label>
                        <input type="text" class="form-control" id="fact_input_subtotal" disabled>
                    </div>

                    <div class="form-group">
                        <label>IVA (13%)</label>
                        <input type="text" class="form-control" id="fact_input_iva" disabled>
                    </div>

                    <div class="form-group">
                        <label>IT (3%)</label>
                        <input type="text" class="form-control" id="fact_input_it" disabled>
                    </div>

                    <div class="form-group">
                        <label>Total Final (Bs)</label>
                        <input type="text" class="form-control" id="fact_input_total" disabled>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">
                        <i class="fa fa-check"></i> Confirmar Facturación
                    </button>
                    <button class="btn btn-default" data-dismiss="modal">
                        Cancelar
                    </button>
                </div>

            </form>

        </div>

    </div>
</div>


<!-- ============================================================
     MODAL PARA VER FACTURA EXISTENTE
============================================================ -->
<div class="modal fade" id="modalVerFactura" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md">

        <div class="modal-content">

            <div class="modal-header bg-info" style="color:#fff;">
                <button class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-eye"></i> Información de la Factura
                </h4>
            </div>

            <div class="modal-body" id="contenidoFactura">
                <!-- Se carga dinámicamente desde facturacion.js -->
            </div>

            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>

        </div>

    </div>
</div>


<!-- ============================================================
     MODAL PARA REPORTES
============================================================ -->
<div class="modal fade" id="modalReportes" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header bg-warning" style="color:#fff;">
                <button class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-bar-chart"></i> Reportes de Facturación
                </h4>
            </div>

            <div class="modal-body">

                <button class="btn btn-success" onclick="reporteFacturadas()">
                    <i class="fa fa-check"></i> Ventas Facturadas
                </button>

                <button class="btn btn-danger" onclick="reporteSinFactura()">
                    <i class="fa fa-close"></i> Ventas Sin Factura
                </button>

                <button class="btn btn-warning" onclick="reporteImpuestos()">
                    <i class="fa fa-percent"></i> Impuestos (IVA / IT)
                </button>

                <hr>

                <div id="contenedorReportes">
                    <div class="alert alert-info">
                        Seleccione una categoría de reporte para visualizar información detallada.
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>

        </div>

    </div>
</div>