<?php
// Revisa que haya alguien con la sesion iniciada y que su rol tenga permiso.
// Si no, corta y devuelve el error.
function verificarRol($rolesPermitidos) {
    if (!isset($_SESSION['usuario_logueado'])) {
        if (isset($_SESSION['sesion_vencida'])) {
            responder(["error" => "Tu sesión se cerró porque pasaron 30 minutos sin usar el sistema. Volvé a iniciar sesión."], 401);
        }
        responder(["error" => "Tenés que iniciar sesión"], 401);
    }
    if (!in_array($_SESSION['usuario_logueado']['rol'], $rolesPermitidos)) {
        responder(["error" => "No tenés permiso para hacer esto"], 403);
    }
}
