#!/bin/bash
# =====================================================================
# Revision automatica de SiGeRU contra el OWASP Top 10 (2021)
#
# Hace pedidos de verdad al sistema (como lo haria un atacante) y muestra OK o FALLA en cada prueba.
# Usa los usuarios de prueba, asi que se corre con los datos de prueba cargados.
#
# Uso: bash revision_owasp.sh                       (http://localhost)
#      bash revision_owasp.sh https://sigeru.local  (en el servidor con HTTPS)
# =====================================================================

BASE=${1:-http://localhost}
U="$BASE/sigeru-api-usuarios/api.php"
G="$BASE/sigeru-api-gestion/api.php"
R="$BASE/sigeru-api-recoleccion/api.php"
TMP=$(mktemp -d)
CURL="curl -s -k"
PASARON=0
FALLARON=0

prueba() {
    if [ "$1" == "$2" ]; then
        echo "  OK     $3"
        PASARON=$((PASARON + 1))
    else
        echo "  FALLA  $3 (esperado $2, dio $1)"
        FALLARON=$((FALLARON + 1))
    fi
}
codigo() { $CURL -o /dev/null -w "%{http_code}" "$@"; }
entrar() { $CURL -c "$TMP/$1" -b "$TMP/$1" -H "Content-Type: application/json" -d "{\"email\":\"$2\",\"contrasena\":\"$3\"}" "$U/login" > /dev/null; }

entrar vecino vecino@sigeru.uy 123456
entrar operario operario@sigeru.uy 123456

echo "A01 Control de acceso roto"
prueba "$(codigo "$U/usuarios")" 401 "Sin sesión no se puede listar usuarios"
prueba "$(codigo -b "$TMP/vecino" "$U/usuarios")" 403 "Un vecino no puede listar usuarios"
prueba "$(codigo -b "$TMP/operario" "$U/auditoria")" 403 "Un operario no puede ver la auditoría"
prueba "$(codigo -b "$TMP/vecino" -X PUT -H "Content-Type: application/json" -d '{"estado":"Resuelta"}' "$G/incidencias?id=1")" 403 "Un vecino no puede cambiar el estado de una incidencia"
prueba "$(codigo -b "$TMP/vecino" -X DELETE "$G/contenedores?id=1")" 403 "Un vecino no puede borrar contenedores"
prueba "$(codigo -b "$TMP/vecino" -X DELETE "$R/camiones?id=1")" 403 "Un vecino no puede borrar camiones"
prueba "$(codigo -b "$TMP/vecino" -X PUT -H "Content-Type: application/json" -d '{"idioma":"en"}' "$U/parametros")" 403 "Un vecino no puede cambiar los parámetros"

echo "A02 Fallas criptográficas"
COOKIE=$($CURL -D - -o /dev/null -H "Content-Type: application/json" -d '{"email":"vecino@sigeru.uy","contrasena":"123456"}' "$U/login" | grep -i "set-cookie" | tail -1)
prueba "$(echo "$COOKIE" | grep -ci httponly)" 1 "La cookie de sesión es HttpOnly"
prueba "$(echo "$COOKIE" | grep -ci "samesite=strict")" 1 "La cookie de sesión es SameSite=Strict"
if [[ "$BASE" == https* ]]; then
    prueba "$(echo "$COOKIE" | grep -ci secure)" 1 "Con HTTPS la cookie es Secure"
fi
LISTA=$($CURL -b "$TMP/vecino" "$U/sesion")
prueba "$(echo "$LISTA" | grep -c '\$2y\$')" 0 "Ninguna respuesta trae hashes de contraseñas"

echo "A03 Inyección"
prueba "$(codigo -H "Content-Type: application/json" -d "{\"email\":\"' OR '1'='1\",\"contrasena\":\"' OR '1'='1\"}" "$U/login")" 401 "Inyección SQL en el login no entra"
prueba "$(codigo -H "Content-Type: application/json" -d "{\"email\":\"admin@sigeru.uy' -- \",\"contrasena\":\"x\"}" "$U/login")" 401 "Comentario SQL en el correo no entra"
prueba "$(codigo -b "$TMP/operario" "$G/contenedores?id=1%20OR%201=1")" 400 "Un id con SQL se rechaza (400)"
prueba "$(codigo -b "$TMP/operario" "$R/camiones?id=-1")" 400 "Un id negativo se rechaza (400)"

echo "A04 Diseño inseguro (fuerza bruta)"
for i in 1 2 3 4 5; do codigo -H "Content-Type: application/json" -d '{"email":"owasp@prueba.uy","contrasena":"mala'$i'"}' "$U/login" > /dev/null; done
prueba "$(codigo -H "Content-Type: application/json" -d '{"email":"owasp@prueba.uy","contrasena":"otra"}' "$U/login")" 429 "Al sexto intento fallido el correo se bloquea"

echo "A05 Configuración de seguridad"
prueba "$(codigo "$BASE/sigeru-api-usuarios/conexion.php")" 403 "conexion.php no se puede abrir"
prueba "$(codigo "$BASE/sigeru-api-usuarios/config_correo.php")" 403 "config_correo.php no se puede abrir"
prueba "$(codigo "$BASE/sigeru-api-usuarios/tests/test_api.php")" 403 "Los tests no se pueden correr desde el navegador"
prueba "$(codigo "$BASE/sigeru-api-usuarios/modelos/")" 403 "No se listan los archivos de las carpetas"
prueba "$(codigo "$BASE/sigeru-api-usuarios/libs/PHPMailer/PHPMailer.php")" 403 "Las librerías no se pueden abrir"
CABECERAS=$($CURL -D - -o /dev/null "$BASE/sigeru-frontend/index.html")
prueba "$(echo "$CABECERAS" | grep -ci "x-frame-options: deny")" 1 "X-Frame-Options: DENY (clickjacking)"
prueba "$(echo "$CABECERAS" | grep -ci "x-content-type-options: nosniff")" 1 "X-Content-Type-Options: nosniff"
prueba "$($CURL "$U/no-existe" | grep -ci "warning\|fatal\|stack")" 0 "Los errores no muestran detalles internos de PHP"

echo "A07 Fallas de identificación y autenticación"
prueba "$($CURL -H "Content-Type: application/json" -d '{"email":"noexiste@prueba.uy","contrasena":"x"}' "$U/login" | grep -c "incorrectos")" 1 "Un correo que no existe da el mismo error que una contraseña mala"
prueba "$(codigo -H "Content-Type: application/json" -d '{"primer_nombre":"A","primer_apellido":"B","email":"owasp2@prueba.uy","contrasena":"123456"}' "$U/registro")" 400 "No acepta contraseñas débiles en el registro"
$CURL -b "$TMP/operario" -c "$TMP/operario" -X POST "$U/logout" > /dev/null
prueba "$(codigo -b "$TMP/operario" "$G/contenedores")" 401 "Después de cerrar sesión la cookie vieja no sirve"

echo "A09 Registro y monitoreo"
entrar admin admin@sigeru.uy admin123
prueba "$($CURL -b "$TMP/admin" "$U/auditoria?accion=Login%20bloqueado" | grep -c "owasp@prueba.uy")" 1 "El ataque de fuerza bruta quedó en la auditoría"

rm -rf "$TMP"
echo ""
echo "Pasaron: $PASARON | Fallaron: $FALLARON"
