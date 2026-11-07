<?php
// Controlador de los mensajes (notificaciones de la campana) y de los parametros del sistema

// Mensajes del usuario que inicio sesion (GET mensajes)
function listarMensajes($mensaje) {
    $idUsuario = $_SESSION['usuario_logueado']['id'];

    responder([
        "sin_leer" => $mensaje->contarSinLeer($idUsuario),
        "mensajes" => $mensaje->obtenerDelUsuario($idUsuario)
    ]);
}

// Marca como leido un mensaje (PUT mensajes?id=3) o todos (PUT mensajes)
function leerMensajes($mensaje, $id) {
    $idUsuario = $_SESSION['usuario_logueado']['id'];

    if ($id != null) {
        $mensaje->marcarLeido($id, $idUsuario);
    } else {
        $mensaje->marcarTodosLeidos($idUsuario);
    }
    responder(["mensaje" => "Listo"]);
}

// Lista de parametros (GET parametros)
function listarParametros($parametro) {
    responder($parametro->obtenerTodos());
}

// El administrador cambia los parametros (PUT parametros con {"nombre": "valor", ...})
function modificarParametros($parametro, $datos) {
    // Lo que puede valer cada parametro
    $numeros = ["dias_alerta_desborde", "porcentaje_centro_lleno", "kilos_por_litro", "intentos_login", "minutos_bloqueo"];
    $opciones = [
        "aprobar_registros" => ["si", "no"],
        "idioma" => ["es", "en"]
    ];

    // Primero revisamos todos, asi no se guarda nada si alguno esta mal
    foreach ($datos as $nombre => $valor) {
        if (in_array($nombre, $numeros)) {
            if (!is_numeric($valor) || $valor < 0) {
                responder(["error" => "El valor de $nombre tiene que ser un número"], 400);
            }
        } else if (isset($opciones[$nombre])) {
            if (!in_array($valor, $opciones[$nombre])) {
                responder(["error" => "El valor de $nombre no es válido"], 400);
            }
        } else {
            responder(["error" => "El parámetro $nombre no existe"], 400);
        }
    }

    $cambiados = [];
    foreach ($datos as $nombre => $valor) {
        if ($parametro->obtener($nombre, null) != $valor) {
            $cambiados[] = $nombre . " = " . $valor;
        }
        $parametro->modificar($nombre, $valor);
    }
    if (count($cambiados) > 0) {
        auditar("Parámetros modificados", implode(", ", $cambiados));
    }
    responder(["mensaje" => "Parámetros guardados correctamente", "parametros" => $parametro->obtenerTodos()]);
}
