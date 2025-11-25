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
   public function CancelarFactura(){
    if(!isset($_POST['CancelarFactura'])) return;

    $db = $this->Conectar();

    $IdFactura = isset($_POST['Idfactura']) ? (int)$_POST['Idfactura'] : 0;
    $Comentario = isset($_POST['Comentario']) ? trim($_POST['Comentario']) : '';

    if($IdFactura <= 0){
        echo '
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ID de factura inválido.
        </div>';
        return;
    }

    // 1) Traer datos de la factura
    $FacturaSql = $db->query("
        SELECT *
        FROM factura
        WHERE id = '{$IdFactura}'
        LIMIT 1
    ");

    if($FacturaSql->num_rows == 0){
        echo '
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            Factura no encontrada.
        </div>';
        return;
    }

    $Factura = $FacturaSql->fetch_assoc();

    // 2) Si ya está cancelada, no hacer nada
    if((int)$Factura['habilitado'] === 0){
        echo '
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            La factura ya estaba cancelada.
        </div>';
        return;
    }

    $metodo_pago = $Factura['metodo_pago'];
    $id_banco    = !empty($Factura['id_banco']) ? (int)$Factura['id_banco'] : NULL;
    $total_caja  = isset($Factura['total_caja']) ? (float)$Factura['total_caja'] : (float)$Factura['total'];

    // 3) REVERSAR EN CAJA (solo si fue efectivo o parte en efectivo)
    //    Aquí asumimos que total_caja es el monto que entró a caja.
    if($total_caja > 0){
        $MaxIdCajaSql = $db->query("SELECT MAX(id) AS id FROM caja");
        $MaxIdCaja    = $MaxIdCajaSql->fetch_assoc();

        if($MaxIdCaja && $MaxIdCaja['id']){
            $db->query("
                UPDATE caja
                SET monto = monto - '{$total_caja}'
                WHERE id = '{$MaxIdCaja['id']}'
            ");
        }
    }

    // 4) REVERSAR EN BANCO (si aplica)
    if($metodo_pago !== 'EFECTIVO' && $id_banco){

        // Insertamos un movimiento de EGRESO ligado a la factura
        $db->query("
            INSERT INTO banco_movimientos (
                id_banco,
                fecha,
                tipo,
                monto,
                concepto,
                id_factura
            ) VALUES (
                {$id_banco},
                NOW(),
                'EGRESO',
                {$total_caja},
                'Reverso de venta factura #{$IdFactura}',
                {$IdFactura}
            )
        ");
    }

    // 5) Marcar FACTURA como cancelada
    $db->query("
        UPDATE factura
        SET habilitado = 0,
            comentario_cancelacion = ".$this->EscaparValor($db, $Comentario)."
        WHERE id = '{$IdFactura}'
    ");

    // 6) Marcar VENTAS como anuladas (si tiene campo habilitada)
    $db->query("
        UPDATE ventas
        SET habilitada = 0
        WHERE idfactura = '{$IdFactura}'
    ");

    // 7) Opcional: marcar DETALLE_VENTA como anulada si tiene campo estado
    //    Si tu tabla detalle_venta NO tiene campo estado, puedes omitir esto
    if($this->ColumnaExiste($db, 'detalle_venta', 'estado')){
        $db->query("
            UPDATE detalle_venta
            SET estado = 'ANULADA'
            WHERE idfactura = '{$IdFactura}'
        ");
    }

    echo '
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        La factura #'.$IdFactura.' ha sido cancelada correctamente.
    </div>';
}

/**
 * Escapar valor seguro para SQL simple
 */
private function EscaparValor($db, $valor){
    if($valor === '' || is_null($valor)){
        return "NULL";
    }
    return "'".$db->real_escape_string($valor)."'";
}

/**
 * Verifica si existe una columna en una tabla (pequeña ayuda)
 */
private function ColumnaExiste($db, $tabla, $columna){
    $res = $db->query("SHOW COLUMNS FROM {$tabla} LIKE '{$columna}'");
    return ($res && $res->num_rows > 0);
}

}