<?php

class Cotizacion extends Conexion
{
    // Etapas válidas según tu modelo actual
    private $etapas = [
        'NUEVO',
        'ENVIADO',
        'SEGUIMIENTO',
        'CIERRE'
    ];

    public function getEtapas()
    {
        return $this->etapas;
    }

    /* ============================================================
       CREAR COTIZACIÓN (USADO POR cotizacion-nueva.php)
       ============================================================ */
    public function CrearCotizacion()
    {
        if (!isset($_POST['CrearCotizacion'])) {
            return;
        }

        $db = $this->Conectar();
        global $usuarioApp;

        $idUsuario = isset($usuarioApp['id']) ? (int)$usuarioApp['id'] : 0;
        $idCliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        $moneda    = isset($_POST['moneda']) ? $db->real_escape_string($_POST['moneda']) : 'BOB';
        $validez   = isset($_POST['validez']) ? (int)$_POST['validez'] : 7;
        $observ    = isset($_POST['observacion']) ? $db->real_escape_string($_POST['observacion']) : '';

        $servicios  = isset($_POST['servicio']) ? $_POST['servicio'] : [];
        $cantidades = isset($_POST['cantidad']) ? $_POST['cantidad'] : [];
        $precios    = isset($_POST['precio']) ? $_POST['precio'] : [];

        if ($idUsuario <= 0 || $idCliente <= 0) {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Debe seleccionar un cliente y tener un usuario válido.
            </div>';
            return;
        }

        if (empty($servicios)) {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Debe agregar al menos un servicio a la cotización.
            </div>';
            return;
        }

        // Construir detalle en memoria
        $detalles = [];
        $subtotal = 0;

        for ($i = 0; $i < count($servicios); $i++) {
            $idProd = isset($servicios[$i]) ? (int)$servicios[$i] : 0;
            if ($idProd <= 0) {
                continue;
            }

            $cant = isset($cantidades[$i]) ? (float)$cantidades[$i] : 1;
            $precio = isset($precios[$i]) ? (float)$precios[$i] : 0;

            if ($cant <= 0) {
                $cant = 1;
            }
            if ($precio < 0) {
                $precio = 0;
            }

            $sub = $cant * $precio;
            $subtotal += $sub;

            $detalles[] = [
                'id_producto' => $idProd,
                'cantidad'    => $cant,
                'precio'      => $precio,
                'subtotal'    => $sub
            ];
        }

        if (empty($detalles)) {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                No se pudo generar el detalle de la cotización (verifique los servicios).
            </div>';
            return;
        }

        // Por ahora IVA/IT/Descuento = 0 (puedes ampliar luego)
        $descuento = 0.00;
        $iva       = 0.00;
        $it        = 0.00;
        $total     = $subtotal - $descuento + $iva + $it;

        $tipoCambio = 1.00;
        $estado     = 'PENDIENTE';
        $etapa      = 'NUEVO';
        $prob       = 0;

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');
        $fecha_venc = date('Y-m-d', strtotime($fecha . " + {$validez} days"));

        $codigo = 'COT-' . date('YmdHis');

        $sql = "
            INSERT INTO cotizacion (
                codigo,
                id_cliente,
                fecha,
                hora,
                validez_dias,
                fecha_vencimiento,
                subtotal,
                descuento,
                iva,
                it,
                total,
                moneda,
                tipo_cambio,
                estado,
                observacion,
                probabilidad,
                etapa,
                fecha_envio,
                fecha_seguimiento,
                enviado_por,
                usuario,
                fecha_aceptada,
                fecha_rechazada,
                convertida_venta,
                id_factura
            ) VALUES (
                '{$codigo}',
                {$idCliente},
                '{$fecha}',
                '{$hora}',
                {$validez},
                '{$fecha_venc}',
                {$subtotal},
                {$descuento},
                {$iva},
                {$it},
                {$total},
                '{$moneda}',
                {$tipoCambio},
                '{$estado}',
                '{$observ}',
                {$prob},
                '{$etapa}',
                NULL,
                NULL,
                NULL,
                {$idUsuario},
                NULL,
                NULL,
                0,
                NULL
            )
        ";

        if (!$db->query($sql)) {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Error al crear la cotización.<br>
                <small>' . $db->error . '</small>
            </div>';
            return;
        }

        $idCot = $db->insert_id;

        // Insertar detalle
        foreach ($detalles as $d) {
            $idProd    = $d['id_producto'];
            $cantidad  = $d['cantidad'];
            $precio    = $d['precio'];
            $sub       = $d['subtotal'];

            // Obtener nombre y comisión del producto (opcional)
            $desc = '';
            $comision = 0.00;
            $ProdSql = $db->query("
                SELECT nombre, comision
                FROM producto
                WHERE id = {$idProd}
                LIMIT 1
            ");
            if ($ProdSql && $ProdSql->num_rows > 0) {
                $P = $ProdSql->fetch_assoc();
                $desc = $db->real_escape_string($P['nombre']);
                $comision = isset($P['comision']) ? (float)$P['comision'] : 0.00;
            }

            $db->query("
                INSERT INTO cotizacion_detalle(
                    id_cotizacion,
                    id_producto,
                    descripcion,
                    cantidad,
                    precio,
                    subtotal,
                    comision
                ) VALUES (
                    {$idCot},
                    {$idProd},
                    '{$desc}',
                    {$cantidad},
                    {$precio},
                    {$sub},
                    {$comision}
                )
            ");
        }

        echo '
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            Cotización creada correctamente.
        </div>
        <meta http-equiv="refresh" content="1;url=cotizacion-detalle.php?id='.$idCot.'" />';
    }

    /* ============================================================
       EDITAR CABECERA DE COTIZACIÓN (USADO POR cotizacion-editar.php)
       ============================================================ */
    public function EditarCotizacion()
    {
        if (!isset($_POST['EditarCotizacion'])) {
            return;
        }

        $db = $this->Conectar();

        $id        = isset($_POST['id_cotizacion']) ? (int)$_POST['id_cotizacion'] : 0;
        $moneda    = isset($_POST['moneda']) ? $db->real_escape_string($_POST['moneda']) : 'BOB';
        $estado    = isset($_POST['estado']) ? $db->real_escape_string($_POST['estado']) : 'PENDIENTE';
        $observ    = isset($_POST['observacion']) ? $db->real_escape_string($_POST['observacion']) : '';

        if ($id <= 0) {
            echo "
            <div class='alert alert-danger'>ID de cotización inválido.</div>";
            return;
        }

        // Asegurar que el estado sea uno permitido
        $estadosValidos = ['PENDIENTE','ACEPTADA','RECHAZADA','VENCIDA'];
        if (!in_array($estado, $estadosValidos)) {
            $estado = 'PENDIENTE';
        }

        $sql = "
            UPDATE cotizacion SET
                moneda      = '{$moneda}',
                estado      = '{$estado}',
                observacion = '{$observ}'
            WHERE id = {$id}
        ";

        if ($db->query($sql)) {
            echo "
            <div class='alert alert-success alert-dismissible'>
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
                Cotización actualizada correctamente.
            </div>
            <meta http-equiv='refresh' content='1;url=cotizacion-detalle.php?id={$id}'>";
        } else {
            echo "<div class='alert alert-danger'>Error al actualizar: {$db->error}</div>";
        }
    }

    /* ============================================================
       LISTADO GENERAL (por si luego quieres una vista tipo tabla)
       ============================================================ */
    public function ListadoCotizaciones($f = [])
    {
        $db = $this->Conectar();

        $cond = " WHERE 1 ";

        if (!empty($f['desde']) && !empty($f['hasta'])) {
            $cond .= " AND fecha BETWEEN '{$f['desde']}' AND '{$f['hasta']}' ";
        }
        if (!empty($f['estado'])) {
            $estado = $db->real_escape_string($f['estado']);
            $cond .= " AND estado = '{$estado}' ";
        }
        if (!empty($f['cliente'])) {
            $id = (int)$f['cliente'];
            $cond .= " AND id_cliente = {$id} ";
        }

        $sql = "
            SELECT c.*, cli.nombre AS cliente_nombre
            FROM cotizacion c
            LEFT JOIN cliente cli ON cli.id = c.id_cliente
            {$cond}
            ORDER BY c.fecha DESC, c.hora DESC
        ";

        return $db->query($sql);
    }

    /* ============================================================
       DETALLE (USADO POR cotizacion-detalle.php)
       ============================================================ */
    public function ObtenerDetalle($id)
    {
        $db = $this->Conectar();
        $id = (int)$id;

        return $db->query("
            SELECT 
                cd.*,
                p.nombre,
                p.tipo_servicio
            FROM cotizacion_detalle cd
            LEFT JOIN producto p ON p.id = cd.id_producto
            WHERE cd.id_cotizacion = {$id}
        ");
    }

    /* ============================================================
       RESUMEN PARA KANBAN
       ============================================================ */
    public function ResumenEtapas()
    {
        $db = $this->Conectar();

        $res = [
            'total'      => 0,
            'pendientes' => 0,
            'proceso'    => 0,
            'ganados'    => 0,
            'perdidos'   => 0
        ];

        $q = $db->query("
            SELECT etapa, COUNT(*) AS total
            FROM cotizacion
            GROUP BY etapa
        ");

        while ($r = $q->fetch_assoc()) {
            $res['total'] += $r['total'];

            if (in_array($r['etapa'], ['NUEVO'])) {
                $res['pendientes'] += $r['total'];
            }

            if (in_array($r['etapa'], ['ENVIADO','SEGUIMIENTO','CIERRE'])) {
                $res['proceso'] += $r['total'];
            }

            // Si luego agregas GANADO/PERDIDO al ENUM podrás usarlos aquí
        }

        return $res;
    }

    /* ============================================================
       CAMBIAR ETAPA (USADO POR kanban y detalle)
       ============================================================ */
    public function CambiarEtapa()
    {
        if (!isset($_POST['MoverEtapa'])) {
            return;
        }

        $db = $this->Conectar();

        $idCot   = isset($_POST['id_cotizacion']) ? (int)$_POST['id_cotizacion'] : 0;
        $nueva   = isset($_POST['nueva_etapa']) ? trim($_POST['nueva_etapa']) : '';

        if ($idCot <= 0 || $nueva === '') {
            return;
        }

        $nueva = $db->real_escape_string($nueva);

        // Validar contra la lista de etapas
        if (!in_array($nueva, $this->etapas)) {
            return;
        }

        $db->query("
            UPDATE cotizacion
            SET etapa = '{$nueva}'
            WHERE id = {$idCot}
        ");
    }

    /* ============================================================
       CONVERTIR COTIZACIÓN → POS (USADO EN cotizacion-detalle.php)
       ============================================================ */
          /*
    |-----------------------------------------------------------|
    | CAMBIAR ETAPA (acción rápida desde el tablero Kanban)
    |-----------------------------------------------------------|
    */
    public function CambiarEtapaRapida()
    {
        if (!isset($_POST['MoverEtapa'])) {
            return;
        }

        $db = $this->Conectar();

        $idCotizacion = isset($_POST['id_cotizacion']) ? (int)$_POST['id_cotizacion'] : 0;
        $nuevaEtapa   = isset($_POST['nueva_etapa']) ? trim($_POST['nueva_etapa']) : '';

        if ($idCotizacion <= 0 || $nuevaEtapa === '') {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                No se pudo cambiar la etapa: datos inválidos.
            </div>';
            return;
        }

        $nuevaEtapaEsc = $db->real_escape_string($nuevaEtapa);

        $db->query("
            UPDATE cotizacion
            SET etapa = '{$nuevaEtapaEsc}'
            WHERE id = {$idCotizacion}
        ");

        echo "
        <meta http-equiv='refresh' content='0;url=cotizaciones-kanban.php' />";
    }

    public function ConvertirAVenta()
    {
        if (!isset($_POST['ConvertirVenta'])) {
            return;
        }

        $db = $this->Conectar();
        global $usuarioApp;

        $idCot = isset($_POST['id_cotizacion']) ? (int)$_POST['id_cotizacion'] : 0;
        $idVend = isset($usuarioApp['id']) ? (int)$usuarioApp['id'] : 0;

        if ($idCot <= 0 || $idVend <= 0) {
            echo "<div class='alert alert-danger'>Datos inválidos para convertir a venta.</div>";
            return;
        }

        // Obtener cabecera para sacar el cliente
        $CotSQL = $db->query("
            SELECT id_cliente
            FROM cotizacion
            WHERE id = {$idCot}
            LIMIT 1
        ");

        if (!$CotSQL || $CotSQL->num_rows == 0) {
            echo "<div class='alert alert-danger'>Cotización no encontrada.</div>";
            return;
        }

        $Cot = $CotSQL->fetch_assoc();
        $idCliente = (int)$Cot['id_cliente'];

        // Limpiar cajatmp del vendedor
        $db->query("DELETE FROM cajatmp WHERE vendedor = {$idVend}");

        // Cargar detalle
        $Det = $db->query("
            SELECT cd.*, p.comision
            FROM cotizacion_detalle cd
            LEFT JOIN producto p ON p.id = cd.id_producto
            WHERE cd.id_cotizacion = {$idCot}
        ");

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        while ($d = $Det->fetch_assoc()) {
            $idProd   = (int)$d['id_producto'];
            $cantidad = (float)$d['cantidad'];
            $precio   = (float)$d['precio'];
            $sub      = (float)$d['subtotal'];

            // comision: puedes definir si la columna ya guarda total, aquí la uso tal cual
            $comision = isset($d['comision']) ? (float)$d['comision'] : 0.00;

            $db->query("
                INSERT INTO cajatmp (
                    idfactura,
                    producto,
                    cantidad,
                    precio,
                    totalprecio,
                    comision,
                    vendedor,
                    cliente,
                    stockTmp,
                    stock,
                    fecha,
                    hora
                ) VALUES (
                    NULL,
                    {$idProd},
                    {$cantidad},
                    {$precio},
                    {$sub},
                    {$comision},
                    {$idVend},
                    {$idCliente},
                    0,
                    0,
                    '{$fecha}',
                    '{$hora}'
                )
            ");
        }

        // Marcar cotización como convertida (opcional)
        $db->query("
            UPDATE cotizacion
            SET convertida_venta = 1
            WHERE id = {$idCot}
        ");

        echo "
        <div class='alert alert-success alert-dismissible'>
            <button type='button' class='close' data-dismiss='alert'>&times;</button>
            Cotización cargada al POS. Continúa el flujo de venta.
        </div>
        <meta http-equiv='refresh' content='0;url=index.php' />";
    }

    public function ProbabilidadSegunEtapa($etapa)
{
    switch($etapa) {
        case 'NUEVO': return 10;
        case 'CONTACTO': return 25;
        case 'PROPUESTA ENVIADA': return 40;
        case 'EN NEGOCIACIÓN': return 60;
        case 'CASI CERRADO': return 80;
        case 'GANADO': return 100;
        case 'PERDIDO': return 0;
        default: return 0;
    }
}

}