<?php

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

$metodo = $_SERVER['REQUEST_METHOD'];

$endpoint = trim($_SERVER['PATH_INFO'] ?? "", "/");

$id = $_GET['id'] ?? null;

if ($id !== null && !ctype_digit((string) $id)) {
    responder(["error" => "El id no es válido"], 400);
}
$idCamion = $_GET['camion'] ?? null;
$nro = $_GET['nro'] ?? null;

$datos = json_decode(file_get_contents("php://input"), true) ?? [];

$personal = ["Administrador", "Operario", "Recolector"];
$gestores = ["Administrador", "Operario"];

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

responder(["error" => "Endpoint no encontrado"], 404);
