<!-- =======================================================
      MODAL CREAR / EDITAR TRÁMITE
======================================================= -->
<div class="modal fade" id="ModalTramite" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST">

                <input type="hidden" name="GuardarTramite" value="1">
                <input type="hidden" name="id_tramite" id="frm_id_tramite">

                <div class="modal-header">
                    <h4 class="modal-title">Gestión de Trámite</h4>
                    <button type="button" class="close" data-dismiss="modal">×</button>
                </div>

                <div class="modal-body">

                    <!-- =====================================
                FILA 1 - CLIENTE + TIPO TRÁMITE
        ====================================== -->
                    <div class="row">

                        <div class="col-md-6">
                            <label><b>Cliente</b></label>
                            <select name="id_cliente" id="frm_id_cliente" class="form-control" required>
                                <option value="">Seleccione cliente</option>
                                <?php
                    $ListaClientesModal = $Tram->Clientes();
                    while($cli = $ListaClientesModal->fetch_assoc()):
                    ?>
                                <option value="<?= $cli['id'] ?>"><?= $cli['nombre'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label><b>Tipo de Trámite</b></label>
                            <select name="tipo_tramite" id="frm_tipo_tramite" class="form-control" required>
                                <option value="">Seleccione</option>
                                <option value="VISA">Visa</option>
                                <option value="RESIDENCIA">Residencia</option>
                                <option value="PASAPORTE">Pasaporte</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>

                    </div>

                    <br>

                    <!-- =====================================
                FILA 2 - PAÍS + FECHAS
        ====================================== -->
                    <div class="row">

                        <div class="col-md-4">
                            <label><b>País destino</b></label>
                            <input type="text" name="pais_destino" id="frm_pais_destino" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label><b>Fecha Inicio</b></label>
                            <input type="date" name="fecha_inicio" id="frm_fecha_inicio" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label><b>Fecha Entrega</b></label>
                            <input type="date" name="fecha_entrega" id="frm_fecha_entrega" class="form-control">
                        </div>

                    </div>

                    <br>

                    <!-- =====================================
                FILA 3 - VENCIMIENTO + ESTADO + MONTO
        ====================================== -->
                    <div class="row">

                        <div class="col-md-4">
                            <label><b>Fecha Vencimiento</b></label>
                            <input type="date" name="fecha_vencimiento" id="frm_fecha_vencimiento" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label><b>Estado</b></label>
                            <select name="estado" id="frm_estado" class="form-control">
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="EN_PROCESO">En proceso</option>
                                <option value="FINALIZADO">Finalizado</option>
                                <option value="RECHAZADO">Rechazado</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label><b>Monto Estimado</b></label>
                            <input type="number" step="0.01" name="monto_estimado" id="frm_monto_estimado"
                                class="form-control" placeholder="0.00">
                        </div>

                    </div>

                    <br>

                    <!-- =====================================
                FILA 4 - OBSERVACIONES
        ====================================== -->
                    <div class="row">
                        <div class="col-md-12">
                            <label><b>Observaciones</b></label>
                            <textarea name="observaciones" id="frm_observaciones" class="form-control"
                                rows="3"></textarea>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-success" type="submit">
                        <i class="fa fa-save"></i> Guardar Trámite
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>

            </form>

        </div>
    </div>
</div>