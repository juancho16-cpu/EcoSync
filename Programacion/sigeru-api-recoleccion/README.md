# SiGeRU - API de Recolección

API RESTful dedicada a la logística móvil del Sistema de Gestión de Residuos Urbanos (SiGeRU),
desarrollado por **EcoSync**: la flota de camiones (estado y mantenimiento) y la asignación de rutas
a las cuadrillas.

Los datos se guardan en la base de datos MySQL `sigeru` (ver `sigeru-base-datos`).

## Tecnologías

PHP 8.

## Estructura (MVC)

```
api.php                          Punto de entrada: recibe la solicitud, revisa el rol y llama al controlador
config.php                       Sesión y formato de respuesta
conexion.php                     Conexión a la base de datos MySQL
seguridad.php                    Revisa que haya sesión iniciada y que el rol tenga permiso
modelos/Camion.php               Modelo (clase) de camiones
modelos/Mantenimiento.php        Modelo (clase) de mantenimientos (entidad débil de Camión)
modelos/Recorrido.php            Modelo (clase) de recorridos: cuadrilla + ruta + camión + zonas
controladores/camiones.php       Controlador de camiones
controladores/mantenimientos.php Controlador de mantenimientos
controladores/recorridos.php     Controlador de recorridos (incluye la RNE06)
vistas/respuesta.php             Vista: devuelve la respuesta en JSON
tests/                           Tests de los modelos y de los endpoints
```

## Endpoints

Todos necesitan la sesión iniciada (se inicia con la API de Usuarios).

| Endpoint | GET (ver) | POST (agregar) / PUT (modificar) / DELETE (borrar) |
|----------|-----------|----------------------------------------------------|
| `api.php/camiones` (`?id=1` para uno) | Administrador, Operario y Recolector | Administrador y Operario |
| `api.php/mantenimientos?camion=1` | Administrador, Operario y Recolector | Administrador y Operario (PUT y DELETE con `?camion=1&nro=2`) |
| `api.php/recorridos` (`?id=1` para uno) | Administrador y Operario ven todos, el Recolector solo los de su cuadrilla | Administrador y Operario |
| `api.php/cuadrillas` | Administrador, Operario y Recolector | - |
| `api.php/rutas` | Administrador, Operario y Recolector | - |
| `api.php/zonas` | Administrador, Operario y Recolector | - |

Reglas que se controlan:

- **RNE06**: una cuadrilla solo puede ser asignada a una ruta si el camión está *Operativo* y *Disponible*
  (si se modifica un recorrido manteniendo su camión, se permite aunque esté en ruta).
- Una cuadrilla no puede tener dos veces la misma ruta.
- Cada zona la recorre un solo recorrido: si se elige una zona que tenía otro recorrido, pasa a este.
- Los mantenimientos se numeran por camión (1, 2, 3...) porque son entidad débil.

## Cómo ejecutarla (XAMPP)

1. Copiar la carpeta `sigeru-api-recoleccion` dentro de `C:\xampp\htdocs\`.
2. Prender **Apache** y **MySQL** en el panel de XAMPP.
3. La base de datos tiene que estar creada (ver el README general).
4. La API queda en http://localhost/sigeru-api-recoleccion/api.php

## Tests

En la carpeta `tests/`:

- `test_modelos.php`: tests unitarios de los modelos (usan la base de datos de prueba `sigeru_test`).
- `test_api.php`: tests de los endpoints (necesitan Apache y MySQL prendidos).

Se corren con `php tests/test_modelos.php` y `php tests/test_api.php`. Ver el README general para más detalles.
