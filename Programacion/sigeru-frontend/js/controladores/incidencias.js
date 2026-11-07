// Controlador de la pagina de incidencias: reporte y seguimiento.
// La usan todos: el personal ve todas, y el vecino ve solo las que reporto ("Mis incidencias").

let listaIncidencias = [];
let misRecorridos = [];   // recorridos del recolector (solo puede cambiar las incidencias de estos)
let idSiguiendo = null;   // incidencia que se esta cambiando en la ventana de seguimiento

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    // El vecino ve la pagina como "Mis incidencias"
    if (usuarioLogueado.rol == "Vecino") {
        document.querySelector("h1").textContent = "Mis incidencias";
        document.querySelector(".subtitulo").textContent = "¿Un contenedor está lleno, roto, no lo vinieron a vaciar o hay basura en la calle? Reportalo y seguí acá cómo va.";
    }

    try {
        // Contenedores para el formulario de reportar
        const contenedores = await obtenerContenedores();
        const select = document.getElementById("id_contenedor");
        for (const c of contenedores) {
            select.innerHTML += `<option value="${c.id}">#${c.id} ${limpiarTexto(c.calle)} (${limpiarTexto(c.zona)})</option>`;
        }

        // Recorridos (solo el personal): el administrador y el operario los eligen en el seguimiento,
        // y al recolector le llegan solo los de su cuadrilla
        if (usuarioLogueado.rol != "Vecino") {
            const recorridos = await obtenerRecorridos();
            const selectRecorrido = document.getElementById("seguimientoRecorrido");
            for (const r of recorridos) {
                selectRecorrido.innerHTML += `<option value="${r.id}">${limpiarTexto(r.cuadrilla)} - ${limpiarTexto(r.ruta)} (${limpiarTexto(r.fecha_asignacion)})</option>`;
                misRecorridos.push(Number(r.id));
            }
        }
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
    await cargarIncidencias();

    // Si viene del mapa (incidencias.html?contenedor=5), se abre el formulario con ese contenedor
    const parametros = new URLSearchParams(window.location.search);
    if (parametros.get("contenedor")) {
        nuevaIncidencia();
        document.getElementById("id_contenedor").value = parametros.get("contenedor");

        // Sacamos el ?contenedor= de la direccion, asi si recarga la pagina no se vuelve a abrir el formulario
        history.replaceState(null, "", "incidencias.html");
    }
}

// Devuelve true si el usuario puede hacerle el seguimiento a esa incidencia
function puedeCambiar(i) {
    if (i.estado == "Resuelta") return false;
    if (usuarioLogueado.rol == "Vecino") return false;
    if (puedeEditar()) return true;
    // El recolector solo las que tiene asignadas su cuadrilla
    return misRecorridos.includes(Number(i.id_recorrido));
}

async function cargarIncidencias() {
    const estado = document.getElementById("filtroEstado").value;
    let filtros = "";
    if (estado != "") {
        filtros = "estado=" + encodeURIComponent(estado);
    }

    try {
        listaIncidencias = await obtenerIncidencias(filtros);
        for (const i of listaIncidencias) {
            i.puedeCambiar = puedeCambiar(i);
            // El historial de un contenedor es para el personal
            i.conHistorial = usuarioLogueado.rol != "Vecino";
        }
        buscar();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

// Filtra la tabla con lo que se escribe en el buscador
function buscar() {
    const texto = document.getElementById("buscador").value.toLowerCase();
    const filtrada = [];
    for (const i of listaIncidencias) {
        const datos = (i.calle + " " + i.zona_incidencia + " " + i.municipio + " " + i.tipo + " " + i.prioridad + " " + (i.cuadrilla ?? "") + " " + i.descripcion + " " + (i.referencia ?? "") + " " + (i.vecino ?? "")).toLowerCase();
        if (datos.includes(texto)) {
            filtrada.push(i);
        }
    }
    mostrarTablaIncidencias(filtrada);
}

// ----- Reportar una incidencia -----
function nuevaIncidencia() {
    document.getElementById("formIncidencia").reset();
    document.getElementById("tituloModal").textContent = "Reportar una incidencia";
    abrirModal();
}

document.getElementById("formIncidencia").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
        id_contenedor: document.getElementById("id_contenedor").value,
        problema: document.getElementById("problema").value,
        prioridad: document.getElementById("prioridad").value,
        descripcion: document.getElementById("descripcion").value.trim(),
        referencia: document.getElementById("referencia").value.trim()
    };

    try {
        const respuesta = await reportarProblema(datos);
        cerrarModal();
        mostrarMensaje(respuesta.mensaje, false);
        cargarIncidencias();
    } catch (error) {
        alert(error.message);
    }
});

// ----- Seguimiento -----
function abrirSeguimiento(id) {
    const i = buscarPorId(listaIncidencias, id);
    idSiguiendo = id;

    document.getElementById("tituloSeguimiento").textContent = "Incidencia #" + id + " - " + i.calle;
    let detalle = i.tipo + " · detectada el " + i.fecha_deteccion + " · " + i.descripcion;
    if (i.referencia) {
        detalle = detalle + " · Lugar: " + i.referencia;
    }
    if (i.vecino) {
        detalle = detalle + " · Reportada por " + i.vecino;
    }
    document.getElementById("detalleSeguimiento").textContent = detalle;
    document.getElementById("seguimientoEstado").value = i.estado;
    document.getElementById("seguimientoPrioridad").value = i.prioridad;
    document.getElementById("seguimientoRecorrido").value = i.id_recorrido ?? "";

    document.getElementById("seguimientoModal").showModal();
}

function cerrarSeguimiento() {
    document.getElementById("seguimientoModal").close();
}

document.getElementById("formSeguimiento").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
        estado: document.getElementById("seguimientoEstado").value,
        prioridad: document.getElementById("seguimientoPrioridad").value,
        id_recorrido: document.getElementById("seguimientoRecorrido").value
    };

    if (datos.estado == "Resuelta" && !confirm("Una incidencia resuelta ya no se puede cambiar. ¿Seguro que está resuelta?")) {
        return;
    }

    try {
        const respuesta = await seguimientoIncidencia(idSiguiendo, datos);
        cerrarSeguimiento();
        mostrarMensaje(respuesta.mensaje, false);
        cargarIncidencias();
    } catch (error) {
        alert(error.message);
    }
});

// ----- Historial de un contenedor -----
async function verHistorial(idContenedor) {
    try {
        const historial = await obtenerIncidencias("contenedor=" + idContenedor);
        let titulo = "Historial del contenedor #" + idContenedor;
        if (historial.length > 0) {
            titulo = titulo + " - " + historial[0].calle;
        }
        document.getElementById("tituloHistorial").textContent = titulo;
        mostrarTablaHistorial(historial);
        document.getElementById("historialModal").showModal();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function cerrarHistorial() {
    document.getElementById("historialModal").close();
}

iniciar();
