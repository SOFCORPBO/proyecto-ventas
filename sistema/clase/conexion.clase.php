<?php

class Conexion {

	private $mysqli;

	function __construct() {
    }

    /**
     * Establecimiento de la conexión de base de datos
     * @return manejador de conexión de base de datos
     */
	public function Conectar(){

		$this->mysqli = new mysqli(HOST, USER, PASSWORD, DB, PORT);
		// Soporte para caracteres especiales en la base de datoss
		$this->mysqli->query("SET NAMES 'utf8'");

		if (mysqli_connect_error()) {
			die("Error al conectar con la base de datos (" . mysqli_connect_errno() . ") " . mysqli_connect_error());
		}
		// Devolver recurso de conexión
		return $this->mysqli;
	}

	public function SQL($sqlconsulta){
		return $this->Conectar()->query($sqlconsulta);
	}
}
