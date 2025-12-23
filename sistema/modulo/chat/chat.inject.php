<?php
/**
 * chat.inject.php
 * Inyecta contexto de chat (cliente/teléfono) para prellenar formularios y (opcionalmente) abrir ModalTramite.
 *
 * Recomendado: incluir este archivo desde sistema/configuracion.php o plantilla base.
 *
 * Contexto esperado en sesión:
 * $_SESSION['chat_ctx'] = [
 *   'id_cliente' => 123,
 *   'telefono'   => '5917XXXXXXX',
 *   'auto_open'  => 'tramite' // opcional
 *   'persist'    => 1         // opcional: no consumir
 * ];
 */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

if (empty($_SESSION['chat_ctx']) || !is_array($_SESSION['chat_ctx'])) {
    return;
}

$ctx = $_SESSION['chat_ctx'];

// One-shot por defecto: consumimos el contexto para no arrastrarlo a otras pantallas.
if (!isset($ctx['persist']) || !$ctx['persist']) {
    unset($_SESSION['chat_ctx']);
}

// Seguridad: limitar tamaño de payload
$ctx_json = json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($ctx_json === false) { return; }
if (strlen($ctx_json) > 10000) { return; } // evita inyectar JSON enorme
?>
<script>
window.__CHAT_CTX = <?php echo $ctx_json; ?>;

(function () {
  function normalizePhone(p) {
    if (!p) return '';
    return String(p).replace(/[^\d]/g, '');
  }

  function trigger(el) {
    try {
      if (typeof jQuery !== 'undefined') {
        jQuery(el).trigger('change').trigger('input');
      } else {
        el.dispatchEvent(new Event('change', { bubbles: true }));
        el.dispatchEvent(new Event('input', { bubbles: true }));
      }
    } catch (e) {}
  }

  function setVal(el, val) {
    if (!el) return false;
    el.value = val;
    trigger(el);
    return true;
  }

  function q(sel) { return document.querySelector(sel); }

  function setFirst(selectors, val) {
    for (var i = 0; i < selectors.length; i++) {
      var el = q(selectors[i]);
      if (el && setVal(el, val)) return true;
    }
    return false;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var ctx = window.__CHAT_CTX || {};

    var idCliente = ctx.id_cliente || ctx.idcliente || ctx.cliente_id || '';
    var tel = normalizePhone(ctx.telefono || ctx.celular || ctx.numero || ctx.numero_e164 || '');

    // CLIENTE: cubre tu modal de trámites (#frm_id_cliente) y variantes típicas en ventas
    if (idCliente) {
      setFirst([
        '#idcliente', 'select#idcliente', 'select[name="idcliente"]', 'input#idcliente', 'input[name="idcliente"]',
        '#id_cliente', 'select#id_cliente', 'select[name="id_cliente"]', 'input#id_cliente', 'input[name="id_cliente"]',
        '#cliente_id', 'select#cliente_id', 'select[name="cliente_id"]', 'input#cliente_id', 'input[name="cliente_id"]',
        '#frm_id_cliente', 'select#frm_id_cliente', 'select[name="id_cliente"]'
      ], idCliente);
    }

    // TELÉFONO
    if (tel) {
      setFirst([
        '#telefono', 'input#telefono', 'input[name="telefono"]',
        '#celular', 'input#celular', 'input[name="celular"]',
        '#whatsapp', 'input#whatsapp', 'input[name="whatsapp"]',
        '#numero_e164', 'input#numero_e164', 'input[name="numero_e164"]',
        '#e164', 'input#e164', 'input[name="e164"]'
      ], tel);
    }

    // Abrir modal de trámite si el contexto lo pide o si viene ?open_tramite=1
    var autoOpen = ctx.auto_open || '';
    var openTramiteParam = (new URLSearchParams(window.location.search)).get('open_tramite');

    if ((autoOpen === 'tramite' || openTramiteParam === '1') && (typeof jQuery !== 'undefined')) {
      var $m = jQuery('#ModalTramite');
      if ($m.length) {
        $m.modal('show');
        $m.on('shown.bs.modal', function () {
          if (idCliente) setVal(document.querySelector('#frm_id_cliente'), idCliente);
        });
      }
    }
  });
})();
</script>
