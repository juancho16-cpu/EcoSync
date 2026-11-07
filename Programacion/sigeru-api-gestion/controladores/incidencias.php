<?php
// Controlador de las incidencias: registro y seguimiento

// Lista de incidencias (GET incidencias). Se puede filtrar con
// ?estado=Abierta  ?contenedor=2  ?desde=2026-11-01&hasta=2026-11-30
// El vecino solo ve las incidencias que reporto el.
function listarIncidencias($incidencia) {
    $usuario = $_SESSION['usuario_logueado'];
    $idVecino = "";
    if ($usuario['rol'] == "Vecino") {
        $idVecino = $usuario['id'];
    }

    $estado = $_GET['estado'] ?? "";
    $contenedor = $_GET['contenedor'] ?? "";
    $desde = $_GET['desde'] ?? "";
    $hasta = $_GET['hasta'] ?? "";

    if ($estado != "" && !in_array($estado, ["Abierta", "En proceso", "Resuelta"])) {
        responder(["error" => "El estado no es válido"], 400);
    }
    // Las fechas tienen que venir como 2026-11-06
    if ($desde != "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $desde)) {
        responder(["error" => "La fecha desde no es válida"], 400);
    }
    if ($hasta != "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $hasta)) {
        responder(["error" => "La fecha hasta no es válida"], 400);
    }
    if ($desde != "" && $hasta != "" && $desde > $hasta) {
        responder(["error" => "La fecha desde no puede ser después de la fecha hasta"], 400);
    }

    responder($incidencia->obtenerTodas($estado, $contenedor, $desde, $hasta, $idVecino));
}

function obtenerIncidencia($incidencia, $id) {
    $encontrada = $incidencia->obtenerPorId($id);
    if ($encontrada == null) {
        responder(["error" => "Incidencia no encontrada"], 404);
    }
    // El vecino solo puede ver las suyas
    $usuario = $_SESSION['usuario_logueado'];
    if ($usuario['rol'] == "Vecino" && $encontrada['id_vecino'] != $usuario['id']) {
        responder(["error" => "No tenés permiso para ver esta incidencia"], 403);
    }
    responder($encontrada);
}

// Reportar una incidencia (POST incidencias). La reportan el personal y los vecinos, con el mismo formulario.
// Datos: id_contenedor, problema (el tipo), descripcion (que paso, con detalle), referencia (donde exactamente)
// y prioridad (solo el personal; la de los vecinos queda Media hasta que el personal la revise).
function reportarIncidencia($incidencia, $contenedor, $notificacion, $mensaje, $datos) {
    $problemas = ["Desbordado", "Roto", "Falta de recolección", "Basura en la calle"];
    $prioridades = ["Baja", "Media", "Alta"];
    $usuario = $_SESSION['usuario_logueado'];
    $esVecino = $usuario['rol'] == "Vecino";

    $encontrado = $contenedor->obtenerPorId($datos['id_contenedor'] ?? 0);
    if ($encontrado == null) {
        responder(["error" => "Elegí el contenedor donde está el problema"], 404);
    }
    if (!in_array($datos['problema'] ?? "", $problemas)) {
        responder(["error" => "Elegí qué problema hay"], 400);
    }

    $descripcion = trim($datos['descripcion'] ?? "");
    if (strlen($descripcion) < 10) {
        responder(["error" => "Cuenta con detalle qué pasó (al menos 10 letras)"], 400);
    }
    if (strlen($descripcion) > 500) {
        responder(["error" => "La descripción puede tener hasta 500 letras"], 400);
    }
    $referencia = trim($datos['referencia'] ?? "");
    if (strlen($referencia) > 200) {
        responder(["error" => "El lugar puede tener hasta 200 letras"], 400);
    }

    if ($esVecino) {
        $prioridad = "Media";
        // Un vecino no puede reportar dos veces el mismo contenedor mientras no se resuelva
        if ($notificacion->yaAviso($usuario['id'], $encontrado['id'])) {
            responder(["error" => "Ya reportaste una incidencia en este contenedor y todavía no se resolvió. Podés ver cómo va en Mis incidencias."], 409);
        }
    } else {
        $prioridad = $datos['prioridad'] ?? "";
        if (!in_array($prioridad, $prioridades)) {
            responder(["error" => "La prioridad no es válida"], 400);
        }
    }

    $nueva = $incidencia->agregar($encontrado['id'], $encontrado['zona'], $datos['problema'], $prioridad, $descripcion, $referencia);

    if ($esVecino) {
        // Se guarda que la reporto este vecino (relacion Genera2 del MER: tabla notificaciones)
        $notificacion->agregar($usuario['id'], $nueva['id'], $descripcion);

        // Le llega una notificacion a todo el personal, y al vecino la confirmacion
        $texto = $usuario['nombre'] . " reportó una incidencia en el contenedor de " . $encontrado['calle'] . ": " . $datos['problema'];
        $mensaje->enviarARol("Administrador", $texto, "incidencias.html");
        $mensaje->enviarARol("Operario", $texto, "incidencias.html");
        $mensaje->enviarARol("Recolector", $texto, "incidencias.html");
        $mensaje->enviarAUsuario($usuario['id'], "Recibimos tu incidencia #" . $nueva['id'] . " en el contenedor de " . $encontrado['calle'] . ". Te avisamos cuando la atiendan.", "incidencias.html");

        $nueva = $incidencia->obtenerPorId($nueva['id']);
        responder(["mensaje" => "¡Gracias! Tu incidencia #" . $nueva['id'] . " quedó registrada y el equipo de recolección ya la puede ver.", "incidencia" => $nueva], 201);
    }

    // Si la reporta el personal y el contenedor esta roto o desbordado, se cambia su estado
    if ($datos['problema'] == "Roto" || $datos['problema'] == "Desbordado") {
        $contenedor->cambiarEstado($encontrado['id'], $datos['problema']);
    }

    // Le avisamos al administrador y a los operarios (menos al que la registro)
    $texto = $usuario['nombre'] . " registró una incidencia: contenedor de " . $encontrado['calle'] . " (" . $datos['problema'] . ", prioridad " . $prioridad . ")";
    $mensaje->enviarARol("Administrador", $texto, "incidencias.html", $usuario['id']);
    $mensaje->enviarARol("Operario", $texto, "incidencias.html", $usuario['id']);

    responder(["mensaje" => "Incidencia registrada correctamente", "incidencia" => $nueva], 201);
}

// Seguimiento de una incidencia (PUT incidencias?id=1)
// - El administrador y el operario cambian el estado, la prioridad y la cuadrilla (recorrido) que la resuelve.
// - El recolector solo cambia el estado, y solo de las incidencias que tiene asignadas su cuadrilla.
// - Una incidencia Resuelta ya no se puede cambiar.
// - Cuando se resuelve y el contenedor no tiene otras incidencias pendientes, el contenedor vuelve a Funcional.
function seguimientoIncidencia($incidencia, $contenedor, $notificacion, $mensaje, $id, $datos) {
    $estados = ["Abierta", "En proceso", "Resuelta"];
    $prioridades = ["Baja", "Media", "Alta"];
    $usuario = $_SESSION['usuario_logueado'];

    $actual = $incidencia->obtenerPorId($id);
    if ($actual == null) {
        responder(["error" => "Incidencia no encontrada"], 404);
    }
    if ($actual['estado'] == "Resuelta") {
        responder(["error" => "La incidencia ya está resuelta y no se puede cambiar"], 409);
    }

    $estado = $datos['estado'] ?? "";
    if (!in_array($estado, $estados)) {
        responder(["error" => "El estado no es válido"], 400);
    }

    if ($usuario['rol'] == "Recolector") {
        // El recolector no cambia la prioridad ni la cuadrilla: quedan como estaban
        $prioridad = $actual['prioridad'];
        $idRecorrido = $actual['id_recorrido'];

        if ($idRecorrido == null || !$incidencia->esRecorridoDelRecolector($idRecorrido, $usuario['id'])) {
            responder(["error" => "Solo podés cambiar las incidencias asignadas a tu cuadrilla"], 403);
        }
    } else {
        $prioridad = $datos['prioridad'] ?? "";
        if (!in_array($prioridad, $prioridades)) {
            responder(["error" => "La prioridad no es válida"], 400);
        }

        // El recorrido puede quedar vacio (sin cuadrilla asignada)
        $idRecorrido = $datos['id_recorrido'] ?? "";
        if ($idRecorrido == "") {
            $idRecorrido = null;
        } else if (!$incidencia->existeRecorrido($idRecorrido)) {
            responder(["error" => "El recorrido elegido no existe"], 400);
        }
    }

    $modificada = $incidencia->modificarSeguimiento($id, $estado, $prioridad, $idRecorrido);

    // Si la reporto un vecino, su registro cambia de estado igual que la incidencia, y le avisamos
    $notificacion->cambiarEstado($id, $estado);
    $idVecino = $notificacion->vecinoDeLaIncidencia($id);
    if ($idVecino != null && $estado != $actual['estado']) {
        if ($estado == "Resuelta") {
            $mensaje->enviarAUsuario($idVecino, "¡Listo! Se resolvió tu incidencia #" . $id . " en el contenedor de " . $actual['calle'] . ". Gracias por avisar.", "incidencias.html");
        } else if ($estado == "En proceso") {
            $mensaje->enviarAUsuario($idVecino, "Tu incidencia #" . $id . " en el contenedor de " . $actual['calle'] . " está en proceso: una cuadrilla ya la está atendiendo.", "incidencias.html");
        }
    }

    // Si le asignaron una cuadrilla nueva, les avisamos a sus recolectores
    if ($modificada['id_recorrido'] != null && $modificada['id_recorrido'] != $actual['id_recorrido']) {
        $mensaje->enviarACuadrilla($incidencia->cuadrillaDelRecorrido($modificada['id_recorrido']),
            "A tu cuadrilla le asignaron la incidencia #" . $id . ": contenedor de " . $actual['calle'] . " (" . $actual['tipo'] . ", prioridad " . $modificada['prioridad'] . ")", "incidencias.html");
    }

    // Si la resolvio un recolector, le avisamos al administrador y a los operarios
    if ($estado == "Resuelta" && $usuario['rol'] == "Recolector") {
        $texto = $usuario['nombre'] . " resolvió la incidencia #" . $id . " en " . $actual['calle'];
        $mensaje->enviarARol("Administrador", $texto, "incidencias.html");
        $mensaje->enviarARol("Operario", $texto, "incidencias.html");
    }

    // Si ya no le quedan problemas, el contenedor vuelve a estar Funcional
    if ($estado == "Resuelta" && $incidencia->contarSinResolver($actual['id_contenedor']) == 0) {
        $contenedor->cambiarEstado($actual['id_contenedor'], "Funcional");
    }

    responder(["mensaje" => "Incidencia actualizada correctamente", "incidencia" => $modificada]);
}

// Volumen recolectado entre dos fechas (GET volumen?desde=2026-11-01&hasta=2026-11-30)
function obtenerVolumen($estadistica) {
    $desde = $_GET['desde'] ?? "";
    $hasta = $_GET['hasta'] ?? "";
    if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $desde) || !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $hasta)) {
        responder(["error" => "Elegí las dos fechas"], 400);
    }
    if ($desde > $hasta) {
        responder(["error" => "La fecha desde no puede ser después de la fecha hasta"], 400);
    }

    responder([
        "total" => $estadistica->totalRecolectado($desde, $hasta),
        "por_municipio" => $estadistica->kilosPorMunicipio($desde, $hasta),
        "por_residuo" => $estadistica->kilosPorResiduo($desde, $hasta),
        "por_contenedor" => $estadistica->vaciadosPorContenedor($desde, $hasta)
    ]);
}

// Estadisticas para el panel de control (GET estadisticas). Con ?mes=2026-11 se elige el mes.
function obtenerEstadisticas($estadistica) {
    $mes = $_GET['mes'] ?? date("Y-m");
    if (!preg_match("/^[0-9]{4}-[0-9]{2}$/", $mes)) {
        responder(["error" => "El mes no es válido"], 400);
    }

    responder([
        "mes" => $mes,
        "por_estado" => $estadistica->incidenciasPorEstado(),
        "top_desbordados" => $estadistica->topDesbordados($mes),
        "por_municipio" => $estadistica->sinResolverPorMunicipio(),
        "por_tipo" => $estadistica->porTipo($mes),
        "promedio_dias" => $estadistica->promedioDiasResolucion(),
        "resueltas_mes" => $estadistica->resueltasDelMes($mes)
    ]);
}
