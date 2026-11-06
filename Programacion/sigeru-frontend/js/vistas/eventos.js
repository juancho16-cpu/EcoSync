// Eventos de la pagina sin usar onclick en el HTML.
// Antes los botones tenian onclick con la llamada a la funcion. Eso es codigo dentro del HTML
// y no deja poner la politica de seguridad CSP (Content-Security-Policy), que bloquea
// todo el codigo que no venga de un archivo .js del sistema.
// Ahora los botones dicen que funcion llamar con atributos data-:
//   <button data-click="nuevoUsuario">                  -> nuevoUsuario()
//   <button data-click="editarUsuario" data-args="5">   -> editarUsuario(5)
//   <select data-change="cargarIncidencias">            -> cargarIncidencias() al cambiar
//   <input data-input="buscar">                         -> buscar() al escribir
// Un solo escuchador en todo el documento sirve tambien para los botones
// que se dibujan despues con JavaScript (las tablas, las notificaciones).

// Pasa el texto de data-args a una lista de valores: "5,true" -> [5, true]
function leerArgumentos(texto) {
    if (!texto) {
        return [];
    }
    return texto.split(",").map(function (parte) {
        const valor = parte.trim();
        if (valor === "true") {
            return true;
        }
        if (valor === "false") {
            return false;
        }
        if (valor !== "" && !isNaN(valor)) {
            return Number(valor);
        }
        return valor;
    });
}

// Llama a la funcion que tiene ese nombre (todas las funciones de los controladores son globales)
function llamarFuncion(nombre, elemento) {
    const funcion = window[nombre];
    if (typeof funcion !== "function") {
        console.error("No existe la funcion " + nombre);
        return;
    }
    funcion(...leerArgumentos(elemento.dataset.args));
}

document.addEventListener("click", function (evento) {
    const elemento = evento.target.closest("[data-click]");
    if (!elemento) {
        return;
    }
    // Los enlaces que llaman a una funcion no tienen que cambiar de pagina
    if (elemento.tagName === "A") {
        evento.preventDefault();
    }
    llamarFuncion(elemento.dataset.click, elemento);
});

document.addEventListener("change", function (evento) {
    const elemento = evento.target.closest("[data-change]");
    if (elemento) {
        llamarFuncion(elemento.dataset.change, elemento);
    }
});

document.addEventListener("input", function (evento) {
    const elemento = evento.target.closest("[data-input]");
    if (elemento) {
        llamarFuncion(elemento.dataset.input, elemento);
    }
});
