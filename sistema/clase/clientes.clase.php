<?php

class Clientes extends Conexion {

    /*
    |-----------------------------------------------------------|
    | CREAR CLIENTE
    |-----------------------------------------------------------|
    */
    public function CrearCliente(){

        if(isset($_POST['CrearCliente'])){

            $nombre            = ucwords(filter_var($_POST['nombre'], FILTER_SANITIZE_STRING));
            $tipo_documento    = filter_var($_POST['tipo_documento'], FILTER_SANITIZE_STRING);
            $numero_documento  = filter_var($_POST['numero_documento'], FILTER_SANITIZE_STRING);
            $nacionalidad      = filter_var($_POST['nacionalidad'], FILTER_SANITIZE_STRING);
            $fecha_nacimiento  = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : NULL;
            $telefono          = filter_var($_POST['telefono'], FILTER_SANITIZE_STRING);
            $correo            = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
            $requiere_visa     = isset($_POST['requiere_visa']) ? 1 : 0;
            $observaciones     = filter_var($_POST['observaciones'], FILTER_SANITIZE_STRING);

            $sql = $this->Conectar()->query("
                INSERT INTO cliente (
                    nombre,
                    tipo_documento,
                    numero_documento,
                    nacionalidad,
                    fecha_nacimiento,
                    telefono,
                    correo,
                    requiere_visa,
                    observaciones,
                    habilitado
                ) VALUES (
                    '{$nombre}',
                    '{$tipo_documento}',
                    '{$numero_documento}',
                    '{$nacionalidad}',
                    " . ($fecha_nacimiento ? "'{$fecha_nacimiento}'" : "NULL") . ",
                    '{$telefono}',
                    '{$correo}',
                    '{$requiere_visa}',
                    '{$observaciones}',
                    1
                )
            ");

            if($sql){
                echo '<div class="alert alert-success">Cliente registrado correctamente.</div>
                      <meta http-equiv="refresh" content="1;url='.URLBASE.'clientes" />';
            }else{
                echo '<div class="alert alert-danger">Error al registrar cliente.</div>';
            }
        }
    }

    /*
    |-----------------------------------------------------------|
    | EDITAR CLIENTE
    |-----------------------------------------------------------|
    */
    public function EditarCliente(){

        if(isset($_POST['EditarCliente'])){

            $id                = filter_var($_POST['id'], FILTER_VALIDATE_INT);
            $nombre            = ucwords(filter_var($_POST['nombre'], FILTER_SANITIZE_STRING));
            $tipo_documento    = filter_var($_POST['tipo_documento'], FILTER_SANITIZE_STRING);
            $numero_documento  = filter_var($_POST['numero_documento'], FILTER_SANITIZE_STRING);
            $nacionalidad      = filter_var($_POST['nacionalidad'], FILTER_SANITIZE_STRING);
            $fecha_nacimiento  = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : NULL;
            $telefono          = filter_var($_POST['telefono'], FILTER_SANITIZE_STRING);
            $correo            = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
            $requiere_visa     = isset($_POST['requiere_visa']) ? 1 : 0;
            $observaciones     = filter_var($_POST['observaciones'], FILTER_SANITIZE_STRING);

            $sql = $this->Conectar()->query("
                UPDATE cliente SET
                    nombre            = '{$nombre}',
                    tipo_documento    = '{$tipo_documento}',
                    numero_documento  = '{$numero_documento}',
                    nacionalidad      = '{$nacionalidad}',
                    fecha_nacimiento  = " . ($fecha_nacimiento ? "'{$fecha_nacimiento}'" : "NULL") . ",
                    telefono          = '{$telefono}',
                    correo            = '{$correo}',
                    requiere_visa     = '{$requiere_visa}',
                    observaciones     = '{$observaciones}'
                WHERE id='{$id}'
            ");

            if($sql){
                echo '<div class="alert alert-success">Cambios guardados.</div>
                      <meta http-equiv="refresh" content="1;url='.URLBASE.'clientes" />';
            }else{
                echo '<div class="alert alert-danger">Error al editar cliente.</div>';
            }
        }
    }

    /*
    |-----------------------------------------------------------|
    | ACTIVAR / DESACTIVAR CLIENTE
    |-----------------------------------------------------------|
    */
    public function CambiarEstado(){

        if(isset($_POST['CambiarEstado'])){

            $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
            $estado = filter_var($_POST['estado'], FILTER_VALIDATE_INT);

            $sql = $this->Conectar()->query("
                UPDATE cliente SET habilitado='{$estado}'
                WHERE id='{$id}'
            ");

            if($sql){
                echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'clientes" />';
            }
        }
    }

    /*
    |-----------------------------------------------------------|
    | CARGAR CLIENTE POR ID
    |-----------------------------------------------------------|
    */
    public function URLClienteID(){
        global $ClienteID;
        global $ClienteIDSql;

        if (isset($_GET['id'])){
            $ClienteIDSql = $this->Conectar()->query("
                SELECT * FROM cliente WHERE id='{$_GET['id']}'
            ");
            $ClienteID = $ClienteIDSql->fetch_assoc();
        }
    }
}

$ClientesClase = new Clientes();