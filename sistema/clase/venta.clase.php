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
        $com    = (float)$P['comision'];
        $total  = $precio * $cantidad;
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

        $IdCajatmp = (int)$_POST['IdCajatmp'];

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
        $idCliente  = $this->cliente(); // sincronizado con POS

        return $this->agregarAlCarrito($idProducto, $cantidad, $idCliente);
    }

    /* ==========================================================
       6) REGISTRAR VENTA COMPLETA
       ✅ AHORA REGISTRA INGRESO EN CAJA_CHICA_MOVIMIENTOS
       ❌ YA NO INSERTA NADA EN CAJA_GENERAL_MOVIMIENTOS
    ========================================================== */
    public function RegistrarVenta1()
    {
        if (!isset($_POST['RegistrarVenta'])) return;

        $db = $this->Conectar();

        $idVendedor = $this->vendedor();
        $idCliente  = $this->cliente();

        $nit            = $db->real_escape_string($_POST['nit'] ?? '');
        $razon          = $db->real_escape_string($_POST['razon_social'] ?? '');
        $con_factura    = (int)($_POST['con_factura'] ?? 0);
        $tipo_comprobante = $db->real_escape_string($_POST['tipo_comprobante'] ?? 'RECIBO');

        $metodo_pago = $db->real_escape_string($_POST['metodo_pago'] ?? 'EFECTIVO');
        $id_banco    = ($_POST['id_banco'] ?? '') !== "" ? (int)$_POST['id_banco'] : "NULL";
        $referencia  = $db->real_escape_string($_POST['referencia_pago'] ?? '');
        $nro_comp    = $db->real_escape_string($_POST['nro_comprobante'] ?? '');

        $iva_porcentaje = (float)($_POST['iva_porcentaje'] ?? 0);
        $it_porcentaje  = (float)($_POST['it_porcentaje'] ?? 0);

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

        if (!$Carrito || $Carrito->num_rows == 0) {
            echo '<div class="alert alert-danger">Carrito vacío.</div>';
            return;
        }

        $subtotal = 0.0;
        $totalComision = 0.0;
        $items = [];

        while ($row = $Carrito->fetch_assoc()) {
            $subtotal += (float)$row['totalprecio'];
            $totalComision += ((float)$row['comision_unidad'] * (int)$row['cantidad']);
            $items[] = $row;
        }

        $montoIva     = $subtotal * ($iva_porcentaje / 100);
        $montoIt      = $subtotal * ($it_porcentaje / 100);
        $totalFactura = $subtotal + $montoIva + $montoIt;

        // INSERTAR FACTURA
        $okFactura = $db->query("
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

        if (!$okFactura) {
            echo '<div class="alert alert-danger">Error al registrar factura.</div>';
            return;
        }

        $idFactura = (int)$db->insert_id;

        // INSERTAR DETALLES EN VENTAS
        foreach ($items as $it) {
            $comLinea = ((float)$it['comision_unidad'] * (int)$it['cantidad']);

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

        /* ==========================================================
           ✅ REGISTRAR INGRESO EN CAJA CHICA (VENTA DIARIA)
        ========================================================== */
        $SaldoChicaSQL = $db->query("
            SELECT saldo_resultante
            FROM caja_chica_movimientos
            ORDER BY id DESC
            LIMIT 1
        ");

        $saldoAnteriorChica = ($SaldoChicaSQL && $SaldoChicaSQL->num_rows > 0)
            ? (float)$SaldoChicaSQL->fetch_assoc()['saldo_resultante']
            : 0.0;

        $saldoNuevoChica = $saldoAnteriorChica + (float)$totalFactura;

        // Concepto con método/banco, sin cambiar estructura de tabla
        $conceptoCaja = "Venta de servicios - Factura #{$idFactura} ({$metodo_pago})";
        if ($id_banco !== "NULL" && (int)$id_banco > 0) {
            $conceptoCaja .= " - Banco #".(int)$id_banco;
        }
        if (!empty($nro_comp)) {
            $conceptoCaja .= " - Comp: {$nro_comp}";
        }

        $refCaja = "VENTA#{$idFactura}";

        $db->query("
            INSERT INTO caja_chica_movimientos
                (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
            VALUES
                (
                    '{$fecha}',
                    '{$hora}',
                    'INGRESO',
                    {$totalFactura},
                    '".addslashes($conceptoCaja)."',
                    {$idVendedor},
                    {$saldoNuevoChica},
                    '{$refCaja}'
                )
        ");

        /* ==========================================================
           LIMPIAR SOLO EL CARRITO DEL CLIENTE
        ========================================================== */
        $db->query("
            DELETE FROM cajatmp
            WHERE vendedor = {$idVendedor}
              AND cliente  = {$idCliente}
        ");

        echo '<meta http-equiv="refresh" content="0;url=' . URLBASE . 'registro-de-ventas.php"/>';
    }

    /*
    |--------------------------------------------------------------------------
    | 7) CANCELAR FACTURA
    | ✅ REVERSA CAJA CHICA (NO CAJA GENERAL)
    |-------------------------------------------------------------------------- 
    */
    public function CancelarFactura()
    {
        if (!isset($_POST['CancelarFactura'])) return;

        $db = $this->Conectar();

        $IdFactura  = (int)$_POST['Idfactura'];
        $Comentario = $db->real_escape_string($_POST['Comentario']);

        if ($IdFactura <= 0) {
            echo '<div class="alert alert-danger">ID inválido.</div>';
            return;
        }

        $FacturaSql = $db->query("SELECT * FROM factura WHERE id = {$IdFactura}");

        if (!$FacturaSql || $FacturaSql->num_rows == 0) {
            echo '<div class="alert alert-danger">Factura no encontrada.</div>';
            return;
        }

        $F = $FacturaSql->fetch_assoc();

        if ((int)$F['habilitado'] === 0) {
            echo '<div class="alert alert-warning">La factura ya estaba cancelada.</div>';
            return;
        }

        $total_caja   = (float)($F['total_caja'] ?? $F['total'] ?? 0);
        $metodo_pago  = $F['metodo_pago'] ?? 'EFECTIVO';

        // ✅ Reverso en CAJA CHICA (EGRESO)
        if ($total_caja > 0) {

            $SaldoChicaSQL = $db->query("
                SELECT saldo_resultante
                FROM caja_chica_movimientos
                ORDER BY id DESC
                LIMIT 1
            ");

            $saldoAnteriorChica = ($SaldoChicaSQL && $SaldoChicaSQL->num_rows > 0)
                ? (float)$SaldoChicaSQL->fetch_assoc()['saldo_resultante']
                : 0.0;

            $saldoNuevoChica = $saldoAnteriorChica - $total_caja;
            if ($saldoNuevoChica < 0) $saldoNuevoChica = 0;

            $conceptoRev = "Reverso de venta - Factura #{$IdFactura}";
            $refRev = "REVERSO#{$IdFactura}";

            $db->query("
                INSERT INTO caja_chica_movimientos
                    (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
                VALUES
                    (
                        '".date('Y-m-d')."',
                        '".date('H:i:s')."',
                        'EGRESO',
                        {$total_caja},
                        '".addslashes($conceptoRev)."',
                        ".$this->vendedor().",
                        {$saldoNuevoChica},
                        '{$refRev}'
                    )
            ");
        }

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