<?php
session_start();
require_once __DIR__ . '/sistema/configuracion.php';

// Login/seguridad igual a tus módulos
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Instancias WhatsApp | <?php echo defined('TITULO') ? TITULO : 'Sistema'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/font-awesome.min.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    /* ===== IMPORTANTE: NO modificar body (para no romper sidebar/topbar) ===== */

    .chatinst-wrap {
        padding: 15px;
    }

    .chatinst-header {
        margin-bottom: 12px;
    }

    .chatinst-title {
        margin: 0;
        font-weight: 700;
    }

    .chatinst-subtitle {
        color: #777;
        margin-top: 3px;
        font-size: 12px;
    }

    /* Barra superior de herramientas */
    .chatinst-toolbar {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .chatinst-search {
        width: 340px;
        max-width: 100%;
        border-radius: 10px;
    }

    /* Contenedor principal (sigue estilo del sistema: claro) */
    .chatinst-panel {
        border-radius: 8px;
    }

    /* Grid tipo Evolution (cards oscuras dentro de layout claro) */
    .evo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 12px;
        padding: 12px;
    }

    .evo-card {
        background: #0f172a;
        /* dark card */
        border: 1px solid #1f2937;
        border-radius: 14px;
        padding: 14px;
        color: #e5e7eb;
        box-shadow: 0 10px 22px rgba(0, 0, 0, .12);
        min-height: 180px;
        position: relative;
    }

    .evo-card .name {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
    }

    .evo-card .sub {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 4px;
        word-break: break-all;
    }

    .evo-chip {
        display: inline-block;
        font-size: 11px;
        padding: 4px 9px;
        border-radius: 999px;
        background: #111827;
        border: 1px solid #243244;
        color: #cbd5e1;
        margin-left: 6px;
        vertical-align: middle;
    }

    .evo-state {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        color: #cbd5e1;
        font-size: 12px;
    }

    .evo-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #6b7280;
    }

    .evo-dot.ok {
        background: #22c55e;
    }

    .evo-dot.warn {
        background: #f59e0b;
    }

    .evo-dot.bad {
        background: #ef4444;
    }

    .evo-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .btn-evo {
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 12px;
        border: 1px solid #243244;
        background: #111827;
        color: #e5e7eb;
    }

    .btn-evo:hover {
        background: #0b1220;
        color: #fff;
    }

    .btn-evo-green {
        background: #16a34a;
        border-color: #15803d;
    }

    .btn-evo-green:hover {
        background: #15803d;
    }

    .btn-evo-blue {
        background: #2563eb;
        border-color: #1d4ed8;
    }

    .btn-evo-blue:hover {
        background: #1d4ed8;
    }

    .btn-evo-red {
        background: #dc2626;
        border-color: #b91c1c;
    }

    .btn-evo-red:hover {
        background: #b91c1c;
    }

    .evo-footer {
        margin-top: 10px;
        font-size: 11px;
        color: #94a3b8;
    }

    /* Modal QR más limpio */
    .modal-content {
        border-radius: 10px;
    }

    .qrbox {
        background: #f7f7f7;
        border: 1px dashed #ddd;
        border-radius: 10px;
        min-height: 260px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        overflow: hidden;
    }

    .qrbox img {
        max-width: 100%;
        height: auto;
    }

    /* Ajustes pequeños */
    .muted {
        color: #777;
    }
    </style>
</head>

<body>

    <?php
// Menú como el resto del sistema
if (isset($usuarioApp['id_perfil']) && (int)$usuarioApp['id_perfil'] == 2) {
  include(MODULO . 'menu_vendedor.php');
} else {
  include(MODULO . 'menu_admin.php');
}
?>

    <div class="chatinst-wrap">

        <div class="chatinst-header">
            <h3 class="chatinst-title"><i class="fa fa-whatsapp"></i> Instancias WhatsApp</h3>
            <div class="chatinst-subtitle">Conecta números por QR y monitorea el estado (estilo Evolution, adaptado a tu
                sistema).</div>

            <div class="chatinst-toolbar">
                <input id="q" class="form-control input-sm chatinst-search"
                    placeholder="Buscar instancia (nombre / instance_name)">
                <button class="btn btn-default btn-sm" id="btnRefresh" title="Actualizar">
                    <i class="fa fa-refresh"></i>
                </button>
                <a class="btn btn-primary btn-sm" href="chat.php"><i class="fa fa-inbox"></i> Bandeja</a>
                <a class="btn btn-default btn-sm" href="chat-config.php"><i class="fa fa-cog"></i> Config</a>
                <button class="btn btn-success btn-sm" id="btnAdd"><i class="fa fa-plus"></i> Instancia</button>
            </div>
        </div>

        <div class="panel panel-default chatinst-panel">
            <div class="panel-heading">
                <strong><i class="fa fa-plug"></i> Lista de instancias</strong>
            </div>

            <div id="gridWrap">
                <div id="grid" class="evo-grid"></div>
            </div>
        </div>

    </div>

    <!-- Modal QR -->
    <div class="modal fade" id="qrModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="max-width:520px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-qrcode"></i> Conectar por QR</h4>
                </div>
                <div class="modal-body">
                    <div class="muted" id="qrTitle" style="margin-bottom:10px;"></div>
                    <div class="qrbox" id="qrBox"><span class="muted">Cargando QR...</span></div>
                    <div class="muted" style="margin-top:10px;font-size:12px;">
                        WhatsApp &gt; Dispositivos vinculados &gt; Vincular dispositivo
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary btn-sm" id="btnQrReload"><i class="fa fa-refresh"></i> Regenerar
                        QR</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document" style="max-width:560px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Crear instancia</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" style="font-size:12px;margin-bottom:10px;">
                        Esto registra la instancia en tu sistema. Luego podrás generar QR y conectar.
                    </div>

                    <div class="form-group">
                        <label>Nombre visible</label>
                        <input class="form-control input-sm" id="addNombre" placeholder="Ej: WhatsApp Ventas">
                    </div>
                    <div class="form-group">
                        <label>Instance Name (Evolution)</label>
                        <input class="form-control input-sm" id="addInstanceName" placeholder="Ej: ventas_rahina_1">
                    </div>
                    <div class="form-group">
                        <label>Prefijo país (opcional)</label>
                        <input class="form-control input-sm" id="addCountry" placeholder="Ej: 591">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success btn-sm" id="btnCreate"><i class="fa fa-check"></i> Crear</button>
                </div>
            </div>
        </div>
    </div>

    <?php include(MODULO . 'Tema.JS.php'); ?>

    <script>
    (function() {
            // Usa tu endpoint REAL (ojo: en tu servidor te dio 404 con chat-api.php porque el archivo se llama chat.api.php)
            const API = "<?php echo URLBASE; ?>sistema/modulo/chat/chat.api.php";

            let INST = [];
            let currentQrId = null;

            function esc(s) {
                return (s || '').toString()
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }

            async function callApi(action, data = null, method = 'POST') {
                let url = API + '?action=' + encodeURIComponent(action);
                const opt = {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                };

                if (method === 'GET' && data) {
                    url += '&' + new URLSearchParams(data).toString();
                } else if (method !== 'GET' && data) {
                    opt.body = JSON.stringify(data);
                }

                const r = await fetch(url, opt);
                const j = await r.json();
                if (!j.ok) throw new Error(j.error || 'Error');
                return j;
            }

            function dotByState(state) {
                const s = (state || '').toLowerCase();
                if (s.includes('open') || s.includes('connected') || s.includes('online')) return 'ok';
                if (s.includes('qr') || s.includes('connecting') || s.includes('sync')) return 'warn';
                if (s.includes('close') || s.includes('disconnected') || s.includes('offline')) return 'bad';
                return '';
            }

            function labelByState(state) {
                if (!state) return 'Desconocido';
                const s = state.toLowerCase();
                if (s.includes('open') || s.includes('connected') || s.includes('online')) return 'Conectado';
                if (s.includes('qr')) return 'Requiere QR';
                if (s.includes('connecting')) return 'Conectando';
                if (s.includes('close') || s.includes('disconnected') || s.includes('offline')) return 'Desconectado';
                return state;
            }

            function render() {
                const q = ($('#q').val() || '').trim().toLowerCase();
                const list = !q ? INST : INST.filter(i =>
                    (i.nombre || '').toLowerCase().includes(q) ||
                    (i.instance_name || '').toLowerCase().includes(q)
                );

                const $grid = $('#grid');

                if (!list.length) {
                    $grid.html('<div class="muted" style="padding:10px;">No hay instancias para mostrar.</div>');
                    return;
                }

                $grid.html(list.map(i => {
                                const chip = (parseInt(i.activo || 1) === 1) ? 'Activo' : 'Inactivo';
                                const st = i.__state || 'Cargando...';
                                const dot = dotByState(st);

                                return `
        <div class="evo-card">
          <div style="display:flex; justify-content:space-between; gap:10px;">
            <div style="min-width:0;">
              <div class="name">${esc(i.nombre || 'Sin nombre')}</div>
              <div class="sub">${esc(i.instance_name || '')}</div>
            </div>
            <div style="text-align:right;">
              <span class="evo-chip">${esc(chip)}</span>
            </div>
          </div>

          <div class="evo-state">
            <span class="evo-dot ${dot}"></span>
            <span>${esc(labelByState(st))}</span>
          </div>

          <div class="evo-actions">
            <button class="btn-evo btn-evo-green" onclick="window.__openQr(${i.id})"><i class="fa fa-qrcode"></i> QR</button>
            <button class="btn-evo btn-evo-blue" onclick="window.__refreshState(${i.id})"><i class="fa fa-refresh"></i> Estado</button>
            <button class="btn-evo btn-evo-red" onclick="window.__logoutInst(${i.id})"><i