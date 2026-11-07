<?php
// Controlador de centros de acopio: valida los datos y usa el modelo Centro

// Revisa que los datos del formulario esten bien. Si hay un error lo devuelve y corta.
function validarCentro($centro, $contenedor, $datos) {
    if (empty($datos['nombre'])) {
        responder(["error" => "El nombre es obligatorio"], 400);
    }

    $actual = $datos['capacidad_actual'] ?? "";
    $maxima = $datos['capacidad_max'] ?? "";
    if (($actual != "" && !is_numeric($actual)) || ($maxima != "" && !is_numeric($maxima))) {
        responder(["error" => "Las capacidades deben ser números"], 400);
    }
    if ($actual != "" && $maxima != "" && $actual > $maxima) {
        responder(["error" => "La capacidad actual no puede ser mayor que la máxima"], 400);
    }

    if (!empty($datos['id_operario']) && !$centro->esOperario($datos['id_operario'])) {
        responder(["error" => "El usuario elegido no es un operario"], 400);
    }

    // Los tipos de residuo tienen que existir
    foreach ($datos['residuos'] ?? [] as $idResiduo) {
        if (!$contenedor->existeResiduo($idResiduo)) {
            responder(["error" => "Uno de los tipos de residuo no existe"], 400);
        }
    }
}

// Devuelve la lista de centros de acopio (GET centros)
function listarCentros($centro) {
    responder($centro->obtenerTodos());
}

// Busca un centro por su numero de id (GET centros?id=1)
function obtenerCentro($centro, $id) {
    $encontrado = $centro->obtenerPorId($id);

    if ($encontrado == null) {
        responder(["error" => "Centro de acopio no encontrado"], 404);
    }
    responder($encontrado);
}

// Devuelve la lista de operarios (GET operarios)
function listarOperarios($centro) {
    responder($centro->obtenerOperarios());
}

// Registra un centro nuevo (POST centros)
function registrarCentro($centro, $contenedor, $datos) {
    validarCentro($centro, $contenedor, $datos);

    $nuevo = $centro->agregar($datos);
    responder(["mensaje" => "Centro de acopio registrado correctamente", "centro" => $nuevo], 201);
}

// Modifica un centro (PUT centros?id=1)
function modificarCentro($centro, $contenedor, $id, $datos) {
    if ($centro->obtenerPorId($id) == null) {
        responder(["error" => "Centro de acopio no encontrado"], 404);
    }
    validarCentro($centro, $contenedor, $datos);

    $modificado = $centro->modificar($id, $datos);
    responder(["mensaje" => "Centro de acopio modificado correctamente", "centro" => $modificado]);
}

// Borra un centro (DELETE centros?id=1)
function eliminarCentro($centro, $id) {
    if ($centro->obtenerPorId($id) == null) {
        responder(["error" => "Centro de acopio no encontrado"], 404);
    }

    $centro->eliminar($id);
    responder(["mensaje" => "Centro de acopio eliminado correctamente"]);
}
