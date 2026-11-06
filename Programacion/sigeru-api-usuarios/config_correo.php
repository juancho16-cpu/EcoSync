<?php
// Configuracion del correo (Gmail) que usa la API para mandar los mails de verificacion.
//
// La contraseña NO se escribe aca (este archivo se sube a GitHub).
// Se escribe en un archivo aparte que esta FUERA de las carpetas del proyecto:
//
//     C:\xampp\sigeru_correo.php        (al lado de la carpeta htdocs, no adentro)
//
// Asi no se sube nunca a GitHub y no se borra cuando se copian las carpetas nuevas del proyecto.
// En cada computadora se crea una sola vez, con este contenido:
//
//     <?php
//     $correoGmail = "el correo de gmail";
//     $claveAppGmail = "la contraseña de aplicacion de 16 letras, sin espacios";
//     $direccionSitio = "http://192.168.1.10";   (opcional: la IP de la PC, para abrir los enlaces desde el celular)
//
// Como se saca la contraseña de aplicacion:
//   1. Entrar a la cuenta de Gmail -> Gestionar tu cuenta de Google -> Seguridad.
//   2. Activar la "Verificación en dos pasos".
//   3. Entrar a https://myaccount.google.com/apppasswords, crear una (por ejemplo "SiGeRU") y copiar las 16 letras.
//
// Si no esta configurado no se mandan correos, y los vecinos no se pueden registrar desde la landing.

$correoGmail = "";
$claveAppGmail = "";

// Direccion de la pagina, para armar los enlaces del correo.
// Se usa la misma direccion con la que entro el que se registro: si entro desde el celular con la IP
// de la PC (por ejemplo http://192.168.1.10), el enlace del correo tambien va a esa IP y abre en el celular.
// Si se quiere una direccion fija, se puede poner en sigeru_correo.php, por ejemplo:
//     $direccionSitio = "http://192.168.1.10";
// Por seguridad solo se acepta "localhost" o una IP de la red de la casa (192.168..., 10..., 172.16 a 172.31...),
// porque la direccion la manda el navegador y alguien podria inventar otra para mandar un enlace falso.
$direccionSitio = "http://localhost";
$host = $_SERVER['HTTP_HOST'] ?? "";
if (preg_match("/^(localhost|127\.0\.0\.1|192\.168\.[0-9]{1,3}\.[0-9]{1,3}|10\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}|172\.(1[6-9]|2[0-9]|3[01])\.[0-9]{1,3}\.[0-9]{1,3})(:[0-9]{1,5})?$/", $host)) {
    $direccionSitio = "http://" . $host;
}
// Tambien se acepta la direccion del tunel de Cloudflare (para entrar desde el celular por internet).
// Esas direcciones son con https y terminan en .trycloudflare.com
if (preg_match("/^[a-z0-9-]+\.trycloudflare\.com$/", $host)) {
    $direccionSitio = "https://" . $host;
}

// Leemos el archivo con la contraseña, que esta dos carpetas mas arriba:
// C:\xampp\htdocs\sigeru-api-usuarios  ->  C:\xampp\htdocs  ->  C:\xampp
$archivoPrivado = __DIR__ . "/../../sigeru_correo.php";
if (file_exists($archivoPrivado)) {
    require $archivoPrivado;
}

// En Docker estos datos se pueden pasar como variables de entorno
if (getenv("GMAIL_USUARIO")) {
    $correoGmail = getenv("GMAIL_USUARIO");
}
if (getenv("GMAIL_CLAVE")) {
    $claveAppGmail = getenv("GMAIL_CLAVE");
}
if (getenv("DIRECCION_SITIO")) {
    $direccionSitio = getenv("DIRECCION_SITIO");
}
