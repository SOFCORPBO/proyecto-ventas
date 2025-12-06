<?php

class Venta extends Conexion
{

  private function vendedor()
    {
        global $usuarioApp;
        return (int)$usuarioApp['id'];
    }

    private function cliente()
    {
        return (int)($_SESSION['cliente_actual'] ?? 1);
    }

    /* ==========================================================
       1) AGREGAR SERVICIO AL CARRITO
    ========================================================== */
    public function agregarAlCarrito($idProducto, $cantidad, $idCliente)
    {
        $db = $this->Conectar();
        $idVendedor = $this->vendedor();
        $idCliente = (int)$idCliente;

        // Verifica producto
        $ProductoSql = $db->query("
            SELECT id, precioventa, comision 
            FROM producto 
            WHERE id = {$idProducto} LIMIT 1
        ");

        if ($ProductoSql->num_rows == 0) return false;

        $P = $ProductoSql->fetch_assoc();

        $precio = (float)$P['precioventa'];
        $com = (float)$P['comision'];
        $total = $precio * $cantidad;
        $totalCom = $com * $cantidad;

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        return $db->query("
            INSERT INTO cajatmp (
                idfactura, producto, cantidad, precio, totalprecio, comision,
                vendedor, cliente, fecha, hora
            ) VALUES (
                NULL, {$idProducto}, {$cantidad}, {$precio},
                {$total}, {$totalCom},
                {$idVendedor}, {$idCliente},
                '{$fecha}', '{$hora}'
            )
        ");
    }

    /* ==========================================================
       2) ELIMINAR SERVICIO DEL CARRITO
    ========================================================== */
    public function EliminarProducto()
    {
        if (!isset($_POST['EliminarProducto'])) return;

        $IdCajatmp  = (int)$_POST['IdCajatmp'];

        $this->Conectar()->query("
            DELETE FROM cajatmp
            WHERE id = {$IdCajatmp}
              AND vendedor = {$this->vendedor()}
              AND cliente  = {$this->cliente()}
        ");

        echo '<meta http-equiv="refresh" content="0">';
    }

    /* ==========================================================
       3) ACTUALIZAR CANTIDAD
    ========================================================== */
    public function ActualizarCantidadCajaTmp()
    {
        if (!isset($_POST['ActualizarCantidadCajaTmp'])) return;

        $IdCajaTmp = (int)$_POST['IdCajaTmp'];
        $Cantidad  = (int)$_POST['Cantidad'];
        $Precio    = (float)$_POST['Precio'];

        $PrecioTotal = $Precio * $Cantidad;

        $this->Conectar()->query("
            UPDATE cajatmp
            SET cantidad = {$Cantidad},
                totalprecio = {$PrecioTotal}
            WHERE id = {$IdCajaTmp}
              AND vendedor = {$this->vendedor()}
              AND cliente  = {$this->cliente()}
        ");

        echo '<meta http-equiv="refresh" content="0">';
    }

    /* ==========================================================
       4) LIMPIAR CARRITO (solo del cliente actual)
    ========================================================== */
    public function LimpiarCarritoCompras()
    {
        if (!isset($_POST['EliminarTodo'])) return;

        $this->Conectar()->query("
            DELETE FROM cajatmp
            WHERE vendedor = {$this->vendedor()}
              AND cliente  = {$this->cliente()}
        ");

        echo '<meta http-equiv="refresh" content="0">';
    }

    /* ==========================================================
       5) AGREGAR PRODUCTO (desde botón principal del POS)
    ========================================================== */
    public function AgregarProductoCarrito()
    {
        if (!isset($_POST['agregar_servicio'])) return;

        $idProducto = (int)$_POST['codigo'];
        $cantidad   = (int)$_POST['cantidad'];
        $idCliente  = $this->cliente(); // SIEMPRE sincronizar con POS

        return $this->agregarAlCarrito($idProducto, $cantidad, $idCliente);
    }

    /* ==========================================================
       6) REGISTRAR VENTA COMPLETA
    ========================================================== */
    public function RegistrarVenta1()
    {
        if (!isset($_POST['RegistrarVenta'])) return;

        $db = $this->Conectar();

        $idVendedor = $this->vendedor();
        $idCliente  = $this->cliente();

        $nit       = $db->real_escape_string($_POST['nit'] ?? '');
        $razon     = $db->real_escape_string($_POST['razon_social'] ?? '');
        $con_factura = (int)$_POST['con_factura'];
        $tipo_comprobante = $db->real_escape_string($_POST['tipo_comprobante']);

        $metodo_pago = $db->real_escape_string($_POST['metodo_pago']);
        $id_banco = $_POST['id_banco'] !== "" ? (int)$_POST['id_banco'] : "NULL";
        $referencia   = $db->real_escape_string($_POST['referencia_pago']);
        $nro_comp     = $db->real_escape_string($_POST['nro_comprobante']);

        $iva_porcentaje = (float)$_POST['iva_porcentaje'];
        $it_porcentaje  = (float)$_POST['it_porcentaje'];

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        // CARGAR CARRITO DEL CLIENTE ACTUAL
        $Carrito = $db->query("
            SELECT c.*, p.comision AS comision_unidad
            FROM cajatmp c
            LEFT JOIN producto p ON p.id = c.producto
            WHERE c.vendedor = {$idVendedor}
              AND c.cliente  = {$idCliente}
        ");

        if ($Carrito->num_rows == 0) {
            echo '<div class="alert alert-danger">Carrito vacío.</div>';
            return;
        }

        $subtotal = 0;
        $totalComision = 0;
        $items = [];

        while ($row = $Carrito->fetch_assoc()) {
            $subtotal += $row['totalprecio'];
            $totalComision += $row['comision_unidad'] * $row['cantidad'];
            $items[] = $row;
        }

        $montoIva = $subtotal * ($iva_porcentaje / 100);
        $montoIt  = $subtotal * ($it_porcentaje / 100);
        $totalFactura = $subtotal + $montoIva + $montoIt;

        // INSERTAR FACTURA
        $db->query("
            INSERT INTO factura(
                subtotal, iva, it, tipo_comprobante, total,
                total_comision, total_caja, fecha, hora,
                usuario, cliente, nit_cliente, razon_social,
                tipo, metodo_pago, referencia, id_banco, habilitado
            ) VALUES (
                {$subtotal}, {$montoIva}, {$montoIt}, '{$tipo_comprobante}',
                {$totalFactura}, {$totalComision}, {$totalFactura},
                '{$fecha}', '{$hora}', '{$idVendedor}', {$idCliente},
                '{$nit}', '{$razon}', 1,
                '{$metodo_pago}', '{$referencia}', {$id_banco}, 1
            )
        ");

        $idFactura = $db->insert_id;

        // INSERTAR DETALLES
        foreach ($items as $it) {
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
                    '{$nit}', '{$razon}',
                    '{$fecha}', '{$hora}', 1, {$con_factura},
                    '{$metodo_pago}', {$id_banco}, '{$referencia}',
                    '{$nro_comp}', NULL, {$comLinea}, 1, 0
                )
            ");
        }

        // LIMPIAR SOLO EL CARRITO DEL CLIENTE
        $db->query("
            DELETE FROM cajatmp
            WHERE vendedor = {$idVendedor}
              AND cliente  = {$idCliente}
        ");

        echo '<meta http-equiv="refresh" content="0;url=' . URLBASE . 'registro-de-ventas.php"/>';
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