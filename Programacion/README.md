# SiGeRU - Sistema de Gestión de Residuos Urbanos

Proyecto de pasaje de grado de **EcoSync** - ESI 2026.

## Aplicaciones

| Carpeta | Qué es |
|---------|--------|
| `sigeru-landing` | Página principal (con video de fondo opcional en `media/fondo.mp4`) y registro de vecinos |
| `sigeru-frontend` | Inicio de sesión, panel de control con estadísticas y alertas, mapa interactivo de Montevideo, seguimiento de incidencias, informes y CRUD de usuarios, contenedores, camiones, recorridos, centros de acopio y maquinaria |
| `sigeru-api-usuarios` | API de registro, inicio de sesión y usuarios |
| `sigeru-api-gestion` | API de contenedores, centros de acopio, maquinaria, inventario de repuestos, incidencias (las reportan el personal y los vecinos, con su seguimiento), volumen recolectado, estadísticas y alertas |
| `sigeru-api-recoleccion` | API de la flota de camiones, mantenimientos y recorridos (rutas asignadas a cuadrillas) |
| `sigeru-base-datos` | MER, modelo físico, DDL y dump de la base de datos |

## Cómo ejecutarlo (XAMPP)

1. Copiar las carpetas `sigeru-landing`, `sigeru-frontend`, `sigeru-api-usuarios`, `sigeru-api-gestion` y `sigeru-api-recoleccion` dentro de `C:\xampp\htdocs\`.
2. Prender **Apache** y **MySQL** en XAMPP.
3. **Solo la primera vez:** entrar a http://localhost/phpmyadmin → **Importar** → `sigeru-base-datos/ddl/sigeru_ddl.sql`.
4. **Solo la primera vez:** otra vez **Importar** → `sigeru-base-datos/datos/sigeru_datos_prueba.sql` (datos de prueba).
5. Abrir http://localhost/sigeru-landing/

### Que no se pierdan los datos

Los datos (usuarios, incidencias, etc.) quedan guardados en MySQL aunque se apague o reinicie la computadora.
Lo único que los borra es **volver a importar `sigeru_ddl.sql`**, porque empieza borrando la base entera.

- Cuando se baja una versión nueva del proyecto, solo se reemplazan las 5 carpetas de `htdocs`. **No** se vuelve a importar la base.
- Si la base cambia (una tabla o columna nueva), el cambio viene en la carpeta `sigeru-base-datos/actualizaciones`
  y se importa ese archivo, que agrega lo nuevo sin borrar nada.
- **Si la base se creó antes del 6/11/2026** (todavía tiene las incidencias de prueba inventadas): importar
  `sigeru-base-datos/actualizaciones/001_sacar_datos_inventados.sql`. Borra solo esas incidencias inventadas,
  no las que cargaron ustedes.
- **Para llevar los datos a otra computadora** (o guardar una copia): en phpMyAdmin elegir la base **sigeru** →
  **Exportar** → **Continuar**. Se descarga un archivo `.sql` con todo. En la otra computadora: crear la base con
  `sigeru_ddl.sql` (sin los datos de prueba) y después **Importar** ese archivo.

### Entrar desde el celular

El celular tiene que estar conectado **al mismo WiFi** que la computadora que tiene XAMPP.

1. En la computadora: tecla Windows → escribir `cmd` → Enter → escribir `ipconfig` → Enter.
   Buscar **"Dirección IPv4"** (por ejemplo `192.168.1.10`).
2. En el celular abrir `http://192.168.1.10/sigeru-landing/` (con la IP de la computadora, **no** `localhost`).
3. Si no abre, es el firewall de Windows: Panel de control → Firewall de Windows Defender →
   Permitir una aplicación → buscar **Apache HTTP Server** y marcar **Privada**.

Los enlaces de los correos (verificar la cuenta) usan la misma dirección con la que se entró: si el vecino se
registra desde el celular con la IP, el enlace del correo también abre en el celular.

**Si la red no deja** (por ejemplo el WiFi de Ceibal, que aísla los aparatos) se puede usar un túnel de Cloudflare,
que da una dirección de internet temporal y funciona desde cualquier red, también con los datos del celular:

1. Descargar https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe y guardarlo en `C:\xampp`.
2. Abrir `cmd` y escribir: `cd C:\xampp` y después `cloudflared-windows-amd64.exe tunnel --url http://localhost:80`
3. Aparece una dirección como `https://palabras-al-azar.trycloudflare.com`. En el celular abrir esa dirección + `/sigeru-landing/`.
4. Mientras la ventana de `cmd` esté abierta, cualquiera con esa dirección puede entrar. Al cerrarla se apaga.
   Cada vez que se abre, la dirección es distinta.

## Cómo ejecutarlo con Docker

Con [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado, desde esta carpeta:

```
docker compose up -d --build
```

La primera vez descarga las imágenes y crea la base de datos con los datos de prueba (tarda unos minutos).

| Qué | Dirección |
|-----|-----------|
| Sistema | http://localhost:8080 |
| phpMyAdmin (usuario `root`, sin contraseña) | http://localhost:8081 |
| MySQL desde afuera | `localhost`, puerto `3307` |

Contenedores:

| Contenedor | Qué tiene |
|------------|-----------|
| `sigeru-mysql` | MySQL 8. La primera vez ejecuta el DDL y los datos de prueba de `sigeru-base-datos` |
| `sigeru-web` | Apache con PHP 8.2 (`docker/Dockerfile`) con las 5 aplicaciones |
| `sigeru-phpmyadmin` | phpMyAdmin para ver la base de datos |

Para apagarlo: `docker compose down`. Para borrar también la base de datos y empezar de cero: `docker compose down -v`.

## Tests

Cada API tiene dos tipos de tests en su carpeta `tests/`, hechos con PHP común (sin librerías):

| Archivo | Qué prueba |
|---------|------------|
| `test_modelos.php` | **Tests unitarios** de cada clase del modelo. Usan una base de datos aparte (`sigeru_test`), así no tocan los datos reales |
| `test_api.php` | **Tests de los endpoints**: le hacen pedidos a la API como el navegador y revisan las respuestas y los permisos de cada rol. Lo que crean lo borran al final |

Con XAMPP (Apache y MySQL prendidos, base de datos importada), desde esta carpeta:

```
C:\xampp\php\php.exe sigeru-api-usuarios/tests/test_modelos.php
C:\xampp\php\php.exe sigeru-api-usuarios/tests/test_api.php
C:\xampp\php\php.exe sigeru-api-gestion/tests/test_modelos.php
C:\xampp\php\php.exe sigeru-api-gestion/tests/test_api.php
C:\xampp\php\php.exe sigeru-api-recoleccion/tests/test_modelos.php
C:\xampp\php\php.exe sigeru-api-recoleccion/tests/test_api.php
```

Con Docker:

```
docker exec sigeru-web php /var/www/html/sigeru-api-usuarios/tests/test_modelos.php
docker exec sigeru-web php /var/www/html/sigeru-api-usuarios/tests/test_api.php
docker exec sigeru-web php /var/www/html/sigeru-api-gestion/tests/test_modelos.php
docker exec sigeru-web php /var/www/html/sigeru-api-gestion/tests/test_api.php
docker exec sigeru-web php /var/www/html/sigeru-api-recoleccion/tests/test_modelos.php
docker exec sigeru-web php /var/www/html/sigeru-api-recoleccion/tests/test_api.php
```

Cada test muestra `OK` o `FALLA` y al final cuántos pasaron:

| Tests | Cantidad |
|-------|----------|
| Modelos de usuarios (usuarios, verificación, cifrado, parámetros, notificaciones) | 44 |
| Endpoints de la API de usuarios (incluye bloqueo por intentos fallidos y auditoría) | 61 |
| Modelos de gestión (contenedores, centros, maquinaria, incidencias, estadísticas, alertas, repuestos, volumen) | 88 |
| Endpoints de la API de gestión | 81 |
| Modelos de recolección (camiones, mantenimientos, recorridos, notificaciones) | 35 |
| Endpoints de la API de recolección | 35 |

## Registro con verificación por correo (Gmail)

Cuando un vecino se registra en la landing:

1. Le llega un **correo con un enlace** para verificar que el correo es suyo.
2. Al abrir el enlace ve **"¡Te registraste correctamente!"** y le llega otro correo confirmándolo.
3. Un **administrador aprueba** su cuenta (le llega una notificación y lo hace desde Usuarios). Al vecino le llega un correo avisando.
   Esto se puede apagar en **Parámetros** → "aprobar registros".

Para que se manden los correos, en cada computadora se crea **una sola vez** el archivo `C:\xampp\sigeru_correo.php`
(al lado de `htdocs`, no adentro) con el correo de Gmail y su "contraseña de aplicación" de Google.
Como está fuera del proyecto, no se sube a GitHub y no se borra al copiar una versión nueva.
Los pasos están en `sigeru-api-usuarios/config_correo.php`.
Si no se configura, no se pueden registrar vecinos (la API avisa que no pudo mandar el correo).

## Notificaciones

La campana del menú muestra las notificaciones de cada usuario y se actualiza sola cada 15 segundos
(cuando llega una nueva aparece un cartel abajo a la derecha). Por ejemplo:

| Qué pasa | A quién le llega |
|----------|------------------|
| Un vecino reporta una incidencia | A todo el personal (administradores, operarios y recolectores) y la confirmación al vecino |
| El personal registra una incidencia | A los administradores y operarios |
| Se asigna una incidencia a una cuadrilla | A los recolectores de esa cuadrilla |
| La incidencia de un vecino pasa a En proceso o Resuelta | Al vecino que la reportó |
| Un recolector resuelve una incidencia | A los administradores y operarios |
| Se asigna o cambia un recorrido (ruta) de una cuadrilla | A los recolectores de esa cuadrilla |
| Un vecino verifica su correo y espera aprobación | A los administradores |
| Un repuesto queda en la cantidad mínima | A los administradores y operarios |

## Seguridad

La seguridad está pensada en capas, una complementa a la otra:

| Capa | Qué tiene SiGeRU |
|------|------------------|
| Navegador (frontend) | Valida los formularios antes de mandarlos, escapa los textos para que no se pueda meter código (XSS), oculta lo que cada rol no puede usar, cabeceras de seguridad (`X-Frame-Options`, `nosniff`) |
| Sesión | Cookie `HttpOnly` y `SameSite=Strict`, número de sesión nuevo al entrar, se cierra sola después de 30 minutos sin usar el sistema |
| APIs | Cada endpoint revisa la sesión y el rol (`verificarRol`), valida todos los datos otra vez, consultas preparadas (evita inyección SQL), desde el navegador solo se puede abrir `api.php` |
| Contraseñas | Guardadas con `password_hash` (bcrypt). Las nuevas tienen que tener al menos 8 caracteres, con letras y números |
| Cifrado | El teléfono de los usuarios se guarda cifrado con AES-256-GCM (`sigeru-api-usuarios/cifrado.php`) y el código de verificación como resumen SHA-256 |
| HTTPS | Certificado SSL firmado por la CA propia "EcoSync CA", solo TLS 1.2 y 1.3 (`seguridad/ssl/`) |
| Respaldos | Hash SHA-256, cifrado AES-256 y firma digital RSA (`seguridad/respaldos/`) |
| OWASP | `seguridad/owasp/revision_owasp.sh`: 28 pruebas contra el OWASP Top 10, todas pasan |
| Fuerza bruta | Después de 5 contraseñas equivocadas el correo se bloquea 15 minutos (se cambia en Parámetros) |
| Auditoría | Página **Seguridad** (solo administrador): quién entró, intentos fallidos y bloqueados, usuarios creados o borrados, cambios de rol y de parámetros. Se exporta a Excel |
| Base de datos | En el servidor las APIs usan el usuario `sigeru_app` con permisos mínimos (`sigeru-base-datos/seguridad/usuario_aplicacion.sql`), su contraseña va en las variables `DB_USER` / `DB_PASS` y no en el código |
| Correo | Gmail por SMTP con STARTTLS (cifrado). La contraseña de aplicación está fuera del proyecto (`sigeru_correo.php`) y nunca se sube a GitHub |

Si ya tenían la base cargada, importen `sigeru-base-datos/actualizaciones/002_seguridad.sql` y después
`003_cifrado.sql` (no borran nada). Más detalles y cómo prender HTTPS en XAMPP: `seguridad/LEEME.md`.

**Importante:** la primera vez que se usa, la API crea `C:\xampp\sigeru_clave.php` con la clave que cifra los teléfonos.
Si se pasa la base de datos a otra computadora, hay que copiar también ese archivo (si no, los teléfonos no se pueden leer).

## Datos de prueba

`sigeru-base-datos/datos/sigeru_datos_prueba.sql` trae usuarios, contenedores, camiones, recorridos, centros,
maquinaria, repuestos y recolecciones simuladas. **No trae incidencias**: se cargan desde el sistema.

## Usuarios de prueba

| Correo | Contraseña | Rol |
|--------|------------|-----|
| admin@sigeru.uy | admin123 | Administrador |
| operario@sigeru.uy | 123456 | Operario |
| recolector@sigeru.uy | 123456 | Recolector |
| vecino@sigeru.uy | 123456 | Vecino |

Cada carpeta tiene su propio README con más detalles.

## Integrantes

- Valentín Ramírez (Coordinador)
- Joaquín Ximénez (Subcoordinador)
- Juan Cuadro
