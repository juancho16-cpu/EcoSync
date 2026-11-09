<?php

function claveDeCifrado() {
    static $clave = null;
    if ($clave != null) {
        return $clave;
    }

    if (getenv("SIGERU_CLAVE")) {
        $clave = base64_decode(getenv("SIGERU_CLAVE"));
        return $clave;
    }

    $archivo = __DIR__ . "/../../sigeru_clave.php";
    if (getenv("SIGERU_CLAVE_ARCHIVO")) {
        
        $archivo = getenv("SIGERU_CLAVE_ARCHIVO");
    }
    if (!file_exists($archivo)) {
        
        $nueva = base64_encode(random_bytes(32));
        $contenido = "<?php\n// Clave para cifrar los datos personales de SiGeRU. NO compartirla ni subirla a GitHub.\n"
                   . "// Si se pierde, los telefonos guardados no se pueden leer.\n"
                   . "\$claveCifrado = \"" . $nueva . "\";\n";
        if (@file_put_contents($archivo, $contenido) === false) {
            http_response_code(500);
            die(json_encode(["error" => "No se pudo crear la clave de cifrado (sigeru_clave.php). Revisá los permisos de la carpeta."]));
        }
    }

    include $archivo;
    $clave = base64_decode($claveCifrado);
    return $clave;
}

function cifrar($texto) {
    if ($texto === null || $texto === "") {
        return null;
    }
    $iv = random_bytes(12);
    $etiqueta = "";
    $cifrado = openssl_encrypt($texto, "aes-256-gcm", claveDeCifrado(), OPENSSL_RAW_DATA, $iv, $etiqueta);
    return "enc:" . base64_encode($iv . $etiqueta . $cifrado);
}

function descifrar($texto) {
    if ($texto === null || !str_starts_with($texto, "enc:")) {
        return $texto;
    }
    $datos = base64_decode(substr($texto, 4));
    $iv = substr($datos, 0, 12);
    $etiqueta = substr($datos, 12, 16);
    $cifrado = substr($datos, 28);

    $resultado = openssl_decrypt($cifrado, "aes-256-gcm", claveDeCifrado(), OPENSSL_RAW_DATA, $iv, $etiqueta);
    if ($resultado === false) {
        
        return "(no se pudo descifrar)";
    }
    return $resultado;
}

function resumen($texto) {
    return hash("sha256", $texto);
}
