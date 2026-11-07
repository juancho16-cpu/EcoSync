// Controlador de la pagina de recorridos (asignar rutas a las cuadrillas)

let listaRecorridos = [];
let listaZonas = [];
let idEditando = null; // null cuando se esta agregando uno nuevo

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    // Las listas del formulario solo hacen falta si puede asignar recorridos
    if (puedeEditar()) {
        try {
            const cuadrillas = await obtenerCuadrillas();
            llenarSelect("id_cuadrilla", cuadrillas, "id");

            const rutas = await obtenerRutas();
            llenarSelect("id_ruta", rutas, "id");

            // En la lista de camiones se muestra su estado, para saber cuales estan disponibles (RNE06)
            const camiones = await obtenerCamiones();
            const select = document.getElementById("id_camion");
            for (const c of camiones) {
                select.innerHTML += `<option value="${c.id}">${limpiarTexto(c.matricula)} (${limpiarTexto(c.estado)} - ${limpiarTexto(c.disponibilidad)})</option>`;
            }
        } catch (error) {
            mostrarMensaje(error.message, true);
        }
    }
    await cargarRecorridos();
}

async function cargarRecorridos() {
    try {
        listaRecorridos = await obtenerRecorridos();
        mostrarTablaRecorridos(listaRecorridos, puedeEditar());
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

// Carga las zonas de nuevo (porque cambian de recorrido) y marca las del recorrido elegido
async function cargarZonas(idRecorrido) {
    listaZonas = await obtenerZonas();
    llenarCasillasZonas(listaZonas);

    const casillas = document.querySelectorAll("input[name='zona']");
    for (const casilla of casillas) {
        const zona = buscarPorId(listaZonas, casilla.value);
        casilla.checked = (idRecorrido != null && zona.id_recorrido == idRecorrido);
    }
}

async function nuevoRecorrido() {
    idEditando = null;
    document.getElementById("formRecorrido").reset();
    document.getElementById("fecha_asignacion").value = fechaDeHoy();
    document.getElementById("tituloModal").textContent = "Asignar una ruta a una cuadrilla";
    await cargarZonas(null);
    abrirModal();
}

// Abre el formulario con los datos del recorrido elegido
async function editarRecorrido(id) {
    const r = buscarPorId(listaRecorridos, id);
    idEditando = id;

    document.getElementById("id_cuadrilla").value = r.id_cuadrilla;
    document.getElementById("id_ruta").value = r.id_ruta;
    document.getElementById("id_camion").value = r.id_camion ?? "";
    document.getElementById("fecha_asignacion").value = r.fecha_asignacion;
    document.getElementById("hora_inicio").value = r.hora_inicio ? r.hora_inicio.substring(0, 5) : "";
    document.getElementById("hora_fin").value = r.hora_fin ? r.hora_fin.substring(0, 5) : "";

    document.getElementById("tituloModal").textContent = "Modificar recorrido #" + id;
    await cargarZonas(id);
    abrirModal();
}

async function borrarRecorridoElegido(id) {
    if (!confirm("¿Seguro que querés borrar el recorrido #" + id + "? Sus zonas quedan sin recorrido.")) {
        return;
    }
    try {
        const respuesta = await borrarRecorrido(id);
        mostrarMensaje(respuesta.mensaje, false);
        cargarRecorridos();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

document.getElementById("formRecorrido").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    // Guardamos las zonas que estan marcadas
    const zonas = [];
    const casillas = document.querySelectorAll("input[name='zona']");
    for (const casilla of casillas) {
        if (casilla.checked) {
            zonas.push(Number(casilla.value));
        }
    }

    const datos = {
        id_cuadrilla: document.getElementById("id_cuadrilla").value,
        id_ruta: document.getElementById("id_ruta").value,
        id_camion: document.getElementById("id_camion").value,
        fecha_asignacion: document.getElementById("fecha_asignacion").value,
        hora_inicio: document.getElementById("hora_inicio").value,
        hora_fin: document.getElementById("hora_fin").value,
        zonas: zonas
    };

    try {
        let respuesta;
        if (idEditando == null) {
            respuesta = await crearRecorrido(datos);
        } else {
            respuesta = await modificarRecorrido(idEditando, datos);
        }
        cerrarModal();
        mostrarMensaje(respuesta.mensaje, false);
        cargarRecorridos();
    } catch (error) {
        // Si hay un error con la ventana abierta, el mensaje no se ve, por eso usamos alert
        alert(error.message);
    }
});

iniciar();
