<?php
<<<<<<< HEAD
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
=======
// Requiere que $ProveedoresSQL y $FacturasPendientesSQL estén definidos en proveedor-pagos.php
?>
<div class="modal fade" id="ModalPagoProveedor" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">

        <form method="post" id="FormPagoProveedor">
            <input type="hidden" name="id_pago" id="id_pago">

            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header" style="background:#0275d8; color:white;">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa fa-money"></i> Registrar / Editar Pago de Proveedor
>>>>>>> 80e5b70 (modulos factura y contabilidad)
                    </h4>
                </div>

                <!-- BODY -->
                <div class="modal-body">

<<<<<<< HEAD
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
=======
                    <div class="row">

                        <!-- PROVEEDOR -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Proveedor</strong></label>
                                <select name="id_proveedor" id="id_proveedor_pago" class="form-control" required>
                                    <option value="">Seleccione proveedor...</option>

                                    <?php
                                    $ProveedoresSQL->data_seek(0);
                                    while ($p = $ProveedoresSQL->fetch_assoc()):
                                    ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= $p['nombre'] ?> (<?= $p['tipo_proveedor'] ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- FACTURAS PENDIENTES -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Factura (opcional)</strong></label>
                                <select name="id_factura" id="id_factura_pago" class="form-control">
                                    <option value="">— Sin factura asociada —</option>

                                    <?php
                                    $FacturasPendientesSQL->data_seek(0);
                                    while ($f = $FacturasPendientesSQL->fetch_assoc()):
                                    ?>
                                    <option value="<?= $f['id'] ?>">
                                        <?= $f['numero_factura'] ?> — <?= $f['proveedor_nombre'] ?> —
                                        Bs <?= number_format($f['monto_total'],2) ?>
                                    </option>
                                    <?php endwhile; ?>

                                </select>
                            </div>
                        </div>

                    </div>


                    <div class="row">

                        <!-- FECHA -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Fecha de Pago</strong></label>
                                <input type="date" name="fecha_pago" id="fecha_pago" class="form-control"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- MONTO -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Monto (Bs)</strong></label>
                                <input type="number" step="0.01" min="0" name="monto" id="monto_pago"
                                    class="form-control" required>
                            </div>
                        </div>

                        <!-- MÉTODO PAGO -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Método de Pago</strong></label>
                                <select name="metodo_pago" id="metodo_pago" class="form-control" required>
                                    <option value="EFECTIVO">Efectivo</option>
                                    <option value="TRANSFERENCIA">Transferencia</option>
                                    <option value="DEPOSITO">Depósito</option>
                                    <option value="TARJETA">Tarjeta</option>
                                </select>
                            </div>
                        </div>

                    </div>


                    <div class="row">

                        <!-- BANCO -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Banco (si aplica)</strong></label>
                                <select name="id_banco" id="id_banco_pago" class="form-control">
                                    <option value="">— Ninguno —</option>

                                    <?php
                                    $Bancos = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");
                                    while ($b = $Bancos->fetch_assoc()):
                                    ?>
                                    <option value="<?= $b['id'] ?>"><?= $b['nombre'] ?></option>
                                    <?php endwhile; ?>

                                </select>
                            </div>
                        </div>

                        <!-- REFERENCIA -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Referencia</strong> (opcional)</label>
                                <input type="text" name="referencia" id="referencia_pago" class="form-control"
                                    placeholder="Ej: N° de operación">
                            </div>
                        </div>

                    </div>


                    <!-- OBSERVACIONES -->
                    <div class="form-group">
                        <label><strong>Observaciones</strong></label>
                        <textarea name="observaciones" id="observaciones_pago" class="form-control" rows="3"
                            placeholder="Notas adicionales..."></textarea>
                    </div>
>>>>>>> 80e5b70 (modulos factura y contabilidad)

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">

                    <button type="button" class="btn btn-default" data-dismiss="modal">
<<<<<<< HEAD
                        <i class="fa fa-times"></i> Cancelar
                    </button>

                    <button type="submit" name="GuardarPagoProveedor" class="btn btn-success">
                        <i class="fa fa-check"></i> Registrar Pago
=======
                        <i class="fa fa-close"></i> Cerrar
                    </button>

                    <button type="submit" name="GuardarPagoProveedor" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar Pago
>>>>>>> 80e5b70 (modulos factura y contabilidad)
                    </button>

                </div>

            </div>
        </form>

    </div>
</div>
