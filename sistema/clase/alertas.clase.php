<?php
class Alertas extends Conexion {

    public function Generar(){
        $db = $this->Conectar();

        $sql = $db->query("
            SELECT id, nro_expediente, fecha_vencimiento
            FROM tramites
            WHERE fecha_vencimiento IS NOT NULL
              AND fecha_vencimiento <= DATE_ADD(NOW(), INTERVAL 10 DAY)
              AND alerta_generada = 0
        ");

        while($t = $sql->fetch_assoc()){
            $msg = "El trámite {$t['nro_expediente']} está próximo a vencer.";
            $db->query("
                INSERT INTO notificaciones (id_tramite, mensaje, fecha_alerta, estado_alerta)
                VALUES ('{$t['id']}', '{$msg}', NOW(), 'pendiente')
            ");

            $db->query("UPDATE tramites SET alerta_generada=1 WHERE id={$t['id']}");
        }
    }

    public function PorCliente($idCliente){
        return $this->Conectar()->query("
            SELECT n.*
            FROM notificaciones n
            INNER JOIN tramites t ON t.id = n.id_tramite
            WHERE t.id_cliente={$idCliente}
        ");
    }
}
?>