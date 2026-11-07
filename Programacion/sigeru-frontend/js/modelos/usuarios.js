// Modelo: pedidos a la API de Usuarios

function iniciarSesion(email, contrasena) {
    return pedirAlServidor(API_USUARIOS + "/login", "POST", { email: email, contrasena: contrasena });
}

function cerrarSesion() {
    return pedirAlServidor(API_USUARIOS + "/logout", "POST");
}

function obtenerSesion() {
    return pedirAlServidor(API_USUARIOS + "/sesion");
}

function obtenerUsuarios() {
    return pedirAlServidor(API_USUARIOS + "/usuarios");
}

function crearUsuario(datos) {
    return pedirAlServidor(API_USUARIOS + "/usuarios", "POST", datos);
}

function modificarUsuario(id, datos) {
    return pedirAlServidor(API_USUARIOS + "/usuarios?id=" + id, "PUT", datos);
}

function borrarUsuario(id) {
    return pedirAlServidor(API_USUARIOS + "/usuarios?id=" + id, "DELETE");
}

// aprobado es true para aprobar el registro de un vecino o false para rechazarlo
function aprobarUsuario(id, aprobado) {
    return pedirAlServidor(API_USUARIOS + "/aprobar?id=" + id, "POST", { aprobado: aprobado });
}

// ----- Notificaciones (la campana) -----
function obtenerMensajes() {
    return pedirAlServidor(API_USUARIOS + "/mensajes");
}

// Sin id marca todas como leidas
function marcarMensajeLeido(id) {
    let direccion = API_USUARIOS + "/mensajes";
    if (id) {
        direccion = direccion + "?id=" + id;
    }
    return pedirAlServidor(direccion, "PUT");
}

// ----- Auditoria (solo el administrador) -----
function obtenerAuditoria(accion) {
    let url = API_USUARIOS + "/auditoria";
    if (accion != "") {
        url += "?accion=" + encodeURIComponent(accion);
    }
    return pedirAlServidor(url);
}

// ----- Parametros del sistema -----
function obtenerParametros() {
    return pedirAlServidor(API_USUARIOS + "/parametros");
}

function guardarParametros(datos) {
    return pedirAlServidor(API_USUARIOS + "/parametros", "PUT", datos);
}
