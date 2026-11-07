<?php
// Controlador de recorridos: asignar rutas a las cuadrillas con un camion

// Revisa que los datos del formulario esten bien. Si hay un error lo devuelve y corta.
// $id es el recorrido que se esta modificando (null si es uno nuevo).
function validarRecorrido($recorrido, $camion, $datos, $id) {
    if (empty($datos['id_cuadrilla']) || !$recorrido->existeCuadrilla($datos['id_cuadrilla'])) {
        responder(["error" => "Elegí una cuadrilla"], 400);
    }
    if (empty($datos['id_ruta']) || !$recorrido->existeRuta($datos['id_ruta'])) {
        responder(["error" => "Elegí una ruta"], 400);
    }

    // Cada cuadrilla puede tener una sola vez cada ruta
    $otro = $recorrido->buscarPorCuadrillaYRuta($datos['id_cuadrilla'], $datos['id_ruta']);
    if ($otro != null && $otro['id'] != $id) {
        responder(["error" => "Esa cuadrilla ya tiene asignada esa ruta"], 409);
    }

    // RNE06: una cuadrilla solo puede ser asignada a rutas si tiene camion disponible
    $elegido = $camion->obtenerPorId($datos['id_camion'] ?? 0);
    if ($elegido == null) {
        responder(["error" => "Elegí un camión"], 400);
    }
    // Si se esta modificando y el camion es el mismo que ya tenia, se deja aunque este en ruta
    $mismoCamion = false;
    if ($id != null) {
        $anterior = $recorrido->obtenerPorId($id);
        if ($anterior['id_camion'] == $elegido['id']) {
            $mismoCamion = true;
        }
    }
    if (!$mismoCamion && ($elegido['estado'] != "Operativo" || $elegido['disponibilidad'] != "Disponible")) {
        responder(["error" => "El camión " . $elegido['matricula'] . " no está disponible (RNE06)"], 400);
    }

    if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $datos['fecha_asignacion'] ?? "")) {
        responder(["error" => "La fecha no es válida"], 400);
    }

    // Las zonas que se eligen tienen que existir
    foreach ($datos['zonas'] ?? [] as $idZona) {
        if (!$recorrido->existeZona($idZona)) {
            responder(["error" => "Una de las zonas no existe"], 400);
        }
    }

    // Las horas son opcionales, pero si estan las dos el fin tiene que ser despues del inicio
    $inicio = $datos['hora_inicio'] ?? "";
    $fin = $datos['hora_fin'] ?? "";
    if ($inicio != "" && $fin != "" && $fin <= $inicio) {
        responder(["error" => "La hora de fin tiene que ser después de la hora de inicio"], 400);
    }
}

// Recorridos (GET recorridos): el recolector ve solo los de su cuadrilla, el resto ve todos
function listarRecorridos($recorrido) {
    $usuario = $_SESSION['usuario_logueado'];

    if ($usuario['rol'] == "Recolector") {
        responder($recorrido->obtenerDelRecolector($usuario['id']));
    }
    responder($recorrido->obtenerTodos());
}

// Un recorrido (GET recorridos?id=1)
function obtenerRecorrido($recorrido, $id) {
    $encontrado = $recorrido->obtenerPorId($id);

    if ($encontrado == null) {
        responder(["error" => "Recorrido no encontrado"], 404);
    }
    responder($encontrado);
}

// Listas para el formulario (GET cuadrillas y GET rutas)
function listarCuadrillas($recorrido) {
    responder($recorrido->obtenerCuadrillas());
}

function listarRutas($recorrido) {
    responder($recorrido->obtenerRutas());
}

function listarZonas($recorrido) {
    responder($recorrido->obtenerZonas());
}

// Le avisa a los recolectores de la cuadrilla que tienen un recorrido asignado
function avisarACuadrilla($mensaje, $r, $esNuevo) {
    $horario = "";
    if (!empty($r['hora_inicio']) && !empty($r['hora_fin'])) {
        $horario = " de " . substr($r['hora_inicio'], 0, 5) . " a " . substr($r['hora_fin'], 0, 5);
    }
    if ($esNuevo) {
        $texto = "A tu cuadrilla le asignaron la " . $r['ruta'] . " el " . $r['fecha_asignacion'] . $horario . " (camión " . $r['camion'] . ")";
    } else {
        $texto = "Cambió el recorrido de tu cuadrilla: " . $r['ruta'] . " el " . $r['fecha_asignacion'] . $horario . " (camión " . $r['camion'] . ")";
    }
    $mensaje->enviarACuadrilla($r['id_cuadrilla'], $texto, "recorridos.html");
}

// Asigna una ruta a una cuadrilla (POST recorridos)
function registrarRecorrido($recorrido, $camion, $mensaje, $datos) {
    validarRecorrido($recorrido, $camion, $datos, null);

    $nuevo = $recorrido->agregar($datos);
    avisarACuadrilla($mensaje, $nuevo, true);
    responder(["mensaje" => "Recorrido asignado correctamente", "recorrido" => $nuevo], 201);
}

// Modifica un recorrido (PUT recorridos?id=1)
function modificarRecorrido($recorrido, $camion, $mensaje, $id, $datos) {
    if ($recorrido->obtenerPorId($id) == null) {
        responder(["error" => "Recorrido no encontrado"], 404);
    }
    validarRecorrido($recorrido, $camion, $datos, $id);

    $modificado = $recorrido->modificar($id, $datos);
    avisarACuadrilla($mensaje, $modificado, false);
    responder(["mensaje" => "Recorrido modificado correctamente", "recorrido" => $modificado]);
}

// Borra un recorrido (DELETE recorridos?id=1)
function eliminarRecorrido($recorrido, $id) {
    if ($recorrido->obtenerPorId($id) == null) {
        responder(["error" => "Recorrido no encontrado"], 404);
    }

    $recorrido->eliminar($id);
    responder(["mensaje" => "Recorrido eliminado correctamente"]);
}
