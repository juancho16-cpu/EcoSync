<?php

require_once __DIR__ . "/funciones_test.php";

$api = "http://localhost/sigeru-api-recoleccion/api.php";
$apiUsuarios = "http://localhost/sigeru-api-usuarios/api.php";
if (isset($argv[1])) {
    $api = $argv[1];
}
if (isset($argv[2])) {
    $apiUsuarios = $argv[2];
}
echo "Probando la API en $api\n";

entrar($apiUsuarios, "admin", "admin@sigeru.uy", "admin123");
entrar($apiUsuarios, "operario", "operario@sigeru.uy", "123456");
entrar($apiUsuarios, "recolector", "recolector@sigeru.uy", "123456");
entrar($apiUsuarios, "vecino", "vecino@sigeru.uy", "123456");

$base = conectarBaseReal();
$ultimoMensaje = mysqli_fetch_assoc(mysqli_query($base, "SELECT COALESCE(MAX(id_mensaje), 0) AS id FROM mensajes"))['id'];

$matricula = "TST " . rand(1000, 9999);

titulo("Permisos por rol");

$r = pedir("GET", $api . "/camiones", null, "nadie");
verificar($r['codigo'] == 401, "Sin sesión no se pueden ver los camiones (401)");

$r = pedir("GET", $api . "/camiones", null, "vecino");
verificar($r['codigo'] == 403, "El vecino no puede ver los camiones (403)");

$r = pedir("GET", $api . "/camiones", null, "recolector");
verificar($r['codigo'] == 200, "El recolector puede ver los camiones");

$r = pedir("POST", $api . "/camiones", ["matricula" => $matricula, "estado" => "Operativo", "disponibilidad" => "Disponible"], "recolector");
verificar($r['codigo'] == 403, "El recolector no puede crear camiones (403)");

$r = pedir("GET", $api . "/mantenimientos?camion=2", null, "recolector");
verificar($r['codigo'] == 200 && count($r['json']) == 1, "El recolector puede ver los mantenimientos");

$r = pedir("POST", $api . "/recorridos", ["id_cuadrilla" => 2], "recolector");
verificar($r['codigo'] == 403, "El recolector no puede asignar recorridos (403)");

$r = pedir("GET", $api . "/recorridos", null, "recolector");
verificar($r['codigo'] == 200 && count($r['json']) == 1, "El recolector ve solo el recorrido de su cuadrilla");

$r = pedir("GET", $api . "/recorridos", null, "admin");
verificar($r['codigo'] == 200 && count($r['json']) == 2, "El administrador ve todos los recorridos");

titulo("CRUD de camiones (administrador)");

$r = pedir("POST", $api . "/camiones", ["matricula" => $matricula, "estado" => "Operativo", "disponibilidad" => "Disponible", "capacidad_carga" => "9000"], "admin");
verificar($r['codigo'] == 201, "Crea un camión");
$idCamion = $r['json']['camion']['id'];

$r = pedir("POST", $api . "/camiones", ["matricula" => $matricula, "estado" => "Operativo", "disponibilidad" => "Disponible"], "admin");
verificar($r['codigo'] == 409, "No deja repetir la matrícula (409)");

$r = pedir("PUT", $api . "/camiones?id=" . $idCamion, ["matricula" => "SBA 1234", "estado" => "Operativo", "disponibilidad" => "Disponible"], "admin");
verificar($r['codigo'] == 409, "No deja poner la matrícula de otro camión (409)");

$r = pedir("PUT", $api . "/camiones?id=" . $idCamion, ["matricula" => $matricula, "estado" => "Operativo", "disponibilidad" => "Disponible", "capacidad_carga" => "mucho"], "admin");
verificar($r['codigo'] == 400, "Con una capacidad que no es un número devuelve 400");

titulo("Mantenimientos (operario)");

$r = pedir("POST", $api . "/mantenimientos", ["id_camion" => $idCamion, "tipo" => "Preventivo", "costo" => "12000", "descripcion" => "Cambio de aceite", "fecha" => "2026-11-01"], "operario");
verificar($r['codigo'] == 201 && $r['json']['mantenimiento']['nro'] == 1, "Registra el mantenimiento número 1 del camión");

$r = pedir("POST", $api . "/mantenimientos", ["id_camion" => $idCamion, "tipo" => "Lavado", "fecha" => "2026-11-01"], "operario");
verificar($r['codigo'] == 400, "Con un tipo que no existe devuelve 400");

$r = pedir("POST", $api . "/mantenimientos", ["id_camion" => $idCamion, "tipo" => "Correctivo", "fecha" => "ayer"], "operario");
verificar($r['codigo'] == 400, "Con una fecha mal escrita devuelve 400");

$r = pedir("POST", $api . "/mantenimientos", ["id_camion" => 99999, "tipo" => "Correctivo", "fecha" => "2026-11-01"], "operario");
verificar($r['codigo'] == 404, "Con un camión que no existe devuelve 404");

$r = pedir("PUT", $api . "/mantenimientos?camion=" . $idCamion . "&nro=1", ["tipo" => "Revisión", "costo" => "", "fecha" => "2026-11-02"], "operario");
verificar($r['codigo'] == 200 && $r['json']['mantenimiento']['tipo'] == "Revisión", "Modifica el mantenimiento");

$r = pedir("GET", $api . "/mantenimientos?camion=" . $idCamion, null, "operario");
verificar($r['codigo'] == 200 && count($r['json']) == 1, "Lista los mantenimientos del camión");

$r = pedir("DELETE", $api . "/mantenimientos?camion=" . $idCamion . "&nro=1", null, "operario");
verificar($r['codigo'] == 200, "Borra el mantenimiento");

$r = pedir("DELETE", $api . "/mantenimientos?camion=" . $idCamion . "&nro=1", null, "operario");
verificar($r['codigo'] == 404, "Borrar uno que ya no existe devuelve 404");

titulo("Recorridos: asignar rutas a cuadrillas (administrador)");

$r = pedir("GET", $api . "/cuadrillas", null, "admin");
verificar($r['codigo'] == 200 && count($r['json']) >= 2, "Lista las cuadrillas");

$r = pedir("GET", $api . "/rutas", null, "admin");
verificar($r['codigo'] == 200 && count($r['json']) >= 2, "Lista las rutas");

$r = pedir("GET", $api . "/zonas", null, "admin");
verificar($r['codigo'] == 200 && count($r['json']) >= 7, "Lista las zonas");

$r = pedir("POST", $api . "/recorridos", ["id_cuadrilla" => 1, "id_ruta" => 2, "id_camion" => 1, "fecha_asignacion" => "2026-11-10", "zonas" => [999]], "admin");
verificar($r['codigo'] == 400, "Con una zona que no existe devuelve 400");

$r = pedir("POST", $api . "/recorridos", ["id_cuadrilla" => 1, "id_ruta" => 2, "id_camion" => $idCamion, "fecha_asignacion" => "2026-11-10", "hora_inicio" => "14:00", "hora_fin" => "18:00"], "admin");
verificar($r['codigo'] == 201 && $r['json']['recorrido']['ruta'] == "Ruta Costa", "Asigna la Ruta Costa a la Cuadrilla Centro");
$idRecorrido = $r['json']['recorrido']['id'];

$r = pedir("POST", $api . "/recorridos", ["id_cuadrilla" => 1, "id_ruta" => 2, "id_camion" => $idCamion, "fecha_asignacion" => "2026-11-10"], "admin");
verificar($r['codigo'] == 409, "No deja asignar dos veces la misma ruta a la misma cuadrilla (409)");

$r = pedir("POST", $api . "/recorridos", ["id_cuadrilla" => 2, "id_ruta" => 1, "id_camion" => 2, "fecha_asignacion" => "2026-11-10"], "admin");
verificar($r['codigo'] == 400 && strpos($r['json']['error'], "RNE06") !== false, "RNE06: no deja usar un camión que no está disponible");

$r = pedir("PUT", $api . "/recorridos?id=" . $idRecorrido, ["id_cuadrilla" => 1, "id_ruta" => 2, "id_camion" => $idCamion, "fecha_asignacion" => "2026-11-10", "hora_inicio" => "18:00", "hora_fin" => "14:00"], "admin");
verificar($r['codigo'] == 400, "No deja que la hora de fin sea antes que la de inicio");

$r = pedir("PUT", $api . "/recorridos?id=" . $idRecorrido, ["id_cuadrilla" => 1, "id_ruta" => 2, "id_camion" => $idCamion, "fecha_asignacion" => "2026-11-12", "hora_inicio" => "", "hora_fin" => ""], "admin");
verificar($r['codigo'] == 200 && $r['json']['recorrido']['fecha_asignacion'] == "2026-11-12", "Modifica el recorrido");

$r = pedir("GET", $api . "/recorridos?id=1", null, "admin");
$uno = $r['json'];
$r = pedir("PUT", $api . "/recorridos?id=1", ["id_cuadrilla" => $uno['id_cuadrilla'], "id_ruta" => $uno['id_ruta'], "id_camion" => $uno['id_camion'], "fecha_asignacion" => $uno['fecha_asignacion'], "hora_inicio" => $uno['hora_inicio'], "hora_fin" => $uno['hora_fin']], "admin");
verificar($r['codigo'] == 200, "Se puede modificar un recorrido que mantiene su mismo camión aunque esté en ruta");

$r = pedir("DELETE", $api . "/recorridos?id=" . $idRecorrido, null, "admin");
verificar($r['codigo'] == 200, "Borra el recorrido de prueba");

$r = pedir("GET", $api . "/recorridos?id=" . $idRecorrido, null, "admin");
verificar($r['codigo'] == 404, "El recorrido borrado ya no existe (404)");

titulo("Borrar el camión de prueba");

$r = pedir("DELETE", $api . "/camiones?id=" . $idCamion, null, "admin");
verificar($r['codigo'] == 200, "Borra el camión de prueba");

$r = pedir("GET", $apiUsuarios . "/mensajes", null, "recolector");
$llegaron = 0;
foreach ($r['json']['mensajes'] as $m) {
    if (str_contains($m['texto'], "recorrido") || str_contains($m['texto'], "asignaron la Ruta")) $llegaron++;
}
verificar($llegaron > 0, "A la recolectora le llegaron notificaciones de los recorridos de su cuadrilla");
mysqli_query($base, "DELETE FROM mensajes WHERE id_mensaje > " . $ultimoMensaje);

$r = pedir("GET", $api . "/no-existe", null, "admin");
verificar($r['codigo'] == 404, "Un endpoint que no existe devuelve 404");

mostrarResumen();
