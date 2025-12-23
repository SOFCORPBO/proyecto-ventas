<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// IMPORTANTE: require_once evita dobles includes (y los "Constant already defined")
require_once __DIR__ . '/sistema/configuracion.php';

// Misma lógica que en plantilla-base.php
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// (Opcional pero recomendado) Asegurar llaves que usan chat-config/chat-bridge
if (isset($usuarioApp) && is_array($usuarioApp)) {
    if (!isset($_SESSION['id_usuario']) && isset($usuarioApp['id'])) {
        $_SESSION['id_usuario'] = (int)$usuarioApp['id'];
    }
    if (!isset($_SESSION['id_perfil']) && isset($usuarioApp['id_perfil'])) {
        $_SESSION['id_perfil'] = (int)$usuarioApp['id_perfil'];
    }
}

// CSS/JS
$bootstrapCss = (defined('ESTATICO') && defined('TEMA'))
    ? (ESTATICO . 'tema/' . TEMA . '/css/bootstrap.min.css')
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';

$bootstrapJs  = (defined('ESTATICO') && defined('TEMA'))
    ? (ESTATICO . 'tema/' . TEMA . '/js/bootstrap.bundle.min.js')
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';


?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Bandeja de Chat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?php echo htmlspecialchars($bootstrapCss); ?>" rel="stylesheet">
    <style>
    body {
        background: #f6f7fb;
    }

    .chat-layout {
        height: calc(100vh - 24px);
    }

    .panel {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
        height: 100%;
    }

    .panel-header {
        padding: 14px 16px;
        border-bottom: 1px solid #eef0f4;
    }

    .panel-body {
        height: calc(100% - 60px);
        overflow: auto;
    }

    .conv-item {
        cursor: pointer;
        padding: 10px 12px;
        border-bottom: 1px solid #f1f2f6;
    }

    .conv-item:hover {
        background: #f8fafc;
    }

    .conv-item.active {
        background: #eef6ff;
    }

    .badge-unread {
        font-size: .72rem;
    }

    .msg {
        max-width: 78%;
        padding: 10px 12px;
        border-radius: 14px;
        margin: 8px 0;
    }

    .msg.in {
        background: #f2f4f7;
    }

    .msg.out {
        background: #e8f1ff;
        margin-left: auto;
    }

    .msg-meta {
        font-size: .72rem;
        opacity: .7;
        margin-top: 3px;
    }

    .composer {
        border-top: 1px solid #eef0f4;
        padding: 10px;
    }

    .tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: .75rem;
        background: #eef0f4;
        margin-right: 6px;
        margin-bottom: 6px;
    }

    .small-muted {
        font-size: .85rem;
        color: #6b7280;
    }
    </style>
</head>

<body class="p-3">
    <div class="container-fluid chat-layout">
        <div class="row g-3 h-100">
            <!-- LEFT: Conversations -->
            <div class="col-12 col-lg-3 h-100">
                <div class="panel h-100">
                    <div class="panel-header d-flex gap-2 align-items-center">
                        <select id="instanceSelect" class="form-select form-select-sm"></select>
                        <button class="btn btn-sm btn-outline-secondary" id="btnRefresh">Actualizar</button>
                    </div>
                    <div class="px-3 py-2">
                        <input id="searchBox" class="form-control form-control-sm"
                            placeholder="Buscar (nombre, número, etc.)">
                        <div class="d-flex gap-2 mt-2">
                            <select id="estadoSelect" class="form-select form-select-sm">
                                <option value="todas">Todas</option>
                                <option value="abierta">Abierta</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="cerrada">Cerrada</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" id="btnSearch">Buscar</button>
                        </div>
                    </div>
                    <div id="convList" class="panel-body"></div>
                </div>
            </div>

            <!-- CENTER: Messages -->
            <div class="col-12 col-lg-6 h-100">
                <div class="panel h-100 d-flex flex-column">
                    <div class="panel-header d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold" id="chatTitle">Selecciona una conversación</div>
                            <div class="small-muted" id="chatSub"></div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btnMarkRead" disabled>Marcar
                                leído</button>
                            <button class="btn btn-sm btn-outline-secondary" id="btnLoadInfo" disabled>Info</button>
                        </div>
                    </div>

                    <div id="msgList" class="panel-body px-3 py-2"></div>

                    <div class="composer">
                        <div class="d-flex gap-2">
                            <select id="tplSelect" class="form-select form-select-sm" style="max-width:220px">
                                <option value="">Plantillas</option>
                            </select>
                            <input id="msgText" class="form-control form-control-sm" placeholder="Escribe un mensaje..."
                                disabled>
                            <button class="btn btn-sm btn-primary" id="btnSend" disabled>Enviar</button>
                        </div>
                        <div class="small-muted mt-2">Tip: agrega plantillas desde Configuración.</div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Details -->
            <div class="col-12 col-lg-3 h-100">
                <div class="panel h-100">
                    <div class="panel-header d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Detalle</div>
                        <a class="btn btn-sm btn-outline-dark" href="chat-config.php">Config</a>
                    </div>
                    <div class="panel-body p-3">
                        <div id="detailEmpty" class="text-muted">Selecciona una conversación para ver detalle.</div>

                        <div id="detailBox" style="display:none">
                            <div class="mb-2">
                                <div class="small-muted">Cliente</div>
                                <div class="fw-semibold" id="clienteNombre">—</div>
                                <div class="small-muted" id="clienteTel">—</div>
                            </div>

                            <div class="mb-3">
                                <div class="small-muted">Etiquetas</div>
                                <div id="tagList" class="mt-1"></div>
                                <button class="btn btn-sm btn-outline-primary mt-2" id="btnEditTags">Editar
                                    etiquetas</button>
                            </div>

                            <div class="mb-3">
                                <div class="small-muted">Estado</div>
                                <div class="d-flex gap-2 mt-1">
                                    <select id="estadoConv" class="form-select form-select-sm" style="max-width:160px">
                                        <option value="abierta">Abierta</option>
                                        <option value="pendiente">Pendiente</option>
                                        <option value="cerrada">Cerrada</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary" id="btnSaveEstado">Guardar</button>
                                </div>
                            </div>

                            <hr>
                            <div class="mb-2 fw-semibold">Notas internas</div>
                            <div id="notesList" class="mb-2"></div>
                            <div class="d-flex gap-2">
                                <input id="noteText" class="form-control form-control-sm" placeholder="Agregar nota...">
                                <button class="btn btn-sm btn-outline-primary" id="btnAddNote">Agregar</button>
                            </div>


                            <hr>
                            <div class="mb-2 fw-semibold">Acciones CRM/POS</div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-outline-dark" id="btnOpenCliente" disabled>Abrir
                                    cliente</button>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-success flex-fill" id="btnNuevaVenta"
                                        disabled>Nueva venta</button>
                                    <button class="btn btn-sm btn-outline-success flex-fill" id="btnNuevoTramite"
                                        disabled>Nuevo trámite</button>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" id="btnCrearClienteRapido">Crear cliente
                                    rápido</button>
                                <button class="btn btn-sm btn-outline-secondary" id="btnNuevoCliente">Abrir formulario
                                    nuevo cliente</button>
                            </div>
                            <div class="small-muted mt-2" id="crmHint">Tip: Las pantallas se abren en una nueva pestaña
                                con el contexto del chat (cliente/teléfono).</div>
                            <hr>
                            <div class="mb-2 fw-semibold">Vincular cliente</div>
                            <input id="clienteSearch" class="form-control form-control-sm"
                                placeholder="Buscar cliente por nombre o teléfono">
                            <div id="clienteResults" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal: Tags -->
    <div class="modal fade" id="modalTags" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="fw-semibold">Etiquetas</div>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="allTags"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" id="btnSaveTags">Guardar</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal: Crear cliente rápido -->
    <div class="modal fade" id="modalCrearCliente" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="fw-semibold">Crear cliente rápido</div>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light small mb-2">
                        Se creará un cliente con el número del chat y quedará vinculado a esta conversación.
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Nombre *</label>
                        <input id="nuevoCliNombre" class="form-control form-control-sm" placeholder="Nombre y apellido">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm">Tipo</label>
                            <select id="nuevoCliTipo" class="form-select form-select-sm">
                                <option value="CI">CI</option>
                                <option value="PASAPORTE">Pasaporte</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm">CI/Pasaporte</label>
                            <input id="nuevoCliCi" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label form-label-sm">Email</label>
                        <input id="nuevoCliEmail" class="form-control form-control-sm" type="email"
                            placeholder="correo@dominio.com">
                    </div>
                    <div class="mt-2">
                        <label class="form-label form-label-sm">Dirección</label>
                        <input id="nuevoCliDir" class="form-control form-control-sm">
                    </div>
                    <div class="small-muted mt-2" id="nuevoCliInfo"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" id="btnDoCrearCliente">Crear y vincular</button>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo htmlspecialchars($bootstrapJs); ?>"></script>
    <script>
    const apiUrl = 'sistema/modulo/chat/chat.api.php';
    const bridgeUrl = "chat-bridge.php";
    let current = {
        instancia: null,
        convId: null,
        conv: null,
        tagsAll: [],
        tagsSelected: []
    };

    async function api(action, data = null, method = 'POST') {
        let url = apiUrl + '?action=' + encodeURIComponent(action);
        let opts = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        if (method !== 'GET' && data) opts.body = JSON.stringify(data);
        if (method === 'GET' && data) {
            const qs = new URLSearchParams(data).toString();
            url += '&' + qs;
        }
        const r = await fetch(url, opts);
        const j = await r.json();
        if (!j.ok) throw new Error(j.error || 'Error');
        return j;
    }

    function esc(s) {
        return (s || '').toString().replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        } [m]));
    }

    function renderConvs(list) {
        const el = document.getElementById('convList');
        if (!list.length) {
            el.innerHTML = '<div class="p-3 text-muted">Sin conversaciones.</div>';
            return;
        }

        el.innerHTML = list.map(c => {
            const active = (current.convId === parseInt(c.id)) ? 'active' : '';
            const name = c.cliente_nombre ? esc(c.cliente_nombre) : ('+' + esc(c.numero_e164));
            const sub = c.ultimo_mensaje ? esc(c.ultimo_mensaje).slice(0, 60) : '';
            const unread = parseInt(c.no_leidos || 0);
            const badge = unread > 0 ? `<span class="badge text-bg-primary badge-unread">${unread}</span>` : '';
            const estado = esc(c.estado || '');
            return `
      <div class="conv-item ${active}" data-id="${c.id}">
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-semibold">${name}</div>
          <div class="d-flex gap-2 align-items-center">${badge}<span class="badge text-bg-light">${estado}</span></div>
        </div>
        <div class="small-muted">${sub}</div>
      </div>
    `;
        }).join('');

        el.querySelectorAll('.conv-item').forEach(item => {
            item.addEventListener('click', () => selectConversation(parseInt(item.dataset.id)));
        });
    }

    function renderMessages(list) {
        const el = document.getElementById('msgList');
        if (!list.length) {
            el.innerHTML = '<div class="py-3 text-muted">Sin mensajes.</div>';
            return;
        }
        el.innerHTML = list.map(m => {
            const cls = (m.direction === 'out') ? 'out' : 'in';
            const txt = m.texto ? esc(m.texto) : `<span class="text-muted">[${esc(m.tipo)}]</span>`;
            const meta = `${esc(m.creado_en || '')} ${m.status ? '• ' + esc(m.status) : ''}`;
            return `<div class="msg ${cls}">
      <div>${txt}</div>
      <div class="msg-meta">${meta}</div>
    </div>`;
        }).join('');
        el.scrollTop = el.scrollHeight;
    }

    function setDetailVisible(on) {
        document.getElementById('detailEmpty').style.display = on ? 'none' : 'block';
        document.getElementById('detailBox').style.display = on ? 'block' : 'none';
    }

    async function loadInstances() {
        const j = await api('list_instancias', null, 'GET');
        const sel = document.getElementById('instanceSelect');
        sel.innerHTML = j.data.map(i => `<option value="${i.id}">${esc(i.nombre)} (${esc(i.estado||'')})</option>`)
            .join('');
        current.instancia = sel.value ? parseInt(sel.value) : null;
        sel.addEventListener('change', () => {
            current.instancia = parseInt(sel.value);
            refreshConvs();
        });
    }

    async function loadTemplates() {
        try {
            const j = await api('list_templates', null, 'GET');
            const sel = document.getElementById('tplSelect');
            sel.innerHTML = `<option value="">Plantillas</option>` + j.data.map(t =>
                `<option value="${esc(t.texto)}">${esc(t.nombre)}</option>`).join('');
            sel.addEventListener('change', () => {
                if (sel.value) document.getElementById('msgText').value = sel.value;
            });
        } catch (e) {}
    }

    async function refreshConvs() {
        if (!current.instancia) return;
        const search = document.getElementById('searchBox').value;
        const estado = document.getElementById('estadoSelect').value;
        const j = await api('list_conversations', {
            id_instancia: current.instancia,
            search,
            estado,
            limit: 80
        }, 'GET');
        renderConvs(j.data);
    }

    async function selectConversation(id) {
        current.convId = id;
        document.getElementById('btnSend').disabled = false;
        document.getElementById('msgText').disabled = false;
        document.getElementById('btnMarkRead').disabled = false;
        document.getElementById('btnLoadInfo').disabled = false;

        await loadConversationInfo();
        await refreshMessages();
        await loadNotes();

        // auto marcar leído
        try {
            await api('mark_read', {
                id_conversacion: current.convId
            });
        } catch (e) {}
        await refreshConvs();
    }

    async function loadConversationInfo() {
        const j = await api('get_conversation', {
            id: current.convId
        }, 'GET');
        current.conv = j.data;

        document.getElementById('chatTitle').innerText = j.data.cliente_nombre ? j.data.cliente_nombre : ('+' + j
            .data.numero_e164);
        document.getElementById('chatSub').innerText = j.data.remote_jid || '';

        document.getElementById('clienteNombre').innerText = j.data.cliente_nombre || 'No vinculado';
        document.getElementById('clienteTel').innerText = j.data.cliente_telefono || ('+' + j.data.numero_e164);

        document.getElementById('estadoConv').value = j.data.estado || 'abierta';

        renderTagPills(j.tags || []);
        setDetailVisible(true);
        updateCrmButtons();
    }

    function renderTagPills(tags) {
        const el = document.getElementById('tagList');
        if (!tags.length) {
            el.innerHTML = '<span class="text-muted">Sin etiquetas</span>';
            return;
        }
        el.innerHTML = tags.map(t => `<span class="tag">${esc(t.nombre)}</span>`).join('');
    }


    function updateCrmButtons() {
        const btnCliente = document.getElementById('btnOpenCliente');
        const btnVenta = document.getElementById('btnNuevaVenta');
        const btnTramite = document.getElementById('btnNuevoTramite');
        const btnCrear = document.getElementById('btnCrearClienteRapido');
        const btnNuevoCliente = document.getElementById('btnNuevoCliente');
        if (!btnCliente || !btnVenta || !btnTramite || !btnCrear || !btnNuevoCliente) return;

        if (!current.convId || !current.conv) {
            btnCliente.disabled = true;
            btnVenta.disabled = true;
            btnTramite.disabled = true;
            btnCrear.disabled = true;
            return;
        }

        const hasCliente = !!parseInt(current.conv.id_cliente || 0);

        btnCliente.disabled = false;
        btnVenta.disabled = false;
        btnTramite.disabled = false;

        btnCliente.innerText = hasCliente ? 'Abrir cliente' : 'Crear / abrir cliente';

        // Si ya hay cliente vinculado, ocultar creación rápida
        btnCrear.style.display = hasCliente ? 'none' : '';
        btnNuevoCliente.style.display = hasCliente ? 'none' : '';
    }
    async function refreshMessages() {
        const j = await api('get_messages', {
            id_conversacion: current.convId,
            limit: 120
        }, 'GET');
        renderMessages(j.data);
    }

    async function sendText() {
        const text = document.getElementById('msgText').value.trim();
        if (!text) return;
        document.getElementById('btnSend').disabled = true;

        try {
            const numero = current.conv ? current.conv.numero_e164 : '';
            await api('send_text', {
                id_instancia: current.instancia,
                id_conversacion: current.convId,
                numero,
                texto: text
            });
            document.getElementById('msgText').value = '';
            await refreshMessages();
            await refreshConvs();
        } catch (e) {
            alert(e.message);
        } finally {
            document.getElementById('btnSend').disabled = false;
        }
    }

    async function loadNotes() {
        const j = await api('list_notes', {
            id_conversacion: current.convId
        }, 'GET');
        const el = document.getElementById('notesList');
        if (!j.data.length) {
            el.innerHTML = '<div class="text-muted">Sin notas.</div>';
            return;
        }
        el.innerHTML = j.data.map(n => `
    <div class="border rounded-3 p-2 mb-2">
      <div class="small-muted">${esc(n.usuario_nombre || 'Usuario')} • ${esc(n.creado_en||'')}</div>
      <div>${esc(n.nota||'')}</div>
    </div>
  `).join('');
    }

    async function addNote() {
        const nota = document.getElementById('noteText').value.trim();
        if (!nota) return;
        await api('add_note', {
            id_conversacion: current.convId,
            nota
        });
        document.getElementById('noteText').value = '';
        await loadNotes();
    }

    async function openTagsModal() {
        const all = await api('list_tags', null, 'GET');
        current.tagsAll = all.data;
        const conv = await api('get_conversation', {
            id: current.convId
        }, 'GET');
        current.tagsSelected = (conv.tags || []).map(t => parseInt(t.id));

        const wrap = document.getElementById('allTags');
        if (!current.tagsAll.length) {
            wrap.innerHTML = '<div class="text-muted">No hay etiquetas. Crea desde Config.</div>';
        } else {
            wrap.innerHTML = current.tagsAll.map(t => {
                const checked = current.tagsSelected.includes(parseInt(t.id)) ? 'checked' : '';
                return `<div class="form-check">
        <input class="form-check-input tagCheck" type="checkbox" value="${t.id}" id="tag_${t.id}" ${checked}>
        <label class="form-check-label" for="tag_${t.id}">${esc(t.nombre)}</label>
      </div>`;
            }).join('');
        }

        new bootstrap.Modal(document.getElementById('modalTags')).show();
    }

    async function saveTags() {
        const ids = Array.from(document.querySelectorAll('.tagCheck'))
            .filter(x => x.checked).map(x => parseInt(x.value));
        await api('set_conv_tags', {
            id_conversacion: current.convId,
            tag_ids: ids
        });
        await loadConversationInfo();
        bootstrap.Modal.getInstance(document.getElementById('modalTags')).hide();
    }

    async function saveEstado() {
        const estado = document.getElementById('estadoConv').value;
        await api('set_status', {
            id_conversacion: current.convId,
            estado
        });
        await refreshConvs();
    }

    let clienteTimer = null;

    function setupClienteSearch() {
        const input = document.getElementById('clienteSearch');
        input.addEventListener('input', () => {
            clearTimeout(clienteTimer);
            clienteTimer = setTimeout(async () => {
                const q = input.value.trim();
                if (!q) {
                    document.getElementById('clienteResults').innerHTML = '';
                    return;
                }
                const j = await api('search_clientes', {
                    q
                }, 'GET');
                const el = document.getElementById('clienteResults');
                if (!j.data.length) {
                    el.innerHTML = '<div class="text-muted">Sin resultados</div>';
                    return;
                }
                el.innerHTML = j.data.map(c => `
        <div class="d-flex justify-content-between align-items-center border rounded-3 p-2 mb-2">
          <div>
            <div class="fw-semibold">${esc(c.nombre||'')}</div>
            <div class="small-muted">${esc(c.telefono||'')}</div>
          </div>
          <button class="btn btn-sm btn-outline-primary" data-id="${c.id}">Vincular</button>
        </div>
      `).join('');
                el.querySelectorAll('button[data-id]').forEach(b => {
                    b.addEventListener('click', async () => {
                        await api('link_cliente', {
                            id_conversacion: current.convId,
                            id_cliente: parseInt(b.dataset.id)
                        });
                        await loadConversationInfo();
                    });
                });
            }, 450);
        });
    }


    function openBridge(go) {
        if (!current.convId) return;
        const url = bridgeUrl + '?go=' + encodeURIComponent(go) + '&id_conversacion=' + encodeURIComponent(current
            .convId);
        window.open(url, '_blank');
    }

    function openCrearClienteModal() {
        if (!current.conv) return;
        document.getElementById('nuevoCliNombre').value = '';
        document.getElementById('nuevoCliTipo').value = 'CI';
        document.getElementById('nuevoCliCi').value = '';
        document.getElementById('nuevoCliEmail').value = '';
        document.getElementById('nuevoCliDir').value = '';
        document.getElementById('nuevoCliInfo').innerText = 'Número del chat: ' + (current.conv.numero_e164 ? ('+' +
            current.conv.numero_e164) : '');

        const modalEl = document.getElementById('modalCrearCliente');
        const m = bootstrap.Modal.getOrCreateInstance(modalEl);
        m.show();
    }

    async function doCrearClienteRapido() {
        const nombre = document.getElementById('nuevoCliNombre').value.trim();
        if (!nombre) {
            alert('Nombre requerido');
            return;
        }
        const tipo_documento = document.getElementById('nuevoCliTipo').value;
        const ci_pasaporte = document.getElementById('nuevoCliCi').value.trim();
        const email = document.getElementById('nuevoCliEmail').value.trim();
        const direccion = document.getElementById('nuevoCliDir').value.trim();

        const btn = document.getElementById('btnDoCrearCliente');
        btn.disabled = true;
        try {
            await api('create_cliente_quick', {
                id_conversacion: current.convId,
                nombre,
                tipo_documento,
                ci_pasaporte,
                email,
                direccion
            });
            bootstrap.Modal.getInstance(document.getElementById('modalCrearCliente'))?.hide();
            await loadConversationInfo();
            await refreshConvs();
            // abrir ficha
            openBridge('cliente');
        } catch (e) {
            alert(e.message);
        } finally {
            btn.disabled = false;
        }
    }
    document.getElementById('btnRefresh').addEventListener('click', refreshConvs);
    document.getElementById('btnSearch').addEventListener('click', refreshConvs);
    document.getElementById('btnSend').addEventListener('click', sendText);
    document.getElementById('msgText').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') sendText();
    });
    document.getElementById('btnMarkRead').addEventListener('click', async () => {
        await api('mark_read', {
            id_conversacion: current.convId
        });
        await refreshConvs();
    });
    document.getElementById('btnLoadInfo').addEventListener('click', loadConversationInfo);
    document.getElementById('btnAddNote').addEventListener('click', addNote);
    document.getElementById('btnEditTags').addEventListener('click', openTagsModal);
    document.getElementById('btnSaveTags').addEventListener('click', saveTags);
    document.getElementById('btnSaveEstado').addEventListener('click', saveEstado);

    document.getElementById('btnOpenCliente')?.addEventListener('click', () => openBridge('cliente'));
    document.getElementById('btnNuevaVenta')?.addEventListener('click', () => openBridge('venta'));
    document.getElementById('btnNuevoTramite')?.addEventListener('click', () => openBridge('tramite'));
    document.getElementById('btnNuevoCliente')?.addEventListener('click', () => openBridge('nuevo_cliente'));
    document.getElementById('btnCrearClienteRapido')?.addEventListener('click', openCrearClienteModal);
    document.getElementById('btnDoCrearCliente')?.addEventListener('click', doCrearClienteRapido);

    (async () => {
        await loadInstances();
        await loadTemplates();
        await refreshConvs();
        setupClienteSearch();

        // auto refresh cada 8s
        setInterval(async () => {
            try {
                if (current.instancia) await refreshConvs();
                if (current.convId) await refreshMessages();
            } catch (e) {}
        }, 8000);
    })();
    </script>
</body>

</html>