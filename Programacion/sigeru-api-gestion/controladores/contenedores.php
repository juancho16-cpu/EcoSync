<?php

function validarContenedor($contenedor, $datos) {
    $estadosContenedor = ["Funcional", "Roto", "Desbordado"];

    if (empty($datos['calle']) || empty($datos['zona'])) {
        responder(["error" => "La calle y la zona son obligatorias"], 400);
    }
    if (!in_array($datos['estado'] ?? "", $estadosContenedor)) {
        responder(["error" => "El estado no es válido"], 400);
    }
    if (empty($datos['id_residuo']) || !$contenedor->existeResiduo($datos['id_residuo'])) {
        responder(["error" => "Elegí un tipo de residuo"], 400);
    }
    if (!empty($datos['capacidad']) && !is_numeric($datos['capacidad'])) {
        responder(["error" => "La capacidad debe ser un número"], 400);
    }

    $municipios = ["A", "B", "C", "CH", "D", "E", "F", "G"];
    if (!empty($datos['municipio']) && !in_array($datos['municipio'], $municipios)) {
        responder(["error" => "El municipio no es válido"], 400);
    }

    $latitud = $datos['latitud'] ?? "";
    $longitud = $datos['longitud'] ?? "";
    if ($latitud != "" || $longitud != "") {
        if (!is_numeric($latitud) || !is_numeric($longitud)) {
            responder(["error" => "La ubicación no es válida"], 400);
        }
        if ($latitud < -35 || $latitud > -34.6 || $longitud < -56.5 || $longitud > -55.9) {
            responder(["error" => "La ubicación tiene que estar dentro de Montevideo"], 400);
        }
    }
}

function listarContenedores($contenedor) {
    responder($contenedor->obtenerTodos());
}

function obtenerContenedor($contenedor, $id) {
    $encontrado = $contenedor->obtenerPorId($id);

    if ($encontrado == null) {
        responder(["error" => "Contenedor no encontrado"], 404);
    }
    responder($encontrado);
}

function listarTiposResiduo($contenedor) {
    responder($contenedor->obtenerTiposResiduo());
}

function registrarContenedor($contenedor, $datos) {
    validarContenedor($contenedor, $datos);

    $nuevo = $contenedor->agregar($datos);
    responder(["mensaje" => "Contenedor registrado correctamente", "contenedor" => $nuevo], 201);
}

function modificarContenedor($contenedor, $id, $datos) {
    if ($contenedor->obtenerPorId($id) == null) {
        responder(["error" => "Contenedor no encontrado"], 404);
    }
    validarContenedor($contenedor, $datos);

    $modificado = $contenedor->modificar($id, $datos);
    responder(["mensaje" => "Contenedor modificado correctamente", "contenedor" => $modificado]);
}

function eliminarContenedor($contenedor, $id) {
    if ($contenedor->obtenerPorId($id) == null) {
        responder(["error" => "Contenedor no encontrado"], 404);
    }
    
    if ($contenedor->tieneIncidencias($id)) {
        responder(["error" => "No se puede borrar: el contenedor tiene incidencias registradas"], 409);
    }

    $contenedor->eliminar($id);
    responder(["mensaje" => "Contenedor eliminado correctamente"]);
}

function vaciarContenedor($contenedor, $parametro, $id) {
    $encontrado = $contenedor->obtenerPorId($id);
    if ($encontrado == null) {
        responder(["error" => "Contenedor no encontrado"], 404);
    }

    $kilosPorLitro = floatval($parametro->obtener("kilos_por_litro", "0.18"));
    $lleno = rand(50, 100) / 100;
    $kilos = round($encontrado['capacidad'] * $kilosPorLitro * $lleno, 2);
    $contenedor->registrarRecoleccion($id, $kilos);

    $vaciado = $contenedor->vaciar($id);
    responder(["mensaje" => "Contenedor marcado como vaciado (se recolectaron " . $kilos . " kg)", "contenedor" => $vaciado, "kilos" => $kilos]);
}
