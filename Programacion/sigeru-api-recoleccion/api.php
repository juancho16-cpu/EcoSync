<?php
// API RESTful de Recoleccion - SiGeRU
// Se encarga de la flota de camiones (estado y mantenimiento) y de asignar rutas a las cuadrillas.
//
// Direcciones que tiene esta API (endpoints). Todas necesitan la sesion iniciada.
//   GET    api.php/camiones                   lista de camiones                 (administrador, operario y recolector)
//   POST / PUT / DELETE camiones                                                (administrador y operario)
//   GET    api.php/mantenimientos?camion=1    mantenimientos de un camion       (administrador, operario y recolector)
//   POST   api.php/mantenimientos             registrar un mantenimiento        (administrador y operario)
//   PUT    api.php/mantenimientos?camion=1&nro=2   modificar un mantenimiento   (administrador y operario)
//   DELETE api.php/mantenimientos?camion=1&nro=2   borrar un mantenimiento      (administrador y operario)
//   GET    api.php/recorridos                 recorridos de las cuadrillas      (administrador, operario y recolector)
//                                             (el recolector solo ve los de su cuadrilla)
//   POST / PUT / DELETE recorridos            asignar rutas a las cuadrillas    (administrador y operario)
//   GET    api.php/cuadrillas                 lista de cuadrillas               (administrador, operario y recolector)
//   GET    api.php/rutas                      lista de rutas                    (administrador, operario y recolector)
//   GET    api.php/zonas                      lista de zonas (barrios)          (administrador, operario y recolector)
// Con ?id=1 en un GET se pide uno solo.

require_once "config.php";
require_once "conexion.php";
require_once "vistas/respuesta.php";
require_once "seguridad.php";
require_once "modelos/Camion.php";
require_once "modelos/Mantenimiento.php";
require_once "modelos/Recorrido.php";
require_once "modelos/Mensaje.php";
require_once "controladores/camiones.php";
require_once "controladores/mantenimientos.php";
require_once "controladores/recorridos.php";

// Tipo de pedido: GET (pedir), POST (crear), PUT (modificar) o DELETE (borrar)
$metodo = $_SERVER['REQUEST_METHOD'];

// Que se pidio: lo que viene despues de api.php/ (por ejemplo camiones)
$endpoint = trim($_SERVER['PATH_INFO'] ?? "", "/");

// Datos que vienen en la direccion
$id = $_GET['id'] ?? null;

// El id tiene que ser un numero entero positivo (si no, alguien esta probando meter otra cosa)
if ($id !== null && !ctype_digit((string) $id)) {
    responder(["error" => "El id no es válido"], 400);
}
$idCamion = $_GET['camion'] ?? null;
$nro = $_GET['nro'] ?? null;

// Datos que mando el formulario (llegan en formato JSON)
$datos = json_decode(file_get_contents("php://input"), true) ?? [];

// Quien puede hacer cada cosa
$personal = ["Administrador", "Operario", "Recolector"];
$gestores = ["Administrador", "Operario"];

// Modelos que usan la base de datos
$camion = new Camion($conn);
$mensaje = new Mensaje($conn);
$mantenimiento = new Mantenimiento($conn);
$recorrido = new Recorrido($conn);

switch ($endpoint) {
    case "camiones":
        if ($metodo == "GET") {
            verificarRol($personal);
            if ($id != null) {
                obtenerCamion($camion, $id);
            } else {
                listarCamiones($camion);
            }
        }
        verificarRol($gestores);
        if ($metodo == "POST") registrarCamion($camion, $datos);
        if ($metodo == "PUT") modificarCamion($camion, $id, $datos);
        if ($metodo == "DELETE") eliminarCamion($camion, $id);
        break;

    case "mantenimientos":
        if ($metodo == "GET") {
            verificarRol($personal);
            listarMantenimientos($mantenimiento, $camion, $idCamion);
        }
        verificarRol($gestores);
        if ($metodo == "POST") registrarMantenimiento($mantenimiento, $camion, $datos);
        if ($metodo == "PUT") modificarMantenimiento($mantenimiento, $idCamion, $nro, $datos);
        if ($metodo == "DELETE") eliminarMantenimiento($mantenimiento, $idCamion, $nro);
        break;

    case "recorridos":
        if ($metodo == "GET") {
            verificarRol($personal);
            if ($id != null) {
                obtenerRecorrido($recorrido, $id);
            } else {
                listarRecorridos($recorrido);
            }
        }
        verificarRol($gestores);
        if ($metodo == "POST") registrarRecorrido($recorrido, $camion, $mensaje, $datos);
        if ($metodo == "PUT") modificarRecorrido($recorrido, $camion, $mensaje, $id, $datos);
        if ($metodo == "DELETE") eliminarRecorrido($recorrido, $id);
        break;

    case "cuadrillas":
        verificarRol($personal);
        if ($metodo == "GET") listarCuadrillas($recorrido);
        break;

    case "rutas":
        verificarRol($personal);
        if ($metodo == "GET") listarRutas($recorrido);
        break;

    case "zonas":
        verificarRol($personal);
        if ($metodo == "GET") listarZonas($recorrido);
        break;
}

// Si llega hasta aca es porque se pidio algo que la API no tiene
responder(["error" => "Endpoint no encontrado"], 404);
