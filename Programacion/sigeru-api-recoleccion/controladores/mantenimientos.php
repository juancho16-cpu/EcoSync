<?php

function validarMantenimiento($datos) {
    $tipos = ["Preventivo", "Correctivo", "Revisión"];

    if (!in_array($datos['tipo'] ?? "", $tipos)) {
        responder(["error" => "Elegí el tipo de mantenimiento"], 400);
    }
    
    if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $datos['fecha'] ?? "")) {
        responder(["error" => "La fecha no es válida"], 400);
    }
    if (!empty($datos['costo']) && !is_numeric($datos['costo'])) {
        responder(["error" => "El costo debe ser un número"], 400);
    }
}

function listarMantenimientos($mantenimiento, $camion, $idCamion) {
    if ($camion->obtenerPorId($idCamion) == null) {
        responder(["error" => "Camión no encontrado"], 404);
    }
    responder($mantenimiento->obtenerDelCamion($idCamion));
}

function registrarMantenimiento($mantenimiento, $camion, $datos) {
    if ($camion->obtenerPorId($datos['id_camion'] ?? 0) == null) {
        responder(["error" => "Camión no encontrado"], 404);
    }
    validarMantenimiento($datos);

    $nuevo = $mantenimiento->agregar($datos);
    responder(["mensaje" => "Mantenimiento registrado correctamente", "mantenimiento" => $nuevo], 201);
}

function modificarMantenimiento($mantenimiento, $idCamion, $nro, $datos) {
    if ($mantenimiento->obtenerUno($idCamion, $nro) == null) {
        responder(["error" => "Mantenimiento no encontrado"], 404);
    }
    validarMantenimiento($datos);

    $modificado = $mantenimiento->modificar($idCamion, $nro, $datos);
    responder(["mensaje" => "Mantenimiento modificado correctamente", "mantenimiento" => $modificado]);
}

function eliminarMantenimiento($mantenimiento, $idCamion, $nro) {
    if ($mantenimiento->obtenerUno($idCamion, $nro) == null) {
        responder(["error" => "Mantenimiento no encontrado"], 404);
    }

    $mantenimiento->eliminar($idCamion, $nro);
    responder(["mensaje" => "Mantenimiento eliminado correctamente"]);
}
