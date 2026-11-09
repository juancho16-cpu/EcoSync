<?php

function listarMensajes($mensaje) {
    $idUsuario = $_SESSION['usuario_logueado']['id'];

    responder([
        "sin_leer" => $mensaje->contarSinLeer($idUsuario),
        "mensajes" => $mensaje->obtenerDelUsuario($idUsuario)
    ]);
}

function leerMensajes($mensaje, $id) {
    $idUsuario = $_SESSION['usuario_logueado']['id'];

    if ($id != null) {
        $mensaje->marcarLeido($id, $idUsuario);
    } else {
        $mensaje->marcarTodosLeidos($idUsuario);
    }
    responder(["mensaje" => "Listo"]);
}

function listarParametros($parametro) {
    responder($parametro->obtenerTodos());
}

function modificarParametros($parametro, $datos) {
    
    $numeros = ["dias_alerta_desborde", "porcentaje_centro_lleno", "kilos_por_litro", "intentos_login", "minutos_bloqueo"];
    $opciones = [
        "aprobar_registros" => ["si", "no"],
        "idioma" => ["es", "en"]
    ];

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
