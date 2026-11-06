<?php
// Controlador de mantenimientos de camiones: valida los datos y usa el modelo Mantenimiento

// Revisa que los datos del formulario esten bien. Si hay un error lo devuelve y corta.
function validarMantenimiento($datos) {
    $tipos = ["Preventivo", "Correctivo", "Revisión"];

    if (!in_array($datos['tipo'] ?? "", $tipos)) {
        responder(["error" => "Elegí el tipo de mantenimiento"], 400);
    }
    // La fecha tiene que venir como 2026-11-06
    if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $datos['fecha'] ?? "")) {
        responder(["error" => "La fecha no es válida"], 400);
    }
    if (!empty($datos['costo']) && !is_numeric($datos['costo'])) {
        responder(["error" => "El costo debe ser un número"], 400);
    }
}

// Mantenimientos de un camion (GET mantenimientos?camion=1)
function listarMantenimientos($mantenimiento, $camion, $idCamion) {
    if ($camion->obtenerPorId($idCamion) == null) {
        responder(["error" => "Camión no encontrado"], 404);
    }
    responder($mantenimiento->obtenerDelCamion($idCamion));
}

// Registra un mantenimiento (POST mantenimientos)
function registrarMantenimiento($mantenimiento, $camion, $datos) {
    if ($camion->obtenerPorId($datos['id_camion'] ?? 0) == null) {
        responder(["error" => "Camión no encontrado"], 404);
    }
    validarMantenimiento($datos);

    $nuevo = $mantenimiento->agregar($datos);
    responder(["mensaje" => "Mantenimiento registrado correctamente", "mantenimiento" => $nuevo], 201);
}

// Modifica un mantenimiento (PUT mantenimientos?camion=1&nro=2)
function modificarMantenimiento($mantenimiento, $idCamion, $nro, $datos) {
    if ($mantenimiento->obtenerUno($idCamion, $nro) == null) {
        responder(["error" => "Mantenimiento no encontrado"], 404);
    }
    validarMantenimiento($datos);

    $modificado = $mantenimiento->modificar($idCamion, $nro, $datos);
    responder(["mensaje" => "Mantenimiento modificado correctamente", "mantenimiento" => $modificado]);
}

// Borra un mantenimiento (DELETE mantenimientos?camion=1&nro=2)
function eliminarMantenimiento($mantenimiento, $idCamion, $nro) {
    if ($mantenimiento->obtenerUno($idCamion, $nro) == null) {
        responder(["error" => "Mantenimiento no encontrado"], 404);
    }

    $mantenimiento->eliminar($idCamion, $nro);
    responder(["mensaje" => "Mantenimiento eliminado correctamente"]);
}
