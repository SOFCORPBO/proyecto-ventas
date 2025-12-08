<?php
// Se asume que $ProveedoresSQL y $FacturasPendientesSQL están disponibles desde proveedor-pagos.php
?>

<style>
    /* Modal estilo corporativo */
    .modal-header {
        background: #3f51b5;
        color: white;
        border-bottom: 0;
    }
    .modal-title i { margin-right: 6px; }

    .form-section-title {
        font-weight: bold;
        font-size: 13px;
        margin-top: 15px;
        color: #555;
    }

    .input-group-addon {
        background: #eee;
        font-weight: bold;
    }

    .badge-info {
        background: #3f51b5;
    }
</style>

<div class="modal fade" id="ModalPagoProveedor" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <form method="post" id="FormPagoProveedor" autocomplete="off">
            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa fa-money"></i> Registrar Pago a Proveedor
                    </h4>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <!-- Proveedor -->
                    <label class="form-section-title">Datos del Proveedor</label>

                    <div class="form-group">
                        <label>Proveedor</label>
                        <select name="id_proveedor" id="id_proveedor_pago" class="form-control" required>
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

                    <!-- Factura opcional -->
                    <label class="form-section-title">Factura Asociada (opcional)</label>

                    <div class="form-group">
                        <label>Factura Pendiente</label>
                        <select name="id_factura" id="id_factura_pago" class="form-control">
                            <option value="">-- Sin asociar --</option>
                            <?php
                            $FacturasPendientesSQL->data_seek(0);
                            while($f = $FacturasPendientesSQL->fetch_assoc()):
                                $saldo = $f['monto_total'] - $f['monto_pagado'];
                            ?>
                                <option value="<?= $f['id'] ?>">
                                    <?= $f['proveedor_nombre'] ?> | Fact. #<?= $f['numero_factura'] ?>
                                    — <strong>Saldo: <?= number_format($saldo,2) ?> Bs</strong>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted">Si no se selecciona, el pago se registrará solo como egreso general del proveedor.</small>
                    </div>

                    <!-- Detalles del pago -->
                    <label class="form-section-title">Detalles del Pago</label>

                    <div class="row">
                        <div class="col-sm-4">
                            <label>Fecha de Pago</label>
                            <input type="date" name="fecha_pago" class="form-control"
                                value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-sm-4">
                            <label>Monto (Bs)</label>
                            <div class="input-group">
                                <span class="input-group-addon">Bs</span>
                                <input type="number" name="monto" step="0.01" min="0" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <label>Método de Pago</label>
                            <select name="metodo_pago" class="form-control" required>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="DEPOSITO">Depósito</option>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TARJETA">Tarjeta</option>
                            </select>
                        </div>
                    </div>

                    <!-- Opciones bancarias -->
                    <label class="form-section-title">Datos de Referencia Bancaria</label>

                    <div class="row">
                        <div class="col-sm-6">
                            <label>Banco (si aplica)</label>
                            <select name="id_banco" class="form-control">
                                <option value="">-- Seleccione --</option>
                                <?php
                                $Bancos = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");
                                while($bn = $Bancos->fetch_assoc()):
                                ?>
                                    <option value="<?= $bn['id'] ?>"><?= $bn['nombre'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-sm-6">
                            <label>Referencia / Nº Operación</label>
                            <input type="text" name="referencia" class="form-control" placeholder="Ej: Nro de voucher, transacción bancaria">
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <label class="form-section-title">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"
                        placeholder="Notas adicionales sobre este pago..."></textarea>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>

                    <button type="submit" name="GuardarPagoProveedor" class="btn btn-success">
                        <i class="fa fa-check"></i> Registrar Pago
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>
