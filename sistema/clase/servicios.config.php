<?php
class ServicioConfig {

    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /* =====================================================
       Guardar parámetro
    ===================================================== */
    public function set($clave, $valor) {
        $clave  = addslashes($clave);
        $valor  = addslashes($valor);

        // Si existe → actualizar
        $exist = $this->db->SQL("SELECT id FROM servicio_config WHERE clave='$clave'");

        if ($exist->num_rows > 0) {
            return $this->db->SQL("UPDATE servicio_config SET valor='$valor' WHERE clave='$clave'");
        }
        
        // Sino → insertar
        return $this->db->SQL("INSERT INTO servicio_config (clave, valor) VALUES ('$clave', '$valor')");
    }

    /* =====================================================
       Obtener configuración
    ===================================================== */
    public function get($clave, $default=null) {
        $clave = addslashes($clave);

        $sql = $this->db->SQL("SELECT valor FROM servicio_config WHERE clave='$clave' LIMIT 1");
        if ($sql->num_rows > 0) {
            return $sql->fetch_assoc()['valor'];
        }

        return $default;
    }

    /* =====================================================
       Obtener todo en array
    ===================================================== */
    public function all() {
        $result = $this->db->SQL("SELECT clave, valor FROM servicio_config");
        $arr = [];

        while ($row = $result->fetch_assoc()) {
            $arr[$row['clave']] = $row['valor'];
        }

        return $arr;
    }
}