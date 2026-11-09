<?php

require_once __DIR__ . "/funciones_test.php";
require_once __DIR__ . "/../modelos/Usuario.php";
require_once __DIR__ . "/../modelos/Parametro.php";
require_once __DIR__ . "/../modelos/Mensaje.php";

$conn = crearBaseDePrueba();
$usuario = new Usuario($conn);

titulo("Listar y buscar usuarios");

$todos = $usuario->obtenerTodos();
verificar(count($todos) == 4, "Hay 4 usuarios de prueba");
verificar(!isset($todos[0]['contrasena']), "La lista no devuelve las contraseñas");

$admin = $usuario->buscarPorEmail("admin@sigeru.uy");
verificar($admin != null && $admin['rol'] == "Administrador", "Encuentra al administrador por su correo");
verificar(password_verify("admin123", $admin['contrasena']), "La contraseña está encriptada y se puede comprobar");
verificar($admin['contrasena'] != "admin123", "La contraseña no está guardada como texto");
verificar($usuario->buscarPorEmail("nadie@mail.com") == null, "Un correo que no existe devuelve null");

$vecina = $usuario->obtenerPorId(4);
verificar($vecina['calle'] == "Av. Brasil 2500" && $vecina['zona'] == "Pocitos", "Trae los datos de la tabla vecinos");
verificar($usuario->obtenerPorId(999) == null, "Un id que no existe devuelve null");

titulo("Agregar un usuario");

$nuevo = $usuario->agregar([
    "primer_nombre" => "Juan",
    "primer_apellido" => "Cuadro",
    "email" => "juan@mail.com",
    "telefono" => "",
    "contrasena" => "123456",
    "rol" => "Vecino",
    "calle" => "Rivera 2000",
    "zona" => "Pocitos"
]);
$id = $nuevo['id'];
verificar($id == 5, "El usuario nuevo recibe el id 5");
verificar($nuevo['rol'] == "Vecino" && $nuevo['calle'] == "Rivera 2000", "Devuelve los datos del usuario nuevo");
verificar($nuevo['telefono'] == null, "Un teléfono vacío se guarda como NULL");
verificar(contarFilas($conn, "SELECT * FROM vecinos WHERE id_usuario = $id") == 1, "También se guarda en la tabla vecinos");

titulo("Modificar un usuario");

$modificado = $usuario->modificar($id, [
    "primer_nombre" => "Juan",
    "primer_apellido" => "Cuadro",
    "email" => "juan@mail.com",
    "telefono" => "099555555",
    "rol" => "Operario",
    "especialidad" => "Choferes",
    "contrasena" => ""
]);
verificar($modificado['rol'] == "Operario" && $modificado['telefono'] == "099555555", "Cambia el rol y el teléfono");
verificar(contarFilas($conn, "SELECT * FROM vecinos WHERE id_usuario = $id") == 0, "Al cambiar de rol se borra de la tabla vecinos");
verificar($modificado['especialidad'] == "Choferes", "Y se guarda en la tabla operarios con su especialidad");

titulo("Cifrado del teléfono (AES-256-GCM)");

$enLaBase = mysqli_fetch_assoc(mysqli_query($conn, "SELECT telefono FROM usuarios WHERE id_usuario = $id"))['telefono'];
verificar(str_starts_with($enLaBase, "enc:") && !str_contains($enLaBase, "099555555"), "En la base el teléfono queda cifrado (no se ve el número)");
verificar(descifrar($enLaBase) == "099555555", "Con la clave se descifra y da el número original");
verificar(cifrar("099555555") != cifrar("099555555"), "El mismo número cifrado dos veces da distinto (IV al azar)");
$cambiado = substr($enLaBase, 0, -4) . (substr($enLaBase, -4) == "AAAA" ? "BBBB" : "AAAA");
verificar(descifrar($cambiado) == "(no se pudo descifrar)", "Si alguien modifica el dato cifrado se detecta (etiqueta GCM)");
mysqli_query($conn, "UPDATE usuarios SET telefono = '094111222' WHERE id_usuario = 1");
$usuario->cifrarTelefonosViejos();
$admin1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT telefono FROM usuarios WHERE id_usuario = 1"))['telefono'];
verificar(str_starts_with($admin1, "enc:") && $usuario->obtenerPorId(1)['telefono'] == "094111222", "Los teléfonos viejos sin cifrar se cifran solos");
verificar(password_verify("123456", $usuario->buscarPorEmail("juan@mail.com")['contrasena']), "Si la contraseña viene vacía no se cambia");

$usuario->modificar($id, [
    "primer_nombre" => "Juan",
    "primer_apellido" => "Cuadro",
    "email" => "juan@mail.com",
    "rol" => "Operario",
    "especialidad" => "Mecánica",
    "contrasena" => "nueva123"
]);
verificar(password_verify("nueva123", $usuario->buscarPorEmail("juan@mail.com")['contrasena']), "Si viene una contraseña nueva se cambia");
verificar($usuario->obtenerPorId($id)['especialidad'] == "Mecánica", "Si sigue con el mismo rol se actualizan los datos de su tabla");

titulo("Borrar un usuario");

$usuario->eliminar($id);
verificar($usuario->obtenerPorId($id) == null, "El usuario ya no existe");
verificar(contarFilas($conn, "SELECT * FROM operarios WHERE id_usuario = $id") == 0, "Sus datos de operario se borran solos (ON DELETE CASCADE)");
verificar(count($usuario->obtenerTodos()) == 4, "Quedan los 4 usuarios de prueba");

titulo("Registro con verificación de correo");

$vecino = $usuario->agregar(["primer_nombre" => "Ana", "primer_apellido" => "Rodríguez", "email" => "ana@mail.com", "contrasena" => "123456", "rol" => "Vecino"], "Sin verificar", "codigo123");
verificar($vecino['estado'] == "Sin verificar", "El vecino que se registra queda Sin verificar");
verificar($usuario->buscarPorCodigo("codigo123")['id_usuario'] == $vecino['id'], "Lo encuentra por el código del enlace");
$codigoEnLaBase = mysqli_fetch_assoc(mysqli_query($conn, "SELECT codigo_verificacion FROM usuarios WHERE id_usuario = " . $vecino['id']))['codigo_verificacion'];
verificar($codigoEnLaBase == hash("sha256", "codigo123") && $codigoEnLaBase != "codigo123", "En la base se guarda el resumen SHA-256 del código, no el código");
verificar($usuario->buscarPorCodigo("otro") == null, "Con otro código no encuentra a nadie");
$usuario->cambiarEstado($vecino['id'], "Pendiente");
verificar($usuario->obtenerPorId($vecino['id'])['estado'] == "Pendiente", "Cambia el estado a Pendiente");
verificar($usuario->buscarPorCodigo("codigo123") == null, "El código ya no sirve una segunda vez");
$admin = $usuario->agregar(["primer_nombre" => "Otro", "primer_apellido" => "Admin", "email" => "otro@mail.com", "contrasena" => "123456", "rol" => "Administrador"]);
verificar($admin['estado'] == "Activo", "Un usuario que crea el administrador queda Activo");

titulo("Parámetros");

$parametro = new Parametro($conn);
verificar(count($parametro->obtenerTodos()) == 7, "Hay 7 parámetros");
verificar($parametro->obtener("aprobar_registros", "x") == "si", "Lee un parámetro");
verificar($parametro->obtener("no_existe", "por defecto") == "por defecto", "Si no existe devuelve el valor por defecto");
$parametro->modificar("idioma", "en");
verificar($parametro->obtener("idioma", "") == "en", "Modifica un parámetro");

titulo("Mensajes (notificaciones)");

$mensaje = new Mensaje($conn);
verificar($mensaje->contarSinLeer(1) == 0, "El administrador de prueba no tiene mensajes");
$mensaje->enviarARol("Administrador", "Mensaje de prueba", "panel.html");
verificar($mensaje->contarSinLeer(1) == 1 && $mensaje->contarSinLeer($admin['id']) == 1, "Mandar a un rol le llega a todos los administradores activos");
verificar($mensaje->contarSinLeer(2) == 0, "Al operario no le llega");
$mensaje->enviarAUsuario(1, "Otro mensaje", "incidencias.html");
$lista = $mensaje->obtenerDelUsuario(1);
verificar(count($lista) == 2 && $lista[0]['texto'] == "Otro mensaje", "El más nuevo aparece primero");
$mensaje->marcarLeido($lista[0]['id'], 2);
verificar($mensaje->contarSinLeer(1) == 2, "Un usuario no puede marcar como leído un mensaje de otro");
$mensaje->marcarLeido($lista[0]['id'], 1);
verificar($mensaje->contarSinLeer(1) == 1, "Marca un mensaje como leído");
$mensaje->marcarTodosLeidos(1);
verificar($mensaje->contarSinLeer(1) == 0, "Marca todos como leídos");

mostrarResumen();
