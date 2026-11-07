<?php
// Controlador del inventario de repuestos

function validarRepuesto($contenedor, $maquinaria, $datos) {
    $tipos = ["Contenedor", "Tapa", "Rueda", "Otro"];

    if (empty(trim($datos['nombre'] ?? ""))) {
        responder(["error" => "El nombre es obligatorio"], 400);
    }
    if (!in_array($datos['tipo'] ?? "", $tipos)) {
        responder(["error" => "El tipo no es válido"], 400);
    }
    // La cantidad y el minimo tienen que ser numeros enteros de 0 para arriba
    if (!preg_match("/^[0-9]+$/", $datos['cantidad'] ?? "")) {
        responder(["error" => "La cantidad tiene que ser un número entero"], 400);
    }
    if (!preg_match("/^[0-9]+$/", $datos['cantidad_minima'] ?? "")) {
        responder(["error" => "La cantidad mínima tiene que ser un número entero"], 400);
    }
    if (!empty($datos['id_residuo']) && !$contenedor->existeResiduo($datos['id_residuo'])) {
        responder(["error" => "El tipo de residuo no existe"], 400);
    }
    if (!empty($datos['id_instalacion']) && !$maquinaria->existeInstalacion($datos['id_instalacion'])) {
        responder(["error" => "La instalación elegida no existe"], 400);
    }
}

function listarRepuestos($repuesto) {
    responder($repuesto->obtenerTodos());
}

function obtenerRepuesto($repuesto, $id) {
    $encontrado = $repuesto->obtenerPorId($id);
    if ($encontrado == null) {
        responder(["error" => "Repuesto no encontrado"], 404);
    }
    responder($encontrado);
}

function registrarRepuesto($repuesto, $contenedor, $maquinaria, $datos) {
    // Los numeros pueden venir como numero o como texto: los pasamos a texto para revisarlos
    $datos['cantidad'] = strval($datos['cantidad'] ?? "");
    $datos['cantidad_minima'] = strval($datos['cantidad_minima'] ?? "");
    validarRepuesto($contenedor, $maquinaria, $datos);

    $nuevo = $repuesto->agregar($datos);
    responder(["mensaje" => "Repuesto registrado correctamente", "repuesto" => $nuevo], 201);
}

function modificarRepuesto($repuesto, $contenedor, $maquinaria, $id, $datos) {
    if ($repuesto->obtenerPorId($id) == null) {
        responder(["error" => "Repuesto no encontrado"], 404);
    }
    $datos['cantidad'] = strval($datos['cantidad'] ?? "");
    $datos['cantidad_minima'] = strval($datos['cantidad_minima'] ?? "");
    validarRepuesto($contenedor, $maquinaria, $datos);

    $modificado = $repuesto->modificar($id, $datos);
    responder(["mensaje" => "Repuesto modificado correctamente", "repuesto" => $modificado]);
}

// Usar o reponer unidades (POST stock?id=1 con {"cambio": -1})
// Si queda en el minimo o menos, se avisa al administrador y a los operarios.
function cambiarStock($repuesto, $mensaje, $id, $datos) {
    $actual = $repuesto->obtenerPorId($id);
    if ($actual == null) {
        responder(["error" => "Repuesto no encontrado"], 404);
    }

    $cambio = $datos['cambio'] ?? 0;
    if (!is_numeric($cambio) || $cambio == 0) {
        responder(["error" => "Indica cuántas unidades se usan o se agregan"], 400);
    }
    if ($actual['cantidad'] + $cambio < 0) {
        responder(["error" => "No hay suficientes unidades de " . $actual['nombre'] . " (quedan " . $actual['cantidad'] . ")"], 409);
    }

    $modificado = $repuesto->cambiarCantidad($id, intval($cambio));

    // Si estaba bien y ahora quedo en el minimo o menos, avisamos para reponer
    if ($cambio < 0 && $modificado['cantidad'] <= $modificado['cantidad_minima'] && $actual['cantidad'] > $actual['cantidad_minima']) {
        $texto = "Quedan pocos repuestos de " . $modificado['nombre'] . ": " . $modificado['cantidad'] . " (mínimo " . $modificado['cantidad_minima'] . ")";
        $mensaje->enviarARol("Administrador", $texto, "repuestos.html");
        $mensaje->enviarARol("Operario", $texto, "repuestos.html");
    }

    responder(["mensaje" => "Inventario actualizado: quedan " . $modificado['cantidad'] . " de " . $modificado['nombre'], "repuesto" => $modificado]);
}

function eliminarRepuesto($repuesto, $id) {
    if ($repuesto->obtenerPorId($id) == null) {
        responder(["error" => "Repuesto no encontrado"], 404);
    }
    $repuesto->eliminar($id);
    responder(["mensaje" => "Repuesto eliminado correctamente"]);
}
