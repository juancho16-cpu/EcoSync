<?php
// Cifrado de datos personales (por ahora el telefono de los usuarios).
//
// Se usa AES-256-GCM (openssl de PHP):
//   - AES-256: cifrado simetrico con una clave de 32 bytes (256 bits). Con la misma clave se cifra y se descifra.
//   - GCM: ademas agrega una "etiqueta" que detecta si alguien modifico el dato cifrado en la base.
//   - Cada dato lleva su propio vector de inicializacion (IV) al azar, asi dos telefonos iguales quedan distintos.
//
// En la base se guarda "enc:" + base64(IV + etiqueta + texto cifrado).
// Si alguien se lleva una copia de la base (o un backup), sin la clave no puede leer los telefonos.
//
// La clave NO esta en el codigo ni en GitHub. Se busca en este orden:
//   1. La variable de entorno SIGERU_CLAVE (en el servidor o en Docker), en base64.
//   2. El archivo sigeru_clave.php FUERA del proyecto (en XAMPP: C:\xampp\sigeru_clave.php;
//      en Docker: el que diga SIGERU_CLAVE_ARCHIVO).
//      Si no existe, se crea solo la primera vez con una clave al azar.
// IMPORTANTE: si se pasa la base a otra computadora, hay que copiar tambien sigeru_clave.php.
// Sin esa clave los telefonos guardados no se pueden leer.

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
        // En Docker la clave va en un volumen aparte (ver docker-compose.yml)
        $archivo = getenv("SIGERU_CLAVE_ARCHIVO");
    }
    if (!file_exists($archivo)) {
        // Primera vez: creamos una clave al azar de 32 bytes y la guardamos
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

// Cifra un texto. Un texto vacio o null queda null.
function cifrar($texto) {
    if ($texto === null || $texto === "") {
        return null;
    }
    $iv = random_bytes(12);
    $etiqueta = "";
    $cifrado = openssl_encrypt($texto, "aes-256-gcm", claveDeCifrado(), OPENSSL_RAW_DATA, $iv, $etiqueta);
    return "enc:" . base64_encode($iv . $etiqueta . $cifrado);
}

// Descifra un texto guardado con cifrar().
// Si no empieza con "enc:" es un dato viejo que se guardo sin cifrar y se devuelve como esta.
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
        // La clave no es la correcta o alguien modifico el dato en la base
        return "(no se pudo descifrar)";
    }
    return $resultado;
}

// Resumen SHA-256 de un texto (en hexadecimal, 64 caracteres).
// Se usa para guardar el codigo del enlace de verificacion: en la base queda el resumen y no el codigo,
// asi alguien que vea la base no puede armar el enlace y verificar una cuenta ajena.
function resumen($texto) {
    return hash("sha256", $texto);
}
