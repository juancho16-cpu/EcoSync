
async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    document.getElementById("saludo").textContent = usuarioLogueado.nombre;
    document.getElementById("fechaHoy").textContent = fechaLarga();

    try {
        const contenedores = await obtenerContenedores();
        contarHasta("totalContenedores", contenedores.length);

        let conProblemas = 0;
        for (const c of contenedores) {
            if (c.estado != "Funcional") conProblemas++;
        }
        contarHasta("contenedoresProblema", conProblemas);

        if (usuarioLogueado.rol != "Vecino") {
            const camiones = await obtenerCamiones();
            contarHasta("totalCamiones", camiones.length);
        }

        if (usuarioLogueado.rol == "Administrador") {
            const usuarios = await obtenerUsuarios();
            contarHasta("totalUsuarios", usuarios.length);
        }

        const alertas = await obtenerAlertas();
        mostrarAlertas(alertas);
    } catch (error) {
        mostrarMensaje(error.message, true);
    }

    if (usuarioLogueado.rol != "Vecino") {
        document.getElementById("mesEstadisticas").value = mesActual();
        await cargarEstadisticas();
    }
}

async function cargarEstadisticas() {
    const mes = document.getElementById("mesEstadisticas").value;
    if (mes == "") return;

    try {
        const datos = await obtenerEstadisticas(mes);
        mostrarEstadisticas(datos);
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

iniciar();
