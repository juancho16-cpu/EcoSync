<?php
// Funciones que usan los tests. No usan ninguna libreria, asi se pueden correr en XAMPP con:
//   php tests/test_modelos.php
//   php tests/test_api.php

$pasaron = 0;
$fallaron = 0;

// Si la condicion es verdadera la prueba pasa, si no falla
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

// Muestra cuantas pasaron y cuantas fallaron. Si alguna fallo, termina con error.
function mostrarResumen() {
    global $pasaron, $fallaron;

    echo "\nPasaron: $pasaron | Fallaron: $fallaron\n";
    if ($fallaron > 0) {
        exit(1);
    }
    exit(0);
}

// ---------- Para los tests de los modelos ----------

// Crea la base de datos de prueba "sigeru_test" con las mismas tablas y datos que la real.
// Asi los tests no tocan los datos de la base "sigeru".
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

    // Los archivos de la base de datos estan en la carpeta sigeru-base-datos
    $carpeta = __DIR__ . "/../../sigeru-base-datos";
    $ddl = file_get_contents($carpeta . "/ddl/sigeru_ddl.sql");
    $datos = file_get_contents($carpeta . "/datos/sigeru_datos_prueba.sql");

    // Cambiamos el nombre de la base por sigeru_test
    $ddl = str_replace("DROP DATABASE IF EXISTS sigeru;", "DROP DATABASE IF EXISTS sigeru_test;", $ddl);
    $ddl = str_replace("CREATE DATABASE sigeru ", "CREATE DATABASE sigeru_test ", $ddl);
    $ddl = str_replace("USE sigeru;", "USE sigeru_test;", $ddl);
    $datos = str_replace("USE sigeru;", "USE sigeru_test;", $datos);

    ejecutarScript($conn, $ddl);
    ejecutarScript($conn, $datos);

    mysqli_select_db($conn, "sigeru_test");
    return $conn;
}

// Ejecuta un archivo .sql entero (tiene muchas consultas separadas por ;)
function ejecutarScript($conn, $sql) {
    // Sacamos las lineas de comentarios (empiezan con --)
    $lineas = explode("\n", $sql);
    $sinComentarios = "";
    foreach ($lineas as $linea) {
        if (substr(trim($linea), 0, 2) != "--") {
            $sinComentarios = $sinComentarios . $linea . "\n";
        }
    }

    // Separamos las consultas por el ; y las ejecutamos una por una
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

// Cuenta las filas de una consulta (para revisar la base de datos en los tests)
function contarFilas($conn, $sql) {
    $resultado = mysqli_query($conn, $sql);
    return mysqli_num_rows($resultado);
}

// ---------- Para los tests de la API ----------

// Se conecta a la base de datos real "sigeru". Los tests de la API la usan solo para
// borrar al final lo que crearon y que la API no deja borrar (las notificaciones).
function conectarBaseReal() {
    $hostname = "localhost";
    if (getenv("DB_HOST")) {
        $hostname = getenv("DB_HOST");
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    return mysqli_connect($hostname, "root", "", "sigeru");
}

// Hace un pedido a la API como lo haria el navegador.
// $usuario es el nombre del archivo donde se guarda la cookie de la sesion (uno por cada usuario).
// Devuelve el codigo HTTP y la respuesta en JSON.
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

// Inicia sesion con un usuario y guarda su cookie
function entrar($urlUsuarios, $usuario, $email, $contrasena) {
    // Se borra la cookie anterior para empezar de cero
    $cookies = sys_get_temp_dir() . "/sigeru_test_" . $usuario . ".txt";
    if (file_exists($cookies)) {
        unlink($cookies);
    }
    return pedir("POST", $urlUsuarios . "/login", ["email" => $email, "contrasena" => $contrasena], $usuario);
}
