<?php

function validarCamion($camion, $datos, $id) {
    $estadosCamion = ["Operativo", "En mantenimiento", "Fuera de servicio"];
    $disponibilidades = ["Disponible", "En ruta", "No disponible"];

    if (empty($datos['matricula'])) {
        responder(["error" => "La matrícula es obligatoria"], 400);
    }
    
    $otro = $camion->buscarPorMatricula(strtoupper($datos['matricula']));
    if ($otro != null && $otro['id'] != $id) {
        responder(["error" => "Ya existe un camión con esa matrícula"], 409);
    }
    if (!in_array($datos['estado'] ?? "", $estadosCamion)) {
        responder(["error" => "El estado no es válido"], 400);
    }
    if (!in_array($datos['disponibilidad'] ?? "", $disponibilidades)) {
        responder(["error" => "La disponibilidad no es válida"], 400);
    }
    if (!empty($datos['capacidad_carga']) && !is_numeric($datos['capacidad_carga'])) {
        responder(["error" => "La capacidad de carga debe ser un número"], 400);
    }
}

function listarCamiones($camion) {
    responder($camion->obtenerTodos());
}

function obtenerCamion($camion, $id) {
    $encontrado = $camion->obtenerPorId($id);

    if ($encontrado == null) {
        responder(["error" => "Camión no encontrado"], 404);
    }
    responder($encontrado);
}

function registrarCamion($camion, $datos) {
    validarCamion($camion, $datos, null);

    $nuevo = $camion->agregar($datos);
    responder(["mensaje" => "Camión registrado correctamente", "camion" => $nuevo], 201);
}

function modificarCamion($camion, $id, $datos) {
    if ($camion->obtenerPorId($id) == null) {
        responder(["error" => "Camión no encontrado"], 404);
    }
    validarCamion($camion, $datos, $id);

    $modificado = $camion->modificar($id, $datos);
    responder(["mensaje" => "Camión modificado correctamente", "camion" => $modificado]);
}

function eliminarCamion($camion, $id) {
    if ($camion->obtenerPorId($id) == null) {
        responder(["error" => "Camión no encontrado"], 404);
    }

    $camion->eliminar($id);
    responder(["mensaje" => "Camión eliminado correctamente"]);
}
