<?php
class Clientes {

    private function Conectar() {
        return new mysqli(HOST, USER, PASSWORD, DB, PORT);
    }

    /* ========================
       CREAR CLIENTE
    =========================*/
    public function CrearCliente(){
        if(isset($_POST['CrearCliente'])){

            $nombre          = ucwords($_POST['nombre']);
            $ci_pasaporte    = $_POST['ci_pasaporte'];
            $tipo_documento  = $_POST['tipo_documento'];
            $nacionalidad    = $_POST['nacionalidad'];
            $fecha_nac       = $_POST['fecha_nacimiento'];
            $telefono        = $_POST['telefono'];
            $email           = $_POST['email'];
            $direccion       = $_POST['direccion'];
            $descuento       = floatval($_POST['descuento']);
            $estado          = isset($_POST['habilitado']) ? 1 : 0;

            $sql = $this->Conectar()->query("
                INSERT INTO cliente (
                    nombre, ci_pasaporte, tipo_documento, nacionalidad,
                    fecha_nacimiento, telefono, email, direccion,
                    descuento, habilitado
                ) VALUES (
                    '{$nombre}', '{$ci_pasaporte}', '{$tipo_documento}', '{$nacionalidad}',
                    '{$fecha_nac}', '{$telefono}', '{$email}', '{$direccion}',
                    '{$descuento}', '{$estado}'
                )
            ");

            if($sql){
                echo '<div class="alert alert-success">Cliente registrado correctamente.</div>';
                echo '<meta http-equiv="refresh" content="1;url='.URLBASE.'clientes">';
            } else {
                echo '<div class="alert alert-danger">Error al registrar el cliente.</div>';
            }
        }
    }

    /* ========================
       OBTENER CLIENTE POR ID
    =========================*/
    public function ObtenerClientePorId($id){
        $id = intval($id);
        $sql = $this->Conectar()->query("SELECT * FROM cliente WHERE id={$id} LIMIT 1");
        return $sql->fetch_assoc();
    }

    /* ========================
       EDITAR CLIENTE
    =========================*/
    public function EditarCliente(){
        if(isset($_POST['EditarCliente'])){

            $id             = intval($_POST['id']);
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

            $sql = $this->Conectar()->query("
                UPDATE cliente SET
                    nombre='{$nombre}',
                    ci_pasaporte='{$ci_pasaporte}',
                    tipo_documento='{$tipo_documento}',
                    nacionalidad='{$nacionalidad}',
                    fecha_nacimiento='{$fecha_nac}',
                    telefono='{$telefono}',
                    email='{$email}',
                    direccion='{$direccion}',
                    descuento='{$descuento}',
                    habilitado='{$estado}'
                WHERE id='{$id}'
            ");

            if($sql){
                echo '<div class="alert alert-success">Cliente actualizado.</div>';
                echo '<meta http-equiv="refresh" content="1;url='.URLBASE.'clientes">';
            } else {
                echo '<div class="alert alert-danger">Error al actualizar el cliente.</div>';
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


    /* ============================================================
       📌 ACTIVAR CLIENTE
    ============================================================ */
    public function ActivarCliente() {
        if (isset($_POST['ActivarCliente'])) {
            $id = intval($_POST['id']);

            $sql = $this->Conectar()->query("
                UPDATE cliente SET habilitado='1' WHERE id={$id}
            ");

            if ($sql) {
                echo '<div class="alert alert-success">Cliente activado.</div>';
            } else {
                echo '<div class="alert alert-danger">Error al activar cliente.</div>';
            }

            echo '<meta http-equiv="refresh" content="1;url='.URLBASE.'clientes">';
        }
    }

    /* ============================================================
       📌 DESACTIVAR CLIENTE
    ============================================================ */
    public function DesactivarCliente() {
        if (isset($_POST['DesactivarCliente'])) {
            $id = intval($_POST['id']);

            $sql = $this->Conectar()->query("
                UPDATE cliente SET habilitado='0' WHERE id={$id}
            ");

            if ($sql) {
                echo '<div class="alert alert-warning">Cliente desactivado.</div>';
            } else {
                echo '<div class="alert alert-danger">Error al desactivar cliente.</div>';
            }

            echo '<meta http-equiv="refresh" content="1;url='.URLBASE.'clientes">';
        }
    }


    /* ========================
       LISTA DE CLIENTES
    =========================*/
    public function ListarClientes(){
        return $this->Conectar()->query("SELECT * FROM cliente ORDER BY id DESC");
    }

    /* ========================
       ELIMINAR CLIENTE
    =========================*/
    public function EliminarCliente(){
        if(isset($_POST['EliminarCliente'])){
            $id = intval($_POST['id']);
            $sql = $this->Conectar()->query("DELETE FROM cliente WHERE id={$id}");

            if($sql){
                echo '<div class="alert alert-success">Cliente eliminado.</div>';
            } else {
                echo '<div class="alert alert-danger">No se pudo eliminar.</div>';
            }

            echo '<meta http-equiv="refresh" content="1;url='.URLBASE.'clientes">';
        }
    }
}


?>