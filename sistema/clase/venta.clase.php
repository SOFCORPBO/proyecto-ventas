<?php

class Venta extends Conexion {

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR ÍTEM DEL CARRITO (cajatmp)
    |   - Antes también devolvía stock al producto
    |   - Ahora solo elimina el registro de cajatmp
    |--------------------------------------------------------------------------
    */
   public function EliminarProducto(){

    if(isset($_POST['EliminarProducto'])):
        // Variables
        $IdCajatmp  = filter_var($_POST['IdCajatmp'], FILTER_VALIDATE_INT);
        $IdProducto = filter_var($_POST['IdProducto'], FILTER_VALIDATE_INT);
        $Cantidad   = filter_var($_POST['CantidadStock'], FILTER_VALIDATE_INT);

        // Solo eliminamos de cajatmp. YA NO tocamos producto.stock
        $EliminarNumeroSql = $this->Conectar()->query("DELETE FROM `cajatmp` WHERE `id` = '{$IdCajatmp}'");

        // Mensaje De Comprobacion
        if($EliminarNumeroSql == true):
            echo'
            <div class="alert alert-dismissible alert-success">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>&iexcl;Bien hecho!</strong> Se ha eliminado el servicio con éxito.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'"/>';
        else:
            echo'
            <div class="alert alert-dismissible alert-danger">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>&iexcl;Lo Sentimos!</strong> Ha ocurrido un error al eliminar el servicio, intentalo de nuevo.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'"/>';
        endif;
    endif;
}

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR CANTIDAD DE UN ÍTEM EN cajatmp
    |   - Antes movía el stock en la tabla producto
    |   - Ahora solo recalcula cantidad y total en cajatmp
    |--------------------------------------------------------------------------
    */
   public function ActualizarCantidadCajaTmp(){
    // Actualiza la cantidad del servicio en el carrito de ventas
    if(isset($_POST['ActualizarCantidadCajaTmp'])):
        $IdCajaTmp       = filter_var($_POST['IdCajaTmp'], FILTER_VALIDATE_INT);
        $IdProducto      = filter_var($_POST['IdProducto'], FILTER_VALIDATE_INT);
        $Cantidad        = filter_var($_POST['Cantidad'], FILTER_VALIDATE_INT);
        $Precio          = filter_var($_POST['Precio'], FILTER_SANITIZE_STRING);
        $AntiguaCantidad = filter_var($_POST['CantidadAnterior'], FILTER_VALIDATE_INT);

        $PrecioTotal = $Precio * $Cantidad;

        // Ya NO tocamos producto.stock ni calculamos stockTmp
        $ActualizarProductoTmpQuery = $this->Conectar()->query("
            UPDATE `cajatmp` SET
                `cantidad`    = '{$Cantidad}',
                `totalprecio` = '{$PrecioTotal}'
            WHERE `id` = '{$IdCajaTmp}'
        ");

        if($ActualizarProductoTmpQuery == true):
            echo'
            <div class="alert alert-dismissible alert-success">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>&iexcl;Bien hecho!</strong> Se ha actualizado la cantidad del servicio con éxito.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'"/>';
        else:
            echo'
            <div class="alert alert-dismissible alert-danger">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>&iexcl;Lo Sentimos!</strong> Ha ocurrido un error al actualizar la cantidad del servicio, intentalo de nuevo.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'"/>';
        endif;
    endif;
}


    /*
    |--------------------------------------------------------------------------
    | LIMPIAR CARRITO (Eliminar varios ítems)
    |   - Antes también devolvía stock a producto
    |   - Ahora solo borra registros de cajatmp
    |--------------------------------------------------------------------------
    */
   public function LimpiarCarritoCompras(){
    //Eliminar Todo del carrito de compras o parte del mismo
    if(isset($_POST['EliminarTodo'])):
        $TotalEliminar = filter_var($_POST['contadorx'], FILTER_VALIDATE_INT);

        for($xrecibe = 1 ; $xrecibe<=$TotalEliminar; $xrecibe++):
            $IdEliminar = isset($_POST['IDS'.$xrecibe]) ? $_POST['IDS'.$xrecibe] : null;
            $IdProducto = filter_var($_POST['IdProducto'.$xrecibe], FILTER_VALIDATE_INT);
            $Cantidad   = filter_var($_POST['cantidad'.$xrecibe], FILTER_VALIDATE_INT);

            if($IdEliminar!=""):
                // Solo eliminamos; ya NO devolvemos stock
                $EliminarQuery = $this->Conectar()->query("DELETE FROM `cajatmp` WHERE `id` ='{$IdEliminar}'");

                if($EliminarQuery == true):
                    echo'
                    <div class="alert alert-dismissible alert-success">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong>&iexcl;Bien hecho!</strong> Se ha eliminado la venta actual con éxito.
                    </div>
                    <meta http-equiv="refresh" content="0;url='.URLBASE.'"/>';
                else:
                    echo '
                    <div class="alert alert-dismissible alert-danger">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong>&iexcl;Lo Sentimos!</strong> Ha ocurrido un error al eliminar la venta actual, intentalo de nuevo.
                    </div>
                    <meta http-equiv="refresh" content="0;url='.URLBASE.'"/>';
                endif;
            endif;
        endfor;
    endif;
}

    /*
    |--------------------------------------------------------------------------
    | CANCELAR FACTURA
    |   - Mantiene ajustes de caja y estado de factura/venta
    |   - Antes devolvía stock a productos, ahora NO (porque son servicios)
    |--------------------------------------------------------------------------
    */
    public function CancelarFactura()
    {
        if (isset($_POST['CancelarFactura'])) {

            $IdFactura  = filter_var($_POST['Idfactura'], FILTER_VALIDATE_INT);
            $TipoFactura= filter_var($_POST['tipo'], FILTER_VALIDATE_INT);
            $Comentario = filter_var($_POST['Comentario'], FILTER_SANITIZE_STRING);

            $ActulizarFactura = $this->Conectar()->query("
                UPDATE `factura`
                SET `habilitado` = '0'
                WHERE `id` = '{$IdFactura}'
            ");

            $ActulizarVenta = $this->Conectar()->query("
                UPDATE `ventas`
                SET `habilitada` = '0'
                WHERE `idfactura` = '{$IdFactura}'
            ");

            $fechaActual = FechaActual();
            $hora        = HoraActual();
            $Unix        = time();

            /* Debitando Dinero de la Caja */
            $MaxIdCajaQuery = $this->Conectar()->query("
                SELECT MAX(id) AS IdCaja FROM `caja`
            ");
            $MaxIdCaja      = $MaxIdCajaQuery->fetch_array();

            $FacturaTotalQuery = $this->Conectar()->query("
                SELECT total FROM `factura` WHERE id='{$IdFactura}'
            ");
            $FacturaTotalRow   = $FacturaTotalQuery->fetch_array();

            $ActualizandoCajaSql = $this->Conectar()->query("
                UPDATE `caja`
                SET `monto` = `monto` - '{$FacturaTotalRow['total']}'
                WHERE id = '{$MaxIdCaja['IdCaja']}'
            ");

            // Registrar cancelación
            $FacturaCancelada = $this->Conectar()->query("
                INSERT INTO `facturascanceladas`
                    (`id_factura`, `tipo`, `nota`, `fecha`, `hora`, `unix`)
                VALUES
                    ('{$IdFactura}', '{$TipoFactura}', '{$Comentario}', '{$fechaActual}', '{$hora}', '{$Unix}')
            ");

            // ⚠️ IMPORTANTE:
            // Antes aquí se devolvía stock a producto si $TipoFactura == 0.
            // Como ahora manejamos SERVICIOS, ya NO actualizamos inventario.

            if ($FacturaCancelada && $ActulizarFactura && $ActulizarVenta && $ActualizandoCajaSql) {
                echo '
                <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Bien hecho!</strong> La factura ha sido cancelada con &eacute;xito.
                </div>
                <meta http-equiv="refresh" content="2;url=' . URLBASE . 'ventas-totales-vendedor"/>';
            } else {
                echo '
                <div class="alert alert-dismissible alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Lo sentimos!</strong> Ocurri&oacute; un error al cancelar la factura, int&eacute;ntalo de nuevo.
                </div>
                <meta http-equiv="refresh" content="2;url=' . URLBASE . 'ventas-totales-vendedor"/>';
            }
        }
    }
}