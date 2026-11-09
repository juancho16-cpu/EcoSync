
let listaRepuestos = [];
let idEditando = null; // null cuando se esta agregando uno nuevo

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    if (puedeEditar()) {
        try {
            const tipos = await obtenerTiposResiduo();
            llenarSelect("id_residuo", tipos, "id_residuo");

            const instalaciones = await obtenerInstalaciones();
            llenarSelect("id_instalacion", instalaciones, "id");
        } catch (error) {
            mostrarMensaje(error.message, true);
        }
    }
    await cargarRepuestos();
}

async function cargarRepuestos() {
    try {
        listaRepuestos = await obtenerRepuestos();
        buscar();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function buscar() {
    const texto = document.getElementById("buscador").value.toLowerCase();

    const filtrados = [];
    for (const r of listaRepuestos) {
        const contenido = (r.nombre + " " + r.tipo + " " + (r.residuo ?? "") + " " + (r.instalacion ?? "")).toLowerCase();
        if (contenido.includes(texto)) {
            filtrados.push(r);
        }
    }
    mostrarTablaRepuestos(filtrados, puedeEditar());
}

async function usarRepuesto(id, cambio) {
    try {
        const respuesta = await cambiarStock(id, cambio);
        mostrarMensaje(respuesta.mensaje, false);
        cargarRepuestos();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function nuevoRepuesto() {
    idEditando = null;
    document.getElementById("formRepuesto").reset();
    document.getElementById("tituloModal").textContent = "Nuevo repuesto";
    abrirModal();
}

function editarRepuesto(id) {
    const r = buscarPorId(listaRepuestos, id);
    idEditando = id;

    document.getElementById("nombre").value = r.nombre;
    document.getElementById("tipo").value = r.tipo;
    document.getElementById("id_residuo").value = r.id_residuo ?? "";
    document.getElementById("cantidad").value = r.cantidad;
    document.getElementById("cantidad_minima").value = r.cantidad_minima;
    document.getElementById("id_instalacion").value = r.id_instalacion ?? "";

    document.getElementById("tituloModal").textContent = "Modificar repuesto #" + id;
    abrirModal();
}

async function borrarRepuestoElegido(id) {
    if (!confirm("¿Seguro que querés borrar el repuesto #" + id + "?")) {
        return;
    }
    try {
        const respuesta = await borrarRepuesto(id);
        mostrarMensaje(respuesta.mensaje, false);
        cargarRepuestos();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

document.getElementById("formRepuesto").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
        nombre: document.getElementById("nombre").value,
        tipo: document.getElementById("tipo").value,
        id_residuo: document.getElementById("id_residuo").value,
        cantidad: document.getElementById("cantidad").value,
        cantidad_minima: document.getElementById("cantidad_minima").value,
        id_instalacion: document.getElementById("id_instalacion").value
    };

    try {
        let respuesta;
        if (idEditando == null) {
            respuesta = await crearRepuesto(datos);
        } else {
            respuesta = await modificarRepuesto(idEditando, datos);
        }
        cerrarModal();
        mostrarMensaje(respuesta.mensaje, false);
        cargarRepuestos();
    } catch (error) {
        alert(error.message);
    }
});

iniciar();
