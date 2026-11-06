// Controlador de la pagina de usuarios (solo la ve el administrador)

let listaUsuarios = [];
let idEditando = null; // null cuando se esta agregando uno nuevo

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    await cargarUsuarios();
}

async function cargarUsuarios() {
    try {
        listaUsuarios = await obtenerUsuarios();
        mostrarTablaUsuarios(listaUsuarios, true);
        mostrarSolicitudes();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function buscar() {
    const texto = document.getElementById("buscador").value.toLowerCase();

    // Guardamos solo los que tienen el texto buscado
    const filtrados = [];
    for (const u of listaUsuarios) {
        const contenido = (u.primer_nombre + " " + u.primer_apellido + " " + u.email + " " + u.rol + " " + u.estado).toLowerCase();
        if (contenido.includes(texto)) {
            filtrados.push(u);
        }
    }
    mostrarTablaUsuarios(filtrados, true);
}

// Muestra arriba de la tabla cuantos vecinos esperan aprobacion
function mostrarSolicitudes() {
    let pendientes = 0;
    for (const u of listaUsuarios) {
        if (u.estado == "Pendiente") pendientes++;
    }

    const aviso = document.getElementById("avisoSolicitudes");
    if (pendientes == 0) {
        aviso.style.display = "none";
        return;
    }
    let texto = "Hay " + pendientes + " vecinos esperando que apruebes su registro.";
    if (pendientes == 1) {
        texto = "Hay 1 vecino esperando que apruebes su registro.";
    }
    aviso.innerHTML = `${texto} <button class="btn btn-secondary btn-sm" data-click="verPendientes">Ver solicitudes</button>`;
    aviso.style.display = "block";
}

function verPendientes() {
    document.getElementById("buscador").value = "pendiente";
    buscar();
}

async function aprobarElegido(id, aprobado) {
    const u = buscarPorId(listaUsuarios, id);
    let pregunta = "¿Aprobar el registro de " + u.primer_nombre + " " + u.primer_apellido + "?";
    if (!aprobado) {
        pregunta = "¿Rechazar el registro de " + u.primer_nombre + " " + u.primer_apellido + "?";
    }
    if (!confirm(pregunta)) return;

    try {
        const respuesta = await aprobarUsuario(id, aprobado);
        mostrarMensaje(respuesta.mensaje, false);
        cargarUsuarios();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

// ----- Cambiar el rol de un usuario (por ejemplo, pasar un vecino a operario o a administrador) -----
let idCambioRol = null;

function abrirCambioRol(id) {
    const u = buscarPorId(listaUsuarios, id);
    idCambioRol = id;

    document.getElementById("nombreCambioRol").textContent = u.primer_nombre + " " + u.primer_apellido + " (ahora es " + u.rol + ")";
    document.getElementById("nuevoRol").value = u.rol;
    document.getElementById("especialidadRol").value = u.especialidad ?? "";
    mostrarEspecialidadRol();
    document.getElementById("rolModal").showModal();
}

function cerrarCambioRol() {
    document.getElementById("rolModal").close();
}

// La especialidad solo se pide si el rol nuevo es Operario
function mostrarEspecialidadRol() {
    const caja = document.getElementById("cajaEspecialidadRol");
    if (document.getElementById("nuevoRol").value == "Operario") {
        caja.style.display = "block";
    } else {
        caja.style.display = "none";
    }
}

document.getElementById("formRol").addEventListener("submit", async function (evento) {
    evento.preventDefault();
    const u = buscarPorId(listaUsuarios, idCambioRol);
    const nuevoRol = document.getElementById("nuevoRol").value;

    if (!confirm("¿Cambiar el rol de " + u.primer_nombre + " " + u.primer_apellido + " a " + nuevoRol + "?")) {
        return;
    }

    // Se mandan los mismos datos que ya tiene, solo cambia el rol (la contraseña vacia no se cambia)
    const datos = {
        primer_nombre: u.primer_nombre,
        primer_apellido: u.primer_apellido,
        email: u.email,
        telefono: u.telefono ?? "",
        rol: nuevoRol,
        contrasena: "",
        calle: u.calle ?? "",
        zona: u.zona ?? "",
        especialidad: document.getElementById("especialidadRol").value
    };

    try {
        await modificarUsuario(idCambioRol, datos);
        cerrarCambioRol();
        mostrarMensaje("Ahora " + u.primer_nombre + " " + u.primer_apellido + " es " + nuevoRol, false);
        cargarUsuarios();
    } catch (error) {
        alert(error.message);
    }
});

function nuevoUsuario() {
    idEditando = null;
    document.getElementById("formUsuario").reset();
    document.getElementById("tituloModal").textContent = "Nuevo usuario";

    // Al crear un usuario la contraseña es obligatoria
    document.getElementById("contrasena").required = true;
    document.getElementById("ayudaContrasena").textContent = "Mínimo 6 caracteres.";
    abrirModal();
}

// Abre el formulario con los datos del usuario elegido
function editarUsuario(id) {
    const u = buscarPorId(listaUsuarios, id);
    idEditando = id;

    document.getElementById("primer_nombre").value = u.primer_nombre;
    document.getElementById("primer_apellido").value = u.primer_apellido;
    document.getElementById("email").value = u.email;
    document.getElementById("telefono").value = u.telefono ?? "";
    document.getElementById("rol").value = u.rol;
    document.getElementById("calle").value = u.calle ?? "";
    document.getElementById("zona").value = u.zona ?? "";
    document.getElementById("especialidad").value = u.especialidad ?? "";

    // Al modificar, la contraseña solo se cambia si escriben una nueva
    document.getElementById("contrasena").value = "";
    document.getElementById("contrasena").required = false;
    document.getElementById("ayudaContrasena").textContent = "Déjala vacía para no cambiarla.";

    document.getElementById("tituloModal").textContent = "Modificar usuario #" + id;
    abrirModal();
}

async function borrarUsuarioElegido(id) {
    if (!confirm("¿Seguro que querés borrar el usuario #" + id + "?")) {
        return;
    }
    try {
        const respuesta = await borrarUsuario(id);
        mostrarMensaje(respuesta.mensaje, false);
        cargarUsuarios();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

document.getElementById("formUsuario").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
        primer_nombre: document.getElementById("primer_nombre").value.trim(),
        primer_apellido: document.getElementById("primer_apellido").value.trim(),
        email: document.getElementById("email").value.trim(),
        telefono: document.getElementById("telefono").value.trim(),
        rol: document.getElementById("rol").value,
        calle: document.getElementById("calle").value.trim(),
        zona: document.getElementById("zona").value.trim(),
        especialidad: document.getElementById("especialidad").value.trim(),
        contrasena: document.getElementById("contrasena").value
    };

    try {
        let respuesta;
        if (idEditando == null) {
            respuesta = await crearUsuario(datos);
        } else {
            respuesta = await modificarUsuario(idEditando, datos);
        }
        cerrarModal();
        mostrarMensaje(respuesta.mensaje, false);
        cargarUsuarios();
    } catch (error) {
        // Si hay un error con la ventana abierta, el mensaje no se ve, por eso usamos alert
        alert(error.message);
    }
});

iniciar();
