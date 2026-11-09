
let listaInforme = [];  // incidencias que se estan mostrando (para exportar)
let listaVolumen = [];  // recolecciones por contenedor (para exportar)

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    document.getElementById("desde").value = mesActual() + "-01";
    document.getElementById("hasta").value = fechaDeHoy();
    await verInforme();
}

async function verInforme() {
    const desde = document.getElementById("desde").value;
    const hasta = document.getElementById("hasta").value;
    const cuales = document.getElementById("cuales").value;

    if (desde == "" || hasta == "") {
        mostrarMensaje("Elegí las dos fechas", true);
        return;
    }

    try {
        const todas = await obtenerIncidencias("desde=" + desde + "&hasta=" + hasta);

        const abiertas = [];
        const cerradas = [];
        for (const i of todas) {
            if (i.estado == "Resuelta") {
                cerradas.push(i);
            } else {
                abiertas.push(i);
            }
        }

        let lista = todas;
        let titulo = "Todas las incidencias";
        if (cuales == "abiertas") {
            lista = abiertas;
            titulo = "Incidencias abiertas (sin resolver)";
        }
        if (cuales == "cerradas") {
            lista = cerradas;
            titulo = "Incidencias cerradas (resueltas)";
        }

        let suma = 0;
        for (const i of cerradas) {
            suma = suma + diasEntre(i.fecha_deteccion, i.fecha_resolucion);
        }
        let promedio = "-";
        if (cerradas.length > 0) {
            promedio = (suma / cerradas.length).toFixed(1) + " días";
        }

        document.getElementById("tituloInforme").textContent = titulo + " del " + desde + " al " + hasta;
        document.getElementById("totalInforme").textContent = todas.length;
        document.getElementById("abiertasInforme").textContent = abiertas.length;
        document.getElementById("cerradasInforme").textContent = cerradas.length;
        document.getElementById("promedioInforme").textContent = promedio;
        document.getElementById("fechaImpresion").textContent = "Generado el " + fechaDeHoy() + " por " + usuarioLogueado.nombre;
        mostrarTablaInforme(lista);
        listaInforme = lista;
        document.getElementById("mensaje").className = "mensaje";
    } catch (error) {
        mostrarMensaje(error.message, true);
    }

    await verVolumen(desde, hasta);
}

async function verVolumen(desde, hasta) {
    try {
        const datos = await obtenerVolumen(desde, hasta);
        listaVolumen = datos.por_contenedor;

        const total = datos.total;
        document.getElementById("volKilos").textContent = Math.round(total.kilos) + " kg";
        document.getElementById("volVaciados").textContent = total.vaciados;

        if (total.vaciados > 0) {
            document.getElementById("volPromedio").textContent = Math.round(total.kilos / total.vaciados) + " kg";

            const dias = diasEntre(desde, hasta) + 1;
            const vecesPorContenedor = total.vaciados / total.contenedores;
            document.getElementById("volFrecuencia").textContent = "cada " + (dias / vecesPorContenedor).toFixed(1) + " días";
        } else {
            document.getElementById("volPromedio").textContent = "-";
            document.getElementById("volFrecuencia").textContent = "-";
        }

        const municipios = [];
        for (const m of datos.por_municipio) {
            municipios.push({ nombre: "Municipio " + m.municipio, cantidad: m.cantidad });
        }
        mostrarBarras("graficoVolMunicipio", municipios, "nombre", "No hubo recolecciones");
        mostrarBarras("graficoVolResiduo", datos.por_residuo, "residuo", "No hubo recolecciones");
        mostrarTablaVolumen(datos.por_contenedor);
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function exportarIncidencias() {
    const filas = [["ID", "Detectada", "Contenedor", "Zona", "Municipio", "Tipo", "Prioridad", "Estado", "Resuelta", "Cuadrilla", "Descripción"]];
    for (const i of listaInforme) {
        filas.push([i.id, i.fecha_deteccion, i.calle, i.zona_incidencia, i.municipio, i.tipo, i.prioridad, i.estado, i.fecha_resolucion, i.cuadrilla, i.descripcion]);
    }
    const desde = document.getElementById("desde").value;
    const hasta = document.getElementById("hasta").value;
    descargarCSV("incidencias_" + desde + "_" + hasta + ".csv", filas);
}

function exportarVolumen() {
    const filas = [["ID", "Contenedor", "Zona", "Municipio", "Vaciados", "Kilos", "Último vaciado"]];
    for (const c of listaVolumen) {
        filas.push([c.id, c.calle, c.zona, c.municipio, c.vaciados, c.kilos, c.ultima]);
    }
    const desde = document.getElementById("desde").value;
    const hasta = document.getElementById("hasta").value;
    descargarCSV("recolecciones_" + desde + "_" + hasta + ".csv", filas);
}

function imprimir() {
    window.print();
}

iniciar();
