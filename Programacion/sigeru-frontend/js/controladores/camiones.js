// Controlador de la pagina de camiones

let listaCamiones = [];
let idEditando = null; // null cuando se esta agregando uno nuevo

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    await cargarCamiones();
}

async function cargarCamiones() {
    try {
        listaCamiones = await obtenerCamiones();
        mostrarTablaCamiones(listaCamiones, puedeEditar());
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function buscar() {
    const texto = document.getElementById("buscador").value.toLowerCase();

    // Guardamos solo los que tienen el texto buscado
    const filtrados = [];
    for (const c of listaCamiones) {
        const contenido = (c.matricula + " " + c.estado + " " + c.disponibilidad).toLowerCase();
        if (contenido.includes(texto)) {
            filtrados.push(c);
        }
    }
    mostrarTablaCamiones(filtrados, puedeEditar());
}

function nuevoCamion() {
    idEditando = null;
    document.getElementById("formCamion").reset();
    document.getElementById("tituloModal").textContent = "Nuevo camión";
    abrirModal();
}

// Abre el formulario con los datos del camion elegido
function editarCamion(id) {
    const c = buscarPorId(listaCamiones, id);
    idEditando = id;

    document.getElementById("matricula").value = c.matricula;
    document.getElementById("capacidad_carga").value = c.capacidad_carga ? Number(c.capacidad_carga) : "";
    document.getElementById("estado").value = c.estado;
    document.getElementById("disponibilidad").value = c.disponibilidad;

    document.getElementById("tituloModal").textContent = "Modificar camión #" + id;
    abrirModal();
}

async function borrarCamionElegido(id) {
    if (!confirm("¿Seguro que querés borrar el camión #" + id + "?")) {
        return;
    }
    try {
        const respuesta = await borrarCamion(id);
        mostrarMensaje(respuesta.mensaje, false);
        cargarCamiones();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

document.getElementById("formCamion").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
        matricula: document.getElementById("matricula").value.trim(),
        capacidad_carga: document.getElementById("capacidad_carga").value,
        estado: document.getElementById("estado").value,
        disponibilidad: document.getElementById("disponibilidad").value
    };

    try {
        let respuesta;
        if (idEditando == null) {
            respuesta = await crearCamion(datos);
        } else {
            respuesta = await modificarCamion(idEditando, datos);
        }
        cerrarModal();
        mostrarMensaje(respuesta.mensaje, false);
        cargarCamiones();
    } catch (error) {
        // Si hay un error con la ventana abierta, el mensaje no se ve, por eso usamos alert
        alert(error.message);
    }
});

// ----- Mantenimientos del camion -----
let camionDeMantenimiento = null; // camion que se esta mirando en la ventana de mantenimientos

async function verMantenimientos(id) {
    camionDeMantenimiento = buscarPorId(listaCamiones, id);
    document.getElementById("tituloMantenimientos").textContent = "Mantenimientos del camión " + camionDeMantenimiento.matricula;
    document.getElementById("formMantenimiento").reset();
    // La fecha de hoy ya viene puesta
    document.getElementById("fechaMantenimiento").value = fechaDeHoy();
    document.getElementById("mantenimientosModal").showModal();
    await cargarMantenimientos();
}

async function cargarMantenimientos() {
    try {
        const lista = await obtenerMantenimientos(camionDeMantenimiento.id);
        mostrarTablaMantenimientos(lista, puedeEditar());
    } catch (error) {
        alert(error.message);
    }
}

function cerrarMantenimientos() {
    document.getElementById("mantenimientosModal").close();
}

async function borrarMantenimientoElegido(idCamion, nro) {
    if (!confirm("¿Seguro que querés borrar este mantenimiento?")) {
        return;
    }
    try {
        await borrarMantenimiento(idCamion, nro);
        await cargarMantenimientos();
    } catch (error) {
        alert(error.message);
    }
}

document.getElementById("formMantenimiento").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
        id_camion: camionDeMantenimiento.id,
        tipo: document.getElementById("tipoMantenimiento").value,
        fecha: document.getElementById("fechaMantenimiento").value,
        costo: document.getElementById("costoMantenimiento").value,
        descripcion: document.getElementById("descripcionMantenimiento").value.trim()
    };

    try {
        await crearMantenimiento(datos);
        document.getElementById("costoMantenimiento").value = "";
        document.getElementById("descripcionMantenimiento").value = "";
        await cargarMantenimientos();
    } catch (error) {
        alert(error.message);
    }
});

iniciar();
