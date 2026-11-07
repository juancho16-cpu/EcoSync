// Controlador: maneja el formulario de registro

// Los botones de "Ingresar" llevan al frontend de usuarios
const enlacesIngresar = document.querySelectorAll(".link-ingresar");
for (const enlace of enlacesIngresar) {
    enlace.href = URL_FRONTEND;
}

const formulario = document.getElementById("formRegistro");

formulario.addEventListener("submit", async function (evento) {
    // Evita que la pagina se recargue
    evento.preventDefault();

    const datos = {
        primer_nombre: document.getElementById("primer_nombre").value.trim(),
        primer_apellido: document.getElementById("primer_apellido").value.trim(),
        email: document.getElementById("email").value.trim(),
        telefono: document.getElementById("telefono").value.trim(),
        calle: document.getElementById("calle").value.trim(),
        zona: document.getElementById("zona").value.trim(),
        contrasena: document.getElementById("contrasena").value
    };
    const contrasena2 = document.getElementById("contrasena2").value;

    // Validacion con JavaScript
    if (datos.primer_nombre == "" || datos.primer_apellido == "" || datos.email == "" || datos.contrasena == "") {
        mostrarMensaje("Completá todos los campos obligatorios", true);
        return;
    } else if (datos.contrasena.length < 8 || !/[a-zA-Z]/.test(datos.contrasena) || !/[0-9]/.test(datos.contrasena)) {
        mostrarMensaje("La contraseña tiene que tener al menos 8 caracteres, con letras y números", true);
        return;
    } else if (datos.contrasena != contrasena2) {
        mostrarMensaje("Las contraseñas no coinciden", true);
        return;
    }

    try {
        const respuesta = await enviarRegistro(datos);
        mostrarMensaje(respuesta.mensaje, false);
        formulario.reset();
    } catch (error) {
        mostrarMensaje(error.message, true);
    }
});
