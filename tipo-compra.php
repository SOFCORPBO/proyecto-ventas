<?php
// Resumen de la venta actual para el vendedor logueado
$TotalNetoSql = $db->SQL("
    SELECT SUM(totalprecio) AS total
    FROM cajatmp
    WHERE vendedor = '{$usuarioApp['id']}'
");
$TotalNeto = $TotalNetoSql->fetch_assoc();
$total_general = isset($TotalNeto['total']) ? floatval($TotalNeto['total']) : 0;
?>

<div class="row">
    <div class="col-md-6 col-md-offset-3">

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Resumen de Venta</h4>
            </div>
            <div class="panel-body">

                <p><strong>Total servicios:</strong>
                    <?php echo $Vendedor->Formato($total_general); ?>
                </p>

                <hr>

                <!-- FORMULARIO DE COBRO -->
                <form method="post" action="registrar-compra.php" onsubmit="return ComprobarVenta();">

                    <!-- Tipo de comprobante -->
                    <div class="form-group">
                        <label for="tipo_comprobante">Tipo de comprobante</label>
                        <select name="tipo_comprobante" id="tipo_comprobante" class="form-control">
                            <option value="RECIBO">Recibo simple (sin factura)</option>
                            <option value="FACTURA">Factura</option>
                        </select>
                    </div>

                    <!-- Con factura (0/1, se actualiza con JS) -->
                    <input type="hidden" name="con_factura" id="con_factura" value="0">

                    <!-- Datos de facturación (solo si es FACTURA) -->
                    <div id="datos_factura" style="display:none;">
                        <div class="form-group">
                            <label for="nit_cliente">NIT / CI</label>
                            <input type="text" name="nit_cliente" id="nit_cliente" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="razon_social">Razón Social</label>
                            <input type="text" name="razon_social" id="razon_social" class="form-control">
                        </div>
                    </div>

                    <hr>

                    <!-- Método de pago -->
                    <div class="form-group">
                        <label for="metodo_pago">Método de pago</label>
                        <select name="metodo_pago" id="metodo_pago" class="form-control">
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                            <option value="TARJETA">Tarjeta</option>
                            <option value="DEPOSITO">Depósito</option>
                        </select>
                    </div>

                    <!-- Banco / referencia (solo cuando NO es efectivo) -->
                    <div id="grupo_banco" style="display:none;">

                        <div class="form-group">
                            <label for="id_banco">Banco / Cuenta</label>
                            <select name="id_banco" id="id_banco" class="form-control">
                                <option value="">Seleccione banco</option>
                                <?php
                                $resBancos = $db->SQL("SELECT id, nombre, numero_cuenta FROM bancos ORDER BY nombre");
                                while ($b = $resBancos->fetch_assoc()) {
                                    echo '<option value="'.$b['id'].'">'.$b['nombre'].' - '.$b['numero_cuenta'].'</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="referencia">N° de referencia (voucher / operación)</label>
                            <input type="text" name="referencia" id="referencia" class="form-control">
                        </div>

                    </div>

                    <hr>

                    <div class="form-group text-center">
                        <button type="submit" name="RegistrarCompra" id="btsubmit" class="btn btn-success btn-lg"
                            <?php if($total_general <= 0) echo 'disabled'; ?>>
                            <i class="fa fa-check"></i> Registrar Venta
                        </button>
                    </div>

                </form>
                <!-- FIN FORMULARIO DE COBRO -->

            </div>
        </div>

    </div>
</div>

<!-- Script específico del formulario de cobro -->
<script>
// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {

    var selTipoComp = document.getElementById('tipo_comprobante');
    var divDatosFact = document.getElementById('datos_factura');
    var inputConFact = document.getElementById('con_factura');

    var selMetodoPago = document.getElementById('metodo_pago');
    var divBanco = document.getElementById('grupo_banco');

    function actualizarTipoComprobante() {
        if (!selTipoComp) return;
        var esFactura = selTipoComp.value === 'FACTURA';
        if (divDatosFact) {
            divDatosFact.style.display = esFactura ? 'block' : 'none';
        }
        if (inputConFact) {
            inputConFact.value = esFactura ? '1' : '0';
        }
    }

    function actualizarMetodoPago() {
        if (!selMetodoPago || !divBanco) return;
        var esEfectivo = selMetodoPago.value === 'EFECTIVO';
        divBanco.style.display = esEfectivo ? 'none' : 'block';
    }

    if (selTipoComp) {
        selTipoComp.addEventListener('change', actualizarTipoComprobante);
        actualizarTipoComprobante();
    }
    if (selMetodoPago) {
        selMetodoPago.addEventListener('change', actualizarMetodoPago);
        actualizarMetodoPago();
    }
});
</script>