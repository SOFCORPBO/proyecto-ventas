<?php

 
class Usuario extends Conexion {
public function LoginUsuario(){

    if (!isset($_POST['sesionPost'])) {
        return;
    }

    $db = $this->Conectar();

    $usuarioPost = isset($_POST['usuarioPost']) ? trim($_POST['usuarioPost']) : '';
    $contrasena  = isset($_POST['contrasenaPost']) ? trim($_POST['contrasenaPost']) : '';

    if ($usuarioPost === '' || $contrasena === '') {
        return;
    }

    // Buscar usuario
    $stmt = $db->prepare("SELECT id, usuario, contrasena, id_perfil, habilitado FROM usuario WHERE usuario = ? LIMIT 1");
    $stmt->bind_param("s", $usuarioPost);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {

        $u = $res->fetch_assoc();

        if ((int)$u['habilitado'] !== 1) {
            return;
        }

        // Compatibilidad: contraseña plana o SHA1 tipo (USER:PASS) en mayúsculas
        $sha = sha1(strtoupper($u['usuario']) . ":" . strtoupper($contrasena));
        $ok  = hash_equals((string)$u['contrasena'], (string)$contrasena) || hash_equals((string)$u['contrasena'], (string)$sha);

        if ($ok) {
            // Seguridad básica: regenerar sesión
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);

            // Sesión completa (clave para que chat-config/chat-bridge NO te boten)
            $_SESSION['usuario']    = $u['usuario'];
            $_SESSION['id_usuario'] = (int)$u['id'];
            $_SESSION['id_perfil']  = (int)$u['id_perfil'];
            $_SESSION['mark']       = true;

            header("Location: " . URLBASE . "index.php");
            exit();
        }
    }

    // opcional: manejar error de login
    // echo "Usuario o contraseña incorrectos";
}
	public function LoginCuentaConsulta(){

		global $usuarioQuerySQL;
		global $SessionUsuario;
		global $usuarioApp;
		global $_SESSION;

		$SessionUsuario		= isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
		$usuarioQuerySQL	= $this->Conectar()->query("SELECT * FROM `usuario` WHERE usuario = '{$SessionUsuario}' AND habilitado='1'");
		$usuarioApp			= $usuarioQuerySQL->fetch_assoc();
		// Al final de LoginCuentaConsulta(), después de $usuarioApp = fetch_assoc();
if (!empty($usuarioApp)) {
    if (!isset($_SESSION['id_usuario']) && isset($usuarioApp['id'])) {
        $_SESSION['id_usuario'] = (int)$usuarioApp['id'];
    }
    if (!isset($_SESSION['id_perfil']) && isset($usuarioApp['id_perfil'])) {
        $_SESSION['id_perfil'] = (int)$usuarioApp['id_perfil'];
    }
}

	}

	public function VerificacionCuenta(){

		global $usuarioApp;

		// La sesion no puede estar vacia
		if(isset($_SESSION['usuario']) == ''){
			header("Location: ".URLBASE."iniciar-sesion");
			exit();
		}

		// Regenerar los identificadores de sesión para sesiones nuevas
		if (isset($_SESSION['mark']) === false)
		{
			session_regenerate_id(true);
			$_SESSION['mark'] = true;
		}
	}

	public function CambiarContrasenaVendedor(){

		global $usuarioApp;

		if(isset($_POST['CambiarPass'])){
			$UsuarioId	= $usuarioApp['id'];
			$usuario	= $usuarioApp['usuario'];
			$contrasena1= filter_var($_POST['contrasena'], FILTER_SANITIZE_STRING);
			$contrasena2= filter_var($_POST['confirmar'], FILTER_SANITIZE_STRING);
			$sha = sha1(strtoupper($usuario) . ":" . strtoupper($contrasena1));
			if($contrasena1 == $contrasena2){
				$ActualizarContrasena = $this->Conectar()->query("UPDATE `usuario` SET `contrasena` = '{$sha}' WHERE `id` = '{$UsuarioId}'");
				if($ActualizarContrasena == true){
					echo'
					<div class="alert alert-dismissible alert-success">
						<button type="button" class="close" data-dismiss="alert">&times;</button>
						<strong>&iexcl;Excelente</strong> La contrase&ntilde;a ha sido actulizada con exito.
					</div>';
				}else{
					echo'
					<div class="alert alert-dismissible alert-danger">
						<button type="button" class="close" data-dismiss="alert">&times;</button>
						<strong>&iexcl;Oh no!</strong> A ocurrido un error al actulizar la contrase&;ntilde;a, por favor intentalo de nuevo.
					</div>';
				}
			}else{
				echo'
				<div class="alert alert-dismissible alert-warning">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<h4>&iexcl;Advertencia!</h4>
					<p>Las contrase&;ntilde;as no coinciden por favor intentar de nuevo.</p>
				</div>';
			}
		}
	}

	public function ActualizarDatos(){

		if(isset($_POST['ActualizarDatosUsuarios'])){
			$UsuarioId	= filter_var($_POST['IdUsuario'], FILTER_VALIDATE_INT);
			$nombre		= filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
			$apellido1	= filter_var($_POST['apellido1'], FILTER_SANITIZE_STRING);
			$apellido2	= filter_var($_POST['apellido2'], FILTER_SANITIZE_STRING);
			$local		= filter_var($_POST['establecimiento'], FILTER_SANITIZE_STRING);
			$direccion	= filter_var($_POST['nota'], FILTER_SANITIZE_STRING);
			$ActualizarVendedor = $this->Conectar()->query("UPDATE `vendedores` SET `nombre` = '{$nombre}' , `apellido1` = '{$apellido1}' , `apellido2` = '{$apellido2}' , `establecimiento` = '{$local}' , `telefono` = '{$telefono}' , `direccion` = '{$direccion}' WHERE `id` = '{$UsuarioId}'");
			$ActualizarUsuario	= $this->Conectar()->query("UPDATE `usuario` SET `email` = '{$email}' WHERE `id` = '{$UsuarioId}'");
			if($ActualizarVendedor && $ActualizarUsuario == true){
				echo'
				<div class="alert alert-dismissible alert-success">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>&iexcl;Excelente</strong> Sus datos han sido actulizados con exito.
				</div>
				<meta http-equiv="refresh" content="0;url='.URLBASE.'ajuste-usuario"/>';
			}else{
				echo'
				<div class="alert alert-dismissible alert-danger">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>&iexcl;Oh no!</strong> A ocurrido un error al actulizar sus datos, por favor intentalo de nuevo.
				</div>
				<meta http-equiv="refresh" content="0;url='.URLBASE.'ajuste-usuario"/>';
			}
		}
	}

	public function EditarUsuario(){

		if(isset($_POST['EditarUsuario'])){
			$IdUsuario	= filter_var($_POST['idUsuario'], FILTER_VALIDATE_INT);
			$local		= filter_var($_POST['establecimiento'], FILTER_SANITIZE_STRING);
			$nombre		= filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
			$apellido1	= filter_var($_POST['apellido1'], FILTER_SANITIZE_STRING);
			$apellido2	= filter_var($_POST['apellido2'], FILTER_SANITIZE_STRING);
			$tipo		= filter_var($_POST['idperfil'], FILTER_VALIDATE_INT);
			$ActualizarVendedor = $this->Conectar()->query("UPDATE `vendedores` SET `nombre` = '{$nombre}' , `apellido1` = '{$apellido1}' , `apellido2` = '{$apellido2}' , `establecimiento` = '{$local}' WHERE `id` = '{$IdUsuario}'");
			$ActualizarUsuario	= $this->Conectar()->query("UPDATE `usuario` SET `id_perfil` = '{$tipo}' WHERE `id` = '{$IdUsuario}'");
			if($ActualizarVendedor && $ActualizarUsuario == true){
				echo'
				<div class="alert alert-dismissible alert-success">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>&iexcl;Excelente</strong> Los datos han sido actulizados con exito.
				</div>
				<meta http-equiv="refresh" content="0;url='.URLBASE.'vendedores"/>';
			}else{
				echo'
				<div class="alert alert-dismissible alert-danger">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>&iexcl;Oh no!</strong> A ocurrido un error al actulizar los datos, por favor intentalo de nuevo.
				</div>
				<meta http-equiv="refresh" content="0;url='.URLBASE.'vendedores"/>';
			}
		}
	}

	public function EliminarUsuario(){

		if(isset($_POST['EliminarUsuario'])){
			$IdUsuario	= filter_var($_POST['idUsuario'], FILTER_VALIDATE_INT);
			$EliminarUsuario	= $this->Conectar()->query("DELETE FROM `usuario` WHERE `id` = '{$IdUsuario}'");
			$EliminarVendedores	= $this->Conectar()->query("DELETE FROM `vendedores` WHERE `id` = '{$IdUsuario}';");
			if($EliminarUsuario && $EliminarVendedores == true){
				echo'
				<div class="alert alert-dismissible alert-success">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>&iexcl;Excelente</strong> El usuario ha sido eliminado con exito.
				</div>
				<meta http-equiv="refresh" content="0;url='.URLBASE.'vendedores"/>';
			}else{
				echo'
				<div class="alert alert-dismissible alert-danger">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>&iexcl;Oh no!</strong> A ocurrido un error al eliminar el usuario, por favor intentalo de nuevo.
				</div>
				<meta http-equiv="refresh" content="0;url='.URLBASE.'vendedores"/>';
			}
		}
	}

	public function ActivarUsuario(){

		if(isset($_POST['ActivarVendedor'])){
			$usuario			= $_POST['IdUsuario'];
			$ActivarUsuario		= $this->Conectar()->query("UPDATE `usuario` SET `habilitado` = '1' WHERE `id` = '{$usuario}'");
			$ActivarVendedor	= $this->Conectar()->query("UPDATE `vendedores` SET `habilitado` = '1' WHERE `id` = '{$usuario}'");
			if($ActivarUsuario	&& $ActivarVendedor = true){
				echo'
				<div class="alert alert-dismissible alert-success">
					  <button type="button" class="close" data-dismiss="alert">&times;</button>
					  <strong>&iexcl;Excelente!</strong> Haz Activado de nuevo el usuario con exito.
				</div>';
			}else{
				echo'
				<div class="alert alert-dismissible alert-danger">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>&iexcl;Lo Sentimos!</strong> A ocurrido un error al Activar el usuario.
				</div>';
			}
		}
	}

	public function InhabilitarUsuario(){

		if(isset($_POST['DesactivarUsuario'])){
			$usuario				= $_POST['IdUsuario'];
			$InhabilatarUsuario		= $this->Conectar()->query("UPDATE `usuario` SET `habilitado` = '0' WHERE `id` = '{$usuario}'");
			$InhabilatarVendedor	= $this->Conectar()->query("UPDATE `vendedores` SET `habilitado` = '0' WHERE `id` = '{$usuario}'");
			if($InhabilatarUsuario	&& $InhabilatarVendedor == true){
				echo'
				<div class="alert alert-dismissible alert-success">
					  <button type="button" class="close" data-dismiss="alert">&times;</button>
					  <strong>&iexcl;Excelente!</strong> Haz desactivado el usuario con exito.
				</div>';
			}else{
				echo'
			<div class="alert alert-dismissible alert-danger">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				<strong>&iexcl;Lo Sentimos!</strong> A ocurrido un error al desactivar el usuario.
			</div>';
			}
		}
	}

	public function CierreSesion(){
		$_SESSION = array();
		session_unset();
		session_destroy();
		echo'<meta http-equiv="refresh" content="1;url='.URLBASE.'iniciar-sesion"/>';
	}

	public function ZonaAdministrador(){
		global $usuarioApp;
		$rango = $usuarioApp['id_perfil'];
		//Permitimos la entrada si el rango es 2 o superior
		if ($rango > 1){
			header('Location: index');
		}
	}
}