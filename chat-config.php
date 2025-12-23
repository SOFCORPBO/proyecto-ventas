
document.getElementById('btnSaveIntegracion')?.addEventListener('click', saveIntegracion);
<?php
require_once __DIR__ . '/sistema/configuracion.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? 0;
if (!$userId) {
    header("Location: iniciar-sesion.php");
    exit;
}

$bootstrapCss = (defined('ESTATICO') && defined('TEMA')) ? (ESTATICO . 'tema/' . TEMA . '/css/bootstrap.min.css') : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
$bootstrapJs  = (defined('ESTATICO') && defined('TEMA')) ? (ESTATICO . 'tema/' . TEMA . '/js/bootstrap.bundle.min.js') : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Config Chat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="<?php echo htmlspecialchars($bootstrapCss); ?>" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .card { border-radius:14px; box-shadow:0 8px 30px rgba(0,0,0,.06); border:0; }
    .small-muted { font-size:.85rem; color:#6b7280; }
  </style>
</head>
<body class="p-3">
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Configuración de Chat</h4>
      <div class="small-muted">Instancias Evolution API, webhooks, cron, etiquetas y plantillas.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="chat.php">Volver a bandeja</a>
    </div>
  </div>

  <div class="row g-3">
    <!-- Instancias -->
    <div class="col-12 col-lg-7">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Instancias</div>
          <button class="btn btn-sm btn-primary" id="btnNewInst">Nueva</button>
        </div>
        <div class="card-body">
          <div id="instList" class="table-responsive"></div>
          <div class="small-muted mt-2">
            Recomendación: usa un <b>webhook_secret</b> largo y aleatorio por instancia.
          </div>
        </div>
      </div>
    </div>

    <!-- Cron -->
    <div class="col-12 col-lg-5">
      <div class="card">
        <div class="card-header fw-semibold">Cron Jobs</div>
        <div class="card-body">
          <div class="alert alert-warning">
            Debes ejecutar <code>sistema/cron/cron-runner.php</code> cada 1 minuto desde el cron del servidor.
          </div>
          <div id="cronList" class="table-responsive"></div>
        </div>
      </div>
    </div>

    <!-- Etiquetas -->
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Etiquetas</div>
          <button class="btn btn-sm btn-outline-primary" id="btnNewTag">Agregar</button>
        </div>
        <div class="card-body">
          <div id="tagsList" class="table-responsive"></div>
        </div>
      </div>
    </div>

    <!-- Plantillas -->
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Plantillas</div>
          <button class="btn btn-sm btn-outline-primary" id="btnNewTpl">Agregar</button>
        </div>
        <div class="card-body">
          <div id="tplList" class="table-responsive"></div>
        </div>
      </div>
    </div>


    <!-- Integración CRM/POS -->
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Integración CRM/POS</div>
          <button class="btn btn-sm btn-outline-primary" id="btnSaveIntegracion">Guardar</button>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label class="form-label">URL ficha cliente</label>
              <input class="form-control" id="set_url_cliente_ficha" placeholder="cliente.php?id={id_cliente}">
              <div class="small-muted">Placeholders disponibles: <code>{id_cliente}</code>, <code>{telefono}</code>, <code>{numero_e164}</code>.</div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">URL nuevo cliente</label>
              <input class="form-control" id="set_url_cliente_nuevo" placeholder="nuevo-cliente.php">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">URL nueva venta</label>
              <input class="form-control" id="set_url_venta_nueva" placeholder="registro-de-ventas.php?idcliente={id_cliente}">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">URL nuevo trámite</label>
              <input class="form-control" id="set_url_tramite_nuevo" placeholder="tramites.php?idcliente={id_cliente}">
            </div>
          </div>
          <div class="small-muted mt-2">
            Para autoselección de cliente/teléfono en las pantallas destino, incluye <code>sistema/modulo/chat/chat.inject.php</code> en tu plantilla base.
          </div>
          <pre id="integracionResult" class="p-2 bg-light rounded-3 mt-2" style="max-height:160px; overflow:auto;"></pre>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Instancia -->
<div class="modal fade" id="modalInst" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div class="fw-semibold">Instancia</div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="inst_id">
        <div class="row g-2">
          <div class="col-12 col-md-6">
            <label class="form-label">Nombre</label>
            <input class="form-control" id="inst_nombre" placeholder="WhatsApp Principal">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Instance Name (Evolution)</label>
            <input class="form-control" id="inst_instance_name" placeholder="rahina-instance">
          </div>
          <div class="col-12">
            <label class="form-label">Base URL Evolution</label>
            <input class="form-control" id="inst_base_url" placeholder="https://tu-evolution:8080">
          </div>
          <div class="col-12">
            <label class="form-label">API Key</label>
            <input class="form-control" id="inst_api_key" placeholder="apikey...">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Webhook Secret</label>
            <input class="form-control" id="inst_webhook_secret" placeholder="secret...">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Activo</label>
            <select class="form-select" id="inst_activo">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Webhook Enabled</label>
            <select class="form-select" id="inst_webhook_enabled">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>

        <hr>
        <div class="small-muted mb-2">Acciones rápidas</div>
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-outline-secondary" id="btnTestConn">Probar conexión</button>
          <button class="btn btn-outline-primary" id="btnRegisterWebhook">Registrar webhook</button>
        </div>
        <div class="mt-2">
          <div class="small-muted">Resultado</div>
          <pre id="instResult" class="p-2 bg-light rounded-3" style="max-height:220px; overflow:auto;"></pre>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary" id="btnSaveInst">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tag -->
<div class="modal fade" id="modalTag" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <div class="fw-semibold">Etiqueta</div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Nombre</label>
        <input class="form-control" id="tag_nombre" placeholder="Ej: VIP">
        <label class="form-label mt-2">Color (opcional)</label>
        <input class="form-control" id="tag_color" placeholder="#0d6efd">
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary" id="btnSaveTag">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Template -->
<div class="modal fade" id="modalTpl" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div class="fw-semibold">Plantilla</div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Nombre</label>
        <input class="form-control" id="tpl_nombre" placeholder="Saludo">
        <label class="form-label mt-2">Texto</label>
        <textarea class="form-control" id="tpl_texto" rows="5" placeholder="Hola, ¿en qué puedo ayudarte?"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary" id="btnSaveTpl">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo htmlspecialchars($bootstrapJs); ?>"></script>
<script>
const apiUrl = 'sistema/modulo/chat/chat.api.php';

async function api(action, data=null, method='POST') {
  let url = apiUrl + '?action=' + encodeURIComponent(action);
  let opts = { method, headers: { 'Content-Type': 'application/json' } };
  if (method !== 'GET' && data) opts.body = JSON.stringify(data);
  if (method === 'GET' && data) url += '&' + new URLSearchParams(data).toString();
  const r = await fetch(url, opts);
  const j = await r.json();
  if (!j.ok) throw new Error(j.error || 'Error');
  return j;
}
function esc(s){ return (s||'').toString().replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m])); }

let instModal, tagModal, tplModal;

async function loadIntegracion(){
  const r = document.getElementById('integracionResult');
  try{
    const j = await api('get_settings', { prefix: 'chat.integracion.' }, 'GET');
    const s = j.data || {};

    document.getElementById('set_url_cliente_ficha').value = s['chat.integracion.url_cliente_ficha'] || 'cliente.php?id={id_cliente}';
    document.getElementById('set_url_cliente_nuevo').value = s['chat.integracion.url_cliente_nuevo'] || 'nuevo-cliente.php';
    document.getElementById('set_url_venta_nueva').value = s['chat.integracion.url_venta_nueva'] || 'registro-de-ventas.php?idcliente={id_cliente}';
    document.getElementById('set_url_tramite_nuevo').value = s['chat.integracion.url_tramite_nuevo'] || 'tramites.php?idcliente={id_cliente}';

    r.innerText = 'OK';
  }catch(e){
    r.innerText = e.message;
  }
}

async function saveIntegracion(){
  const r = document.getElementById('integracionResult');
  const settings = {
    'chat.integracion.url_cliente_ficha': document.getElementById('set_url_cliente_ficha').value.trim(),
    'chat.integracion.url_cliente_nuevo': document.getElementById('set_url_cliente_nuevo').value.trim(),
    'chat.integracion.url_venta_nueva': document.getElementById('set_url_venta_nueva').value.trim(),
    'chat.integracion.url_tramite_nuevo': document.getElementById('set_url_tramite_nuevo').value.trim(),
  };
  try{
    await api('save_settings', { settings });
    r.innerText = 'Guardado.';
  }catch(e){
    r.innerText = e.message;
  }
}

async function loadInst() {
  const j = await api('list_instancias', null, 'GET');
  const el = document.getElementById('instList');
  if(!j.data.length){
    el.innerHTML = '<div class="text-muted">No hay instancias configuradas.</div>';
    return;
  }
  el.innerHTML = `
    <table class="table table-sm align-middle">
      <thead><tr>
        <th>Nombre</th><th>Instance</th><th>Estado</th><th>Activo</th><th style="width:220px"></th>
      </tr></thead>
      <tbody>
        ${j.data.map(i=>`
          <tr>
            <td>${esc(i.nombre)}</td>
            <td><code>${esc(i.instance_name)}</code></td>
            <td>${esc(i.estado||'')}</td>
            <td>${parseInt(i.activo||0)===1 ? 'Sí' : 'No'}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary" data-edit="${i.id}">Editar</button>
              <button class="btn btn-sm btn-outline-danger" data-del="${i.id}">Desactivar</button>
            </td>
          </tr>`).join('')}
      </tbody>
    </table>
  `;
  el.querySelectorAll('button[data-edit]').forEach(b=>{
    b.addEventListener('click', ()=>openInst(parseInt(b.dataset.edit), j.data));
  });
  el.querySelectorAll('button[data-del]').forEach(b=>{
    b.addEventListener('click', async ()=>{
      if(!confirm('Desactivar instancia?')) return;
      await api('instance_delete', { id: parseInt(b.dataset.del) });
      await loadInst();
    });
  });
}

function openInst(id=null, list=[]) {
  const i = list.find(x=>parseInt(x.id)===id) || {};
  document.getElementById('inst_id').value = i.id || '';
  document.getElementById('inst_nombre').value = i.nombre || '';
  document.getElementById('inst_base_url').value = i.base_url || '';
  document.getElementById('inst_api_key').value = i.api_key || '';
  document.getElementById('inst_instance_name').value = i.instance_name || '';
  document.getElementById('inst_webhook_secret').value = i.webhook_secret || '';
  document.getElementById('inst_activo').value = (i.activo!==undefined) ? i.activo : 1;
  document.getElementById('inst_webhook_enabled').value = (i.webhook_enabled!==undefined) ? i.webhook_enabled : 1;
  document.getElementById('instResult').innerText = '';
  instModal.show();
}

async function saveInst(){
  const data = {
    id: document.getElementById('inst_id').value || undefined,
    nombre: document.getElementById('inst_nombre').value,
    base_url: document.getElementById('inst_base_url').value,
    api_key: document.getElementById('inst_api_key').value,
    instance_name: document.getElementById('inst_instance_name').value,
    webhook_secret: document.getElementById('inst_webhook_secret').value,
    activo: parseInt(document.getElementById('inst_activo').value),
    webhook_enabled: parseInt(document.getElementById('inst_webhook_enabled').value),
  };
  const j = await api('instance_save', data);
  document.getElementById('inst_id').value = j.data.id;
  await loadInst();
  alert('Guardado.');
}

async function testConn(){
  const id = parseInt(document.getElementById('inst_id').value || 0);
  if(!id){ alert('Guarda la instancia primero.'); return; }
  const j = await api('instance_test', { id_instancia: id });
  document.getElementById('instResult').innerText = JSON.stringify(j.data, null, 2);
}

async function regWebhook(){
  const id = parseInt(document.getElementById('inst_id').value || 0);
  if(!id){ alert('Guarda la instancia primero.'); return; }
  const j = await api('instance_register_webhook', { id_instancia: id });
  document.getElementById('instResult').innerText = JSON.stringify(j.data, null, 2);
}

async function loadCron(){
  const j = await api('cron_list', null, 'GET');
  const el = document.getElementById('cronList');
  el.innerHTML = `
    <table class="table table-sm align-middle">
      <thead><tr><th>Job</th><th>Intervalo (seg)</th><th>Habilitado</th><th></th></tr></thead>
      <tbody>
        ${j.data.map(x=>`
          <tr>
            <td>${esc(x.nombre)}</td>
            <td><input class="form-control form-control-sm" data-int="${x.id}" value="${esc(x.intervalo_seg)}"></td>
            <td>
              <select class="form-select form-select-sm" data-hab="${x.id}">
                <option value="1" ${parseInt(x.habilitado)===1?'selected':''}>Sí</option>
                <option value="0" ${parseInt(x.habilitado)===0?'selected':''}>No</option>
              </select>
            </td>
            <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-save="${x.id}">Guardar</button></td>
          </tr>`).join('')}
      </tbody>
    </table>
  `;
  el.querySelectorAll('button[data-save]').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id = parseInt(b.dataset.save);
      const intervalo = parseInt(document.querySelector(`input[data-int="${id}"]`).value || 60);
      const hab = parseInt(document.querySelector(`select[data-hab="${id}"]`).value || 1);
      await api('cron_save', { id, intervalo_seg: intervalo, habilitado: hab });
      alert('Cron actualizado.');
    });
  });
}

async function loadTags(){
  const j = await api('list_tags', null, 'GET');
  const el = document.getElementById('tagsList');
  if(!j.data.length){ el.innerHTML = '<div class="text-muted">Sin etiquetas.</div>'; return; }
  el.innerHTML = `
    <table class="table table-sm">
      <thead><tr><th>Nombre</th><th>Color</th></tr></thead>
      <tbody>
        ${j.data.map(t=>`<tr><td>${esc(t.nombre)}</td><td>${esc(t.color||'')}</td></tr>`).join('')}
      </tbody>
    </table>
  `;
}

async function saveTag(){
  const nombre = document.getElementById('tag_nombre').value;
  const color = document.getElementById('tag_color').value;
  await api('save_tag', { nombre, color });
  tagModal.hide();
  document.getElementById('tag_nombre').value='';
  document.getElementById('tag_color').value='';
  await loadTags();
}

async function loadTpl(){
  const j = await api('list_templates', null, 'GET');
  const el = document.getElementById('tplList');
  if(!j.data.length){ el.innerHTML = '<div class="text-muted">Sin plantillas.</div>'; return; }
  el.innerHTML = `
    <table class="table table-sm">
      <thead><tr><th>Nombre</th><th>Texto</th></tr></thead>
      <tbody>
        ${j.data.map(t=>`<tr><td>${esc(t.nombre)}</td><td class="small-muted">${esc(t.texto).slice(0,120)}</td></tr>`).join('')}
      </tbody>
    </table>
  `;
}

async function saveTpl(){
  const nombre = document.getElementById('tpl_nombre').value;
  const texto = document.getElementById('tpl_texto').value;
  await api('save_template', { nombre, texto });
  tplModal.hide();
  document.getElementById('tpl_nombre').value='';
  document.getElementById('tpl_texto').value='';
  await loadTpl();
}

document.getElementById('btnNewInst').addEventListener('click', ()=>openInst(null, []));
document.getElementById('btnSaveInst').addEventListener('click', saveInst);
document.getElementById('btnTestConn').addEventListener('click', testConn);
document.getElementById('btnRegisterWebhook').addEventListener('click', regWebhook);

document.getElementById('btnNewTag').addEventListener('click', ()=>tagModal.show());
document.getElementById('btnSaveTag').addEventListener('click', saveTag);

document.getElementById('btnNewTpl').addEventListener('click', ()=>tplModal.show());
document.getElementById('btnSaveTpl').addEventListener('click', saveTpl);
document.getElementById('btnSaveIntegracion')?.addEventListener('click', saveIntegracion);

(async ()=>{
  instModal = new bootstrap.Modal(document.getElementById('modalInst'));
  tagModal  = new bootstrap.Modal(document.getElementById('modalTag'));
  tplModal  = new bootstrap.Modal(document.getElementById('modalTpl'));

  await loadInst();
  await loadCron();
  await loadTags();
  await loadTpl();
  await loadIntegracion();
})();
</script>
</body>
</html>
