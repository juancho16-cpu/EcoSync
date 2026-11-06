<?php
// Tests unitarios de los modelos de la API de Recoleccion.
// Usan la base de datos de prueba "sigeru_test", asi no se tocan los datos reales.
// Se corren con: php tests/test_modelos.php

require_once __DIR__ . "/funciones_test.php";
require_once __DIR__ . "/../modelos/Camion.php";
require_once __DIR__ . "/../modelos/Mantenimiento.php";
require_once __DIR__ . "/../modelos/Recorrido.php";
require_once __DIR__ . "/../modelos/Mensaje.php";

$conn = crearBaseDePrueba();

// ---------------------------------------------------------------
titulo("Camiones");

$camion = new Camion($conn);
verificar(count($camion->obtenerTodos()) == 4, "Hay 4 camiones de prueba");
verificar($camion->buscarPorMatricula("SBA 1234")['id'] == 1, "Encuentra un camión por su matrícula");
verificar($camion->buscarPorMatricula("XXX 0000") == null, "Una matrícula que no existe devuelve null");

$nuevo = $camion->agregar(["matricula" => "sbe 3456", "estado" => "Operativo", "disponibilidad" => "Disponible", "capacidad_carga" => "9000"]);
$idCamion = $nuevo['id'];
verificar($nuevo['matricula'] == "SBE 3456", "La matrícula se guarda en mayúsculas");

$modificado = $camion->modificar($idCamion, ["matricula" => "SBE 3456", "estado" => "En mantenimiento", "disponibilidad" => "No disponible", "capacidad_carga" => ""]);
verificar($modificado['estado'] == "En mantenimiento" && $modificado['capacidad_carga'] == null, "Modifica el camión");

// ---------------------------------------------------------------
titulo("Mantenimientos (entidad débil de Camión)");

$mantenimiento = new Mantenimiento($conn);
verificar(count($mantenimiento->obtenerDelCamion(2)) == 1, "El camión SBC 5678 tiene 1 mantenimiento de prueba");

$primero = $mantenimiento->agregar(["id_camion" => $idCamion, "tipo" => "Preventivo", "costo" => "12000", "descripcion" => "Cambio de aceite", "fecha" => "2026-11-01"]);
verificar($primero['nro'] == 1, "El primer mantenimiento del camión nuevo es el número 1");

$segundo = $mantenimiento->agregar(["id_camion" => $idCamion, "tipo" => "Correctivo", "costo" => "", "fecha" => "2026-11-05"]);
verificar($segundo['nro'] == 2 && $segundo['costo'] == null, "El segundo es el número 2 (y sin costo queda NULL)");

$lista = $mantenimiento->obtenerDelCamion($idCamion);
verificar(count($lista) == 2 && $lista[0]['nro'] == 2, "Lista los mantenimientos del más nuevo al más viejo");

$modificado = $mantenimiento->modificar($idCamion, 1, ["tipo" => "Revisión", "costo" => "5000", "descripcion" => "Revisión general", "fecha" => "2026-11-02"]);
verificar($modificado['tipo'] == "Revisión" && $modificado['fecha'] == "2026-11-02", "Modifica un mantenimiento");

$mantenimiento->eliminar($idCamion, 2);
verificar($mantenimiento->obtenerUno($idCamion, 2) == null, "Borra un mantenimiento");

$camion->eliminar($idCamion);
verificar($camion->obtenerPorId($idCamion) == null, "Borra el camión");
verificar(count($mantenimiento->obtenerDelCamion($idCamion)) == 0, "Sus mantenimientos se borran solos (ON DELETE CASCADE)");

// ---------------------------------------------------------------
titulo("Recorridos (asignar rutas a cuadrillas)");

$recorrido = new Recorrido($conn);
verificar(count($recorrido->obtenerTodos()) == 2, "Hay 2 recorridos");
verificar(count($recorrido->obtenerCuadrillas()) == 2 && count($recorrido->obtenerRutas()) == 2, "Hay 2 cuadrillas y 2 rutas");
verificar($recorrido->existeCuadrilla(1) && !$recorrido->existeCuadrilla(99), "existeCuadrilla funciona");
verificar($recorrido->existeRuta(2) && !$recorrido->existeRuta(99), "existeRuta funciona");

$deLaRecolectora = $recorrido->obtenerDelRecolector(3);
verificar(count($deLaRecolectora) == 1 && $deLaRecolectora[0]['ruta'] == "Ruta Centro", "La recolectora ve solo el recorrido de su cuadrilla");
verificar(count($deLaRecolectora[0]['zonas']) == 3, "Su recorrido pasa por 3 zonas");
verificar(count($deLaRecolectora[0]['contenedores']) == 4, "Y tiene 4 contenedores");
verificar(count($recorrido->obtenerDelRecolector(1)) == 0, "Un usuario que no es recolector no tiene recorridos");

verificar($recorrido->buscarPorCuadrillaYRuta(1, 1) != null, "La Cuadrilla Centro ya tiene la Ruta Centro");
verificar($recorrido->buscarPorCuadrillaYRuta(1, 2) == null, "La Cuadrilla Centro no tiene la Ruta Costa");

$nuevo = $recorrido->agregar(["id_cuadrilla" => 1, "id_ruta" => 2, "id_camion" => 1, "fecha_asignacion" => "2026-11-10", "hora_inicio" => "14:00", "hora_fin" => ""]);
$id = $nuevo['id'];
verificar($nuevo['cuadrilla'] == "Cuadrilla Centro" && $nuevo['ruta'] == "Ruta Costa" && $nuevo['camion'] == "SBA 1234", "Asigna la Ruta Costa a la Cuadrilla Centro con el camión SBA 1234");
verificar($nuevo['hora_fin'] == null, "Una hora vacía se guarda como NULL");
verificar(count($nuevo['zonas']) == 0 && count($nuevo['contenedores']) == 0, "Si no se eligen zonas, el recorrido empieza sin zonas ni contenedores");

// Le damos las zonas Malvín (6) y Carrasco (7), que eran del recorrido 2
$conZonas = $recorrido->modificar($id, ["id_cuadrilla" => 1, "id_ruta" => 2, "id_camion" => 1, "fecha_asignacion" => "2026-11-10", "zonas" => [6, 7]]);
verificar(count($conZonas['zonas']) == 2 && count($conZonas['contenedores']) == 2, "Al elegir las zonas Malvín y Carrasco trae sus 2 contenedores");
verificar(count($recorrido->obtenerPorId(2)['zonas']) == 2, "Esas zonas salen del recorrido 2 (cada zona la recorre un solo recorrido)");
verificar(count($recorrido->obtenerZonas()) == 7 && $recorrido->existeZona(6) && !$recorrido->existeZona(99), "Lista las 7 zonas y existeZona funciona");

$modificado = $recorrido->modificar($id, ["id_cuadrilla" => 1, "id_ruta" => 2, "id_camion" => 1, "fecha_asignacion" => "2026-11-11", "hora_inicio" => "14:00", "hora_fin" => "18:00"]);
verificar($modificado['fecha_asignacion'] == "2026-11-11" && $modificado['hora_fin'] == "18:00:00", "Modifica la fecha y el horario");

$recorrido->eliminar($id);
verificar($recorrido->obtenerPorId($id) == null, "Borra el recorrido");
verificar(contarFilas($conn, "SELECT * FROM zonas WHERE id_recorrido IS NULL") == 2, "Sus zonas quedan sin recorrido (ON DELETE SET NULL)");

titulo("Mensajes");

$mensaje = new Mensaje($conn);
$mensaje->enviarACuadrilla(1, "Prueba a la cuadrilla", "recorridos.html");
verificar(contarFilas($conn, "SELECT * FROM mensajes WHERE texto = 'Prueba a la cuadrilla' AND id_usuario = 3") == 1, "Mandar a una cuadrilla le llega a su recolectora");
verificar(contarFilas($conn, "SELECT * FROM mensajes WHERE texto = 'Prueba a la cuadrilla'") == 1, "Y a nadie más");
$mensaje->enviarARol("Administrador", "Prueba al admin", "panel.html");
verificar(contarFilas($conn, "SELECT * FROM mensajes WHERE texto = 'Prueba al admin'") == 1, "Mandar a un rol le llega al administrador");

mostrarResumen();
