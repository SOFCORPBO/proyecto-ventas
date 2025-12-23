<?php

class ContabilidadCuentas extends Conexion
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ============================================================
       📌 CREAR CUENTA CONTABLE
    ============================================================ */
    public function CrearCuenta()
    {
        if (!isset($_POST['CrearCuenta'])) return;

        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);
        $tipo   = $_POST['tipo'];
        $nivel  = intval($_POST['nivel']);
        $padre  = $_POST['padre_id'] ?: 'NULL';

        $this->db->SQL("
            INSERT INTO contabilidad_cuentas
            (codigo, nombre, tipo, nivel, padre_id)
            VALUES 
            ('{$codigo}', '{$nombre}', '{$tipo}', {$nivel}, {$padre})
        ");

        echo '<div class="alert alert-success">Cuenta registrada.</div>';
        echo '<meta http-equiv="refresh" content="1;url=contabilidad-cuentas.php">';
    }

    /* ============================================================
       📌 EDITAR CUENTA
    ============================================================ */
    public function EditarCuenta()
    {
        if (!isset($_POST['EditarCuenta'])) return;

        $id     = intval($_POST['id']);
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);
        $tipo   = $_POST['tipo'];
        $nivel  = intval($_POST['nivel']);
        $padre  = $_POST['padre_id'] ?: 'NULL';

        $this->db->SQL("
            UPDATE contabilidad_cuentas
            SET codigo='{$codigo}', nombre='{$nombre}', tipo='{$tipo}',
                nivel={$nivel}, padre_id={$padre}
            WHERE id={$id}
        ");

        echo '<div class="alert alert-success">Cuenta actualizada.</div>';
        echo '<meta http-equiv="refresh" content="1;url=contabilidad-cuentas.php">';
    }

    /* ============================================================
       📌 LISTAR CUENTAS
    ============================================================ */
    public function ListarCuentas()
    {
        return $this->db->SQL("
            SELECT *
            FROM contabilidad_cuentas
            WHERE habilitado = 1
            ORDER BY codigo ASC
        ");
    }

    /* ============================================================
       📌 OBTENER CUENTA POR ID
    ============================================================ */
    public function ObtenerCuenta($id)
    {
        return $this->db->SQL("
            SELECT * FROM contabilidad_cuentas
            WHERE id={$id} LIMIT 1
        ")->fetch_assoc();
    }

    /* ============================================================
       📌 DESHABILITAR / ELIMINAR CUENTA
    ============================================================ */
    public function EliminarCuenta()
    {
        if (!isset($_POST['EliminarCuenta'])) return;

        $id = intval($_POST['id']);

        $this->db->SQL("
            UPDATE contabilidad_cuentas 
            SET habilitado = 0 
            WHERE id={$id}
        ");

        echo '<div class="alert alert-success">Cuenta eliminada.</div>';
        echo '<meta http-equiv="refresh" content="1;url=contabilidad-cuentas.php">';
    }

}

?>
