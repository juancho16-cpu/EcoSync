
document.getElementById("linkRegistro").href = URL_LANDING + "#registro";
document.getElementById("linkLanding").href = URL_LANDING;
document.getElementById("linkLogo").href = URL_LANDING;

const formulario = document.getElementById("formLogin");

formulario.addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const email = document.getElementById("email").value.trim();
    const contrasena = document.getElementById("contrasena").value;

    if (email == "" || contrasena == "") {
        mostrarMensaje("Completá el correo y la contraseña", true);
        return;
    }

    try {
        await iniciarSesion(email, contrasena);
        window.location.href = "panel.html";
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
});
