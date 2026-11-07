<?php
// Datos de la base de datos (en XAMPP el usuario es root sin contraseña)
$hostname = "localhost";
$username = "root";
$password = "";
$database = "sigeru";

// Con Docker la base de datos esta en otro contenedor: su direccion viene en DB_HOST (ver docker-compose.yml)
if (getenv("DB_HOST")) {
    $hostname = getenv("DB_HOST");
}

// En el servidor de verdad las APIs usan un usuario de MySQL con permisos limitados (sigeru_app).
// Su usuario y contraseña vienen en DB_USER y DB_PASS, asi la contraseña no queda escrita en el codigo.
if (getenv("DB_USER")) {
    $username = getenv("DB_USER");
    $password = getenv("DB_PASS");
}

// Para que los errores los manejemos nosotros
mysqli_report(MYSQLI_REPORT_OFF);

// Conexion a la base de datos
$conn = mysqli_connect($hostname, $username, $password, $database);

// Si no se pudo conectar, avisamos y cortamos
if (!$conn) {
    http_response_code(500);
    die(json_encode(["error" => "No se pudo conectar a la base de datos"]));
}

// Para que los acentos y la ñ se guarden bien
mysqli_set_charset($conn, "utf8mb4");
