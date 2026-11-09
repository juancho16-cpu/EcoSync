
let listaContenedores = [];
let idEditando = null; // null cuando se esta agregando uno nuevo

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    try {
        const tipos = await obtenerTiposResiduo();
        llenarSelect("id_residuo", tipos, "id_residuo");
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
    await cargarContenedores();
}

async function cargarContenedores() {
    try {
        listaContenedores = await obtenerContenedores();
        mostrarTablaContenedores(listaContenedores, puedeEditar());
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function buscar() {
    const texto = document.getElementById("buscador").value.toLowerCase();

    const filtrados = [];
    for (const c of listaContenedores) {
        const contenido = (c.calle + " " + c.zona + " municipio " + c.municipio + " " + c.estado + " " + c.residuo).toLowerCase();
        if (contenido.includes(texto)) {
            filtrados.push(c);
        }
    }
    mostrarTablaContenedores(filtrados, puedeEditar());
}

function nuevoContenedor() {
    idEditando = null;
    document.getElementById("formContenedor").reset();
    document.getElementById("tituloModal").textContent = "Nuevo contenedor";
    abrirModal();
}

function editarContenedor(id) {
    const c = buscarPorId(listaContenedores, id);
    idEditando = id;

    document.getElementById("calle").value = c.calle;
    document.getElementById("zona").value = c.zona;
    document.getElementById("id_residuo").value = c.id_residuo;
    document.getElementById("capacidad").value = c.capacidad ? Number(c.capacidad) : "";
    document.getElementById("estado").value = c.estado;
    document.getElementById("municipio").value = c.municipio ?? "";
    document.getElementById("latitud").value = c.latitud ?? "";
    document.getElementById("longitud").value = c.longitud ?? "";

    document.getElementById("tituloModal").textContent = "Modificar contenedor #" + id;
    abrirModal();
}

async function borrarContenedorElegido(id) {
    if (!confirm("¿Seguro que querés borrar el contenedor #" + id + "?")) {
        return;
    }
    try {
        const respuesta = await borrarContenedor(id);
        mostrarMensaje(respuesta.mensaje, false);
        cargarContenedores();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

document.getElementById("formContenedor").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
        calle: document.getElementById("calle").value.trim(),
        zona: document.getElementById("zona").value.trim(),
        estado: document.getElementById("estado").value,
        capacidad: document.getElementById("capacidad").value,
        id_residuo: document.getElementById("id_residuo").value,
        municipio: document.getElementById("municipio").value,
        latitud: document.getElementById("latitud").value,
        longitud: document.getElementById("longitud").value
    };

    try {
        let respuesta;
        if (idEditando == null) {
            respuesta = await crearContenedor(datos);
        } else {
            respuesta = await modificarContenedor(idEditando, datos);
        }
        cerrarModal();
        mostrarMensaje(respuesta.mensaje, false);
        cargarContenedores();
    } catch (error) {
        alert(error.message);
    }
});

iniciar();
