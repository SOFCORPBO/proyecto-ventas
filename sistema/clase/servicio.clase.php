<?php

class Servicio extends Conexion {

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
public function EditarServicio() {

    if(!isset($_POST['EditarServicio'])) return;

    $db = $this->Conectar();

    $id             = (int)$_POST['IdServicio'];
    $codigo         = $db->real_escape_string($_POST['Codigo']);
    $nombre         = $db->real_escape_string($_POST['Nombre']);
    $tipo           = $db->real_escape_string($_POST['TipoServicio']);
    $descripcion    = $db->real_escape_string($_POST['DescripcionServicio']);
    $requiereBoleto = (int)$_POST['RequiereBoleto'];
    $requiereVisa   = (int)$_POST['RequiereVisa'];
    $precioCosto    = (float)$_POST['PrecioCosto'];
    $precioVenta    = (float)$_POST['PrecioVenta'];
    $iva            = (float)$_POST['IVA'];
    $comision       = (float)$_POST['Comision'];
    $esComisionable = (int)$_POST['EsComisionable'];
    $proveedor      = (int)$_POST['Proveedor'];
    $categoria      = (int)$_POST['Categoria'];
    $especifica     = $db->real_escape_string($_POST['Especificaciones']);

    $sql = "
        UPDATE producto SET
            codigo          = '$codigo',
            nombre          = '$nombre',
            tipo_servicio   = '$tipo',
            descripcion     = '$descripcion',
            requiere_boleto = $requiereBoleto,
            requiere_visa   = $requiereVisa,
            preciocosto     = $precioCosto,
            precioventa     = $precioVenta,
            iva             = $iva,
            comision        = $comision,
            es_comisionable = $esComisionable,
            proveedor       = $proveedor,
            categoria_id    = $categoria,
            especificaciones= '$especifica'
        WHERE id = $id
    ";

    if($db->query($sql)) {
        echo '
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            Servicio actualizado correctamente.
        </div>
        <meta http-equiv="refresh" content="1;url='.URLBASE.'servicios.php" />';
    } else {
        echo '
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            Error al actualizar el servicio.
        </div>';
    }
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