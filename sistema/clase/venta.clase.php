<?php

class Venta extends Conexion
{

 // Agregar producto o servicio al carrito (cajatmp)
    public function agregarAlCarrito($idProducto, $cantidad, $idCliente) {
        $db = $this->Conectar();

        // Verificar si el producto/servicio existe
        $ProductoSql = $db->query("SELECT id, precioventa, comision FROM producto WHERE id = {$idProducto} LIMIT 1");
        if ($ProductoSql->num_rows == 0) {
            return false;
        }

        $Producto = $ProductoSql->fetch_assoc();
        $precioUnitario = (float)$Producto['precioventa'];
        $comisionUnidad = (float)$Producto['comision'];
        $totalPrecio = $precioUnitario * $cantidad;
        $totalComision = $comisionUnidad * $cantidad;

        // Agregar al carrito temporal
        $sql = "INSERT INTO cajatmp (idfactura, producto, cantidad, precio, totalprecio, comision, vendedor, cliente, fecha, hora) 
                VALUES (NULL, {$idProducto}, {$cantidad}, {$precioUnitario}, {$totalPrecio}, {$totalComision}, 1, {$idCliente}, NOW(), NOW())";
        return $db->query($sql);
    }

    // Registrar venta completa (factura + ventas)
    public function registrarVenta($idCliente, $metodoPago, $iva, $it) {
    $db = $this->Conectar();

    global $usuarioApp;
    $idVendedor = (int)$usuarioApp['id'];
    $usuarioNombre = $usuarioApp['usuario'];

    // Obtener carrito FILTRADO por vendedor y cliente
    $CarritoSql = $db->query("
        SELECT c.*, p.comision AS comision_unidad 
        FROM cajatmp c
        LEFT JOIN producto p ON p.id = c.producto 
        WHERE c.vendedor = {$idVendedor} AND c.cliente = {$idCliente}
    ");

    if ($CarritoSql->num_rows == 0) {
        echo '<div class="alert alert-danger">Carrito vacío.</div>';
        return false;
    }

    $subtotal = 0;
    $totalComision = 0;
    $items = [];

    while ($row = $CarritoSql->fetch_assoc()) {
        $subtotal += $row['totalprecio'];
        $totalComision += ($row['comision_unidad'] * $row['cantidad']);
        $items[] = $row;
    }

    $montoIva = $subtotal * ($iva / 100);
    $montoIt  = $subtotal * ($it / 100);
    $totalFactura = $subtotal + $montoIva + $montoIt;

    // Registrar factura con cliente correcto
    $sqlFactura = "
        INSERT INTO factura (
            subtotal, iva, it, total, total_comision, fecha, hora,
            cliente, usuario, metodo_pago
        ) VALUES (
            {$subtotal}, {$montoIva}, {$montoIt}, {$totalFactura},
            {$totalComision}, NOW(), NOW(),
            {$idCliente}, '{$usuarioNombre}', '{$metodoPago}'
        )";

    if (!$db->query($sqlFactura)) {
        echo '<div class="alert alert-danger">Error al registrar factura.</div>';
        return false;
    }

    $idFactura = $db->insert_id;

    // Registrar detalles
    foreach ($items as $it) {
        $db->query("
            INSERT INTO ventas (idfactura, producto, cantidad, precio, totalprecio, comision, cliente, vendedor, fecha, hora)
            VALUES (
                {$idFactura},
                {$it['producto']},
                {$it['cantidad']},
                {$it['precio']},
                {$it['totalprecio']},
                {$it['comision']},
                {$idCliente},
                {$idVendedor},
                NOW(),
                NOW()
            )
        ");
    }

    // Limpiar carrito
    $db->query("DELETE FROM cajatmp WHERE vendedor={$idVendedor} AND cliente={$idCliente}");

    return true;
}


    /*
    |--------------------------------------------------------------------------
    | 1) ELIMINAR ÍTEM DEL CARRITO (cajatmp)
    |--------------------------------------------------------------------------
    */
    public function EliminarProducto()
    {
        if (isset($_POST['EliminarProducto'])):

            $IdCajatmp  = filter_var($_POST['IdCajatmp'], FILTER_VALIDATE_INT);

            $EliminarNumeroSql = $this->Conectar()->query("
                DELETE FROM `cajatmp`
                WHERE `id` = '{$IdCajatmp}'
            ");

            if ($EliminarNumeroSql):
                echo '
                <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>¡Bien hecho!</strong> Se eliminó el servicio correctamente.
                </div>
                <meta http-equiv="refresh" content="0;url=' . URLBASE . '"/>';
            else:
                echo '
                <div class="alert alert-dismissible alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Error:</strong> No se pudo eliminar el servicio.
                </div>
                <meta http-equiv="refresh" content="0;url=' . URLBASE . '"/>';
            endif;

        endif;
    }


    /*
    |--------------------------------------------------------------------------
    | 2) ACTUALIZAR CANTIDAD (cajatmp)
    |--------------------------------------------------------------------------
    */
    public function ActualizarCantidadCajaTmp()
    {
        if (isset($_POST['ActualizarCantidadCajaTmp'])):

            $IdCajaTmp       = filter_var($_POST['IdCajaTmp'], FILTER_VALIDATE_INT);
            $Cantidad        = filter_var($_POST['Cantidad'], FILTER_VALIDATE_INT);
            $Precio          = filter_var($_POST['Precio'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            $PrecioTotal = $Precio * $Cantidad;

            $Actualizar = $this->Conectar()->query("
                UPDATE `cajatmp`
                SET cantidad = '{$Cantidad}',
                    totalprecio = '{$PrecioTotal}'
                WHERE id = '{$IdCajaTmp}'
            ");

            if ($Actualizar):
                echo '
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    Cantidad actualizada correctamente.
                </div>
                <meta http-equiv="refresh" content="0;url=' . URLBASE . '"/>';
            else:
                echo '
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    Error al actualizar la cantidad.
                </div>
                <meta http-equiv="refresh" content="0;url=' . URLBASE . '"/>';
            endif;

        endif;
    }


    /*
    |--------------------------------------------------------------------------
    | 3) LIMPIAR CARRITO COMPLETO (cajatmp)
    |--------------------------------------------------------------------------
    */
    public function LimpiarCarritoCompras()
    {
        if (isset($_POST['EliminarTodo'])):

            $TotalEliminar = filter_var($_POST['contadorx'], FILTER_VALIDATE_INT);

            for ($i = 1; $i <= $TotalEliminar; $i++):

                $IdEliminar = $_POST['IDS' . $i] ?? null;

                if ($IdEliminar != ""):
                    $Eliminar = $this->Conectar()->query("
                        DELETE FROM cajatmp
                        WHERE id = '{$IdEliminar}'
                    ");

                    if ($Eliminar):
                        echo '
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            Se eliminó correctamente.
                        </div>
                        <meta http-equiv="refresh" content="0;url=' . URLBASE . '"/>';
                    else:
                        echo '
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            Error al eliminar.
                        </div>
                        <meta http-equiv="refresh" content="0;url=' . URLBASE . '"/>';
                    endif;

                endif;

            endfor;

        endif;
    }


    /*
    |--------------------------------------------------------------------------
    | 4) AGREGAR SERVICIO AL CARRITO
    |--------------------------------------------------------------------------
    */
    public function AgregarProductoCarrito()
    {
        if (!isset($_POST['agregar_servicio'])) return;

        $db = $this->Conectar();

        $idProducto = (int)$_POST['codigo'];
        $cantidad   = (int)$_POST['cantidad'];
        $idCliente  = (int)$_POST['cliente'];

        global $usuarioApp;
        $idVendedor = (int)$usuarioApp['id'];

        if ($idProducto <= 0 || $cantidad <= 0):
            echo '<div class="alert alert-danger">Datos inválidos.</div>';
            return;
        endif;

        $ProductoSql = $db->query("
            SELECT id, precioventa, comision
            FROM producto
            WHERE id = {$idProducto}
            LIMIT 1
        ");

        if ($ProductoSql->num_rows == 0):
            echo '<div class="alert alert-danger">Servicio no encontrado.</div>';
            return;
        endif;

        $P = $ProductoSql->fetch_assoc();

        $precio = (float)$P['precioventa'];
        $comisionUnidad = (float)$P['comision'];
        $total = $precio * $cantidad;
        $totalComision = $comisionUnidad * $cantidad;

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        $Insert = $db->query("
            INSERT INTO cajatmp (
                idfactura, producto, cantidad, precio, totalprecio, comision,
                vendedor, cliente, stockTmp, stock, fecha, hora
            ) VALUES (
                NULL, {$idProducto}, {$cantidad}, {$precio},
                {$total}, {$totalComision},
                {$idVendedor}, {$idCliente},
                0, 0,
                '{$fecha}', '{$hora}'
            )
        ");

        if ($Insert):
            echo '<div class="alert alert-success">Servicio agregado al carrito.</div>';
        else:
            echo '<div class="alert alert-danger">Error al agregar servicio.</div>';
        endif;
    }


    /*
    |--------------------------------------------------------------------------
    | 5) REGISTRAR VENTA COMPLETA (FACTURA + VENTAS)
    |--------------------------------------------------------------------------
    */
    public function RegistrarVenta1()
    {
        if (!isset($_POST['RegistrarVenta'])) return;

        $db = $this->Conectar();

        global $usuarioApp;
        $idVendedor = (int)$usuarioApp['id'];
        $usuarioNombre = $usuarioApp['usuario'];

        $idCliente = (int)$_POST['cliente'];
        $nit       = $db->real_escape_string($_POST['nit']);
        $razon     = $db->real_escape_string($_POST['razon_social']);

        $con_factura      = (int)$_POST['con_factura'];
        $tipo_comprobante = $db->real_escape_string($_POST['tipo_comprobante']);

        $metodo_pago  = $db->real_escape_string($_POST['metodo_pago']);
        $id_banco     = $_POST['id_banco'] !== "" ? (int)$_POST['id_banco'] : "NULL";
        $referencia   = $db->real_escape_string($_POST['referencia_pago']);
        $nro_comp     = $db->real_escape_string($_POST['nro_comprobante']);

        $iva_porcentaje = (float)$_POST['iva_porcentaje'];
        $it_porcentaje  = (float)$_POST['it_porcentaje'];

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        // -------------------------
        // Obtener el carrito actual
        // -------------------------
        $Carrito = $db->query("
            SELECT c.*, p.comision AS comision_unidad
            FROM cajatmp c
            LEFT JOIN producto p ON p.id = c.producto
            WHERE c.vendedor = {$idVendedor}
        ");

        if ($Carrito->num_rows == 0):
            echo '<div class="alert alert-danger">Carrito vacío.</div>';
            return;
        endif;

        $subtotal = 0;
        $totalComision = 0;
        $items = [];

        while ($row = $Carrito->fetch_assoc()):
            $subtotal += $row['totalprecio'];

            $comLinea = $row['comision_unidad'] * $row['cantidad'];
            $totalComision += $comLinea;

            $items[] = $row;
        endwhile;

        $montoIva = $subtotal * ($iva_porcentaje / 100);
        $montoIt  = $subtotal * ($it_porcentaje / 100);
        $totalFactura = $subtotal + $montoIva + $montoIt;
        $totalCaja = $totalFactura;

        // -------------------------
        // Insertar FACTURA
        // -------------------------
        $FacturaSql = $db->query("
            INSERT INTO factura(
                subtotal, iva, it, tipo_comprobante, total, total_comision, total_caja,
                fecha, hora, usuario, cliente, nit_cliente, razon_social,
                tipo, metodo_pago, referencia, id_banco, habilitado
            ) VALUES (
                {$subtotal}, {$montoIva}, {$montoIt}, '{$tipo_comprobante}',
                {$totalFactura}, {$totalComision}, {$totalCaja},
                '{$fecha}', '{$hora}', '{$usuarioNombre}', {$idCliente},
                '{$nit}', '{$razon}', 1,
                '{$metodo_pago}', '{$referencia}', {$id_banco}, 1
            )
        ");

        if (!$FacturaSql):
            echo '<div class="alert alert-danger">Error al registrar factura.</div>';
            return;
        endif;

        $idFactura = $db->insert_id;

        // -------------------------
        // Insertar detalle en VENTAS
        // -------------------------
        foreach ($items as $it):

            $comLinea = $it['comision_unidad'] * $it['cantidad'];

            $db->query("
                INSERT INTO ventas (
                    idfactura, producto, cantidad, precio, totalprecio,
                    vendedor, usuario_factura, cliente, nit, razon_social,
                    fecha, hora, tipo, con_factura, metodo_pago,
                    id_banco, referencia_pago, nro_comprobante,
                    id_tramite, comision, habilitada, anulada
                ) VALUES (
                    {$idFactura},
                    {$it['producto']},
                    {$it['cantidad']},
                    {$it['precio']},
                    {$it['totalprecio']},
                    {$idVendedor},
                    {$idVendedor},
                    {$idCliente},
                    '{$nit}',
                    '{$razon}',
                    '{$fecha}',
                    '{$hora}',
                    1,
                    {$con_factura},
                    '{$metodo_pago}',
                    {$id_banco},
                    '{$referencia}',
                    '{$nro_comp}',
                    NULL,
                    {$comLinea},
                    1,
                    0
                )
            ");

        endforeach;

        // -------------------------
        // Limpiar carrito
        // -------------------------
        $db->query("DELETE FROM cajatmp WHERE vendedor = {$idVendedor}");

        echo '
        <div class="alert alert-success">
            Venta registrada. Factura #' . $idFactura . '
        </div>
        <meta http-equiv="refresh" content="0;url=' . URLBASE . 'registro-de-ventas.php"/>';
    }


    /*
    |--------------------------------------------------------------------------
    | 6) CANCELAR FACTURA
    |--------------------------------------------------------------------------
    */
    public function CancelarFactura()
    {
        if (!isset($_POST['CancelarFactura'])) return;

        $db = $this->Conectar();

        $IdFactura = (int)$_POST['Idfactura'];
        $Comentario = $db->real_escape_string($_POST['Comentario']);

        if ($IdFactura <= 0):
            echo '<div class="alert alert-danger">ID inválido.</div>';
            return;
        endif;

        $FacturaSql = $db->query("SELECT * FROM factura WHERE id = {$IdFactura}");

        if ($FacturaSql->num_rows == 0):
            echo '<div class="alert alert-danger">Factura no encontrada.</div>';
            return;
        endif;

        $F = $FacturaSql->fetch_assoc();

        if ((int)$F['habilitado'] === 0):
            echo '<div class="alert alert-warning">La factura ya estaba cancelada.</div>';
            return;
        endif;

        $total_caja = $F['total_caja'];
        $metodo_pago = $F['metodo_pago'];
        $id_banco = $F['id_banco'];

        // Reversar caja
        if ($total_caja > 0):
            $MaxCaja = $db->query("SELECT MAX(id) AS id FROM caja")->fetch_assoc();
            $db->query("UPDATE caja SET monto = monto - {$total_caja} WHERE id = {$MaxCaja['id']}");
        endif;

        // Reversar banco
        if ($metodo_pago !== 'EFECTIVO' && $id_banco):
            $db->query("
                INSERT INTO banco_movimientos(
                    id_banco, fecha, tipo, monto, concepto, id_factura
                ) VALUES (
                    {$id_banco}, NOW(), 'EGRESO', {$total_caja},
                    'Reverso Factura #{$IdFactura}', {$IdFactura}
                )
            ");
        endif;

        // Cancelar factura
        $db->query("
            UPDATE factura
            SET habilitado = 0,
                comentario_cancelacion = '{$Comentario}'
            WHERE id = {$IdFactura}
        ");

        // Cancelar ventas
        $db->query("UPDATE ventas SET habilitada = 0 WHERE idfactura = {$IdFactura}");

        echo '<div class="alert alert-success">Factura cancelada correctamente.</div>';
    }
}