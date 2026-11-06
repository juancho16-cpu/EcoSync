// Modelo: funcion general para hablar con las APIs

// Hace un pedido a una API y devuelve la respuesta en JSON.
// Si la API devuelve un error, corta y pasa el mensaje de error.
async function pedirAlServidor(url, metodo = "GET", datos = null) {
    const opciones = { method: metodo };

    if (datos != null) {
        opciones.headers = { "Content-Type": "application/json" };
        opciones.body = JSON.stringify(datos);
    }

    let respuesta;
    try {
        respuesta = await fetch(url, opciones);
    } catch (error) {
        throw new Error("No se pudo conectar con el servidor");
    }

    const json = await respuesta.json();

    // Si la sesion se cerro sola por no usar el sistema, avisamos y volvemos al inicio de sesion
    if (respuesta.status == 401 && json.error.includes("se cerró") && !location.pathname.endsWith("index.html")) {
        alert(json.error);
        location.href = "index.html";
    }

    if (!respuesta.ok) {
        throw new Error(json.error);
    }
    return json;
}
