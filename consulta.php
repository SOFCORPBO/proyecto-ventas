<?php
// CANTIDAD DE SERVICIOS EN EL CARRITO
$numerosTotalSql = $db->SQL("
    SELECT COUNT(id) 
    FROM cajatmp 
    WHERE vendedor='{$usuarioApp['id']}'
");
$numerosTotal = $numerosTotalSql->fetch_row()[0];
?>

<div class="col-md-9">
    <div style="width:100%; height:300px; overflow:auto;">
        <form method="post" action="">

            <table class="table table-bordered">
                <tr class="well">
                    <td style="width:20px;"><input type="checkbox" id="todos" onclick="todosuno(this.value)" /></td>
                    <td><strong>Código</strong></td>
                    <td><strong>Servicio</strong></td>
                    <td><strong>Tipo</strong></td>
                    <td><strong>Cantidad</strong></td>
                    <td><strong>Precio</strong></td>
                    <td><strong>Importe</strong></td>
                    <td><strong>Comisión</strong></td>
                    <td style="width:180px;">
                        <button type="button"
                            class="btn btn-primary btn-xs <?php if($numerosTotal <= 0) echo 'disabled'; ?>"
                            data-toggle="modal" data-target="#EliminarVenta">
                            <i class="fa fa-trash-o"></i> Limpiar Venta
                        </button>
                    </td>
                </tr>

                <!-- MODAL LIMPIAR VENTA -->
                <div class="modal fade" id="EliminarVenta" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">Eliminar Venta Actual</h4>
                                </div>
                                <div class="modal-body">
                                    <p>¿Está seguro que desea eliminar toda la venta actual?</p>
                                    <input type="hidden" name="IdUsuario" value="<?php echo $usuarioApp['id']; ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                                    <button type="submit" name="EliminarTodo" class="btn btn-primary">
                                        Sí, Eliminar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php
                // OPTIMIZACIÓN: UNA SOLA CONSULTA (JOIN)
                $cajatmpSql = $db->SQL("
                    SELECT c.*, 
                           p.codigo, p.nombre, p.tipo_servicio, 
                           p.comision AS comision_base,
                           (p.comision * c.cantidad) AS comision_total
                    FROM cajatmp c
                    INNER JOIN producto p ON p.id = c.producto
                    WHERE c.vendedor='{$usuarioApp['id']}'
                    ORDER BY c.id DESC
                ");

                $i = 0;
                while ($row = $cajatmpSql->fetch_assoc()):
                    $i++;
                ?>
                <tr>
                    <td><input type="checkbox" name="IDS<?php echo $i; ?>" value="<?php echo $row['id']; ?>"></td>

                    <!-- CÓDIGO -->
                    <td><?php echo $row['codigo']; ?></td>

                    <!-- SERVICIO -->
                    <td><?php echo $row['nombre']; ?></td>

                    <!-- TIPO -->
                    <td><span class="label label-info"><?php echo $row['tipo_servicio']; ?></span></td>

                    <!-- CANTIDAD -->
                    <td><?php echo $row['cantidad']; ?></td>

                    <!-- PRECIO -->
                    <td>$ <?php echo number_format($row['precio'], 2); ?></td>

                    <!-- IMPORTE -->
                    <td>$ <?php echo number_format($row['totalprecio'], 2); ?></td>

                    <!-- COMISIÓN DEL SERVICIO -->
                    <td>
                        <?php if ($row['comision_base'] > 0): ?>
                        <span class="label label-success">
                            <?php echo number_format($row['comision_total'], 2); ?> Bs
                        </span>
                        <?php else: ?>
                        <span class="label label-default">0</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <!-- BOTÓN ELIMINAR -->
                        <button type="button" class="btn btn-danger btn-xs" data-toggle="modal"
                            data-target="#Eliminar<?php echo $row['id']; ?>">
                            <i class="fa fa-trash"></i>
                        </button>

                        <!-- MODAL ELIMINAR ITEM -->
                        <div class="modal fade" id="Eliminar<?php echo $row['id']; ?>">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            <h4 class="modal-title">Eliminar servicio</h4>
                                        </div>
                                        <div class="modal-body">
                                            ¿Eliminar este servicio de la venta?
                                            <input type="hidden" name="IdCajatmp" value="<?php echo $row['id']; ?>">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default"
                                                data-dismiss="modal">Cerrar</button>
                                            <button type="submit" name="EliminarProducto" class="btn btn-danger">
                                                Eliminar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- BOTÓN ACTUALIZAR -->
                        <button type="button" class="btn btn-primary btn-xs" data-toggle="modal"
                            data-target="#Actualizar<?php echo $row['id']; ?>">
                            <i class="fa fa-edit"></i>
                        </button>

                        <!-- MODAL ACTUALIZAR CANTIDAD -->
                        <div class="modal fade" id="Actualizar<?php echo $row['id']; ?>">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header">
                                            <h4 class="modal-title">Actualizar cantidad</h4>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="IdCajaTmp" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="Precio" value="<?php echo $row['precio']; ?>">

                                            <input type="number" min="1" class="form-control" name="Cantidad"
                                                value="<?php echo $row['cantidad']; ?>" required>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="ActualizarCantidadCajaTmp"
                                                class="btn btn-primary">
                                                Actualizar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </td>
                </tr>
                <?php endwhile; ?>

                <input type="hidden" name="contadorx" value="<?php echo $i; ?>">
            </table>

        </form>
    </div>
</div>

<!-- PANEL DERECHO (TOTAL) -->
<div class="col-md-3">
    <?php
    $netoSql = $db->SQL("
        SELECT SUM(totalprecio) AS total 
        FROM cajatmp 
        WHERE vendedor='{$usuarioApp['id']}'
    ");
    $neto = $netoSql->fetch_assoc();
    ?>

    <div class="panel panel-default">
        <div class="panel-heading text-center"><strong>Neto a Pagar</strong></div>

        <div class="panel-body">
            <h2 class="text-success text-center">
                $ <?php echo number_format($neto['total'], 2); ?>
            </h2>
        </div>

        <div class="panel-heading text-center">
            <strong>
                Servicios agregados:
                <span class="badge badge-success"><?php echo $numerosTotal; ?></span>
            </strong>
        </div>
    </div>

    <!-- BOTÓN REGISTRAR VENTA -->
    <?php if ($numerosTotal > 0): ?>
    <button type="button" class="btn btn-primary btn-lg btn-block" data-toggle="modal" data-target="#RegistrarCompra">
        <i class="fa fa-shopping-cart"></i> Registrar Venta
    </button>
    <?php else: ?>
    <button class="btn btn-default btn-lg btn-block" disabled>
        <i class="fa fa-shopping-cart"></i> Registrar Venta
    </button>
    <?php endif; ?>

    <hr>
    <?php include(MODULO.'notificacion.php'); ?>

</div>