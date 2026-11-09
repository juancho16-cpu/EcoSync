<?php

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "httponly" => true,
    "samesite" => "Strict",
    "secure" => (($_SERVER['HTTPS'] ?? "") == "on")
]);

session_name("SIGERU");
session_start();

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

header("Content-Type: application/json; charset=utf-8");

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Cache-Control: no-store");
header("Referrer-Policy: same-origin");
