
let listaParametros = [];

const OPCIONES_PARAMETROS = {
    aprobar_registros: [["si", "Sí, un administrador aprueba a cada vecino"], ["no", "No, entran apenas verifican el correo"]],
    idioma: [["es", "Español"], ["en", "English (inglés)"]]
};

async function iniciar() {
    const haySesion = await revisarSesion();
    if (!haySesion) return;

    try {
        listaParametros = await obtenerParametros();
        mostrarFormularioParametros(listaParametros);
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
}

function mostrarFormularioParametros(lista) {
    let html = "";
    for (const p of lista) {
        html += `<div class="form-group">
            <label for="param_${p.nombre}">${limpiarTexto(p.descripcion)}</label>`;

        if (OPCIONES_PARAMETROS[p.nombre] != undefined) {
            html += `<select id="param_${p.nombre}">`;
            for (const opcion of OPCIONES_PARAMETROS[p.nombre]) {
                let elegida = "";
                if (opcion[0] == p.valor) elegida = "selected";
                html += `<option value="${opcion[0]}" ${elegida}>${opcion[1]}</option>`;
            }
            html += `</select>`;
        } else {
            html += `<input id="param_${p.nombre}" type="number" min="0" step="any" value="${limpiarTexto(p.valor)}">`;
        }
        html += `<small>${limpiarTexto(p.nombre)}</small></div>`;
    }
    document.getElementById("camposParametros").innerHTML = html;
}

document.getElementById("formParametros").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {};
    for (const p of listaParametros) {
        datos[p.nombre] = document.getElementById("param_" + p.nombre).value;
    }

    try {
        const respuesta = await guardarParametros(datos);
        mostrarMensaje(respuesta.mensaje, false);
        if (datos.idioma != idiomaActual) {
            setTimeout(function () {
                window.location.reload();
            }, 800);
        }
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
});

iniciar();
