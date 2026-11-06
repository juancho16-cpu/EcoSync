// Modelo: pedidos a la API de Recoleccion

// ----- Camiones -----
function obtenerCamiones() {
    return pedirAlServidor(API_RECOLECCION + "/camiones");
}

function crearCamion(datos) {
    return pedirAlServidor(API_RECOLECCION + "/camiones", "POST", datos);
}

function modificarCamion(id, datos) {
    return pedirAlServidor(API_RECOLECCION + "/camiones?id=" + id, "PUT", datos);
}

function borrarCamion(id) {
    return pedirAlServidor(API_RECOLECCION + "/camiones?id=" + id, "DELETE");
}

// ----- Mantenimientos de un camion -----
function obtenerMantenimientos(idCamion) {
    return pedirAlServidor(API_RECOLECCION + "/mantenimientos?camion=" + idCamion);
}

function crearMantenimiento(datos) {
    return pedirAlServidor(API_RECOLECCION + "/mantenimientos", "POST", datos);
}

function borrarMantenimiento(idCamion, nro) {
    return pedirAlServidor(API_RECOLECCION + "/mantenimientos?camion=" + idCamion + "&nro=" + nro, "DELETE");
}

// ----- Recorridos (rutas asignadas a las cuadrillas) -----
function obtenerRecorridos() {
    return pedirAlServidor(API_RECOLECCION + "/recorridos");
}

function crearRecorrido(datos) {
    return pedirAlServidor(API_RECOLECCION + "/recorridos", "POST", datos);
}

function modificarRecorrido(id, datos) {
    return pedirAlServidor(API_RECOLECCION + "/recorridos?id=" + id, "PUT", datos);
}

function borrarRecorrido(id) {
    return pedirAlServidor(API_RECOLECCION + "/recorridos?id=" + id, "DELETE");
}

function obtenerCuadrillas() {
    return pedirAlServidor(API_RECOLECCION + "/cuadrillas");
}

function obtenerRutas() {
    return pedirAlServidor(API_RECOLECCION + "/rutas");
}

// Pide el camino por las calles que une varios puntos (lista de [latitud, longitud]).
// Usa el servicio gratis de OSRM (mapas de OpenStreetMap). Si no responde en 8 segundos o falla, devuelve null.
async function pedirCaminoPorCalles(puntos) {
    // OSRM pide los puntos al reves: longitud,latitud;longitud,latitud;...
    const partes = [];
    for (const p of puntos) {
        partes.push(Number(p[1]).toFixed(6) + "," + Number(p[0]).toFixed(6));
    }
    const direccion = "https://router.project-osrm.org/route/v1/driving/" + partes.join(";") + "?overview=full&geometries=geojson";

    const cancelar = new AbortController();
    const reloj = setTimeout(function () { cancelar.abort(); }, 8000);
    try {
        const respuesta = await fetch(direccion, { signal: cancelar.signal });
        const datos = await respuesta.json();
        if (datos.code != "Ok") {
            return null;
        }
        // Lo pasamos de nuevo a [latitud, longitud]
        const camino = [];
        for (const c of datos.routes[0].geometry.coordinates) {
            camino.push([c[1], c[0]]);
        }
        return camino;
    } catch (error) {
        return null;
    } finally {
        clearTimeout(reloj);
    }
}

function obtenerZonas() {
    return pedirAlServidor(API_RECOLECCION + "/zonas");
}
