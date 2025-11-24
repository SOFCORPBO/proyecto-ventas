<?php


class EstadoCuenta extends Conexion {

	public function MostrarVendedor(){

		global $VendedorDatos;
		global $VendedorSql;
		$IdUsuario = filter_var($_GET['id'],FILTER_VALIDATE_INT);
		if (isset($IdUsuario)){
			$VendedorSql = $this->Conectar()->query("SELECT * FROM `usuario` WHERE id='{$IdUsuario}'");
			$VendedorDatos = $VendedorSql->fetch_assoc();
			if (!$VendedorDatos['id']){
				$error = true;
			}
		}else{
			$error = true;
		}
	}
}