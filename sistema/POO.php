<?php


$fechaActual = FechaActual();
/**
 |-------------------------------------------
 |	Establecimientos
 |-------------------------------------------
 */
 $EstablecimientosSql		= $db->SQL("SELECT nombre FROM `establecimiento`");
 $EstablecimientosArray		= array();
 while($Establecimientos	= $EstablecimientosSql->fetch_array()):
 $EstablecimientosArray[]	= $Establecimientos;
 endwhile;

/**
 |-------------------------------------------
 |	Temas
 |-------------------------------------------
 */
 $TodosTemasSql			= $db->SQL("SELECT * FROM `tema`");
 $TodosTemasArray		= array();
 while($TodosTemas		= $TodosTemasSql->fetch_array()):
 $TodosTemasArray[]		= $TodosTemas;
 endwhile;

/**
 |-------------------------------------------
 |	Usuarios
 |-------------------------------------------
 */
 $TodosLosUsuariosSql		= $db->SQL("SELECT id, usuario FROM `usuario` WHERE habilitado='1'");
 $TodosLosUsuariosArray		= array();
 while($TodosLosUsuariosVar	= $TodosLosUsuariosSql->fetch_array()):
 $TodosLosUsuariosArray[]	= $TodosLosUsuariosVar;
 endwhile;

/**
 |-------------------------------------------
 |	Selector de ProductosDATOS
 |-------------------------------------------
 */
 $SelectorProductosQuery	= $db->SQL("SELECT codigo, nombre  FROM producto");
 $SelectorProductosArray	= array();
 while($SelectorProductos	= $SelectorProductosQuery->fetch_array()):
 $SelectorProductosArray[]	= $SelectorProductos;
 endwhile;

/**
 |-------------------------------------------
 |	Selector de Clientes
 |-------------------------------------------
 */
 $SelectorClientesQuery		= $db->SQL("SELECT id, nombre  FROM `cliente` WHERE habilitado='1'");
 $SelectorClientesArray		= array();
 while($SelectorClientes	= $SelectorClientesQuery->fetch_array()):
 $SelectorClientesArray[]	= $SelectorClientes;
 endwhile;

/**
 |-------------------------------------------
 |	Selector de Productos
 |-------------------------------------------
 */
 $ProductosStockQuery	= $db->SQL("SELECT * FROM `producto`");
 $ProductosStockArray	= array();
 while($ProductosStock	= $ProductosStockQuery->fetch_array()):
 $ProductosStockArray[]	= $ProductosStock;
 endwhile;

/**
 |-------------------------------------------
 |	Selector de Departamentos
 |-------------------------------------------
 */
 $DepartamentoStockQuery	= $db->SQL("SELECT * FROM `departamento`");
 $DepartamentoStockArray	= array();
 while($DepartamentoStock	= $DepartamentoStockQuery->fetch_array()):
 $DepartamentoStockArray[]	= $DepartamentoStock;
 endwhile;

/**
 |-------------------------------------------
 |	Selector de Proveedores
 |-------------------------------------------
 */
 $ProveedoresStockQuery		= $db->SQL("SELECT * FROM `proveedor`");
 $ProveedoresStockArray		= array();
 while($ProveedoresStock	= $ProveedoresStockQuery->fetch_array()):
 $ProveedoresStockArray[]	= $ProveedoresStock;
 endwhile;

/**
 |-------------------------------------------
 |	Selector de Unidad
 |-------------------------------------------
 */
 $UnidadStockQuery	= $db->SQL("SELECT * FROM `medida`");
 $UnidadStockArray	= array();
 while($UnidadStock	= $UnidadStockQuery->fetch_array()):
 $UnidadStockArray[]= $UnidadStock;
 endwhile;

/**
 |-------------------------------------------
 |	Selector de IVA Venta
 |-------------------------------------------
 */
 $IVAVentaStockQuery	= $db->SQL("SELECT * FROM `iva`");
 $IVAVentaStockArray	= array();
 while($IVAVentaStock	= $IVAVentaStockQuery->fetch_array()):
 $IVAVentaStockArray[]	= $IVAVentaStock;
 endwhile;

/**
 |-------------------------------------------
 |	Selector de IVA Venta
 |-------------------------------------------
 */
 $SelectorMonedaQuery	= $db->SQL("SELECT * FROM `moneda`");
 $SelectorMonedaArray	= array();
 while($SelectorMoneda	= $SelectorMonedaQuery->fetch_array()):
 $SelectorMonedaArray[]	= $SelectorMoneda;
 endwhile;

/**
 |-------------------------------------------
 |	Kardex por fechas General
 |-------------------------------------------
 */
 $KardexPorfechasQuery	= $db->SQL("SELECT * FROM `kardex`");
 $KardexPorfechasArray	= array();
 while($KardexPorfechas	= $KardexPorfechasQuery->fetch_array()):
 $KardexPorfechasArray[]= $KardexPorfechas;
 endwhile;

/**
 |-------------------------------------------
 |	Notificaciones Stock
 |-------------------------------------------
 */
 $NotificacionesStockQuery	= $db->SQL("SELECT id, codigo, nombre, stock, stockMin FROM `producto` WHERE stock < stockMin");
 $NotificacionesStockArray	= array();
 while($NotificacionesStock	= $NotificacionesStockQuery->fetch_array()):
 $NotificacionesStockArray[]= $NotificacionesStock;
 endwhile;

/**
 |-------------------------------------------
 |	Caja
 |-------------------------------------------
 */
 $CajaQuery		= $db->SQL("SELECT * FROM `caja` WHERE estado='1'");
 $CajaArray		= array();
 while($Caja	= $CajaQuery->fetch_array()):
 $CajaArray[]	= $Caja;
 endwhile;

/**
 |-------------------------------------------
 |	Caja
 |-------------------------------------------
 */
 $CajaRegistroQuery		= $db->SQL("SELECT * FROM `cajaregistros` ORDER BY id DESC");
 $CajaRegistroArray		= array();
 while($CajaRegistro	= $CajaRegistroQuery->fetch_array()):
 $CajaRegistroArray[]	= $CajaRegistro;
 endwhile;

/**
 |-------------------------------------------
 |	Caja Apertura
 |-------------------------------------------
 */
 $CajaAperturaRegistroQuery		= $db->SQL("SELECT * FROM `cajaregistros` WHERE tipo='1' ORDER BY id DESC");
 $CajaAperturaRegistroArray		= array();
 while($CajaAperturaRegistro	= $CajaAperturaRegistroQuery->fetch_array()):
 $CajaAperturaRegistroArray[]	= $CajaAperturaRegistro;
 endwhile;

/**
 |-------------------------------------------
 |	Caja Cierre
 |-------------------------------------------
 */
 $CajaCierreRegistroQuery	= $db->SQL("SELECT * FROM `cajaregistros` WHERE tipo='2' ORDER BY id DESC");
 $CajaCierreRegistroArray	= array();
 while($CajaCierreRegistro	= $CajaCierreRegistroQuery->fetch_array()):
 $CajaCierreRegistroArray[]	= $CajaCierreRegistro;
 endwhile;

/**
 |-------------------------------------------
 |	Caja Efectivo
 |-------------------------------------------
 */
 $CajaCierreRegistroEfectivoQuery	= $db->SQL("SELECT * FROM `factura` WHERE tipo='1' AND fecha='{$fechaActual}'");
 $CajaCierreRegistroEfectivoArray	= array();
 while($CajaCierreRegistroEfectivo	= $CajaCierreRegistroEfectivoQuery->fetch_array()):
 $CajaCierreRegistroEfectivoArray[]	= $CajaCierreRegistroEfectivo;
 endwhile;

/**
 |-------------------------------------------
 |	Caja Tarjeta
 |-------------------------------------------
 */
 $CajaCierreRegistroTarjetaQuery	= $db->SQL("SELECT * FROM `factura` WHERE tipo='0' AND fecha='{$fechaActual}'");
 $CajaCierreRegistroTarjetaArray	= array();
 while($CajaCierreRegistroTarjeta	= $CajaCierreRegistroTarjetaQuery->fetch_array()):
 $CajaCierreRegistroTarjetaArray[]	= $CajaCierreRegistroTarjeta;
 endwhile;

/**
 |-------------------------------------------
 |	Caja Chica
 |-------------------------------------------
 */
 $CajaChicaQuery	= $db->SQL("SELECT * FROM `cajachica` ORDER BY id DESC");
 $CajaChicaArray	= array();
 while($CajaChica	= $CajaChicaQuery->fetch_array()):
 $CajaChicaArray[]	= $CajaChica;
 endwhile;

/**
 |-------------------------------------------
 |	Caja Chica Registro Entrada Dinero
 |-------------------------------------------
 */
 $CajaChicaRegistroEntradaDineroQuery	= $db->SQL("SELECT * FROM `cajachicaregistros` WHERE tipo='0' ORDER BY id DESC");
 $CajaChicaRegistroEntradaDineroArray	= array();
 while($CajaChicaRegistroEntradaDinero	= $CajaChicaRegistroEntradaDineroQuery->fetch_array()):
 $CajaChicaRegistroEntradaDineroArray[]	= $CajaChicaRegistroEntradaDinero;
 endwhile;

/**
 |-------------------------------------------
 |	Caja Chica Registro Salida Dinero
 |-------------------------------------------
 */
 $CajaChicaRegistroSalidaDineroQuery	= $db->SQL("SELECT * FROM `cajachicaregistros` WHERE tipo='1' ORDER BY id DESC");
 $CajaChicaRegistroSalidaDineroArray	= array();
 while($CajaChicaRegistroSalidaDinero	= $CajaChicaRegistroSalidaDineroQuery->fetch_array()):
 $CajaChicaRegistroSalidaDineroArray[]	= $CajaChicaRegistroSalidaDinero;
 endwhile;

 // ===============================
// CLIENTES
// ===============================
$ClientesStockSQL = $db->SQL("SELECT * FROM cliente ORDER BY id DESC");
$ClientesStockArray = [];
while($c = $ClientesStockSQL->fetch_assoc()){
    $ClientesStockArray[] = $c;
}

