<?php

require_once "config.php";
require_once "conexion.php";
require_once "vistas/respuesta.php";
require_once "seguridad.php";
require_once "correo.php";
require_once "modelos/Usuario.php";
require_once "modelos/Parametro.php";
require_once "modelos/Mensaje.php";
require_once "modelos/Auditoria.php";
require_once "controladores/usuarios.php";
require_once "controladores/mensajes.php";

$metodo = $_SERVER['REQUEST_METHOD'];

$endpoint = trim($_SERVER['PATH_INFO'] ?? "", "/");

$id = $_GET['id'] ?? null;

if ($id !== null && !ctype_digit((string) $id)) {
    responder(["error" => "El id no es válido"], 400);
}

$datos = json_decode(file_get_contents("php://input"), true) ?? [];

$usuario = new Usuario($conn);
$parametro = new Parametro($conn);
$mensaje = new Mensaje($conn);
$auditoria = new Auditoria($conn);

$todos = ["Administrador", "Operario", "Recolector", "Vecino"];

switch ($endpoint) {
    case "registro":
        if ($metodo == "POST") registrarUsuario($usuario, $datos);
        break;

    case "verificar":
        if ($metodo == "GET") verificarCorreo($usuario, $parametro, $mensaje, $_GET['codigo'] ?? "");
        break;

    case "aprobar":
        verificarRol(["Administrador"]);
        if ($metodo == "POST") aprobarRegistro($usuario, $mensaje, $id, $datos);
        break;

    case "mensajes":
        verificarRol($todos);
        if ($metodo == "GET") listarMensajes($mensaje);
        if ($metodo == "PUT") leerMensajes($mensaje, $id);
        break;

    case "parametros":
        if ($metodo == "GET") {
            verificarRol($todos);
            listarParametros($parametro);
        }
        verificarRol(["Administrador"]);
        if ($metodo == "PUT") modificarParametros($parametro, $datos);
        break;

    case "login":
        if ($metodo == "POST") iniciarSesion($usuario, $parametro, $datos);
        break;

    case "logout":
        if ($metodo == "POST") cerrarSesion();
        break;

    case "sesion":
        if ($metodo == "GET") sesionActual();
        break;

    case "auditoria":
        verificarRol(["Administrador"]);
        if ($metodo == "GET") listarAuditoria($auditoria);
        break;

    case "usuarios":
        
        verificarRol(["Administrador"]);

        if ($metodo == "GET") {
            if ($id != null) {
                obtenerUsuario($usuario, $id);
            } else {
                listarUsuarios($usuario);
            }
        }
        if ($metodo == "POST") crearUsuario($usuario, $datos);
        if ($metodo == "PUT") modificarUsuario($usuario, $id, $datos);
        if ($metodo == "DELETE") eliminarUsuario($usuario, $id);
        break;
}

responder(["error" => "Endpoint no encontrado"], 404);
