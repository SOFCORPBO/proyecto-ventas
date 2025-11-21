<?php

class Productos extends Conexion {

    /*
    |-----------------------------------------------------------|
    | CREAR SERVICIO (antes CrearProducto)
    |   - Sin stock
    |   - Sin inventario
    |   - Solo catálogo de servicios
    |-----------------------------------------------------------|
    */
    public function CrearProducto(){
        if(isset($_POST['CrearProducto'])){

            // Campos básicos
            $Codigo         = isset($_POST['Codigo']) ? filter_var($_POST['Codigo'], FILTER_SANITIZE_STRING) : '';
            $Nombre         = isset($_POST['Nombre']) ? filter_var($_POST['Nombre'], FILTER_SANITIZE_STRING) : '';
            $PrecioCosto    = isset($_POST['PrecioCosto']) ? filter_var($_POST['PrecioCosto'], FILTER_SANITIZE_STRING) : '0';
            $PrecioVenta    = isset($_POST['PrecioVenta']) ? filter_var($_POST['PrecioVenta'], FILTER_SANITIZE_STRING) : '0';
            $Proveedor      = isset($_POST['Proveedor']) ? filter_var($_POST['Proveedor'], FILTER_VALIDATE_INT) : 0;

            // Campos específicos para servicios de agencia
            $TipoServicio   = isset($_POST['TipoServicio'])
                                ? filter_var($_POST['TipoServicio'], FILTER_SANITIZE_STRING)
                                : 'OTRO'; // PASAJE / PAQUETE / SEGURO / TRAMITE / OTRO

            $Descripcion    = isset($_POST['DescripcionServicio'])
                                ? filter_var($_POST['DescripcionServicio'], FILTER_SANITIZE_STRING)
                                : '';

            $RequiereBoleto = isset($_POST['RequiereBoleto']) ? 1 : 0;
            $RequiereVisa   = isset($_POST['RequiereVisa'])   ? 1 : 0;

            $Comision       = isset($_POST['Comision'])
                                ? filter_var($_POST['Comision'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
                                : 0;

            $EsComisionable = isset($_POST['EsComisionable']) ? 1 : 0;

            // Mayúsculas
            $Codigo = strtoupper($Codigo);
            $Nombre = ucwords($Nombre);

            // Insert SOLO con campos de servicios (catálogo)
            $CrearProductoSql = $this->Conectar()->query("
                INSERT INTO `producto` (
                    `codigo`,
                    `nombre`,
                    `tipo_servicio`,
                    `descripcion`,
                    `proveedor`,
                    `preciocosto`,
                    `precioventa`,
                    `comision`,
                    `es_comisionable`,
                    `requiere_boleto`,
                    `requiere_visa`,
                    `habilitado`
                ) VALUES (
                    '{$Codigo}',
                    '{$Nombre}',
                    '{$TipoServicio}',
                    '{$Descripcion}',
                    '{$Proveedor}',
                    '{$PrecioCosto}',
                    '{$PrecioVenta}',
                    '{$Comision}',
                    '{$EsComisionable}',
                    '{$RequiereBoleto}',
                    '{$RequiereVisa}',
                    '1'
                )
            ");

            if($CrearProductoSql == true){
                echo '
                <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Excelente</strong> El servicio "'.$Nombre.'" ha sido creado con &eacute;xito.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'nuevo-producto"/>';
            }else{
                echo '
                <div class="alert alert-dismissible alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Oh no!</strong> Ha ocurrido un error al crear el servicio "'.$Nombre.'", por favor int&eacute;ntalo de nuevo.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'nuevo-producto"/>';
            }
        }
    }

    /*
    |-----------------------------------------------------------|
    | ACTUALIZAR INVENTARIO (DESHABILITADO EN SISTEMA DE SERVICIOS)
    |   - Se deja solo para no romper estructura si se llama
    |-----------------------------------------------------------|
    */
    public function ActualizarInventario(){
        if(isset($_POST['ActualizarInventario'])){
            echo '
            <div class="alert alert-dismissible alert-warning">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>Funci&oacute;n no disponible.</strong> El sistema est&aacute; configurado para manejar <b>servicios</b>, no inventario f&iacute;sico.
            </div>
            <meta http-equiv="refresh" content="2;url='.URLBASE.'productos"/>';
        }
    }
	

	/*
|-----------------------------------------------------------|
| ELIMINAR SERVICIO
|-----------------------------------------------------------|
*/
public function EliminarServicio() {

    if(isset($_POST['EliminarServicio'])){

        $IdServicio = filter_var($_POST['IdServicio'], FILTER_VALIDATE_INT);

        // OPCIONAL: evitar eliminar si el servicio ya fue usado en ventas
        /*
        $check = $this->Conectar()->query("
            SELECT COUNT(*) AS total FROM detalleventa WHERE producto='{$IdServicio}'
        ");
        $resp = $check->fetch_assoc();
        if($resp['total'] > 0){
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                No se puede eliminar. El servicio ya fue utilizado en ventas.
            </div>';
            return;
        }
        */

        $Eliminar = $this->Conectar()->query("
            DELETE FROM producto WHERE id='{$IdServicio}'
        ");

        if($Eliminar){
            echo '
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Servicio eliminado correctamente.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'productos" />';
        } else {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Error al eliminar el servicio.
            </div>';
        }
    }
}

    /*
    |-----------------------------------------------------------|
    | DEPARTAMENTOS (pueden usarse como categorías de servicios)
    |-----------------------------------------------------------|
    */
    public function CrearDepartamentos(){

        if(isset($_POST['CrearDepartamento'])){
            $Nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
            $Estado = filter_var($_POST['estado'], FILTER_VALIDATE_INT);
            $Nombre = ucwords($Nombre);

            $CrearCategoriaSql = $this->Conectar()->query("
                INSERT INTO `departamento` (`nombre`, `habilitada`)
                VALUES ('{$Nombre}', '{$Estado}')
            ");

            if($CrearCategoriaSql == true){
                echo'
                <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Excelente</strong> El departamento "'.$Nombre.'" ha sido creado con &eacute;xito.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'departamentos"/>';
            }else{
                echo'
                <div class="alert alert-dismissible alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Oh no!</strong> Ha ocurrido un error al crear el departamento "'.$Nombre.'", por favor int&eacute;ntalo de nuevo.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'departamentos"/>';
            }
        }
    }

    /*
    |-----------------------------------------------------------|
    | PROVEEDORES (aerolíneas, hoteles, aseguradoras, consulados)
    |-----------------------------------------------------------|
    */
    public function CrearProveedor(){

        if(isset($_POST['CrearProveedor'])){
            $Nombre     = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
            $Telefono   = filter_var($_POST['telefono'], FILTER_SANITIZE_STRING);
            $Contacto   = filter_var($_POST['contacto'], FILTER_SANITIZE_STRING);
            $Direccion  = filter_var($_POST['direccion'], FILTER_SANITIZE_STRING);
            $Estado     = filter_var($_POST['estado'], FILTER_VALIDATE_INT);
            $Nombre     = ucwords($Nombre);

            $CrearProveedorSql = $this->Conectar()->query("
                INSERT INTO `proveedor` (`nombre`, `telefono`, `contacto`, `direccion`, `habilitado`)
                VALUES ('{$Nombre}', '{$Telefono}', '{$Contacto}', '{$Direccion}', '{$Estado}')
            ");

            if($CrearProveedorSql == true){
                echo'
                <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Excelente</strong> El proveedor "'.$Nombre.'" ha sido creado con &eacute;xito.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'proveedores"/>';
            }else{
                echo'
                <div class="alert alert-dismissible alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Oh no!</strong> Ha ocurrido un error al crear el proveedor "'.$Nombre.'", por favor int&eacute;ntalo de nuevo.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'proveedores"/>';
            }
        }
    }

    public function EliminarProveedor(){

        if(isset($_POST['EliminarProveedor'])){
            $IdProveedor = filter_var($_POST['IdProveedor'], FILTER_VALIDATE_INT);
            $Nombre      = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
            $Nombre      = ucwords($Nombre);

            $EliminarProveedorSql = $this->Conectar()->query("
                DELETE FROM `proveedor` WHERE `id` = '{$IdProveedor}'
            ");

            if($EliminarProveedorSql == true){
                echo'
                <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Excelente</strong> El proveedor "'.$Nombre.'" ha sido eliminado con &eacute;xito.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'proveedores"/>';
            }else{
                echo'
                <div class="alert alert-dismissible alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Oh no!</strong> Ha ocurrido un error al eliminar el proveedor "'.$Nombre.'", por favor int&eacute;ntalo de nuevo.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'proveedores"/>';
            }
        }
    }

    public function EditarProveedor(){

        if(isset($_POST['EditarProveedor'])){
            $IdProveedor= filter_var($_POST['IdProveedor'], FILTER_VALIDATE_INT);
            $Nombre     = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
            $Telefono   = filter_var($_POST['telefono'], FILTER_SANITIZE_STRING);
            $Contacto   = filter_var($_POST['contacto'], FILTER_SANITIZE_STRING);
            $Direccion  = filter_var($_POST['direccion'], FILTER_SANITIZE_STRING);
            $Estado     = filter_var($_POST['estado'], FILTER_VALIDATE_INT);
            $Nombre     = ucwords($Nombre);

            $EditarProveedorSql = $this->Conectar()->query("
                UPDATE `proveedor` SET
                    `nombre`     = '{$Nombre}',
                    `telefono`   = '{$Telefono}',
                    `contacto`   = '{$Contacto}',
                    `direccion`  = '{$Direccion}',
                    `habilitado` = '{$Estado}'
                WHERE `id` = '{$IdProveedor}'
            ");

            if($EditarProveedorSql == true){
                echo'
                <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Excelente</strong> El proveedor "'.$Nombre.'" ha sido editado con &eacute;xito.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'proveedores"/>';
            }else{
                echo'
                <div class="alert alert-dismissible alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Oh no!</strong> Ha ocurrido un error al editar el proveedor "'.$Nombre.'", por favor int&eacute;ntalo de nuevo.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'proveedores"/>';
            }
        }
    }

	
/*  
|-----------------------------------------------------------|
| ACTIVAR SERVICIO (estado = 1)
|-----------------------------------------------------------|
------------------------------------------------------|
*/
public function ActivarServicio() {

    if(isset($_POST['ActivarServicio'])){

        $IdServicio = filter_var($_POST['IdServicio'], FILTER_VALIDATE_INT);

        $SQL = $this->Conectar()->query("
            UPDATE producto SET habilitado='1' WHERE id='{$IdServicio}'
        ");

        if($SQL){
            echo '
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Servicio activado correctamente.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'productos" />';
        } else {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Error al activar el servicio.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'productos" />';
        }
    }
}


/*
|-----------------------------------------------------------|
| DESACTIVAR SERVICIO (estado = 0)
|-----------------------------------------------------------|
*/
public function DesactivarServicio() {

    if(isset($_POST['DesactivarServicio'])){

        $IdServicio = filter_var($_POST['IdServicio'], FILTER_VALIDATE_INT);

        $SQL = $this->Conectar()->query("
            UPDATE producto SET habilitado='0' WHERE id='{$IdServicio}'
        ");

        if($SQL){
            echo '
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Servicio desactivado correctamente.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'productos" />';
        } else {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                Error al desactivar el servicio.
            </div>
            <meta http-equiv="refresh" content="0;url='.URLBASE.'productos" />';
        }
    }
}


    /*
    |-----------------------------------------------------------|
    | CARGAR SERVICIO POR ID (para editar)
    |-----------------------------------------------------------|
    */
    public function URLProductoID(){

        global $ProductoID;
        global $ProductoIDSql;

        if (isset($_GET['id'])){
            $ProductoIDSql = $this->Conectar()->query("
                SELECT * FROM `producto` WHERE id='{$_GET['id']}'
            ");
            $ProductoID = $ProductoIDSql->fetch_assoc();
            if (!$ProductoID['id']){
                $error = true;
            }
        }else{
            $error = true;
        }
    }

    /*
    |-----------------------------------------------------------|
    | EDITAR SERVICIO (antes EditarProducto)
    |-----------------------------------------------------------|
    */
    public function EditarProducto(){
        if(isset($_POST['EditarProducto'])){
            $IdProducto     = filter_var($_POST['Id'], FILTER_VALIDATE_INT);
            $Codigo         = isset($_POST['Codigo']) ? filter_var($_POST['Codigo'], FILTER_SANITIZE_STRING) : '';
            $Nombre         = isset($_POST['Nombre']) ? filter_var($_POST['Nombre'], FILTER_SANITIZE_STRING) : '';
            $PrecioCosto    = isset($_POST['PrecioCosto']) ? filter_var($_POST['PrecioCosto'], FILTER_SANITIZE_STRING) : '0';
            $PrecioVenta    = isset($_POST['PrecioVenta']) ? filter_var($_POST['PrecioVenta'], FILTER_SANITIZE_STRING) : '0';
            $Proveedor      = isset($_POST['Proveedor']) ? filter_var($_POST['Proveedor'], FILTER_VALIDATE_INT) : 0;

            $TipoServicio   = isset($_POST['TipoServicio'])
                                ? filter_var($_POST['TipoServicio'], FILTER_SANITIZE_STRING)
                                : 'OTRO';

            $Descripcion    = isset($_POST['DescripcionServicio'])
                                ? filter_var($_POST['DescripcionServicio'], FILTER_SANITIZE_STRING)
                                : '';

            $RequiereBoleto = isset($_POST['RequiereBoleto']) ? 1 : 0;
            $RequiereVisa   = isset($_POST['RequiereVisa'])   ? 1 : 0;

            $Comision       = isset($_POST['Comision'])
                                ? filter_var($_POST['Comision'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
                                : 0;

            $EsComisionable = isset($_POST['EsComisionable']) ? 1 : 0;

            // Mayúsculas
            $Codigo = strtoupper($Codigo);
            $Nombre = ucwords($Nombre);

            $EditarProductoSql = $this->Conectar()->query("
                UPDATE `producto` SET
                    `codigo`          = '{$Codigo}',
                    `nombre`          = '{$Nombre}',
                    `tipo_servicio`   = '{$TipoServicio}',
                    `descripcion`     = '{$Descripcion}',
                    `proveedor`       = '{$Proveedor}',
                    `preciocosto`     = '{$PrecioCosto}',
                    `precioventa`     = '{$PrecioVenta}',
                    `comision`        = '{$Comision}',
                    `es_comisionable` = '{$EsComisionable}',
                    `requiere_boleto` = '{$RequiereBoleto}',
                    `requiere_visa`   = '{$RequiereVisa}'
                WHERE `id` = '{$IdProducto}'
            ");

            if($EditarProductoSql == true){
                echo'
                <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Excelente</strong> El servicio "'.$Nombre.'" ha sido actualizado con &eacute;xito.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'productos"/>';
            }else{
                echo'
                <div class="alert alert-dismissible alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>&iexcl;Oh no!</strong> Ha ocurrido un error al actualizar el servicio "'.$Nombre.'", por favor int&eacute;ntalo de nuevo.
                </div>
                <meta http-equiv="refresh" content="0;url='.URLBASE.'productos"/>';
            }
        }
    }
}