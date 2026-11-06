// Controlador de la pagina que se abre desde el enlace del correo

const enlacesIngresar = document.querySelectorAll(".link-ingresar");
for (const enlace of enlacesIngresar) {
    enlace.href = URL_FRONTEND;
}

// Cambia el icono de arriba: "ok" si salio bien o "alerta" si hubo un error
function mostrarIconoVerificacion(nombre, esError) {
    const caja = document.getElementById("icono");
    document.getElementById("iconoUso").setAttribute("href", "img/iconos.svg#" + nombre);
    caja.classList.remove("cargando");
    if (esError) {
        caja.classList.add("mal");
    }
}

async function verificar() {
    // El codigo viene en la direccion: verificar.html?codigo=abc123
    const parametros = new URLSearchParams(window.location.search);
    const codigo = parametros.get("codigo") ?? "";
    const titulo = document.querySelector("h2");

    try {
        const respuesta = await enviarVerificacion(codigo);
        mostrarIconoVerificacion("ok", false);
        titulo.textContent = "¡Te registraste correctamente!";
        mostrarMensaje(respuesta.mensaje, false);

        // Si la cuenta ya quedo activa, mostramos el boton para entrar
        if (respuesta.estado == "Activo") {
            document.getElementById("botonIngresar").style.display = "inline-flex";
        }
    } catch (error) {
        mostrarIconoVerificacion("alerta", true);
        titulo.textContent = "No pudimos verificar tu correo";
        mostrarMensaje(error.message, true);
    }
}

verificar();
