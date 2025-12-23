<?php

class ContabilidadDiario extends Conexion
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ============================================================
       📌 CREAR ASIENTO (CABECERA)
    ============================================================ */
    public function CrearAsiento($descripcion, $referencia, $usuario)
    {
        $fecha = date("Y-m-d");

        $this->db->SQL("
            INSERT INTO contabilidad_diario (fecha, descripcion, referencia, creado_por)
            VALUES ('{$fecha}', '{$descripcion}', '{$referencia}', {$usuario})
        ");

        return $this->db->InsertID();
    }

    /* ============================================================
       📌 INSERTAR DETALLE DEL ASIENTO
    ============================================================ */
    public function InsertarLinea($id_asiento, $id_cuenta, $debe, $haber)
    {
        $this->db->SQL("
            INSERT INTO contabilidad_diario_detalle
            (id_diario, id_cuenta, debe, haber)
            VALUES 
            ({$id_asiento}, {$id_cuenta}, {$debe}, {$haber})
        ");
    }

    /* ============================================================
       📌 OBTENER ASIENTOS
    ============================================================ */
    public function ListarAsientos()
    {
        return $this->db->SQL("
            SELECT d.*, u.usuario AS creado_por
            FROM contabilidad_diario d
            LEFT JOIN usuario u ON u.id = d.creado_por
            ORDER BY d.id DESC
        ");
    }

    /* ============================================================
       📌 OBTENER DETALLE POR ASIENTO
    ============================================================ */
    public function DetalleAsiento($id_asiento)
    {
        return $this->db->SQL("
            SELECT dd.*, c.codigo, c.nombre
            FROM contabilidad_diario_detalle dd
            INNER JOIN contabilidad_cuentas c ON c.id = dd.id_cuenta
            WHERE dd.id_diario = {$id_asiento}
        ");
    }
}

?>
