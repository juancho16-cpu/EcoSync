<?php

function validarUsuario($usuario, $datos, $id) {
    $rolesValidos = ["Administrador", "Operario", "Recolector", "Vecino"];

    if (empty($datos['primer_nombre']) || empty($datos['primer_apellido']) || empty($datos['email'])) {
        responder(["error" => "Completá todos los campos obligatorios"], 400);
    }

    $pattern = "/^[a-zA-Z0-9._-]+@[a-zA-Z0-9-]+\.[a-zA-Z.]{2,5}$/";
    if (!preg_match($pattern, $datos['email'])) {
        responder(["error" => "El formato del correo no es válido"], 400);
    }

    $contrasena = $datos['contrasena'] ?? "";
    if ($id == null && $contrasena == "") {
        responder(["error" => "La contraseña es obligatoria"], 400);
    }
    
    if ($contrasena != "") {
        $error = revisarContrasena($contrasena);
        if ($error != "") {
            responder(["error" => $error], 400);
        }
    }

    if (!in_array($datos['rol'] ?? "", $rolesValidos)) {
        responder(["error" => "El rol no es válido"], 400);
    }

    $otro = $usuario->buscarPorEmail($datos['email']);
    if ($otro != null && $otro['id_usuario'] != $id) {
        responder(["error" => "Ese correo ya está registrado"], 409);
    }
}

function revisarContrasena($contrasena) {
    if (strlen($contrasena) < 8) {
        return "La contraseña tiene que tener al menos 8 caracteres";
    }
    if (strlen($contrasena) > 72) {
        return "La contraseña es demasiado larga (máximo 72 caracteres)";
    }
    if (!preg_match("/[a-zA-Z]/", $contrasena) || !preg_match("/[0-9]/", $contrasena)) {
        return "La contraseña tiene que tener letras y números";
    }
    return "";
}

function auditar($accion, $detalle, $email = null) {
    global $auditoria;
    $idUsuario = null;
    if (isset($_SESSION['usuario_logueado'])) {
        $idUsuario = $_SESSION['usuario_logueado']['id'];
    }
    $auditoria->registrar($idUsuario, $email, $accion, $detalle);
}

function listarUsuarios($usuario) {
    responder($usuario->obtenerTodos());
}

function obtenerUsuario($usuario, $id) {
    $encontrado = $usuario->obtenerPorId($id);

    if ($encontrado == null) {
        responder(["error" => "Usuario no encontrado"], 404);
    }
    responder($encontrado);
}

function registrarUsuario($usuario, $datos) {
    global $direccionSitio;

    $datos['rol'] = "Vecino";
    validarUsuario($usuario, $datos, null);

    if (!correoConfigurado()) {
        responder(["error" => "El registro no está disponible: falta configurar el correo de SiGeRU (archivo sigeru_correo.php). Avisale al administrador."], 503);
    }

    $codigo = bin2hex(random_bytes(16));
    $nuevo = $usuario->agregar($datos, "Sin verificar", $codigo);

    $enlace = $direccionSitio . "/sigeru-landing/verificar.html?codigo=" . $codigo;
    $texto = "<p>Hola " . htmlspecialchars($nuevo['primer_nombre']) . ", gracias por registrarte en SiGeRU.</p>
              <p>Para terminar el registro, confirmá que este correo es tuyo:</p>"
              . botonCorreo("Verificar mi correo", $enlace) .
              "<p style='color: #6b7280; font-size: 13px'>Si no te registraste, ignora este mensaje.</p>";
    $seMando = enviarCorreo($nuevo['email'], $nuevo['primer_nombre'], "Verificá tu correo", $texto);

    if (!$seMando) {
        $usuario->eliminar($nuevo['id']);
        responder(["error" => "No pudimos mandarte el correo de verificación: Gmail no aceptó el envío. Intentalo más tarde (si sigue pasando, avisale al administrador)."], 500);
    }

    auditar("Registro", "Se registró " . $nuevo['primer_nombre'] . " " . $nuevo['primer_apellido'] . " (falta verificar el correo)", $nuevo['email']);
    responder([
        "mensaje" => "¡Ya casi! Te mandamos un correo a " . $nuevo['email'] . ". Abrí el enlace del correo para verificar tu cuenta (si no lo ves, revisá la carpeta de spam).",
        "usuario" => $nuevo
    ], 201);
}

function verificarCorreo($usuario, $parametro, $mensaje, $codigo) {
    global $direccionSitio;

    $encontrado = null;
    if ($codigo != "") {
        $encontrado = $usuario->buscarPorCodigo($codigo);
    }
    if ($encontrado == null) {
        responder(["error" => "El enlace no es válido o ya se usó"], 404);
    }

    $nombre = $encontrado['primer_nombre'] . " " . $encontrado['primer_apellido'];

    if ($parametro->obtener("aprobar_registros", "si") == "si") {
        $usuario->cambiarEstado($encontrado['id_usuario'], "Pendiente");
        $mensaje->enviarARol("Administrador", $nombre . " se registró y espera que apruebes su cuenta", "usuarios.html");

        $texto = "<p>Hola " . htmlspecialchars($encontrado['primer_nombre']) . ", ¡te registraste correctamente!</p>
                  <p>Tu correo ya está verificado. Ahora un administrador tiene que aprobar tu cuenta: te vamos a avisar por correo cuando esté lista.</p>";
        enviarCorreo($encontrado['email'], $encontrado['primer_nombre'], "Te registraste correctamente", $texto);

        responder(["mensaje" => "¡Te registraste correctamente! Tu correo ya está verificado. Cuando un administrador apruebe tu cuenta te avisamos por correo.", "estado" => "Pendiente"]);
    }

    $usuario->cambiarEstado($encontrado['id_usuario'], "Activo");
    $mensaje->enviarAUsuario($encontrado['id_usuario'], "¡Bienvenido a SiGeRU! Desde Incidencias o desde el mapa podés avisar si un contenedor está lleno, roto o hay basura en la calle", "mapa.html");

    $texto = "<p>Hola " . htmlspecialchars($encontrado['primer_nombre']) . ", ¡te registraste correctamente!</p>
              <p>Ya podés iniciar sesión y avisar desde el mapa si un contenedor está lleno o roto.</p>"
              . botonCorreo("Iniciar sesión", $direccionSitio . "/sigeru-frontend/index.html");
    enviarCorreo($encontrado['email'], $encontrado['primer_nombre'], "Te registraste correctamente", $texto);

    responder(["mensaje" => "¡Te registraste correctamente! Ya podés iniciar sesión.", "estado" => "Activo"]);
}

function aprobarRegistro($usuario, $mensaje, $id, $datos) {
    global $direccionSitio;

    $encontrado = $usuario->obtenerPorId($id);
    if ($encontrado == null) {
        responder(["error" => "Usuario no encontrado"], 404);
    }
    if ($encontrado['estado'] != "Pendiente") {
        responder(["error" => "Ese usuario no está esperando aprobación"], 409);
    }

    if (($datos['aprobado'] ?? false) == true) {
        $usuario->cambiarEstado($id, "Activo");
        $mensaje->enviarAUsuario($id, "¡Bienvenido a SiGeRU! Desde Incidencias o desde el mapa podés avisar si un contenedor está lleno, roto o hay basura en la calle", "mapa.html");

        $texto = "<p>Hola " . htmlspecialchars($encontrado['primer_nombre']) . ", ¡tu cuenta fue aprobada!</p>
                  <p>Ya podés iniciar sesión en SiGeRU.</p>"
                  . botonCorreo("Iniciar sesión", $direccionSitio . "/sigeru-frontend/index.html");
        enviarCorreo($encontrado['email'], $encontrado['primer_nombre'], "Tu cuenta fue aprobada", $texto);

        auditar("Registro aprobado", "Se aprobó la cuenta de " . $encontrado['email'], $encontrado['email']);
        responder(["mensaje" => "Cuenta de " . $encontrado['primer_nombre'] . " aprobada. Le avisamos por correo."]);
    }

    $usuario->cambiarEstado($id, "Rechazado");
    $texto = "<p>Hola " . htmlspecialchars($encontrado['primer_nombre']) . ", tu registro en SiGeRU no fue aprobado.</p>
              <p>Si crees que es un error, comunícate con la Intendencia.</p>";
    enviarCorreo($encontrado['email'], $encontrado['primer_nombre'], "Tu registro no fue aprobado", $texto);

    auditar("Registro rechazado", "Se rechazó la cuenta de " . $encontrado['email'], $encontrado['email']);
    responder(["mensaje" => "Registro de " . $encontrado['primer_nombre'] . " rechazado."]);
}

function crearUsuario($usuario, $datos) {
    validarUsuario($usuario, $datos, null);

    $nuevo = $usuario->agregar($datos);
    auditar("Usuario creado", "Se creó el usuario " . $nuevo['email'] . " con el rol " . $nuevo['rol'], $nuevo['email']);
    responder(["mensaje" => "Usuario creado correctamente", "usuario" => $nuevo], 201);
}

function modificarUsuario($usuario, $id, $datos) {
    $anterior = $usuario->obtenerPorId($id);
    if ($anterior == null) {
        responder(["error" => "Usuario no encontrado"], 404);
    }
    validarUsuario($usuario, $datos, $id);

    if ($id == $_SESSION['usuario_logueado']['id'] && $datos['rol'] != $anterior['rol']) {
        responder(["error" => "No podés cambiarte tu propio rol"], 400);
    }

    $modificado = $usuario->modificar($id, $datos);
    if ($datos['rol'] != $anterior['rol']) {
        auditar("Cambio de rol", $anterior['email'] . " pasó de " . $anterior['rol'] . " a " . $datos['rol'], $anterior['email']);
    } else {
        auditar("Usuario modificado", "Se modificaron los datos de " . $anterior['email'], $anterior['email']);
    }
    if (($datos['contrasena'] ?? "") != "") {
        auditar("Cambio de contraseña", "Se cambió la contraseña de " . $anterior['email'], $anterior['email']);
    }
    responder(["mensaje" => "Usuario modificado correctamente", "usuario" => $modificado]);
}

function eliminarUsuario($usuario, $id) {
    if ($usuario->obtenerPorId($id) == null) {
        responder(["error" => "Usuario no encontrado"], 404);
    }
    
    if ($id == $_SESSION['usuario_logueado']['id']) {
        responder(["error" => "No podés borrar tu propio usuario"], 400);
    }

    $borrado = $usuario->obtenerPorId($id);
    $usuario->eliminar($id);
    auditar("Usuario eliminado", "Se borró el usuario " . $borrado['email'], $borrado['email']);
    responder(["mensaje" => "Usuario eliminado correctamente"]);
}

function iniciarSesion($usuario, $parametro, $datos) {
    global $auditoria;
    $email = trim($datos['email'] ?? "");
    $contrasena = $datos['contrasena'] ?? "";

    if (empty($email) || empty($contrasena)) {
        responder(["error" => "Completá el correo y la contraseña"], 400);
    }
    if (strlen($email) > 100) {
        responder(["error" => "Correo o contraseña incorrectos"], 401);
    }

    $maximo = (int) $parametro->obtener("intentos_login", 5);
    $minutosBloqueo = (int) $parametro->obtener("minutos_bloqueo", 15);
    if ($maximo > 0 && $auditoria->contarIntentosFallidos($email, $minutosBloqueo) >= $maximo) {
        auditar("Login bloqueado", "Intento de entrar con una cuenta bloqueada por muchos intentos fallidos", $email);
        responder(["error" => "Hubo demasiados intentos fallidos con este correo. Por seguridad esperá " . $minutosBloqueo . " minutos y volvé a intentar."], 429);
    }

    $encontrado = $usuario->buscarPorEmail($email);

    if ($encontrado == null || !password_verify($contrasena, $encontrado['contrasena'])) {
        auditar("Login fallido", "Correo o contraseña incorrectos", $email);
        responder(["error" => "Correo o contraseña incorrectos"], 401);
    }

    if ($encontrado['estado'] == "Sin verificar") {
        responder(["error" => "Todavía no verificaste tu correo. Revisá tu bandeja de entrada (y la carpeta de spam)."], 403);
    }
    if ($encontrado['estado'] == "Pendiente") {
        responder(["error" => "Tu cuenta está esperando que un administrador la apruebe. Te avisamos por correo."], 403);
    }
    if ($encontrado['estado'] == "Rechazado") {
        responder(["error" => "Tu registro no fue aprobado."], 403);
    }

    session_regenerate_id(true);
    unset($_SESSION['sesion_vencida']);
    $_SESSION['ultimo_uso'] = time();

    $_SESSION['usuario_logueado'] = [
        "id" => $encontrado['id_usuario'],
        "nombre" => $encontrado['primer_nombre'] . " " . $encontrado['primer_apellido'],
        "rol" => $encontrado['rol']
    ];
    auditar("Inicio de sesión", "Entró como " . $encontrado['rol'], $email);
    responder(["mensaje" => "Bienvenido", "usuario" => $_SESSION['usuario_logueado']]);
}

function cerrarSesion() {
    if (isset($_SESSION['usuario_logueado'])) {
        auditar("Cierre de sesión", "Salió del sistema");
    }
    unset($_SESSION['usuario_logueado']);
    session_regenerate_id(true);
    responder(["mensaje" => "Sesión cerrada"]);
}

function sesionActual() {
    if (!isset($_SESSION['usuario_logueado'])) {
        responder(["error" => "No hay ninguna sesión iniciada"], 401);
    }
    responder(["usuario" => $_SESSION['usuario_logueado']]);
}

function listarAuditoria($auditoria) {
    $accion = $_GET['accion'] ?? "";
    responder($auditoria->obtenerUltimos(300, $accion));
}
