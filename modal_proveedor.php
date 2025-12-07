<div class="modal fade" id="ModalProveedor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="post" id="FormProveedor">

                <div class="modal-header">
                    <h4 class="modal-title">
                        <i class="fa fa-truck"></i> Proveedor
                    </h4>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="id" id="id">

                    <div class="form-group">
                        <label>Nombre del Proveedor</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" required>
                    </div>

                    <div class="form-group">
                        <label>Contacto</label>
                        <input type="text" class="form-control" name="contacto" id="contacto">
                    </div>

                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" class="form-control" name="telefono" id="telefono">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="email">
                    </div>

                    <div class="form-group">
                        <label>Dirección</label>
                        <textarea class="form-control" name="direccion" id="direccion"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Tipo de Proveedor</label>
                        <select class="form-control" name="tipo_proveedor" id="tipo_proveedor">
                            <option value="AEROLINEA">Aerolínea</option>
                            <option value="HOTEL">Hotel</option>
                            <option value="ASEGURADORA">Aseguradora</option>
                            <option value="CONSULADO">Consulado</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="habilitado" id="habilitado" checked> Proveedor Activo
                        </label>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-default">Cancelar</button>
                    <button type="submit" name="CrearProveedor" class="btn btn-primary" id="btnGuardar">
                        Guardar
                    </button>
                    <button type="submit" name="EditarProveedor" class="btn btn-success" id="btnActualizar"
                        style="display:none;">
                        Actualizar
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
$("#ModalProveedor").on("show.bs.modal", function() {
    let id = $("#id").val();
    if (id === "" || id === null) {
        $("#btnGuardar").show();
        $("#btnActualizar").hide();
    } else {
        $("#btnGuardar").hide();
        $("#btnActualizar").show();
    }
});
</script>