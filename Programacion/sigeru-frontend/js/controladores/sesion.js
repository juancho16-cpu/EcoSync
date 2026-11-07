// Controlador de la sesion: lo usan todas las paginas del panel

// Guarda los datos del usuario que inicio sesion
let usuarioLogueado = null;

// Revisa que haya un usuario logueado. Si no hay, lo manda al login y devuelve false.
async function revisarSesion() {
    try {
        const respuesta = await obtenerSesion();
        usuarioLogueado = respuesta.usuario;
    } catch (error) {
        window.location.href = "index.html";
        return false;
    }

    mostrarDatosUsuario(usuarioLogueado);
    mostrarContadorAlertas();
    await aplicarIdioma();

    // Revisamos las notificaciones ahora y despues cada 15 segundos
    revisarMensajes();
    setInterval(revisarMensajes, 15000);
    return true;
}

// Busca el idioma en los parametros del sistema y traduce la pagina
async function aplicarIdioma() {
    try {
        const parametros = await obtenerParametros();
        for (const p of parametros) {
            if (p.nombre == "idioma") {
                traducirPagina(p.valor);
            }
        }
    } catch (error) {
        // Si no se puede leer, la pagina queda en español
    }
}

// ----- Notificaciones (la campana) -----

let ultimoMensaje = null; // id de la notificacion mas nueva que ya vimos

async function revisarMensajes() {
    try {
        const datos = await obtenerMensajes();
        mostrarContadorMensajes(datos.sin_leer);
        mostrarListaMensajes(datos.mensajes);

        // Buscamos la notificacion mas nueva
        let masNueva = null;
        for (const m of datos.mensajes) {
            if (masNueva == null || Number(m.id) > Number(masNueva.id)) {
                masNueva = m;
            }
        }

        // Si llego una nueva desde la ultima vez (y no esta leida), mostramos el cartelito
        if (masNueva != null && ultimoMensaje != null && Number(masNueva.id) > ultimoMensaje && masNueva.leido == 0) {
            mostrarAvisoEmergente(masNueva);
        }
        if (masNueva != null) {
            ultimoMensaje = Number(masNueva.id);
        } else {
            ultimoMensaje = 0;
        }
    } catch (error) {
        // Si falla (por ejemplo, se corto internet) se vuelve a intentar en 15 segundos
    }
}

// Abre o cierra la lista de notificaciones
function abrirNotificaciones() {
    const panel = document.getElementById("panelNotificaciones");
    panel.classList.toggle("abierto");

    // Al abrirla, la actualizamos para ver las ultimas
    if (panel.classList.contains("abierto")) {
        revisarMensajes();
    }
}

// Al tocar una notificacion se marca como leida y se va a la pagina que corresponde
async function abrirMensaje(id, enlace) {
    try {
        await marcarMensajeLeido(id);
    } catch (error) {
        // Si no se pudo marcar, igual vamos a la pagina
    }
    window.location.href = enlace;
}

async function leerTodos() {
    try {
        await marcarMensajeLeido(null);
        revisarMensajes();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

// Muestra en el menu (al lado de Inicio) cuantas alertas tiene el usuario
async function mostrarContadorAlertas() {
    const contador = document.getElementById("contadorAlertas");
    if (contador == null) return;

    try {
        const alertas = await obtenerAlertas();
        if (alertas.length > 0) {
            contador.textContent = alertas.length;
            contador.style.display = "inline-block";
        }
    } catch (error) {
        // Si no se pueden cargar las alertas, el menu sigue funcionando igual
    }
}

// Devuelve true si el usuario puede agregar, editar y borrar contenedores, camiones, centros y maquinaria
function puedeEditar() {
    return usuarioLogueado.rol == "Administrador" || usuarioLogueado.rol == "Operario";
}

async function salir() {
    await cerrarSesion();
    window.location.href = "index.html";
}
