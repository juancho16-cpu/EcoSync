// Vista: animaciones de la pagina de inicio

// La barra de arriba es transparente sobre la portada y se pone blanca cuando se baja
const barra = document.getElementById("navbar");
if (barra) {
    window.addEventListener("scroll", function () {
        if (window.scrollY > 40) {
            barra.classList.add("con-fondo");
        } else {
            barra.classList.remove("con-fondo");
        }
    });
}

// Las partes con la clase "aparecer" se muestran con una animacion cuando aparecen en la pantalla
const partes = document.querySelectorAll(".aparecer");
if ("IntersectionObserver" in window) {
    const observador = new IntersectionObserver(function (entradas) {
        for (const entrada of entradas) {
            if (entrada.isIntersecting) {
                entrada.target.classList.add("visible");
                observador.unobserve(entrada.target);
            }
        }
    }, { threshold: 0.15 });

    for (const parte of partes) {
        observador.observe(parte);
    }
} else {
    // Si el navegador es muy viejo, se muestran sin animacion
    for (const parte of partes) {
        parte.classList.add("visible");
    }
}

// Si no hay video de fondo (no esta el archivo media/fondo.mp4), lo sacamos y queda el fondo animado
const video = document.querySelector(".hero-video");
if (video) {
    video.addEventListener("error", function () {
        video.remove();
    }, true);
    const fuente = video.querySelector("source");
    if (fuente) {
        fuente.addEventListener("error", function () {
            video.remove();
        });
    }
}
