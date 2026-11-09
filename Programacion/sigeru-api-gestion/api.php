<?php

require_once "config.php";
require_once "conexion.php";
require_once "vistas/respuesta.php";
require_once "seguridad.php";
require_once "modelos/Contenedor.php";
require_once "modelos/Centro.php";
require_once "modelos/Maquinaria.php";
require_once "modelos/Incidencia.php";
require_once "modelos/Notificacion.php";
require_once "modelos/Estadistica.php";
require_once "modelos/Alerta.php";
require_once "modelos/Mensaje.php";
require_once "modelos/Repuesto.php";
require_once "modelos/Parametro.php";
require_once "controladores/contenedores.php";
require_once "controladores/centros.php";
require_once "controladores/maquinaria.php";
require_once "controladores/incidencias.php";
require_once "controladores/alertas.php";
require_once "controladores/repuestos.php";

$metodo = $_SERVER['REQUEST_METHOD'];

$endpoint = trim($_SERVER['PATH_INFO'] ?? "", "/");

$id = $_GET['id'] ?? null;

if ($id !== null && !ctype_digit((string) $id)) {
    responder(["error" => "El id no es válido"], 400);
}

$datos = json_decode(file_get_contents("php://input"), true) ?? [];

$todos = ["Administrador", "Operario", "Recolector", "Vecino"];
$personal = ["Administrador", "Operario", "Recolector"];
$gestores = ["Administrador", "Operario"];

$contenedor = new Contenedor($conn);
$centro = new Centro($conn);
$maquinaria = new Maquinaria($conn);
$incidencia = new Incidencia($conn);
$notificacion = new Notificacion($conn);
$estadistica = new Estadistica($conn);
$alerta = new Alerta($conn);
$mensaje = new Mensaje($conn);
$repuesto = new Repuesto($conn);
$parametro = new Parametro($conn);

switch ($endpoint) {
    case "contenedores":
        if ($metodo == "GET") {
            verificarRol($todos);
            if ($id != null) {
                obtenerContenedor($contenedor, $id);
            } else {
                listarContenedores($contenedor);
            }
        }
        verificarRol($gestores);
        if ($metodo == "POST") registrarContenedor($contenedor, $datos);
        if ($metodo == "PUT") modificarContenedor($contenedor, $id, $datos);
        if ($metodo == "DELETE") eliminarContenedor($contenedor, $id);
        break;

    case "tipos_residuo":
        verificarRol($todos);
        if ($metodo == "GET") listarTiposResiduo($contenedor);
        break;

    case "centros":
        if ($metodo == "GET") {
            verificarRol($personal);
            if ($id != null) {
                obtenerCentro($centro, $id);
            } else {
                listarCentros($centro);
            }
        }
        verificarRol($gestores);
        if ($metodo == "POST") registrarCentro($centro, $contenedor, $datos);
        if ($metodo == "PUT") modificarCentro($centro, $contenedor, $id, $datos);
        if ($metodo == "DELETE") eliminarCentro($centro, $id);
        break;

    case "maquinaria":
        if ($metodo == "GET") {
            verificarRol($personal);
            if ($id != null) {
                obtenerMaquina($maquinaria, $id);
            } else {
                listarMaquinaria($maquinaria);
            }
        }
        verificarRol($gestores);
        if ($metodo == "POST") registrarMaquina($maquinaria, $datos);
        if ($metodo == "PUT") modificarMaquina($maquinaria, $id, $datos);
        if ($metodo == "DELETE") eliminarMaquina($maquinaria, $id);
        break;

    case "operarios":
        verificarRol($gestores);
        if ($metodo == "GET") listarOperarios($centro);
        break;

    case "instalaciones":
        verificarRol($personal);
        if ($metodo == "GET") listarInstalaciones($maquinaria);
        break;

    case "vaciar":
        verificarRol($personal);
        if ($metodo == "POST") vaciarContenedor($contenedor, $parametro, $id);
        break;

    case "incidencias":
        verificarRol($todos);
        if ($metodo == "GET") {
            if ($id != null) {
                obtenerIncidencia($incidencia, $id);
            } else {
                listarIncidencias($incidencia);
            }
        }
        if ($metodo == "POST") reportarIncidencia($incidencia, $contenedor, $notificacion, $mensaje, $datos);
        verificarRol($personal);
        if ($metodo == "PUT") seguimientoIncidencia($incidencia, $contenedor, $notificacion, $mensaje, $id, $datos);
        break;

    case "repuestos":
        if ($metodo == "GET") {
            verificarRol($personal);
            if ($id != null) {
                obtenerRepuesto($repuesto, $id);
            } else {
                listarRepuestos($repuesto);
            }
        }
        verificarRol($gestores);
        if ($metodo == "POST") registrarRepuesto($repuesto, $contenedor, $maquinaria, $datos);
        if ($metodo == "PUT") modificarRepuesto($repuesto, $contenedor, $maquinaria, $id, $datos);
        if ($metodo == "DELETE") eliminarRepuesto($repuesto, $id);
        break;

    case "stock":
        verificarRol($personal);
        if ($metodo == "POST") cambiarStock($repuesto, $mensaje, $id, $datos);
        break;

    case "volumen":
        verificarRol($personal);
        if ($metodo == "GET") obtenerVolumen($estadistica);
        break;

    case "estadisticas":
        verificarRol($personal);
        if ($metodo == "GET") obtenerEstadisticas($estadistica);
        break;

    case "alertas":
        verificarRol($todos);
        if ($metodo == "GET") listarAlertas($alerta, $parametro);
        break;

}

responder(["error" => "Endpoint no encontrado"], 404);
