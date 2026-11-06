# SiGeRU - API de Usuarios

API RESTful encargada del registro (con verificación por correo y aprobación), la autenticación,
la gestión de usuarios, las notificaciones y los parámetros del
Sistema de Gestión de Residuos Urbanos (SiGeRU), desarrollado por **EcoSync**.

Los datos se guardan en la base de datos MySQL `sigeru` (ver `sigeru-base-datos`).

## Tecnologías

PHP 8.

## Estructura (MVC)

```
api.php                       Punto de entrada: recibe la solicitud, revisa el rol y llama al controlador
config.php                    Sesión y formato de respuesta
conexion.php                  Conexión a la base de datos MySQL
seguridad.php                 Revisa que haya sesión iniciada y que el rol tenga permiso
correo.php                    Manda los correos con Gmail (usa la libreria PHPMailer)
config_correo.php             Lee la cuenta de Gmail del archivo C:\xampp\sigeru_correo.php (fuera del proyecto)
libs/PHPMailer/               Libreria PHPMailer 6.12.0 (solo los 3 archivos que se usan)
modelos/Usuario.php           Modelo (clase): guarda, busca, modifica y borra usuarios
modelos/Parametro.php         Modelo (clase): parametros del sistema
modelos/Mensaje.php           Modelo (clase): notificaciones de cada usuario
controladores/usuarios.php    Controlador: registro, verificacion, aprobacion, login y CRUD de usuarios
controladores/mensajes.php    Controlador: notificaciones y parametros
vistas/respuesta.php          Vista: devuelve la respuesta en JSON
```

## Endpoints

| Método | Endpoint | Descripción | Quién puede |
|--------|----------|-------------|-------------|
| POST | `api.php/registro` | Registro de un vecino (desde la landing). Le manda un correo para verificar | Cualquiera |
| GET | `api.php/verificar?codigo=...` | El vecino abre el enlace del correo | Cualquiera |
| POST | `api.php/aprobar?id=5` | Aprobar (`{"aprobado": true}`) o rechazar el registro de un vecino | Administrador |
| GET | `api.php/mensajes` | Notificaciones del usuario y cuántas no leyó | Con sesión iniciada |
| PUT | `api.php/mensajes?id=3` | Marcar una notificación como leída (sin id: todas) | Con sesión iniciada |
| GET | `api.php/parametros` | Parámetros del sistema | Con sesión iniciada |
| PUT | `api.php/parametros` | Cambiar parámetros (`{"idioma": "en"}`) | Administrador |
| POST | `api.php/login` | Inicio de sesión | Cualquiera |
| POST | `api.php/logout` | Cierre de sesión | Cualquiera |
| GET | `api.php/sesion` | Usuario que inició sesión | Con sesión iniciada |
| GET | `api.php/usuarios` | Lista de usuarios | Administrador |
| GET | `api.php/usuarios?id=1` | Un usuario | Administrador |
| POST | `api.php/usuarios` | Crear un usuario con cualquier rol | Administrador |
| PUT | `api.php/usuarios?id=1` | Modificar un usuario (la contraseña es opcional) | Administrador |
| DELETE | `api.php/usuarios?id=1` | Borrar un usuario (no se puede borrar a sí mismo) | Administrador |

Según la RNE04, solo el administrador puede crear, modificar o eliminar usuarios.

Estados de un usuario: **Sin verificar** (se registró y no abrió el correo) → **Pendiente** (espera que lo apruebe
un administrador) → **Activo**. Si lo rechazan queda **Rechazado**. Solo los usuarios activos pueden iniciar sesión.

### Correo (Gmail)

La contraseña **no** va en el código. En cada computadora se crea una sola vez el archivo
`C:\xampp\sigeru_correo.php` (al lado de la carpeta `htdocs`, no adentro), con este contenido:

```php
<?php
$correoGmail = "el correo de gmail";
$claveAppGmail = "la contraseña de aplicacion de 16 letras, sin espacios";
```

La contraseña de aplicación se crea en la cuenta de Google → Seguridad → Verificación en dos pasos →
Contraseñas de aplicaciones. Como el archivo está fuera del proyecto, no se sube a GitHub y no se borra
cuando se copia una versión nueva. Si no está, no se mandan correos y no se pueden registrar vecinos.

Ejemplo:

```
curl -X POST http://localhost/sigeru-api-usuarios/api.php/login -H "Content-Type: application/json" -d "{\"email\":\"admin@sigeru.uy\",\"contrasena\":\"admin123\"}"
```

## Cómo ejecutarla (XAMPP)

1. Copiar la carpeta `sigeru-api-usuarios` dentro de `C:\xampp\htdocs\`.
2. Prender **Apache** y **MySQL** en el panel de XAMPP.
3. Crear la base de datos importando en phpMyAdmin `sigeru-base-datos/ddl/sigeru_ddl.sql` y después `sigeru-base-datos/datos/sigeru_datos_prueba.sql`.
4. La API queda en http://localhost/sigeru-api-usuarios/api.php

Usuario de prueba: **admin@sigeru.uy** / **admin123**


## Tests

En la carpeta `tests/`:

- `test_modelos.php`: tests unitarios de los modelos (usan la base de datos de prueba `sigeru_test`).
- `test_api.php`: tests de los endpoints (necesitan Apache y MySQL prendidos).

Se corren con `php tests/test_modelos.php` y `php tests/test_api.php`. Ver el README general para más detalles.
