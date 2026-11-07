// Controlador de la pagina de Seguridad: muestra la auditoria (solo el administrador)

let listaAuditoria = [];

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    // Los numeros de arriba se cuentan con todos los eventos (sin filtro)
    try {
        const todos = await obtenerAuditoria("");
        mostrarResumenAuditoria(todos);
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
    cargarAuditoria();
}

// Pide la auditoria a la API (con el filtro elegido) y la muestra en la tabla
async function cargarAuditoria() {
    const accion = document.getElementById("filtroAccion").value;
    try {
        listaAuditoria = await obtenerAuditoria(accion);
        mostrarTablaAuditoria(listaAuditoria);
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

// Cuenta cuantos eventos hay de cada tipo
function mostrarResumenAuditoria(lista) {
    let entradas = 0, fallidos = 0, bloqueos = 0, cambios = 0;
    for (const e of lista) {
        if (e.accion == "Inicio de sesión") entradas++;
        if (e.accion == "Login fallido") fallidos++;
        if (e.accion == "Login bloqueado") bloqueos++;
        if (e.accion.startsWith("Usuario") || e.accion.startsWith("Cambio") || e.accion.startsWith("Registro ")) cambios++;
    }
    contarHasta("cantEntradas", entradas);
    contarHasta("cantFallidos", fallidos);
    contarHasta("cantBloqueos", bloqueos);
    contarHasta("cantCambios", cambios);
}

// Color de la etiqueta segun lo que paso
function colorAccion(accion) {
    if (accion == "Login fallido") return "alerta";
    if (accion == "Login bloqueado" || accion == "Usuario eliminado" || accion == "Registro rechazado") return "mal";
    if (accion == "Inicio de sesión" || accion == "Registro aprobado") return "bien";
    return "gris";
}

function mostrarTablaAuditoria(lista) {
    const tabla = document.getElementById("tablaAuditoria");
    if (lista.length == 0) {
        tabla.innerHTML = '<tr><td colspan="6" class="vacio">Todavía no hay nada anotado.</td></tr>';
        return;
    }
    let html = "";
    for (const e of lista) {
        html += `<tr>
            <td>${limpiarTexto(e.fecha)}</td>
            <td><span class="etiqueta ${colorAccion(e.accion)}">${limpiarTexto(e.accion)}</span></td>
            <td>${limpiarTexto(e.usuario ?? "-")}</td>
            <td>${limpiarTexto(e.email ?? "-")}</td>
            <td>${limpiarTexto(e.detalle ?? "")}</td>
            <td>${limpiarTexto(e.ip ?? "")}</td>
        </tr>`;
    }
    tabla.innerHTML = html;
}

// Descarga lo que se esta viendo en un archivo que abre Excel
function exportarAuditoria() {
    const filas = [["Fecha", "Qué pasó", "Quién", "Correo", "Detalle", "IP"]];
    for (const e of listaAuditoria) {
        filas.push([e.fecha, e.accion, e.usuario, e.email, e.detalle, e.ip]);
    }
    descargarCSV("auditoria_" + fechaDeHoy() + ".csv", filas);
}

iniciar();
