<?php
// Configuracion general de la API de Recoleccion

// La cookie de la sesion: JavaScript no la puede leer (httponly) y no se manda desde otras paginas (samesite)
session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "httponly" => true,
    "samesite" => "Strict",
    "secure" => (($_SERVER['HTTPS'] ?? "") == "on")
]);

// La sesion guarda quien inicio sesion (la comparten todas las APIs)
session_name("SIGERU");
session_start();

// Si pasaron 30 minutos sin usar el sistema, se cierra la sesion sola.
// Las consultas automaticas de la campana (GET mensajes) no cuentan como uso.
$minutosSesion = 30;
if (isset($_SESSION['usuario_logueado'])) {
    $ultimoUso = $_SESSION['ultimo_uso'] ?? time();
    if (time() - $ultimoUso > $minutosSesion * 60) {
        unset($_SESSION['usuario_logueado']);
        $_SESSION['sesion_vencida'] = true;
    } else {
        $esLaCampana = ($_SERVER['REQUEST_METHOD'] ?? "") == "GET" && trim($_SERVER['PATH_INFO'] ?? "", "/") == "mensajes";
        if (!$esLaCampana) {
            $_SESSION['ultimo_uso'] = time();
        }
    }
}

// La API siempre responde en formato JSON
header("Content-Type: application/json; charset=utf-8");

// Cabeceras de seguridad:
// nosniff: el navegador no adivina el tipo de archivo
// DENY: la API no se puede meter dentro de otra pagina (iframe)
// no-store: las respuestas con datos personales no se guardan en el cache
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Cache-Control: no-store");
header("Referrer-Policy: same-origin");
