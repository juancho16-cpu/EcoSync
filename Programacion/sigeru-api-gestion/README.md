# SiGeRU - API de Gestión

API RESTful que actúa como orquestador operativo del Sistema de Gestión de Residuos Urbanos (SiGeRU),
desarrollado por **EcoSync**: contenedores, centros de acopio, maquinaria, repuestos e incidencias (las reportan el personal y los vecinos),
y los números del panel de control (estadísticas y alertas).
Los camiones y los recorridos están en la **API de Recolección**.

Los datos se guardan en la base de datos MySQL `sigeru` (ver `sigeru-base-datos`).

## Tecnologías

PHP 8.

## Estructura (MVC)

```
api.php                          Punto de entrada: recibe la solicitud, revisa el rol y llama al controlador
config.php                       Sesión y formato de respuesta
conexion.php                     Conexión a la base de datos MySQL
seguridad.php                    Revisa que haya sesión iniciada y que el rol tenga permiso
modelos/Contenedor.php           Modelo (clase) de contenedores y tipos de residuo
modelos/Centro.php               Modelo (clase) de centros de acopio
modelos/Maquinaria.php           Modelo (clase) de maquinaria
modelos/Incidencia.php           Modelo (clase) de incidencias
modelos/Notificacion.php         Modelo (clase) que guarda qué vecino reportó cada incidencia
modelos/Estadistica.php          Modelo (clase) de las estadísticas del panel
modelos/Alerta.php               Modelo (clase) de las alertas de cada rol
modelos/Repuesto.php             Modelo (clase) del inventario de repuestos
modelos/Parametro.php            Modelo (clase) que lee los parámetros del sistema
modelos/Mensaje.php              Modelo (clase) que manda notificaciones a los usuarios
controladores/contenedores.php   Controlador de contenedores
controladores/centros.php        Controlador de centros de acopio
controladores/maquinaria.php     Controlador de maquinaria
controladores/incidencias.php    Controlador de incidencias (registro y seguimiento) y estadísticas
controladores/alertas.php        Controlador de las alertas
controladores/repuestos.php      Controlador del inventario de repuestos
vistas/respuesta.php             Vista: devuelve la respuesta en JSON
```

## Endpoints

Todos necesitan la sesión iniciada (se inicia con la API de Usuarios).
Para ver, modificar o borrar uno solo se agrega `?id=1` a la dirección.

| Endpoint | GET (ver) | POST (agregar) / PUT (modificar) / DELETE (borrar) |
|----------|-----------|----------------------------------------------------|
| `api.php/contenedores` | Todos los roles | Administrador y Operario |
| `api.php/tipos_residuo` | Todos los roles | - |
| `api.php/centros` | Administrador, Operario y Recolector | Administrador y Operario |
| `api.php/maquinaria` | Administrador, Operario y Recolector | Administrador y Operario |
| `api.php/operarios` | Administrador y Operario | - |
| `api.php/instalaciones` | Administrador, Operario y Recolector | - |
| `api.php/incidencias` | Todos (el Vecino ve solo las que reportó) | POST (reportar una incidencia): todos los roles<br>PUT (seguimiento): Administrador y Operario; el Recolector solo cambia el estado de las de su cuadrilla |
| `api.php/repuestos` | Administrador, Operario y Recolector | Administrador y Operario |
| `api.php/stock?id=1` | - | POST (usar o reponer unidades, `{"cambio": -1}`): Administrador, Operario y Recolector |
| `api.php/volumen?desde=...&hasta=...` | Administrador, Operario y Recolector (kilos recolectados) | - |
| `api.php/estadisticas` | Administrador, Operario y Recolector | - |
| `api.php/alertas` | Todos los roles (cada uno ve las suyas) | - |
| `api.php/vaciar?id=1` | - | POST (marcar un contenedor como vaciado): Administrador, Operario y Recolector |

Respuestas de error:

| Código | Cuándo |
|--------|--------|
| 400 | Faltan datos o están mal |
| 401 | No hay sesión iniciada |
| 403 | El rol no tiene permiso |
| 404 | No existe el registro o el endpoint |
| 409 | Contenedor con incidencias que no se puede borrar, un vecino que reporta dos veces el mismo contenedor, o cambiar una incidencia ya resuelta |

## Incidencias

Reportar (`POST api.php/incidencias`): `id_contenedor`, `problema` (Desbordado, Roto, Falta de recolección
o Basura en la calle), `descripcion` (qué pasó, de 10 a 500 letras), `referencia` (dónde exactamente, opcional)
y `prioridad` (solo el personal; las de los vecinos quedan en Media). Las que reporta un vecino quedan guardadas
con su nombre (tabla `notificaciones`, relación *Genera2* del MER) y le llegan a todo el personal.

Filtros del listado: `?estado=Abierta`, `?contenedor=2` (historial de un contenedor) y
`?desde=2026-11-01&hasta=2026-11-30` (para los informes). Se pueden juntar.

Seguimiento (`PUT api.php/incidencias?id=1`):

- Estados: **Abierta → En proceso → Resuelta** (RNE02: un solo estado a la vez).
- El administrador y el operario cambian el estado, la prioridad y la cuadrilla (recorrido) que la resuelve.
- El recolector solo cambia el estado, y solo de las incidencias asignadas a su cuadrilla.
- Al resolverla se guarda la fecha de resolución. Una incidencia resuelta ya no se puede cambiar.
- Si el contenedor ya no tiene otras incidencias sin resolver, vuelve a estar **Funcional**.
- Si la reportó un vecino, le llega una notificación cuando cambia de estado.

## Estadísticas y alertas

`GET api.php/estadisticas?mes=2026-11` devuelve: incidencias por estado, top 5 de contenedores
desbordados del mes, incidencias sin resolver por municipio, incidencias del mes por tipo,
cuántas se resolvieron en el mes y el tiempo promedio de resolución (en días).

`GET api.php/alertas` devuelve las alertas de cada rol:

| Rol | Alertas |
|-----|---------|
| Administrador y Operario | Contenedores desbordados hace 2 días o más sin atender, incidencias de prioridad alta abiertas, incidencias de vecinos sin atender, centros al 80% o más de su capacidad |
| Recolector | Incidencias sin resolver asignadas a su cuadrilla |
| Vecino | Sus incidencias que pasaron a "en proceso" o "resuelto" en los últimos 7 días |

## Cómo ejecutarla (XAMPP)

1. Copiar la carpeta `sigeru-api-gestion` dentro de `C:\xampp\htdocs\`.
2. Prender **Apache** y **MySQL** en el panel de XAMPP.
3. Crear la base de datos importando en phpMyAdmin `sigeru-base-datos/ddl/sigeru_ddl.sql` y después `sigeru-base-datos/datos/sigeru_datos_prueba.sql`.
4. La API queda en http://localhost/sigeru-api-gestion/api.php


## Tests

En la carpeta `tests/`:

- `test_modelos.php`: tests unitarios de los modelos (usan la base de datos de prueba `sigeru_test`).
- `test_api.php`: tests de los endpoints (necesitan Apache y MySQL prendidos).

Se corren con `php tests/test_modelos.php` y `php tests/test_api.php`. Ver el README general para más detalles.
