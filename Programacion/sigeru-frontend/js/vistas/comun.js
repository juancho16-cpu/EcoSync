// Vista: funciones para mostrar cosas en pantalla que usan todas las paginas

// Devuelve el HTML de un icono (los iconos estan todos en img/iconos.svg)
function icono(nombre) {
    return `<svg class="icono" aria-hidden="true"><use href="img/iconos.svg#${nombre}"></use></svg>`;
}

// Muestra un mensaje verde (bien) o rojo (error)
function mostrarMensaje(texto, esError) {
    const caja = document.getElementById("mensaje");
    caja.textContent = texto;
    if (esError) {
        caja.className = "mensaje error";
    } else {
        caja.className = "mensaje ok";
    }
}

// Para que nadie pueda meter codigo HTML en las tablas desde un formulario
function limpiarTexto(texto) {
    if (texto == null) return "";
    return String(texto)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;");
}

// Muestra el nombre y rol en el menu, y oculta lo que su rol no puede usar
function mostrarDatosUsuario(usuario) {
    document.getElementById("nombreUsuario").textContent = usuario.nombre;
    document.getElementById("rolUsuario").textContent = usuario.rol;

    // Solo el administrador ve lo de usuarios
    if (usuario.rol != "Administrador") {
        ocultar("solo-admin");
    }

    // Solo el administrador y el operario pueden agregar, editar y borrar contenedores, camiones, centros y maquinaria
    if (usuario.rol != "Administrador" && usuario.rol != "Operario") {
        ocultar("solo-gestion");
    }

    // Los vecinos no ven los camiones, los centros de acopio ni la maquinaria
    if (usuario.rol == "Vecino") {
        ocultar("no-vecino");
    }

    // Lo que es solo para vecinos (por ejemplo "Mis avisos")
    if (usuario.rol != "Vecino") {
        ocultar("solo-vecino");
    }
}

// Oculta todas las partes de la pagina que tienen esa clase
function ocultar(clase) {
    const elementos = document.querySelectorAll("." + clase);
    for (const elemento of elementos) {
        elemento.style.display = "none";
    }
}

// Abre la ventana emergente (modal)
function abrirModal() {
    const modal = document.getElementById("formModal");
    modal.showModal();
}

// Cierra la ventana emergente (modal)
function cerrarModal() {
    const modal = document.getElementById("formModal");
    modal.close();
}

// Elige el color de la etiqueta segun el estado: verde (bien), amarillo (alerta) o rojo (mal)
function colorEstado(estado) {
    if (estado == "Funcional" || estado == "Operativo" || estado == "Operativa" || estado == "Disponible" || estado == "Resuelta" || estado == "Activo") return "";
    if (estado == "En mantenimiento" || estado == "En reparación" || estado == "En ruta" || estado == "En proceso" || estado == "Pendiente" || estado == "Sin verificar") return "alerta";
    return "mal";
}

// Color de la etiqueta de la prioridad de una incidencia
function colorPrioridad(prioridad) {
    if (prioridad == "Alta") return "mal";
    if (prioridad == "Media") return "alerta";
    return "";
}

// Mes actual como "2026-11"
function mesActual() {
    return fechaDeHoy().substring(0, 7);
}

// Descarga un archivo CSV (se abre con Excel). "filas" es una lista de listas:
// la primera fila son los titulos de las columnas.
function descargarCSV(nombreArchivo, filas) {
    let texto = "";
    for (const fila of filas) {
        const celdas = [];
        for (const celda of fila) {
            // Cada celda va entre comillas (y las comillas de adentro se duplican)
            celdas.push('"' + String(celda ?? "").replaceAll('"', '""') + '"');
        }
        // Excel en español usa ; para separar las columnas
        texto += celdas.join(";") + "\r\n";
    }

    // El \uFEFF del principio es para que Excel muestre bien los acentos
    const archivo = new Blob(["\uFEFF" + texto], { type: "text/csv;charset=utf-8" });
    const enlace = document.createElement("a");
    enlace.href = URL.createObjectURL(archivo);
    enlace.download = nombreArchivo;
    enlace.click();
    URL.revokeObjectURL(enlace.href);
}

// Dias que pasaron entre dos fechas "2026-11-01" y "2026-11-04" (da 3)
function diasEntre(desde, hasta) {
    const unDia = 24 * 60 * 60 * 1000;
    return Math.round((new Date(hasta) - new Date(desde)) / unDia);
}

// Busca un elemento de una lista por su id. Si no lo encuentra devuelve null.
function buscarPorId(lista, id) {
    for (const item of lista) {
        if (item.id == id) {
            return item;
        }
    }
    return null;
}

// Muestra un numero contando desde 0 hasta el numero final (efecto del panel)
function contarHasta(idElemento, numero) {
    const elemento = document.getElementById(idElemento);
    const final = Number(numero);
    let actual = 0;
    // Se divide en 20 pasos de 30 milisegundos (en total tarda un poco mas de medio segundo)
    const paso = Math.max(1, Math.ceil(final / 20));
    const reloj = setInterval(function () {
        actual = actual + paso;
        if (actual >= final) {
            actual = final;
            clearInterval(reloj);
        }
        elemento.textContent = actual;
    }, 30);
}

// Fecha de hoy escrita completa, como "viernes 6 de noviembre de 2026"
function fechaLarga() {
    return new Date().toLocaleDateString("es-UY", { weekday: "long", day: "numeric", month: "long", year: "numeric" });
}

// Fecha de hoy como "2026-11-06" (para los campos de fecha de los formularios)
function fechaDeHoy() {
    const hoy = new Date();
    let mes = hoy.getMonth() + 1;
    let dia = hoy.getDate();
    if (mes < 10) mes = "0" + mes;
    if (dia < 10) dia = "0" + dia;
    return hoy.getFullYear() + "-" + mes + "-" + dia;
}
