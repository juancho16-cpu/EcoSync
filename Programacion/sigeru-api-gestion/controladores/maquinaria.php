<?php
// Controlador de maquinaria: valida los datos y usa el modelo Maquinaria

// Revisa que los datos del formulario esten bien. Si hay un error lo devuelve y corta.
function validarMaquina($maquinaria, $datos) {
    $estados = ["Operativa", "En reparación", "Fuera de servicio"];

    if (empty($datos['nombre'])) {
        responder(["error" => "El nombre es obligatorio"], 400);
    }
    if (!in_array($datos['estado'] ?? "", $estados)) {
        responder(["error" => "El estado no es válido"], 400);
    }
    if (!empty($datos['id_instalacion']) && !$maquinaria->existeInstalacion($datos['id_instalacion'])) {
        responder(["error" => "La instalación elegida no existe"], 400);
    }
}

// Devuelve la lista de maquinas (GET maquinaria)
function listarMaquinaria($maquinaria) {
    responder($maquinaria->obtenerTodos());
}

// Busca una maquina por su numero de id (GET maquinaria?id=1)
function obtenerMaquina($maquinaria, $id) {
    $encontrada = $maquinaria->obtenerPorId($id);

    if ($encontrada == null) {
        responder(["error" => "Máquina no encontrada"], 404);
    }
    responder($encontrada);
}

// Devuelve la lista de instalaciones (GET instalaciones)
function listarInstalaciones($maquinaria) {
    responder($maquinaria->obtenerInstalaciones());
}

// Registra una maquina nueva (POST maquinaria)
function registrarMaquina($maquinaria, $datos) {
    validarMaquina($maquinaria, $datos);

    $nueva = $maquinaria->agregar($datos);
    responder(["mensaje" => "Máquina registrada correctamente", "maquina" => $nueva], 201);
}

// Modifica una maquina (PUT maquinaria?id=1)
function modificarMaquina($maquinaria, $id, $datos) {
    if ($maquinaria->obtenerPorId($id) == null) {
        responder(["error" => "Máquina no encontrada"], 404);
    }
    validarMaquina($maquinaria, $datos);

    $modificada = $maquinaria->modificar($id, $datos);
    responder(["mensaje" => "Máquina modificada correctamente", "maquina" => $modificada]);
}

// Borra una maquina (DELETE maquinaria?id=1)
function eliminarMaquina($maquinaria, $id) {
    if ($maquinaria->obtenerPorId($id) == null) {
        responder(["error" => "Máquina no encontrada"], 404);
    }

    $maquinaria->eliminar($id);
    responder(["mensaje" => "Máquina eliminada correctamente"]);
}
