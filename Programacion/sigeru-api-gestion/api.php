<?php
// API RESTful de Gestion - SiGeRU
// Se encarga de los elementos fijos: contenedores, centros de acopio, maquinaria e incidencias,
// y de las estadisticas y alertas del panel.
// (los camiones y los recorridos estan en la API de Recoleccion)
//
// Direcciones que tiene esta API (endpoints). Todas necesitan la sesion iniciada.
//   GET    api.php/contenedores       lista de contenedores            (todos los roles)
//   POST   api.php/contenedores       registrar un contenedor          (administrador y operario)
//   PUT    api.php/contenedores?id=1  modificar un contenedor          (administrador y operario)
//   DELETE api.php/contenedores?id=1  borrar un contenedor             (administrador y operario)
//   GET    api.php/tipos_residuo      lista de tipos de residuo        (todos los roles)
//   GET    api.php/centros            lista de centros de acopio       (administrador, operario y recolector)
//   POST / PUT / DELETE centros                                        (administrador y operario)
//   GET    api.php/maquinaria         lista de maquinas                (administrador, operario y recolector)
//   POST / PUT / DELETE maquinaria                                     (administrador y operario)
//   GET    api.php/operarios          lista de operarios               (administrador y operario)
//   GET    api.php/instalaciones      instalaciones con su ubicacion   (administrador, operario y recolector)
//   POST   api.php/vaciar?id=1        marcar un contenedor como vaciado (administrador, operario y recolector)
//   GET    api.php/incidencias        lista de incidencias             (todos; el vecino ve solo las que reporto)
//                                     filtros: ?estado=Abierta ?contenedor=2 ?desde=2026-11-01&hasta=2026-11-30
//   POST   api.php/incidencias        reportar una incidencia          (todos los roles)
//   PUT    api.php/incidencias?id=1   seguimiento: estado, prioridad y cuadrilla (administrador y operario;
//                                     el recolector solo cambia el estado de las de su cuadrilla)
//   GET    api.php/repuestos          inventario de repuestos          (administrador, operario y recolector)
//   POST / PUT / DELETE repuestos                                      (administrador y operario)
//   POST   api.php/stock?id=1         usar o reponer unidades {"cambio": -1} (administrador, operario y recolector)
//   GET    api.php/volumen?desde=2026-11-01&hasta=2026-11-30   kilos recolectados (administrador, operario y recolector)
//   GET    api.php/estadisticas       numeros para el panel de control (administrador, operario y recolector)
//   GET    api.php/alertas            alertas de cada rol              (todos los roles)
// Con ?id=1 en un GET se pide uno solo.

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

// Tipo de pedido: GET (pedir), POST (crear), PUT (modificar) o DELETE (borrar)
$metodo = $_SERVER['REQUEST_METHOD'];

// Que se pidio: lo que viene despues de api.php/ (por ejemplo contenedores)
$endpoint = trim($_SERVER['PATH_INFO'] ?? "", "/");

// Id que viene en la direccion (para ver, modificar o borrar uno)
$id = $_GET['id'] ?? null;

// El id tiene que ser un numero entero positivo (si no, alguien esta probando meter otra cosa)
if ($id !== null && !ctype_digit((string) $id)) {
    responder(["error" => "El id no es válido"], 400);
}

// Datos que mando el formulario (llegan en formato JSON)
$datos = json_decode(file_get_contents("php://input"), true) ?? [];

// Quien puede hacer cada cosa
$todos = ["Administrador", "Operario", "Recolector", "Vecino"];
$personal = ["Administrador", "Operario", "Recolector"];
$gestores = ["Administrador", "Operario"];

// Modelos que usan la base de datos
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

// Si llega hasta aca es porque se pidio algo que la API no tiene
responder(["error" => "Endpoint no encontrado"], 404);
