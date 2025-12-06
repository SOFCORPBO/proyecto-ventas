<?php

class Proveedor extends Conexion
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ============================================================
       📌 CREAR PROVEEDOR
    ============================================================ */
    public function CrearProveedor()
    {
        if (!isset($_POST['CrearProveedor'])) return;

        $nombre        = ucwords(trim($_POST['nombre']));
        $telefono      = trim($_POST['telefono']);
        $contacto      = trim($_POST['contacto']);
        $direccion     = trim($_POST['direccion']);
        $email         = trim($_POST['email']);
        $tipo          = $_POST['tipo_proveedor'];
        $habilitado    = isset($_POST['habilitado']) ? 1 : 0;

        $this->db->SQL("
            INSERT INTO proveedor (
                nombre, telefono, contacto, direccion, email, 
                tipo_proveedor, saldo_pendiente, habilitado, fecha_registro
            ) VALUES (
                '{$nombre}', '{$telefono}', '{$contacto}', '{$direccion}', '{$email}',
                '{$tipo}', 0.00, '{$habilitado}', NOW()
            )
        ");

        echo '<div class="alert alert-success">Proveedor registrado correctamente.</div>';
        echo '<meta http-equiv="refresh" content="1;url=' . URLBASE . 'proveedores.php">';
    }

    /* ============================================================
       📌 EDITAR PROVEEDOR
    ============================================================ */
    public function EditarProveedor()
    {
        if (!isset($_POST['EditarProveedor'])) return;

        $id           = intval($_POST['id']);
        $nombre       = ucwords(trim($_POST['nombre']));
        $telefono     = trim($_POST['telefono']);
        $contacto     = trim($_POST['contacto']);
        $direccion    = trim($_POST['direccion']);
        $email        = trim($_POST['email']);
        $tipo         = $_POST['tipo_proveedor'];
        $habilitado   = isset($_POST['habilitado']) ? 1 : 0;

        $this->db->SQL("
            UPDATE proveedor SET
                nombre = '{$nombre}',
                telefono = '{$telefono}',
                contacto = '{$contacto}',
                direccion = '{$direccion}',
                email = '{$email}',
                tipo_proveedor = '{$tipo}',
                habilitado = '{$habilitado}'
            WHERE id = {$id}
        ");

        echo '<div class="alert alert-success">Proveedor actualizado correctamente.</div>';
        echo '<meta http-equiv="refresh" content="1;url=' . URLBASE . 'proveedores.php">';
    }

    /* ============================================================
       📌 LISTAR PROVEEDORES
    ============================================================ */
    public function ListarProveedores()
    {
        return $this->db->SQL("
            SELECT * 
            FROM proveedor
            ORDER BY id DESC
        ");
    }

    /* ============================================================
       📌 OBTENER PROVEEDOR POR ID
    ============================================================ */
    public function ObtenerProveedor($id)
    {
        $id = intval($id);

        $sql = $this->db->SQL("
            SELECT * 
            FROM proveedor
            WHERE id = {$id}
            LIMIT 1
        ");

        return $sql->fetch_assoc();
    }

    /* ============================================================
       📌 ACTIVAR PROVEEDOR
    ============================================================ */
    public function ActivarProveedor()
    {
        if (!isset($_POST['ActivarProveedor'])) return;

        $id = intval($_POST['id']);

        $this->db->SQL("UPDATE proveedor SET habilitado = 1 WHERE id = {$id}");

        echo '<meta http-equiv="refresh" content="0;url=' . URLBASE . 'proveedores.php">';
    }

    /* ============================================================
       📌 DESACTIVAR PROVEEDOR
    ============================================================ */
    public function DesactivarProveedor()
    {
        if (!isset($_POST['DesactivarProveedor'])) return;

        $id = intval($_POST['id']);

        $this->db->SQL("UPDATE proveedor SET habilitado = 0 WHERE id = {$id}");

        echo '<meta http-equiv="refresh" content="0;url=' . URLBASE . 'proveedores.php">';
    }

    /* ============================================================
       📌 ELIMINAR PROVEEDOR
    ============================================================ */
    public function EliminarProveedor()
    {
        if (!isset($_POST['EliminarProveedor'])) return;

        $id = intval($_POST['id']);

        $this->db->SQL("DELETE FROM proveedor WHERE id = {$id}");

        echo '<div class="alert alert-success">Proveedor eliminado correctamente.</div>';
        echo '<meta http-equiv="refresh" content="1;url=' . URLBASE . 'proveedores.php">';
    }

    /* ============================================================
       📌 LISTAR PROVEEDORES PARA SELECT (CAMPOS LIGEROS)
    ============================================================ */
    public function SelectorProveedores()
    {
        return $this->db->SQL("
            SELECT id, nombre, tipo_proveedor
            FROM proveedor
            WHERE habilitado = 1
            ORDER BY nombre ASC
        ");
    }

    /* ============================================================
       📌 OBTENER SALDO DEL PROVEEDOR
    ============================================================ */
    public function ObtenerSaldo($idProveedor)
    {
        $idProveedor = intval($idProveedor);

        $sql = $this->db->SQL("
            SELECT saldo_pendiente
            FROM proveedor
            WHERE id = {$idProveedor}
            LIMIT 1
        ");

        $row = $sql->fetch_assoc();
        return $row ? $row['saldo_pendiente'] : 0.00;
    }

    /* ============================================================
       📌 ACTUALIZAR SALDO (FACTURAS Y PAGOS)
    ============================================================ */
    public function ActualizarSaldo($idProveedor)
    {
        $idProveedor = intval($idProveedor);

        // Total facturas pendientes
        $facturas = $this->db->SQL("
            SELECT SUM(monto_total - monto_pagado) AS deuda
            FROM proveedor_factura
            WHERE id_proveedor = {$idProveedor}
        ")->fetch_assoc()['deuda'];

        $deuda = $facturas ? $facturas : 0;

        // Actualizar en tabla proveedor
        $this->db->SQL("
            UPDATE proveedor
            SET saldo_pendiente = {$deuda}
            WHERE id = {$idProveedor}
        ");

        return $deuda;
    }

    /* ============================================================
       📌 KPI PROVEEDORES
    ============================================================ */
    public function KPIs()
    {
        return $this->db->SQL("
            SELECT
                (SELECT COUNT(*) FROM proveedor) AS total,
                (SELECT COUNT(*) FROM proveedor WHERE habilitado=1) AS activos,
                (SELECT COUNT(*) FROM proveedor WHERE habilitado=0) AS inactivos,
                (SELECT SUM(saldo_pendiente) FROM proveedor) AS deuda_total
        ")->fetch_assoc();
    }

}

?>