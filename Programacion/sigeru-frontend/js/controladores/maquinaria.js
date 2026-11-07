// Controlador de la pagina de maquinaria

let listaMaquinaria = [];
let idEditando = null; // null cuando se esta agregando una nueva

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    // La lista de instalaciones solo hace falta si puede agregar o editar
    if (puedeEditar()) {
        try {
            const instalaciones = await obtenerInstalaciones();
            llenarSelect("id_instalacion", instalaciones, "id");
        } catch (error) {
            mostrarMensaje(error.message, true);
        }
    }
    await cargarMaquinaria();
}

async function cargarMaquinaria() {
    try {
        listaMaquinaria = await obtenerMaquinaria();
        mostrarTablaMaquinaria(listaMaquinaria, puedeEditar());
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function buscar() {
    const texto = document.getElementById("buscador").value.toLowerCase();

    // Guardamos solo las que tienen el texto buscado
    const filtrados = [];
    for (const m of listaMaquinaria) {
        const contenido = (m.nombre + " " + m.tipo + " " + m.estado + " " + m.instalacion).toLowerCase();
        if (contenido.includes(texto)) {
            filtrados.push(m);
        }
    }
    mostrarTablaMaquinaria(filtrados, puedeEditar());
}

function nuevaMaquina() {
    idEditando = null;
    document.getElementById("formMaquina").reset();
    document.getElementById("tituloModal").textContent = "Nueva máquina";
    abrirModal();
}

// Abre el formulario con los datos de la maquina elegida
function editarMaquina(id) {
    const m = buscarPorId(listaMaquinaria, id);
    idEditando = id;

    document.getElementById("nombre").value = m.nombre;
    document.getElementById("tipo").value = m.tipo ?? "";
    document.getElementById("estado").value = m.estado;
    document.getElementById("id_instalacion").value = m.id_instalacion ?? "";

    document.getElementById("tituloModal").textContent = "Modificar máquina #" + id;
    abrirModal();
}

async function borrarMaquinaElegido(id) {
    if (!confirm("¿Seguro que querés borrar la máquina #" + id + "?")) {
        return;
    }
    try {
        const respuesta = await borrarMaquina(id);
        mostrarMensaje(respuesta.mensaje, false);
        cargarMaquinaria();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

document.getElementById("formMaquina").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
        nombre: document.getElementById("nombre").value.trim(),
        tipo: document.getElementById("tipo").value.trim(),
        estado: document.getElementById("estado").value,
        id_instalacion: document.getElementById("id_instalacion").value
    };

    try {
        let respuesta;
        if (idEditando == null) {
            respuesta = await crearMaquina(datos);
        } else {
            respuesta = await modificarMaquina(idEditando, datos);
        }
        cerrarModal();
        mostrarMensaje(respuesta.mensaje, false);
        cargarMaquinaria();
    } catch (error) {
        // Si hay un error con la ventana abierta, el mensaje no se ve, por eso usamos alert
        alert(error.message);
    }
});

iniciar();
