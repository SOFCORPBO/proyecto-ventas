<?php

class Notificacion  extends Conexion {

	public function EnviarNotificacionContenido(){

		if(isset($_POST['EnviarNotificacion'])){

			$notificacion	= filter_var($_POST['notificacion'], FILTER_SANITIZE_STRING);
			$FechaHora		= date('Y-m-d G:i:s');
			$InsertarNotificacion = $this->Conectar()->Query("INSERT INTO `notificaciones` (`notificacion`, `fecha`) VALUES ('{$notificacion}', '{$FechaHora}')");

			if($InsertarNotificacion == true){
				echo'
				<div class="alert alert-dismissible alert-success">
				  <button type="button" class="close" data-dismiss="alert">&times;</button>
				  <strong>Exceltente!</strong> La notificaci&oacute;n se public&oacute; con exito.
				</div>';
			}else{
				echo'error';
			}
		}
	}
	
	public function MostrarNotificion(){
		
		global $notificaciones;
		$ExisteNotificacionSql	= $this->Conectar()->query("SELECT COUNT(id) AS cantidad FROM `notificaciones`");
		$ExisteNotificacion		= $ExisteNotificacionSql->fetch_array();
		if($ExisteNotificacion['cantidad']>0){
			$notificacionesQuerySql = $this->Conectar()->query("SELECT notificacion, fecha FROM `notificaciones` WHERE id ORDER BY id DESC LIMIT 2");
			while($notificaciones	= $notificacionesQuerySql->fetch_array()){
				echo'<a href="#" class="list-group-item">
					<div class="circulo"></div>
					<p class="list-group-item-text">'.$notificaciones['notificacion'].'</p>
					<small class="text-primary">Administrador</small> <i class="fa fa-angle-right"></i> <time datetime="'.$notificaciones['fecha'].'">'.TiempoPublicacion($notificaciones['fecha']).'</time>
				</a>';
			}
		}else{
			echo'<p class="text-primary">No hay notificaciones del Administrador.</p>';
		}
	}
	
	public function EnviarNotificacion(){
		
		global $usuarioApp;
		
		if($usuarioApp['id_perfil']==1){
		echo'<div class="panel-footer">
			<form method="post" action="">
				<div class="input-group">
					<input id="btn-input" type="text" name="notificacion" class="form-control input-sm chat_input" placeholder="Escriba su mensaje aqu&iacute;..." autocomplete="off" required/>
					<span class="input-group-btn">
						<button class="btn btn-primary btn-sm" name ="EnviarNotificacion" id="btn-chat">Enviar</button>
					</span>
				</div>
			</form>
		</div>';
		}else{
			// No mostrar nada si es vendedor
		}
	}
}
