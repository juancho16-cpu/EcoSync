// Vista: la campana de notificaciones del menu

// Numero rojo arriba de la campana (cuantas no se leyeron)
function mostrarContadorMensajes(cantidad) {
    const contador = document.getElementById("contadorMensajes");
    if (cantidad > 0) {
        contador.textContent = cantidad;
        contador.style.display = "inline-block";
    } else {
        contador.style.display = "none";
    }
}

// Pasa "2026-11-06 10:28:00" a algo mas facil de leer, como "06/11 10:28"
function fechaCorta(fecha) {
    const dia = fecha.substring(8, 10);
    const mes = fecha.substring(5, 7);
    const hora = fecha.substring(11, 16);
    return dia + "/" + mes + " " + hora;
}

// Lista de notificaciones que se ve al tocar la campana
function mostrarListaMensajes(lista) {
    const caja = document.getElementById("listaMensajes");

    if (lista.length == 0) {
        caja.innerHTML = `<p class="sin-mensajes">No tenés notificaciones</p>`;
        return;
    }

    let html = "";
    for (const m of lista) {
        let clase = "mensaje-item";
        if (m.leido == 0) {
            clase = "mensaje-item sin-leer";
        }
        html += `<a class="${clase}" href="#" data-click="abrirMensaje" data-args="${m.id},${limpiarTexto(m.enlace)}">
            ${limpiarTexto(m.texto)}
            <small>${fechaCorta(m.fecha)}</small>
        </a>`;
    }
    caja.innerHTML = html;
}

// Cartelito que aparece abajo a la derecha cuando llega una notificacion nueva
function mostrarAvisoEmergente(m) {
    const aviso = document.getElementById("avisoEmergente");
    aviso.innerHTML = `<strong>${icono("campana")}Nueva notificación</strong><br>${limpiarTexto(m.texto)}`;
    aviso.onclick = function () {
        abrirMensaje(m.id, m.enlace);
    };
    aviso.classList.add("visible");

    // A los 6 segundos se va solo
    setTimeout(function () {
        aviso.classList.remove("visible");
    }, 6000);
}
