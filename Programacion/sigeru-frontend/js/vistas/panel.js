// Vista: estadisticas y alertas del panel de control

// Lista de alertas. Cada alerta tiene nivel (alta, media o info), texto y enlace.
function mostrarAlertas(alertas) {
    const caja = document.getElementById("listaAlertas");

    if (alertas.length == 0) {
        caja.innerHTML = `<p class="sin-alertas">${icono("ok")}No hay alertas. ¡Está todo en orden!</p>`;
        return;
    }

    const iconos = { alta: "alerta", media: "reloj", info: "info" };
    let html = "";
    for (const a of alertas) {
        html += `<a class="alerta-item ${a.nivel}" href="${a.enlace}">${icono(iconos[a.nivel])}<span>${limpiarTexto(a.texto)}</span></a>`;
    }
    caja.innerHTML = html;
}

// Grafico de barras hecho con divs. "lista" tiene filas con un nombre y una cantidad.
// La barra mas larga es la de mayor cantidad y las demas se calculan en proporcion.
function mostrarBarras(idCaja, lista, campoNombre, textoVacio) {
    const caja = document.getElementById(idCaja);

    if (lista.length == 0) {
        caja.innerHTML = `<p class="texto-gris">${textoVacio}</p>`;
        return;
    }

    // Buscamos la cantidad mas grande
    let mayor = 0;
    for (const fila of lista) {
        if (Number(fila.cantidad) > mayor) mayor = Number(fila.cantidad);
    }

    let html = "";
    for (const fila of lista) {
        const ancho = Math.round(Number(fila.cantidad) * 100 / mayor);
        html += `<div class="barra-fila">
            <span class="barra-nombre">${limpiarTexto(fila[campoNombre])}</span>
            <div class="barra-fondo"><div class="barra" style="width: ${ancho}%"></div></div>
            <strong>${fila.cantidad}</strong>
        </div>`;
    }
    caja.innerHTML = html;
}

// Busca la cantidad de un estado en la lista que manda la API (si no esta, es 0)
function cantidadDelEstado(porEstado, estado) {
    for (const fila of porEstado) {
        if (fila.estado == estado) return Number(fila.cantidad);
    }
    return 0;
}

// Numeros grandes y graficos del panel
function mostrarEstadisticas(datos) {
    document.getElementById("statAbiertas").textContent = cantidadDelEstado(datos.por_estado, "Abierta");
    document.getElementById("statEnProceso").textContent = cantidadDelEstado(datos.por_estado, "En proceso");
    document.getElementById("statResueltasMes").textContent = datos.resueltas_mes;

    if (datos.promedio_dias == null) {
        document.getElementById("statPromedio").textContent = "-";
    } else {
        document.getElementById("statPromedio").textContent = datos.promedio_dias + " días";
    }

    // En el top se muestra la calle y la zona del contenedor
    const top = [];
    for (const c of datos.top_desbordados) {
        top.push({ nombre: "#" + c.id + " " + c.calle + " (" + c.zona + ")", cantidad: c.cantidad });
    }
    mostrarBarras("graficoTop", top, "nombre", "Ningún contenedor se desbordó este mes");

    const municipios = [];
    for (const m of datos.por_municipio) {
        municipios.push({ nombre: "Municipio " + m.municipio, cantidad: m.cantidad });
    }
    mostrarBarras("graficoMunicipios", municipios, "nombre", "No hay incidencias sin resolver");

    mostrarBarras("graficoTipos", datos.por_tipo, "tipo", "No hubo incidencias este mes");
}
