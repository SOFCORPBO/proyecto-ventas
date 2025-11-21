<?php

class Vendedor extends Conexion {

	
	public function MostrarCliente(){

		global $clientes;
		global $clientesSql;

		if (isset($_GET['id'])){
			$clientesSql = $this->Conectar()->query("SELECT * FROM `clientes` WHERE id='{$_GET['id']}'");
			$clientes = $clientesSql->fetch_assoc();
			if (!$clientes['id']){
				$error = true;
			}
		}else{
			$error = true;
		}
	}

	
	public function AbonosSaldo($cuenta){
		$sql = $this->Conectar()->query("SELECT SUM(abono) AS valores FROM abono WHERE id_credito='{$_GET['id']}'");
		if($row=$sql->fetch_array()){
			return $row['valores'];
		}else{
			return 0;	
		}
	}

	public function Formato($valor){
		return number_format($valor, 1, ',', '.');
	}

	public function FormatoSaldo($valor){
		return number_format($valor, 0, ',', ',');
	}
}
