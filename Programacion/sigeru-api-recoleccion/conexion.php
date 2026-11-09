<?php

$hostname = "localhost";
$username = "root";
$password = "";
$database = "sigeru";

if (getenv("DB_HOST")) {
    $hostname = getenv("DB_HOST");
}

if (getenv("DB_USER")) {
    $username = getenv("DB_USER");
    $password = getenv("DB_PASS");
}

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect($hostname, $username, $password, $database);

if (!$conn) {
    http_response_code(500);
    die(json_encode(["error" => "No se pudo conectar a la base de datos"]));
}

mysqli_set_charset($conn, "utf8mb4");
