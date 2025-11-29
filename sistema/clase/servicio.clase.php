<?php

class Servicio {

    private $db;

    // Configuración "dinámica" básica (podemos pasarla luego a BD si quieres)
    private $config = [
        'tipos_servicio'       => 'PASAJE,PAQUETE,SEGURO,TRAMITE,OTRO',
        'proveedor_obligatorio'=> 0,    // 1 = obligatorio, 0 = opcional
        'impuesto_servicio'    => 0.0   // % de impuesto
    ];

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /* ====== CONFIGURACIÓN ====== */

    private function getConfig($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    public function obtenerTiposDeServicio() {
        $tiposServicio = $this->getConfig('tipos_servicio', 'PASAJE,PAQUETE,SEGURO,TRAMITE,OTRO');
        $arr = array_map('trim', explode(',', $tiposServicio));
        return array_filter($arr);
    }

    public function requiereProveedor() {
        return (int)$this->getConfig('proveedor_obligatorio', 0) === 1;
    }

    public function obtenerImpuesto() {
        return (float)$this->getConfig('impuesto_servicio', 0);
    }

    /* ====== CRUD SERVICIOS ====== */

    // Listar servicios con filtros
    public function obtenerServicios($filtro_tipo = '', $filtro_proveedor = '') {
        $cond = " WHERE p.habilitado = 1 ";

        if ($filtro_tipo != '') {
            $cond .= " AND p.tipo_servicio='" . addslashes($filtro_tipo) . "' ";
        }

        if ($filtro_proveedor != '') {
            $cond .= " AND p.proveedor='" . intval($filtro_proveedor) . "' ";
        }

        $sql = "
            SELECT 
                p.*, 
                pr.nombre AS proveedor_nombre
            FROM producto p
            LEFT JOIN proveedor pr ON pr.id = p.proveedor
            $cond
            ORDER BY p.id DESC
        ";

        return $this->db->SQL($sql);
    }

    // Crear nuevo servicio
    public function crearServicio($nombre, $codigo, $tipo_servicio, $proveedor, $precio_costo, $precio_venta, $comision) {

        $impuesto     = $this->obtenerImpuesto();
        $precio_final = $precio_venta + ($precio_venta * $impuesto / 100);

        $nombre        = addslashes($nombre);
        $codigo        = addslashes($codigo);
        $tipo_servicio = addslashes($tipo_servicio);
        $comision      = (float)$comision;
        $precio_costo  = (float)$precio_costo;
        $precio_final  = (float)$precio_final;
        $proveedor     = ($proveedor !== '' ? (int)$proveedor : 'NULL');

        $sql = "
            INSERT INTO producto
                (nombre, codigo, tipo_servicio, proveedor, preciocosto, precioventa, comision, habilitado)
            VALUES
                ('$nombre', '$codigo', '$tipo_servicio', $proveedor, '$precio_costo', '$precio_final', '$comision', 1)
        ";

        $this->db->SQL($sql);
    }

    // Editar servicio
    public function editarServicio($id, $nombre, $codigo, $tipo_servicio, $proveedor, $precio_costo, $precio_venta, $comision) {

        $impuesto     = $this->obtenerImpuesto();
        $precio_final = $precio_venta + ($precio_venta * $impuesto / 100);

        $id           = (int)$id;
        $nombre       = addslashes($nombre);
        $codigo       = addslashes($codigo);
        $tipo_servicio= addslashes($tipo_servicio);
        $comision     = (float)$comision;
        $precio_costo = (float)$precio_costo;
        $precio_final = (float)$precio_final;
        $proveedor    = ($proveedor !== '' ? (int)$proveedor : 'NULL');

        $sql = "
            UPDATE producto
            SET 
                nombre      = '$nombre',
                codigo      = '$codigo',
                tipo_servicio = '$tipo_servicio',
                proveedor   = $proveedor,
                preciocosto = '$precio_costo',
                precioventa = '$precio_final',
                comision    = '$comision'
            WHERE id = $id
        ";

        $this->db->SQL($sql);
    }

    // Eliminar servicio (borrado lógico recomendado)
    public function eliminarServicio($id) {
        $id = (int)$id;
        // recomendado: deshabilitar en lugar de borrar
        $sql = "UPDATE producto SET habilitado = 0 WHERE id = $id";
        $this->db->SQL($sql);
    }

    // Activar / desactivar
    public function cambiarEstado($id, $estado) {
        $id     = (int)$id;
        $estado = $estado ? 1 : 0;
        $sql = "UPDATE producto SET habilitado = $estado WHERE id = $id";
        $this->db->SQL($sql);
    }
}
