
let listaCentros = [];
let idEditando = null; // null cuando se esta agregando uno nuevo

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    if (puedeEditar()) {
        try {
            const operarios = await obtenerOperarios();
            llenarSelect("id_operario", operarios, "id");

            const tipos = await obtenerTiposResiduo();
            llenarCasillasResiduos(tipos);
        } catch (error) {
            mostrarMensaje(error.message, true);
        }
    }
    await cargarCentros();
}

async function cargarCentros() {
    try {
        listaCentros = await obtenerCentros();
        mostrarTablaCentros(listaCentros, puedeEditar());
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function buscar() {
    const texto = document.getElementById("buscador").value.toLowerCase();

    const filtrados = [];
    for (const c of listaCentros) {
        const contenido = (c.nombre + " " + c.calle + " " + c.zona).toLowerCase();
        if (contenido.includes(texto)) {
            filtrados.push(c);
        }
    }
    mostrarTablaCentros(filtrados, puedeEditar());
}

function nuevoCentro() {
    idEditando = null;
    document.getElementById("formCentro").reset();
    document.getElementById("tituloModal").textContent = "Nuevo centro de acopio";
    abrirModal();
}

function editarCentro(id) {
    const c = buscarPorId(listaCentros, id);
    idEditando = id;

    document.getElementById("nombre").value = c.nombre;
    document.getElementById("calle").value = c.calle ?? "";
    document.getElementById("zona").value = c.zona ?? "";
    document.getElementById("capacidad_actual").value = c.capacidad_actual ? Number(c.capacidad_actual) : "";
    document.getElementById("capacidad_max").value = c.capacidad_max ? Number(c.capacidad_max) : "";
    document.getElementById("id_operario").value = c.id_operario ?? "";

    const casillas = document.querySelectorAll("input[name='residuo']");
    for (const casilla of casillas) {
        casilla.checked = false;
        for (const r of c.residuos) {
            if (r.id_residuo == casilla.value) {
                casilla.checked = true;
            }
        }
    }

    document.getElementById("tituloModal").textContent = "Modificar centro #" + id;
    abrirModal();
}

async function borrarCentroElegido(id) {
    if (!confirm("¿Seguro que querés borrar el centro de acopio #" + id + "?")) {
        return;
    }
    try {
        const respuesta = await borrarCentro(id);
        mostrarMensaje(respuesta.mensaje, false);
        cargarCentros();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

document.getElementById("formCentro").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const residuos = [];
    const casillas = document.querySelectorAll("input[name='residuo']");
    for (const casilla of casillas) {
        if (casilla.checked) {
            residuos.push(Number(casilla.value));
        }
    }

    const datos = {
        nombre: document.getElementById("nombre").value.trim(),
        calle: document.getElementById("calle").value.trim(),
        zona: document.getElementById("zona").value.trim(),
        capacidad_actual: document.getElementById("capacidad_actual").value,
        capacidad_max: document.getElementById("capacidad_max").value,
        id_operario: document.getElementById("id_operario").value,
        residuos: residuos
    };

    try {
        let respuesta;
        if (idEditando == null) {
            respuesta = await crearCentro(datos);
        } else {
            respuesta = await modificarCentro(idEditando, datos);
        }
        cerrarModal();
        mostrarMensaje(respuesta.mensaje, false);
        cargarCentros();
    } catch (error) {
        alert(error.message);
    }
});

iniciar();
