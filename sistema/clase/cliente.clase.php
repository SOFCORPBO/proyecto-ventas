<?php

class Cliente extends Conexion {

    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /* =====================================================
       📌 CREAR CLIENTE
    ====================================================== */
    public function CrearCliente()
    {
        if (!isset($_POST['CrearCliente'])) return;

        $nombre         = ucwords($_POST['nombre']);
        $ci_pasaporte   = $_POST['ci_pasaporte'];
        $tipo_documento = $_POST['tipo_documento'];
        $nacionalidad   = $_POST['nacionalidad'];
        $fecha_nac      = $_POST['fecha_nacimiento'];
        $telefono       = $_POST['telefono'];
        $email          = $_POST['email'];
        $direccion      = $_POST['direccion'];
        $descuento      = floatval($_POST['descuento']);
        $estado         = isset($_POST['habilitado']) ? 1 : 0;

        $this->db->SQL("
            INSERT INTO cliente (
                nombre, ci_pasaporte, tipo_documento, nacionalidad,
                fecha_nacimiento, telefono, email, direccion,
                descuento, habilitado
            ) VALUES (
                '$nombre', '$ci_pasaporte', '$tipo_documento', '$nacionalidad',
                '$fecha_nac', '$telefono', '$email', '$direccion',
                '$descuento', '$estado'
            )
        ");

        echo '<div class="alert alert-success">Cliente registrado correctamente.</div>';
        echo '<meta http-equiv="refresh" content="1;url='.URLBASE.'cliente.php">';
    }

    /* =====================================================
       📌 EDITAR CLIENTE
    ====================================================== */
    public function EditarCliente()
    {
        if (!isset($_POST['EditarCliente'])) return;

        $id             = intval($_POST['id']);
        $nombre         = ucwords($_POST['nombre']);
        $ci_pasaporte   = $_POST['ci_pasaporte'];
        $tipo_documento = $_POST['tipo_documento'];
        $nacionalidad   = $_POST['nacionalacionalidad'];
        $fecha_nac      = $_POST['fecha_nacimiento'];
        $telefono       = $_POST['telefono'];
        $email          = $_POST['email'];
        $direccion      = $_POST['direccion'];
        $descuento      = floatval($_POST['descuento']);
        $habilitado     = isset($_POST['habilitado']) ? 1 : 0;

        $this->db->SQL("
            UPDATE cliente SET
                nombre='$nombre',
                ci_pasaporte='$ci_pasaporte',
                tipo_documento='$tipo_documento',
                nacionalidad='$nacionalidad',
                fecha_nacimiento='$fecha_nac',
                telefono='$telefono',
                email='$email',
                direccion='$direccion',
                descuento='$descuento',
                habilitado='$habilitado'
            WHERE id=$id
        ");

        echo '<div class="alert alert-success">Cliente actualizado correctamente.</div>';
        echo '<meta http-equiv="refresh" content="1;url='.URLBASE.'cliente.php">';
    }

    /* =====================================================
       📌 LISTAR CLIENTES
    ====================================================== */
    public function ListarClientes()
    {
        return $this->db->SQL("
            SELECT * FROM cliente ORDER BY id DESC
        ");
    }

    /* =====================================================
       📌 OBTENER CLIENTE POR ID
    ====================================================== */
    public function ObtenerClientePorId($id)
    {
        $id = intval($id);
        return $this->db->SQL("
            SELECT * FROM cliente WHERE id=$id LIMIT 1
        ")->fetch_assoc();
    }

    /* =====================================================
       📌 ACTIVAR CLIENTE
    ====================================================== */
    public function ActivarCliente()
    {
        if (!isset($_POST['ActivarCliente'])) return;

        $id = intval($_POST['id']);

        $this->db->SQL("UPDATE cliente SET habilitado=1 WHERE id=$id");

        echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cliente.php">';
    }

    /* =====================================================
       📌 DESACTIVAR CLIENTE
    ====================================================== */
    public function DesactivarCliente()
    {
        if (!isset($_POST['DesactivarCliente'])) return;

        $id = intval($_POST['id']);

        $this->db->SQL("UPDATE cliente SET habilitado=0 WHERE id=$id");

        echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cliente.php">';
    }

    /* =====================================================
       📌 ELIMINAR CLIENTE
    ====================================================== */
    public function EliminarCliente()
    {
        if (!isset($_POST['EliminarCliente'])) return;

        $id = intval($_POST['id']);

        $this->db->SQL("DELETE FROM cliente WHERE id=$id");

        echo '<div class="alert alert-success">Cliente eliminado.</div>';
        echo '<meta http-equiv="refresh" content="1;url='.URLBASE.'cliente.php">';
    }

    /* =====================================================
       📌 EXPEDIENTE — TRÁMITES ASOCIADOS
    ====================================================== */
    public function TramitesCliente($idCliente)
    {
        return $this->db->SQL("
            SELECT *
            FROM tramites
            WHERE id_cliente=$idCliente
            ORDER BY id DESC
        ");
    }

    /* =====================================================
       📌 EXPEDIENTE — HISTORIAL DE SERVICIOS (ventas)
    ====================================================== */
    public function HistorialServicios($idCliente)
    {
        return $this->db->SQL("
            SELECT v.*, p.nombre AS servicio
            FROM ventas v
            LEFT JOIN producto p ON p.id=v.idproducto
            WHERE v.id_cliente=$idCliente
            ORDER BY v.id DESC
        ");
    }

    /* =====================================================
       📌 EXPEDIENTE — COTIZACIONES
    ====================================================== */
    public function CotizacionesCliente($idCliente)
    {
        return $this->db->SQL("
            SELECT *
            FROM cotizacion
            WHERE id_cliente=$idCliente
            ORDER BY id DESC
        ");
    }

    /* =====================================================
       📌 EXPEDIENTE — ALERTAS DE TRÁMITES
    ====================================================== */
    public function AlertasCliente($idCliente)
    {
        return $this->db->SQL("
            SELECT n.*
            FROM notificaciones n
            INNER JOIN tramites t ON t.id=n.id_tramite
            WHERE t.id_cliente=$idCliente
            ORDER BY fecha_alerta DESC
        ");
    }

    /* =====================================================
       📌 DASHBOARD DEL CLIENTE (KPIs)
    ====================================================== */
    public function DashboardCliente($idCliente)
    {
        return $this->db->SQL("
            SELECT  
                (SELECT COUNT(*) FROM tramites WHERE id_cliente=$idCliente) AS tramites,
                (SELECT COUNT(*) FROM ventas WHERE id_cliente=$idCliente) AS servicios,
                (SELECT COUNT(*) FROM cotizacion WHERE id_cliente=$idCliente) AS cotizaciones,
                (SELECT COUNT(*)
                    FROM notificaciones n
                    INNER JOIN tramites t ON t.id=n.id_tramite
                    WHERE t.id_cliente=$idCliente
                    AND n.estado_alerta='pendiente'
                ) AS alertas
        ")->fetch_assoc();
    }

}

?>