<?php

$pasaron = 0;
$fallaron = 0;

function verificar($condicion, $descripcion) {
    global $pasaron, $fallaron;

    if ($condicion) {
        echo "  OK     $descripcion\n";
        $pasaron++;
    } else {
        echo "  FALLA  $descripcion\n";
        $fallaron++;
    }
}

function titulo($texto) {
    echo "\n== $texto ==\n";
}

function mostrarResumen() {
    global $pasaron, $fallaron;

    echo "\nPasaron: $pasaron | Fallaron: $fallaron\n";
    if ($fallaron > 0) {
        exit(1);
    }
    exit(0);
}

function crearBaseDePrueba() {
    $hostname = "localhost";
    if (getenv("DB_HOST")) {
        $hostname = getenv("DB_HOST");
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = mysqli_connect($hostname, "root", "");
    if (!$conn) {
        echo "No se pudo conectar a MySQL. ¿Está prendido en XAMPP?\n";
        exit(1);
    }
    mysqli_set_charset($conn, "utf8mb4");

    $carpeta = __DIR__ . "/../../sigeru-base-datos";
    $ddl = file_get_contents($carpeta . "/ddl/sigeru_ddl.sql");
    $datos = file_get_contents($carpeta . "/datos/sigeru_datos_prueba.sql");

    $ddl = str_replace("DROP DATABASE IF EXISTS sigeru;", "DROP DATABASE IF EXISTS sigeru_test;", $ddl);
    $ddl = str_replace("CREATE DATABASE sigeru ", "CREATE DATABASE sigeru_test ", $ddl);
    $ddl = str_replace("USE sigeru;", "USE sigeru_test;", $ddl);
    $datos = str_replace("USE sigeru;", "USE sigeru_test;", $datos);

    ejecutarScript($conn, $ddl);
    ejecutarScript($conn, $datos);

    mysqli_select_db($conn, "sigeru_test");
    return $conn;
}

function ejecutarScript($conn, $sql) {
    
    $lineas = explode("\n", $sql);
    $sinComentarios = "";
    foreach ($lineas as $linea) {
        if (substr(trim($linea), 0, 2) != "--") {
            $sinComentarios = $sinComentarios . $linea . "\n";
        }
    }

    $consultas = explode(";", $sinComentarios);
    foreach ($consultas as $consulta) {
        if (trim($consulta) == "") {
            continue;
        }
        if (!mysqli_query($conn, $consulta)) {
            echo "Error en el script SQL: " . mysqli_error($conn) . "\n";
            exit(1);
        }
    }
}

function contarFilas($conn, $sql) {
    $resultado = mysqli_query($conn, $sql);
    return mysqli_num_rows($resultado);
}

function conectarBaseReal() {
    $hostname = "localhost";
    if (getenv("DB_HOST")) {
        $hostname = getenv("DB_HOST");
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    return mysqli_connect($hostname, "root", "", "sigeru");
}

function pedir($metodo, $url, $datos, $usuario) {
    $cookies = sys_get_temp_dir() . "/sigeru_test_" . $usuario . ".txt";

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookies);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookies);
    if ($datos != null) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($datos));
    }

    $texto = curl_exec($curl);
    $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($codigo == 0) {
        echo "No se pudo conectar con $url. ¿Está prendido Apache en XAMPP?\n";
        exit(1);
    }
    return ["codigo" => $codigo, "json" => json_decode($texto, true)];
}

function entrar($urlUsuarios, $usuario, $email, $contrasena) {
    
    $cookies = sys_get_temp_dir() . "/sigeru_test_" . $usuario . ".txt";
    if (file_exists($cookies)) {
        unlink($cookies);
    }
    return pedir("POST", $urlUsuarios . "/login", ["email" => $email, "contrasena" => $contrasena], $usuario);
}
