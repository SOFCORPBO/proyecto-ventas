// JavaScript Document

// Crear instancia AJAX según navegador
function objetoAjax() {
  var xmlhttp = false;
  try {
    xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
  } catch (e) {
    try {
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (E) {
      xmlhttp = false;
    }
  }

  if (!xmlhttp && typeof XMLHttpRequest != "undefined") {
    xmlhttp = new XMLHttpRequest();
  }
  return xmlhttp;
}

// Enviar datos del servicio (producto) al backend
function enviarDatosProducto() {
  let divResultado = document.getElementById("resultado");

  let Cliente = document.nuevo_producto.cliente.value;
  let Codigo = document.nuevo_producto.codigo.value;
  let Cantidad = document.nuevo_producto.cantidad.value;

  if (Codigo === "" || Cliente === "" || Cantidad === "" || Cantidad <= 0) {
    alert("Debe seleccionar servicio, cliente y cantidad válida.");
    return false;
  }

  let ajax = objetoAjax();

  ajax.open("POST", "registro.php", true);

  ajax.onreadystatechange = function () {
    if (ajax.readyState === 4) {
      divResultado.innerHTML = ajax.responseText;
      LimpiarCampos();
    }
  };

  ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  ajax.send(
    "cliente=" + Cliente + "&codigo=" + Codigo + "&cantidad=" + Cantidad
  );
}

// Limpia los campos después de agregar el producto
function LimpiarCampos() {
  document.nuevo_producto.codigo.value = "";
  document.nuevo_producto.cantidad.value = 1;
  document.nuevo_producto.codigo.focus();
}
