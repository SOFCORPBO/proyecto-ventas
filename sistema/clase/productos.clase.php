<?php

class Productos extends Conexion
{
    /* ============================================================
     *  🔵 FUNCIONES DE CONSULTA
     * ============================================================ */

    // Obtener todos los servicios habilitados o con condiciones
    public function obtenerProductos($condiciones = "")
    {
        $sql = "SELECT * FROM producto WHERE 1=1 {$condiciones}";
        return $this->Conectar()->query($sql);
    }

    // Obtener servicios por tipo
    public function obtenerPorTipo($tipo)
    {
        $tipo = $this->Conectar()->real_escape_string($tipo);
        return $this->Conectar()->query("
            SELECT * FROM producto WHERE tipo_servicio = '{$tipo}'
        ");
    }

    // Cargar servicio por ID
    public function obtenerServicioPorId($id)
    {
        $id = (int)$id;
        return $this->Conectar()->query("
            SELECT * FROM producto WHERE id = {$id} LIMIT 1
        ");
    }


    /* ============================================================
     *  🟢 CREAR SERVICIO (CATÁLOGO DE SERVICIOS)
     * ============================================================ */
    public function CrearProducto()
    {
        if (!isset($_POST['CrearProducto'])) return;

        $db = $this->Conectar();

        $Codigo         = strtoupper($db->real_escape_string($_POST['Codigo']));
        $Nombre         = ucwords($db->real_escape_string($_POST['Nombre']));
        $TipoServicio   = $db->real_escape_string($_POST['TipoServicio']);
        $Proveedor      = (int)$_POST['Proveedor'];
        $PrecioCosto    = (float)$_POST['PrecioCosto'];
        $PrecioVenta    = (float)$_POST['PrecioVenta'];
        $Comision       = (float)$_POST['Comision'];
        $EsComisionable = (int)$_POST['EsComisionable'];
        $RequiereBoleto = (int)$_POST['RequiereBoleto'];
        $RequiereVisa   = (int)$_POST['RequiereVisa'];
        $Descripcion    = $db->real_escape_string($_POST['DescripcionServicio']);

        $sql = "
            INSERT INTO producto (
                codigo, nombre, tipo_servicio, descripcion,
                proveedor, preciocosto, precioventa,
                comision, es_comisionable,
                requiere_boleto, requiere_visa, habilitado
            ) VALUES (
                '{$Codigo}', '{$Nombre}', '{$TipoServicio}', '{$Descripcion}',
                {$Proveedor}, {$PrecioCosto}, {$PrecioVenta},
                {$Comision}, {$EsComisionable},
                {$RequiereBoleto}, {$RequiereVisa}, 1
            )
        ";

        if ($db->query($sql)) {
            echo '
            <div class="alert alert-success">Servicio creado correctamente.</div>
            <meta http-equiv="refresh" content="1;url=nuevo-producto">';
        } else {
            echo '<div class="alert alert-danger">Error al crear servicio.</div>';
        }
    }


    /* ============================================================
     *  🟡 EDITAR SERVICIO
     * ============================================================ */
    public function EditarServicio()
    {
        if (!isset($_POST['EditarServicio'])) return;

        $db = $this->Conectar();

        $idServicio     = (int)$_POST['IdServicio'];
        $Nombre         = ucwords($db->real_escape_string($_POST['Nombre']));
        $Codigo         = strtoupper($db->real_escape_string($_POST['Codigo']));
        $TipoServicio   = $db->real_escape_string($_POST['TipoServicio']);
        $Categoria      = ($_POST['Categoria'] != "" ? (int)$_POST['Categoria'] : "NULL");
        $Proveedor      = (int)$_POST['Proveedor'];
        $PrecioCosto    = (float)$_POST['PrecioCosto'];
        $PrecioVenta    = (float)$_POST['PrecioVenta'];
        $IVA            = (float)$_POST['IVA'];
        $Comision       = (float)$_POST['Comision'];
        $EsComisionable = (int)$_POST['EsComisionable'];
        $RequiereBoleto = (int)$_POST['RequiereBoleto'];
        $RequiereVisa   = (int)$_POST['RequiereVisa'];
        $Descripcion    = $db->real_escape_string($_POST['DescripcionServicio']);
        $Especificacion = $db->real_escape_string($_POST['Especificaciones']);

        $sql = "
            UPDATE producto SET
                codigo            = '{$Codigo}',
                nombre            = '{$Nombre}',
                tipo_servicio     = '{$TipoServicio}',
                categoria_id      = {$Categoria},
                proveedor         = {$Proveedor},
                preciocosto       = {$PrecioCosto},
                precioventa       = {$PrecioVenta},
                iva               = {$IVA},
                comision          = {$Comision},
                es_comisionable   = {$EsComisionable},
                requiere_boleto   = {$RequiereBoleto},
                requiere_visa     = {$RequiereVisa},
                descripcion       = '{$Descripcion}',
                especificaciones  = '{$Especificacion}'
            WHERE id = {$idServicio}
            LIMIT 1
        ";

        if ($db->query($sql)) {
            echo '
            <div class="alert alert-success">Servicio actualizado exitosamente.</div>
            <meta http-equiv="refresh" content="1;url=servicios.php">';
        } else {
            echo '<div class="alert alert-danger">Error al actualizar servicio.</div>';
        }
    }


    /* ============================================================
     *  🔴 ELIMINAR SERVICIO
     * ============================================================ */
    public function EliminarServicio()
    {
        if (!isset($_POST['EliminarServicio'])) return;

        $db = $this->Conectar();
        $IdServicio = (int)$_POST['IdServicio'];

        $sql = "DELETE FROM producto WHERE id = {$IdServicio}";

        if ($db->query($sql)) {
            echo '
            <div class="alert alert-success">Servicio eliminado correctamente.</div>
            <meta http-equiv="refresh" content="1;url=servicios.php">';
        } else {
            echo '<div class="alert alert-danger">No se pudo eliminar.</div>';
        }
    }


    /* ============================================================
     *  🟣 ACTIVAR / DESACTIVAR SERVICIO
     * ============================================================ */
    public function ActivarServicio()
    {
        if (!isset($_POST['ActivarServicio'])) return;

        $id = (int)$_POST['IdServicio'];
        $this->Conectar()->query("UPDATE producto SET habilitado = 1 WHERE id = {$id}");

        echo '
        <div class="alert alert-success">Servicio activado.</div>
        <meta http-equiv="refresh" content="1;url=servicios.php">';
    }

    public function DesactivarServicio()
    {
        if (!isset($_POST['DesactivarServicio'])) return;

        $id = (int)$_POST['IdServicio'];
        $this->Conectar()->query("UPDATE producto SET habilitado = 0 WHERE id = {$id}");

        echo '
        <div class="alert alert-warning">Servicio desactivado.</div>
        <meta http-equiv="refresh" content="1;url=servicios.php">';
    }


    /* ============================================================
     *  🟤 CATEGORÍAS DE SERVICIOS (Departamentos)
     * ============================================================ */
    public function CrearDepartamentos()
    {
        if (!isset($_POST['CrearDepartamento'])) return;

        $db = $this->Conectar();

        $Nombre = ucwords($db->real_escape_string($_POST['nombre']));
        $Estado = (int)$_POST['estado'];

        $sql = "
            INSERT INTO departamento (nombre, habilitada)
            VALUES ('{$Nombre}', '{$Estado}')
        ";

        if ($db->query($sql)) {
            echo '<div class="alert alert-success">Categoría creada.</div>';
        } else {
            echo '<div class="alert alert-danger">Error al crear categoría.</div>';
        }
    }


    /* ============================================================
     *  🟠 PROVEEDORES (Aerolíneas, Hoteles, Aseguradoras…)
     * ============================================================ */
    public function CrearProveedor()
    {
        if (!isset($_POST['CrearProveedor'])) return;

        $db = $this->Conectar();

        $Nombre     = ucwords($db->real_escape_string($_POST['nombre']));
        $Telefono   = $db->real_escape_string($_POST['telefono']);
        $Contacto   = $db->real_escape_string($_POST['contacto']);
        $Direccion  = $db->real_escape_string($_POST['direccion']);
        $Estado     = (int)$_POST['estado'];

        $sql = "
            INSERT INTO proveedor (nombre, telefono, contacto, direccion, habilitado)
            VALUES ('{$Nombre}', '{$Telefono}', '{$Contacto}', '{$Direccion}', {$Estado})
        ";

        if ($db->query($sql)) {
            echo '<div class="alert alert-success">Proveedor creado.</div>';
        } else {
            echo '<div class="alert alert-danger">Error al crear proveedor.</div>';
        }
    }

    public function EditarProveedor()
    {
        if (!isset($_POST['EditarProveedor'])) return;

        $db = $this->Conectar();

        $IdProveedor= (int)$_POST['IdProveedor'];
        $Nombre     = ucwords($db->real_escape_string($_POST['nombre']));
        $Telefono   = $db->real_escape_string($_POST['telefono']);
        $Contacto   = $db->real_escape_string($_POST['contacto']);
        $Direccion  = $db->real_escape_string($_POST['direccion']);
        $Estado     = (int)$_POST['estado'];

        $db->query("
            UPDATE proveedor SET
                nombre = '{$Nombre}',
                telefono = '{$Telefono}',
                contacto = '{$Contacto}',
                direccion = '{$Direccion}',
                habilitado = {$Estado}
            WHERE id = {$IdProveedor}
        ");

        echo '<div class="alert alert-success">Proveedor actualizado.</div>';
    }

    public function EliminarProveedor()
    {
        if (!isset($_POST['EliminarProveedor'])) return;

        $id = (int)$_POST['IdProveedor'];
        $this->Conectar()->query("DELETE FROM proveedor WHERE id = {$id}");

        echo '<div class="alert alert-success">Proveedor eliminado.</div>';
    }
}