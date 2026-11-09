<?php

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

    foreach ($datos['residuos'] ?? [] as $idResiduo) {
        if (!$contenedor->existeResiduo($idResiduo)) {
            responder(["error" => "Uno de los tipos de residuo no existe"], 400);
        }
    }
}

function listarCentros($centro) {
    responder($centro->obtenerTodos());
}

function obtenerCentro($centro, $id) {
    $encontrado = $centro->obtenerPorId($id);

    if ($encontrado == null) {
        responder(["error" => "Centro de acopio no encontrado"], 404);
    }
    responder($encontrado);
}

function listarOperarios($centro) {
    responder($centro->obtenerOperarios());
}

function registrarCentro($centro, $contenedor, $datos) {
    validarCentro($centro, $contenedor, $datos);

    $nuevo = $centro->agregar($datos);
    responder(["mensaje" => "Centro de acopio registrado correctamente", "centro" => $nuevo], 201);
}

function modificarCentro($centro, $contenedor, $id, $datos) {
    if ($centro->obtenerPorId($id) == null) {
        responder(["error" => "Centro de acopio no encontrado"], 404);
    }
    validarCentro($centro, $contenedor, $datos);

    $modificado = $centro->modificar($id, $datos);
    responder(["mensaje" => "Centro de acopio modificado correctamente", "centro" => $modificado]);
}

function eliminarCentro($centro, $id) {
    if ($centro->obtenerPorId($id) == null) {
        responder(["error" => "Centro de acopio no encontrado"], 404);
    }

    $centro->eliminar($id);
    responder(["mensaje" => "Centro de acopio eliminado correctamente"]);
}
