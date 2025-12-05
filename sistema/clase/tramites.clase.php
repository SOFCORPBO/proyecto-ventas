<?php
class Tramites extends Conexion {

    private $db;

    function __construct() {
        global $db;
        $this->db = $db;
    }

    /* ======================================================
        1. GUARDAR / ACTUALIZAR TRÁMITE
    ======================================================= */
    public function Guardar($post)
    {
        $id           = isset($post['id_tramite']) ? (int)$post['id_tramite'] : 0;
        $id_cliente   = (int)$post['id_cliente'];
        $tipo         = $post['tipo_tramite'];
        $pais         = trim($post['pais_destino']);
        $fecha_ini    = $post['fecha_inicio'];
        $fecha_ent    = !empty($post['fecha_entrega']) ? $post['fecha_entrega'] : null;
        $fecha_venc   = !empty($post['fecha_vencimiento']) ? $post['fecha_vencimiento'] : null;
        $estado       = $post['estado'];
        $monto        = floatval($post['monto_estimado']);
        $obs          = addslashes(trim($post['observaciones']));

        if ($id > 0) {
            $this->db->SQL("
                UPDATE tramites SET
                    id_cliente         = '{$id_cliente}',
                    tipo_tramite       = '{$tipo}',
                    pais_destino       = '{$pais}',
                    fecha_inicio       = '{$fecha_ini}',
                    fecha_entrega      = ".($fecha_ent ? "'{$fecha_ent}'" : "NULL").",
                    fecha_vencimiento  = ".($fecha_venc ? "'{$fecha_venc}'" : "NULL").",
                    estado             = '{$estado}',
                    monto_estimado     = '{$monto}',
                    observaciones      = '{$obs}'
                WHERE id = '{$id}'
            ");
            return ['ok'=>true, 'msg'=>'Trámite actualizado correctamente.'];
        }

        $this->db->SQL("
            INSERT INTO tramites
                (id_cliente, tipo_tramite, pais_destino, fecha_inicio,
                 fecha_entrega, fecha_vencimiento, alerta_generada, estado,
                 monto_estimado, observaciones)
            VALUES (
                '{$id_cliente}', '{$tipo}', '{$pais}', '{$fecha_ini}',
                ".($fecha_ent ? "'{$fecha_ent}'" : "NULL").",
                ".($fecha_venc ? "'{$fecha_venc}'" : "NULL").",
                0, '{$estado}', '{$monto}', '{$obs}'
            )
        ");

        return ['ok'=>true, 'msg'=>'Trámite registrado correctamente.'];
    }
/* ======================================================
   5. Clientes para selects
====================================================== */
public function Clientes()
{
    return $this->db->SQL("
        SELECT id, nombre
        FROM cliente
        WHERE habilitado = 1
        ORDER BY nombre ASC
    ");
}

    /* ======================================================
        2. CAMBIAR ESTADO
    ======================================================= */
    public function CambiarEstado($id, $nuevoEstado)
    {
        $id = (int)$id;
        if ($id <= 0) return;

        $this->db->SQL("
            UPDATE tramites SET estado = '{$nuevoEstado}'
            WHERE id = '{$id}'
        ");
    }

    public function Activar($id)
{
    $id = intval($id);
    return $this->db->SQL("UPDATE tramites SET habilitado = 1 WHERE id = '$id'");
}

public function Desactivar($id)
{
    $id = intval($id);
    return $this->db->SQL("UPDATE tramites SET habilitado = 0 WHERE id = '$id'");
}


    /* ======================================================
        3. FILTROS
    ======================================================= */
    public function Listar($f)
    {
        $where = "1=1";

        if (!empty($f['desde']))        $where .= " AND t.fecha_inicio >= '{$f['desde']}'";
        if (!empty($f['hasta']))        $where .= " AND t.fecha_inicio <= '{$f['hasta']}'";
        if (!empty($f['estado']))       $where .= " AND t.estado = '".addslashes($f['estado'])."'";
        if (!empty($f['tipo']))         $where .= " AND t.tipo_tramite = '".addslashes($f['tipo'])."'";
        if (!empty($f['id_cliente']))   $where .= " AND t.id_cliente = '".(int)$f['id_cliente']."'";

        return $this->db->SQL("
            SELECT t.*, c.nombre AS cliente_nombre
            FROM tramites t
            LEFT JOIN cliente c ON c.id = t.id_cliente
            WHERE {$where}
            ORDER BY t.id DESC
        ");
    }

    /* ======================================================
        4. KPIs de Trámites
    ======================================================= */
    public function KPIs()
    {
        $sql = $this->db->SQL("
            SELECT 
                COUNT(*) total,
                SUM(CASE WHEN estado='PENDIENTE'    THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado='EN_PROCESO'   THEN 1 ELSE 0 END) AS en_proceso,
                SUM(CASE WHEN estado='FINALIZADO'   THEN 1 ELSE 0 END) AS finalizados,
                SUM(CASE WHEN estado='RECHAZADO'    THEN 1 ELSE 0 END) AS rechazados
            FROM tramites
        ");
        return $sql->fetch_assoc();
    }

    /* ======================================================
        5. Obtener trámite
    ======================================================= */
    public function Obtener($id){
        $sql = $this->db->SQL("SELECT * FROM tramites WHERE id={$id} LIMIT 1");
        return $sql->fetch_assoc();
    }

    /* ======================================================
        6. Listar trámites por cliente
    ======================================================= */
    public function PorCliente($idCliente){
        return $this->db->SQL("
            SELECT * FROM tramites 
            WHERE id_cliente={$idCliente}
            ORDER BY id DESC
        ");
    }

    /* ======================================================
        7. Detectar trámites próximos a vencer
    ======================================================= */
    public function TramitesPorVencer(){
        return $this->db->SQL("
            SELECT *
            FROM tramites
            WHERE fecha_vencimiento IS NOT NULL
              AND fecha_vencimiento <= DATE_ADD(NOW(), INTERVAL 10 DAY)
              AND estado IN ('PENDIENTE','EN_PROCESO')
        ");
    }
}
?>