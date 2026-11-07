# Actualizaciones de la base de datos

`ddl/sigeru_ddl.sql` **borra toda la base** y la crea vacía. Solo se usa la primera vez que se instala
el sistema en una computadora.

Cuando cambia la estructura de la base (una tabla o columna nueva), el cambio se agrega acá en un archivo
numerado, por ejemplo `001_columna_nueva.sql`. Esos archivos **agregan lo nuevo sin borrar los datos**.

Cómo se usan:

1. Entrar a http://localhost/phpmyadmin y elegir la base **sigeru** (a la izquierda).
2. **Importar** → elegir el archivo de actualización → **Importar**.
3. Se importa cada archivo una sola vez, en orden.

| Archivo | Qué hace |
|---------|----------|
| `001_sacar_datos_inventados.sql` | Borra las incidencias de prueba que venían inventadas (sin tocar las que cargaron ustedes), agrega a incidencias el lugar exacto y el tipo "Basura en la calle", y saca del agua al camión SBD 9012 |
| `002_seguridad.sql` | Crea la tabla de auditoría (quién entró, intentos fallidos, cambios de usuarios y roles) y los parámetros del bloqueo por intentos fallidos |
| `003_cifrado.sql` | Agranda la columna del teléfono (ahora se guarda cifrado) y guarda el código de verificación como resumen SHA-256 |

Si la base se creó después de esta actualización, no hace falta importarla (pero si se importa no rompe nada).
