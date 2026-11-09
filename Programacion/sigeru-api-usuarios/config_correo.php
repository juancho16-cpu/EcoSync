<?php

$correoGmail = "sigeru460@gmail.com";
$claveAppGmail = "exeznyzqpynofblv";

$direccionSitio = "http://localhost";
$host = $_SERVER['HTTP_HOST'] ?? "";
if (preg_match("/^(localhost|127\.0\.0\.1|192\.168\.[0-9]{1,3}\.[0-9]{1,3}|10\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}|172\.(1[6-9]|2[0-9]|3[01])\.[0-9]{1,3}\.[0-9]{1,3})(:[0-9]{1,5})?$/", $host)) {
    $direccionSitio = "http://" . $host;
}

if (preg_match("/^[a-z0-9-]+\.trycloudflare\.com$/", $host)) {
    $direccionSitio = "https://" . $host;
}

$archivoPrivado = __DIR__ . "/../../sigeru_correo.php";
if (file_exists($archivoPrivado)) {
    require $archivoPrivado;
}

if (getenv("GMAIL_USUARIO")) {
    $correoGmail = getenv("GMAIL_USUARIO");
}
if (getenv("GMAIL_CLAVE")) {
    $claveAppGmail = getenv("GMAIL_CLAVE");
}
if (getenv("DIRECCION_SITIO")) {
    $direccionSitio = getenv("DIRECCION_SITIO");
}
