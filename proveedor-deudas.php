<?php
session_start();
include("sistema/configuracion.php");
include("sistema/clase/proveedor.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();

// Lista completa de proveedores
$Proveedores = $Proveedor->ListarProveedores();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Estado de Cuenta – Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO . "Tema.CSS.php"); ?>
    <style>
    /* Contenedor general de cada proveedor */
    .panel-finanzas {
        border-left: 4px solid #90A4AE;
        /* gris azulado suave */
        padding: 15px;
        margin-bottom: 22px;
        background: #fafafa;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    /* Encabezado por proveedor */
    .proveedor-header {
        background: #CFD8DC;
        /* gris azul muy suave */
        color: #37474F;
        /* gris oscuro para mejor contraste */
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 12px;
        font-size: 18px;
        font-weight: 600;
    }

    /* Tarjetas KPI pastel */
    .kpi-box {
        text-align: center;
        padding: 18px;
        border-radius: 12px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #37474F;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    }

    .k-blue {
        background: #E3F2FD;
        /* Celeste pastel */
    }

    .k-green {
        background: #E8F5E9;
        /* Verde pastel */
    }

    .k-orange {
        background: #FFF3E0;
        /* Naranja pastel */
    }

    .k-red {
        background: #FFEBEE;
        /* Rojo pastel */
    }

    /* Encabezado de tabla */
    .table thead {
        background: #ECEFF1;
        /* gris claro elegante */
        color: #37474F;
        font-weight: 600;
    }

    .saldo-pendiente {
        font-size: 18px;
        color: #D32F2F;
        font-weight: bold;
    }

    /* Estado de facturas */
    .badge-vencida {
        background: #EF9A9A;
        /* rojo pastel */
        color: #B71C1C;
    }

    .badge-casi {
        background: #FFE0B2;
        /* naranja pastel */
        color: #E65100;
    }

    .badge-normal {
        background: #C8E6C9;
        /* verde pastel */
        color: #1B5E20;
    }
    </style>

</head>

<body>

    <?php include(MODULO . "menu_admin.php"); ?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>
                <i class="fa fa-book"></i> Estado Financiero de Proveedores
            </h1>
            <small>Resumen completo de facturas pendientes, vencidas y situación actual.</small>
        </div>

        <!-- ============================
         RESUMEN GENERAL DEUDA
    ============================ -->
        <?php
    $KPI = $Proveedor->KPIs();
    ?>

        <div class="row">
            <div class="col-sm-3">
                <div class="kpi-box k-blue">
                    <?= $KPI['total'] ?><br>
                    <small>Total Proveedores</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box k-green">
                    <?= $KPI['activos'] ?><br>
                    <small>Activos</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box k-orange">
                    <?= $KPI['inactivos'] ?><br>
                    <small>Inactivos</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box k-red">
                    <?= number_format($KPI['deuda_total'], 2) ?> Bs<br>
                    <small>Deuda Global</small>
                </div>
            </div>
        </div>

        <hr>

        <!-- ============================
         ESTADO DE CUENTA POR PROVEEDOR
    ============================ -->
        <?php while ($p = $Proveedores->fetch_assoc()): ?>

        <?php
        // Obtener facturas del proveedor
        $Facturas = $db->SQL("
            SELECT *,
            (monto_total - monto_pagado) AS saldo
            FROM proveedor_factura
            WHERE id_proveedor = {$p['id']}
              AND estado IN ('PENDIENTE','PARCIAL')
            ORDER BY fecha_emision DESC
        ");

        $total_deuda = $p['saldo_pendiente'];
        ?>

        <div class="panel-finanzas">
            <div class="proveedor-header">
                <h3 style="margin:0;">
                    <i class="fa fa-truck"></i> <?= $p['nombre'] ?>
                    <span class="pull-right saldo-pendiente">
                        Deuda: <?= number_format($total_deuda, 2) ?> Bs
                    </span>
                </h3>
            </div>

            <?php if ($Facturas->num_rows > 0): ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Emisión</th>
                        <th>Vencimiento</th>
                        <th>Total</th>
                        <th>Pagado</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($f = $Facturas->fetch_assoc()): ?>

                    <?php
                        // Cálculo color por proximidad de vencimiento
                        $vence = $f['fecha_vencimiento'];
                        $dias = (strtotime($vence) - time()) / 86400;

                        if ($dias < 0) {
                            $badge = "badge-vencida";
                            $txt = "VENCIDA";
                        } elseif ($dias <= 5) {
                            $badge = "badge-casi";
                            $txt = "POR VENCER";
                        } else {
                            $badge = "badge-normal";
                            $txt = "VIGENTE";
                        }
                        ?>

                    <tr>
                        <td>#<?= $f['numero_factura'] ?></td>
                        <td><?= $f['fecha_emision'] ?></td>
                        <td><?= $f['fecha_vencimiento'] ?></td>
                        <td><?= number_format($f['monto_total'], 2) ?> Bs</td>
                        <td><?= number_format($f['monto_pagado'], 2) ?> Bs</td>
                        <td><strong><?= number_format($f['saldo'], 2) ?> Bs</strong></td>
                        <td><span class="badge <?= $badge ?>"><?= $txt ?></span></td>
                    </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php else: ?>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                No hay facturas pendientes ni parciales para este proveedor.
            </div>

            <?php endif; ?>
        </div>

        <?php endwhile; ?>

    </div>

    <?php include(MODULO . "footer.php"); ?>

</body>

</html>