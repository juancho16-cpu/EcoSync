<?php

require_once __DIR__ . "/funciones_test.php";

$api = "http://localhost/sigeru-api-usuarios/api.php";
if (isset($argv[1])) {
    $api = $argv[1];
}
echo "Probando la API en $api\n";

$correoPrueba = "prueba" . time() . "@mail.com";

titulo("Inicio de sesion");

$r = entrar($api, "admin", "admin@sigeru.uy", "contraseña-mal");
verificar($r['codigo'] == 401, "Con la contraseña mal devuelve 401");

$r = pedir("GET", $api . "/sesion", null, "admin");
verificar($r['codigo'] == 401, "Sin sesión, /sesion devuelve 401");

$r = entrar($api, "admin", "admin@sigeru.uy", "admin123");
verificar($r['codigo'] == 200 && $r['json']['usuario']['rol'] == "Administrador", "El administrador inicia sesión");

$r = pedir("GET", $api . "/sesion", null, "admin");
verificar($r['codigo'] == 200 && $r['json']['usuario']['id'] == 1, "/sesion devuelve quién inició sesión");

$r = pedir("POST", $api . "/login", ["email" => ""], "otro");
verificar($r['codigo'] == 400, "Login sin datos devuelve 400");

titulo("Permisos (RNE04: solo el administrador maneja usuarios)");

$r = pedir("GET", $api . "/usuarios", null, "nadie");
verificar($r['codigo'] == 401, "Sin sesión no se pueden ver los usuarios (401)");

entrar($api, "vecino", "vecino@sigeru.uy", "123456");
$r = pedir("GET", $api . "/usuarios", null, "vecino");
verificar($r['codigo'] == 403, "Un vecino no puede ver los usuarios (403)");

entrar($api, "operario", "operario@sigeru.uy", "123456");
$r = pedir("POST", $api . "/usuarios", ["primer_nombre" => "X"], "operario");
verificar($r['codigo'] == 403, "Un operario no puede crear usuarios (403)");

titulo("Registro desde la landing");

$base = conectarBaseReal();
$correoRegistro = "registro" . $correoPrueba;

$r = pedir("POST", $api . "/registro", [
    "primer_nombre" => "Prueba",
    "primer_apellido" => "Registro",
    "email" => $correoRegistro,
    "contrasena" => "clave1234",
    "rol" => "Administrador"
], "landing");

if ($r['codigo'] == 503 || $r['codigo'] == 500) {
    echo "  (Gmail no está configurado: se prueba que no quede el registro y se crea el vecino directo en la base)\n";
    verificar(str_contains($r['json']['error'], "correo"), "Si no se puede mandar el correo avisa el error");
    $quedo = mysqli_fetch_assoc(mysqli_query($base, "SELECT COUNT(*) AS cantidad FROM usuarios WHERE email = '" . $correoRegistro . "'"))['cantidad'];
    verificar($quedo == 0, "Y no deja creado el usuario, para que se pueda registrar de nuevo");

    $clave = password_hash("clave1234", PASSWORD_DEFAULT);
    mysqli_query($base, "INSERT INTO usuarios (primer_nombre, primer_apellido, email, contrasena, rol, estado, codigo_verificacion)
                         VALUES ('Prueba', 'Registro', '" . $correoRegistro . "', '" . $clave . "', 'Vecino', 'Sin verificar', SHA2('codigo-del-test', 256))");
    $idRegistrado = mysqli_insert_id($base);
    mysqli_query($base, "INSERT INTO vecinos (id_usuario) VALUES (" . $idRegistrado . ")");
} else {
    verificar($r['codigo'] == 201, "Registro correcto devuelve 201");
    verificar($r['json']['usuario']['rol'] == "Vecino", "El que se registra solo queda como Vecino aunque mande otro rol");
    verificar($r['json']['usuario']['estado'] == "Sin verificar", "Queda Sin verificar hasta que abra el enlace del correo");
    $idRegistrado = $r['json']['usuario']['id'];
}

$enLaBase = mysqli_fetch_assoc(mysqli_query($base, "SELECT codigo_verificacion FROM usuarios WHERE id_usuario = " . $idRegistrado))['codigo_verificacion'];
verificar(strlen($enLaBase) == 64, "En la base el código del enlace está guardado como resumen SHA-256");

$codigo = "codigo-conocido-" . time();
mysqli_query($base, "UPDATE usuarios SET codigo_verificacion = '" . hash("sha256", $codigo) . "' WHERE id_usuario = " . $idRegistrado);

$r = entrar($api, "registrado", $correoRegistro, "clave1234");
verificar($r['codigo'] == 403 && str_contains($r['json']['error'], "verificaste"), "Sin verificar el correo no puede iniciar sesión (403)");

$r = pedir("GET", $api . "/verificar?codigo=codigo-falso", null, "landing");
verificar($r['codigo'] == 404, "Con un código falso devuelve 404");

$r = pedir("GET", $api . "/verificar?codigo=" . $codigo, null, "landing");
verificar($r['codigo'] == 200 && $r['json']['estado'] == "Pendiente", "Con el enlace del correo queda verificado y Pendiente de aprobación");

$r = pedir("GET", $api . "/verificar?codigo=" . $codigo, null, "landing");
verificar($r['codigo'] == 404, "El mismo enlace no sirve dos veces");

$r = entrar($api, "registrado", $correoRegistro, "clave1234");
verificar($r['codigo'] == 403 && str_contains($r['json']['error'], "apruebe"), "Mientras no lo aprueban no puede entrar (403)");

$r = pedir("GET", $api . "/mensajes", null, "admin");
verificar(str_contains($r['json']['mensajes'][0]['texto'], "Prueba Registro"), "Al administrador le llega la notificación de la solicitud");

$r = pedir("POST", $api . "/aprobar?id=" . $idRegistrado, ["aprobado" => true], "operario");
verificar($r['codigo'] == 403, "El operario no puede aprobar registros (403)");

$r = pedir("POST", $api . "/aprobar?id=" . $idRegistrado, ["aprobado" => true], "admin");
verificar($r['codigo'] == 200, "El administrador aprueba el registro");

$r = pedir("POST", $api . "/aprobar?id=" . $idRegistrado, ["aprobado" => true], "admin");
verificar($r['codigo'] == 409, "No se puede aprobar dos veces (409)");

$r = entrar($api, "registrado", $correoRegistro, "clave1234");
verificar($r['codigo'] == 200, "Ahora sí puede iniciar sesión");

$r = pedir("GET", $api . "/mensajes", null, "registrado");
verificar($r['codigo'] == 200 && $r['json']['sin_leer'] == 1 && str_contains($r['json']['mensajes'][0]['texto'], "Bienvenido"), "Y tiene la notificación de bienvenida");

$r = pedir("PUT", $api . "/mensajes", null, "registrado");
$r = pedir("GET", $api . "/mensajes", null, "registrado");
verificar($r['json']['sin_leer'] == 0, "Marca todas sus notificaciones como leídas");

$r = pedir("POST", $api . "/registro", ["primer_nombre" => "A", "primer_apellido" => "B", "email" => "admin@sigeru.uy", "contrasena" => "clave1234"], "landing");
verificar($r['codigo'] == 409, "Con un correo que ya existe devuelve 409");

$r = pedir("POST", $api . "/registro", ["primer_nombre" => "A", "primer_apellido" => "B", "email" => "correo-malo", "contrasena" => "clave1234"], "landing");
verificar($r['codigo'] == 400, "Con un correo mal escrito devuelve 400");

$r = pedir("POST", $api . "/registro", ["primer_nombre" => "A", "primer_apellido" => "B", "email" => "x" . $correoPrueba, "contrasena" => "123"], "landing");
verificar($r['codigo'] == 400, "Con una contraseña de menos de 8 caracteres devuelve 400");

$r = pedir("POST", $api . "/registro", ["primer_nombre" => "A", "primer_apellido" => "B", "email" => "x" . $correoPrueba, "contrasena" => "soloLetras"], "landing");
verificar($r['codigo'] == 400 && str_contains($r['json']['error'], "letras y números"), "Una contraseña sin números devuelve 400 (política de contraseñas)");

$r = pedir("POST", $api . "/registro", ["primer_nombre" => "A", "primer_apellido" => "B", "email" => "x" . $correoPrueba, "contrasena" => "12345678"], "landing");
verificar($r['codigo'] == 400, "Una contraseña sin letras devuelve 400");

titulo("Parámetros");

$r = pedir("GET", $api . "/parametros", null, "operario");
verificar($r['codigo'] == 200 && count($r['json']) == 7, "Cualquier usuario puede leer los parámetros");

$r = pedir("PUT", $api . "/parametros", ["idioma" => "en"], "operario");
verificar($r['codigo'] == 403, "Solo el administrador los cambia (403)");

$r = pedir("PUT", $api . "/parametros", ["idioma" => "fr"], "admin");
verificar($r['codigo'] == 400, "Un idioma que no existe devuelve 400");

$r = pedir("PUT", $api . "/parametros", ["dias_alerta_desborde" => "-3"], "admin");
verificar($r['codigo'] == 400, "Un número negativo devuelve 400");

$r = pedir("PUT", $api . "/parametros", ["inventado" => "1"], "admin");
verificar($r['codigo'] == 400, "Un parámetro que no existe devuelve 400");

$r = pedir("PUT", $api . "/parametros", ["dias_alerta_desborde" => "3", "idioma" => "en"], "admin");
verificar($r['codigo'] == 200, "El administrador cambia dos parámetros juntos");

pedir("PUT", $api . "/parametros", ["dias_alerta_desborde" => "2", "idioma" => "es"], "admin");

titulo("CRUD de usuarios (administrador)");

$r = pedir("POST", $api . "/usuarios", [
    "primer_nombre" => "Ana",
    "primer_apellido" => "Prueba",
    "email" => $correoPrueba,
    "contrasena" => "clave1234",
    "rol" => "Recolector"
], "admin");
verificar($r['codigo'] == 201 && $r['json']['usuario']['rol'] == "Recolector", "Crea un usuario con rol Recolector");
$idNuevo = $r['json']['usuario']['id'];

$r = pedir("GET", $api . "/usuarios?id=" . $idNuevo, null, "admin");
verificar($r['codigo'] == 200 && $r['json']['email'] == $correoPrueba, "Lo encuentra por su id");

$r = pedir("POST", $api . "/usuarios", ["primer_nombre" => "Ana", "primer_apellido" => "Prueba", "email" => "y" . $correoPrueba, "contrasena" => "clave1234", "rol" => "Jefe"], "admin");
verificar($r['codigo'] == 400, "Con un rol que no existe devuelve 400");

$r = pedir("PUT", $api . "/usuarios?id=" . $idNuevo, [
    "primer_nombre" => "Ana María",
    "primer_apellido" => "Prueba",
    "email" => $correoPrueba,
    "contrasena" => "",
    "rol" => "Operario",
    "especialidad" => "Choferes"
], "admin");
verificar($r['codigo'] == 200 && $r['json']['usuario']['rol'] == "Operario", "Modifica el usuario y le cambia el rol");

$r = pedir("PUT", $api . "/usuarios?id=" . $idNuevo, ["primer_nombre" => "Ana", "primer_apellido" => "Prueba", "email" => "admin@sigeru.uy", "rol" => "Operario"], "admin");
verificar($r['codigo'] == 409, "No deja poner el correo de otro usuario (409)");

$r = pedir("PUT", $api . "/usuarios?id=99999", ["primer_nombre" => "A", "primer_apellido" => "B", "email" => "z" . $correoPrueba, "rol" => "Vecino"], "admin");
verificar($r['codigo'] == 404, "Modificar un usuario que no existe devuelve 404");

$r = pedir("DELETE", $api . "/usuarios?id=1", null, "admin");
verificar($r['codigo'] == 400, "El administrador no se puede borrar a sí mismo");

$r = pedir("DELETE", $api . "/usuarios?id=" . $idNuevo, null, "admin");
verificar($r['codigo'] == 200, "Borra el usuario de prueba");

$r = pedir("GET", $api . "/usuarios?id=" . $idNuevo, null, "admin");
verificar($r['codigo'] == 404, "El usuario borrado ya no existe (404)");

titulo("Cambiar el rol");

$r = pedir("PUT", $api . "/usuarios?id=1", ["primer_nombre" => "Usuario", "primer_apellido" => "Administrador", "email" => "admin@sigeru.uy", "rol" => "Operario"], "admin");
verificar($r['codigo'] == 400, "El administrador no se puede cambiar su propio rol (400)");

$r = pedir("PUT", $api . "/usuarios?id=" . $idRegistrado, ["primer_nombre" => "Prueba", "primer_apellido" => "Registro", "email" => $correoRegistro, "rol" => "Operario", "especialidad" => "Choferes"], "admin");
verificar($r['codigo'] == 200 && $r['json']['usuario']['rol'] == "Operario", "El administrador le da el rol de Operario a un vecino");

$r = pedir("PUT", $api . "/usuarios?id=" . $idRegistrado, ["primer_nombre" => "Prueba", "primer_apellido" => "Registro", "email" => $correoRegistro, "rol" => "Administrador"], "admin");
verificar($r['codigo'] == 200 && $r['json']['usuario']['rol'] == "Administrador", "Y después lo hace Administrador");

$r = pedir("DELETE", $api . "/usuarios?id=" . $idRegistrado, null, "admin");
verificar($r['codigo'] == 200, "Borra el usuario que se registró en la prueba");

titulo("Seguridad: bloqueo por intentos fallidos y auditoría");

$correoAtacado = "atacado" . $correoPrueba;
for ($i = 1; $i <= 5; $i++) {
    $r = entrar($api, "atacante", $correoAtacado, "mala" . $i);
}
verificar($r['codigo'] == 401, "Los primeros 5 intentos con contraseña mala devuelven 401");

$r = entrar($api, "atacante", $correoAtacado, "otra-mala");
verificar($r['codigo'] == 429 && str_contains($r['json']['error'], "demasiados intentos"), "Al sexto intento el correo queda bloqueado (429)");

$r = entrar($api, "atacante", "vecino@sigeru.uy", "mala");
verificar($r['codigo'] == 401, "El bloqueo es solo para ese correo: los demás pueden seguir intentando");

$r = pedir("GET", $api . "/auditoria", null, "operario");
verificar($r['codigo'] == 403, "El operario no puede ver la auditoría (403)");

$r = pedir("GET", $api . "/auditoria?accion=" . urlencode("Login fallido"), null, "admin");
$fallidos = 0;
foreach ($r['json'] as $evento) {
    if ($evento['email'] == $correoAtacado) $fallidos++;
}
verificar($r['codigo'] == 200 && $fallidos == 5, "La auditoría guarda los 5 intentos fallidos con el correo");
verificar($r['json'][0]['ip'] != "", "Y la IP desde donde se intentó");

$r = pedir("GET", $api . "/auditoria?accion=" . urlencode("Cambio de rol"), null, "admin");
verificar(count($r['json']) >= 2 && str_contains($r['json'][0]['detalle'], "pasó de Operario a Administrador"), "Los cambios de rol quedan anotados con quién los hizo");
verificar($r['json'][0]['usuario'] == "Usuario Administrador", "A nombre del administrador que lo cambió");

$r = pedir("GET", $api . "/auditoria?accion=" . urlencode("Login bloqueado"), null, "admin");
verificar(count($r['json']) >= 1, "El intento bloqueado también queda anotado");

$r = pedir("GET", str_replace("/api.php", "/conexion.php", $api), null, "landing");
verificar($r['codigo'] == 403, "No se puede abrir conexion.php desde el navegador (403)");
$r = pedir("GET", str_replace("/api.php", "/tests/test_api.php", $api), null, "landing");
verificar($r['codigo'] == 403, "No se pueden correr los tests desde el navegador (403)");

mysqli_query($base, "DELETE FROM auditoria WHERE email = '" . $correoAtacado . "'");

titulo("Cerrar sesion");

$r = pedir("POST", $api . "/logout", null, "admin");
verificar($r['codigo'] == 200, "Cierra la sesión");

$r = pedir("GET", $api . "/usuarios", null, "admin");
verificar($r['codigo'] == 401, "Después de cerrar sesión ya no puede ver los usuarios");

mysqli_query($base, "DELETE FROM mensajes WHERE texto LIKE '%Prueba Registro%'");

$r = pedir("GET", $api . "/no-existe", null, "admin");
verificar($r['codigo'] == 404, "Un endpoint que no existe devuelve 404");

mostrarResumen();
