<?php
// API RESTful de Usuarios - SiGeRU
//
// Direcciones que tiene esta API (endpoints):
//   POST   api.php/registro        registro de un vecino (desde la landing). Le llega un correo para verificar
//   GET    api.php/verificar?codigo=...   el vecino abre el enlace del correo
//   POST   api.php/aprobar?id=5    aprobar o rechazar el registro de un vecino ({"aprobado": true}) (solo administrador)
//   POST   api.php/login           inicio de sesion
//   POST   api.php/logout          cierre de sesion
//   GET    api.php/sesion          usuario que inicio sesion
//   GET    api.php/usuarios        lista de usuarios          (solo administrador)
//   GET    api.php/usuarios?id=1   un usuario                 (solo administrador)
//   POST   api.php/usuarios        crear un usuario           (solo administrador)
//   PUT    api.php/usuarios?id=1   modificar un usuario       (solo administrador)
//   DELETE api.php/usuarios?id=1   borrar un usuario          (solo administrador)
//   GET    api.php/mensajes        notificaciones del usuario  (todos los roles)
//   PUT    api.php/mensajes?id=3   marcar una como leida (sin id: todas)
//   GET    api.php/parametros      parametros del sistema      (todos los roles)
//   PUT    api.php/parametros      cambiar los parametros      (solo administrador)
//   GET    api.php/auditoria       registro de lo que pasa en el sistema (solo administrador)

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

// Tipo de pedido: GET (pedir), POST (crear), PUT (modificar) o DELETE (borrar)
$metodo = $_SERVER['REQUEST_METHOD'];

// Que se pidio: lo que viene despues de api.php/ (por ejemplo login)
$endpoint = trim($_SERVER['PATH_INFO'] ?? "", "/");

// Id que viene en la direccion (para ver, modificar o borrar uno)
$id = $_GET['id'] ?? null;

// El id tiene que ser un numero entero positivo (si no, alguien esta probando meter otra cosa)
if ($id !== null && !ctype_digit((string) $id)) {
    responder(["error" => "El id no es válido"], 400);
}

// Datos que mando el formulario (llegan en formato JSON)
$datos = json_decode(file_get_contents("php://input"), true) ?? [];

// Modelos que usan la base de datos
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
        // RNE04: solo un administrador puede ver, crear, modificar o borrar usuarios
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

// Si llega hasta aca es porque se pidio algo que la API no tiene
responder(["error" => "Endpoint no encontrado"], 404);
