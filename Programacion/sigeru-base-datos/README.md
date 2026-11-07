# SiGeRU - Base de datos

Modelo de datos del Sistema de Gestión de Residuos Urbanos (SiGeRU), desarrollado por **EcoSync**.

## Contenido

| Archivo | Descripción |
|---------|-------------|
| `mer/sigeru_mer.drawio` | Diagrama Entidad-Relación con RNE (abrir en https://app.diagrams.net) |
| `modelo_fisico.md` | Primera versión del modelo físico: tablas, columnas, claves y relaciones |
| `ddl/sigeru_ddl.sql` | DDL de creación de la base de datos y sus tablas |
| `dump/sigeru_dump_estructura.sql` | Dump de la estructura de la base de datos (sin datos) |
| `datos/sigeru_datos_prueba.sql` | Datos de prueba (usuarios de cada rol, contenedores, camiones, incidencias, etc.) |

## Cómo crear la base de datos

En phpMyAdmin: **Importar** → `ddl/sigeru_ddl.sql`, y después **Importar** → `datos/sigeru_datos_prueba.sql`.

Desde la terminal:

```
mysql -u root -p < ddl/sigeru_ddl.sql
mysql -u root -p < datos/sigeru_datos_prueba.sql
```

## Cómo generar el dump

```
mysqldump -u root -p --no-data --databases sigeru > dump/sigeru_dump_estructura.sql
```
