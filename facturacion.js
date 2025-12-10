/* ==========================================================
   facturacion.js — Módulo Profesional
   Sistema POS / Agencia de Viajes
========================================================== */

/* ----------------------------------------------------------
   ABRIR MODAL PARA FACTURAR
---------------------------------------------------------- */
function facturar(idVenta, monto) {
  // Llenar campos ocultos
  document.getElementById("fact_id_venta").value = idVenta;
  document.getElementById("fact_subtotal").value = monto;

  // Mostrar mensaje en el modal
  document.getElementById("fact_mensaje").innerHTML =
    "ID de Venta: <strong>" +
    idVenta +
    "</strong> | Monto: <strong>" +
    monto +
    " Bs</strong>";

  // Cálculos profesionales
  let iva = (monto * 0.13).toFixed(2);
  let it = (monto * 0.03).toFixed(2);
  let total = (parseFloat(monto) + parseFloat(iva) + parseFloat(it)).toFixed(2);

  // Asignar valores visuales
  document.getElementById("fact_input_subtotal").value = monto;
  document.getElementById("fact_input_iva").value = iva;
  document.getElementById("fact_input_it").value = it;
  document.getElementById("fact_input_total").value = total;

  $("#modalFacturacion").modal("show");
}

/* ----------------------------------------------------------
   GUIAR MODAL VER FACTURA
---------------------------------------------------------- */
function verFactura(idVenta) {
  $("#contenidoFactura").html(`
        <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <p>Cargando factura...</p>
        </div>
    `);

  $.post(
    "facturar-dashboard.php",
    { accion: "verFactura", id_venta: idVenta },
    function (data) {
      $("#contenidoFactura").hide().html(data).fadeIn(200);
    }
  );

  $("#modalVerFactura").modal("show");
}

/* ----------------------------------------------------------
   ENVIAR FORMULARIO DE FACTURACIÓN
---------------------------------------------------------- */
$("#formFacturar").on("submit", function (e) {
  e.preventDefault();

  let form = $(this);

  // Animación de envío
  form
    .find("button[type='submit']")
    .html("<i class='fa fa-spinner fa-spin'></i> Procesando...");

  $.post("facturar-dashboard.php", form.serialize(), function () {
    // Mensaje de éxito
    alert("Factura generada correctamente.");

    // Recargar para actualizar el dashboard
    location.reload();
  });
});

/* ==========================================================
   REPORTES PROFESIONALES
========================================================== */

/* -------------------------------
   Ventas FACTURADAS
------------------------------- */
function reporteFacturadas() {
  $("#contenedorReportes").html(`
        <div class='text-center'>
            <i class='fa fa-spinner fa-spin fa-3x'></i>
            <p>Generando reporte...</p>
        </div>
    `);

  $.post("facturar-dashboard.php", { accion: "facturadas" }, function (data) {
    $("#contenedorReportes").hide().html(data).fadeIn(200);
  });
}

/* -------------------------------
   Ventas SIN FACTURA
------------------------------- */
function reporteSinFactura() {
  $("#contenedorReportes").html(`
        <div class='text-center'>
            <i class='fa fa-spinner fa-spin fa-3x'></i>
            <p>Consultando ventas sin factura...</p>
        </div>
    `);

  $.post("facturar-dashboard.php", { accion: "sinFactura" }, function (data) {
    $("#contenedorReportes").hide().html(data).fadeIn(200);
  });
}

/* -------------------------------
   REPORTE DE IMPUESTOS (IVA / IT)
------------------------------- */
function reporteImpuestos() {
  $("#contenedorReportes").html(`
        <div class='text-center'>
            <i class='fa fa-cog fa-spin fa-3x text-warning'></i>
            <p>Calculando impuestos...</p>
        </div>
    `);

  $.post("reporte-impuestos.php", {}, function (data) {
    $("#contenedorReportes")
      .hide()
      .html(
        `
            <h4><i class="fa fa-percent"></i> Resumen de Impuestos (IVA / IT)</h4>
            <hr>
            ${data}
        `
      )
      .fadeIn(200);
  });
}
