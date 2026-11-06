-- =====================================================================
-- Usuario de MySQL solo para las APIs (principio de minimo privilegio)
-- Para el servidor de base de datos (MV2). En XAMPP no hace falta: ahi se usa root sin contraseña.
--
-- Las APIs no necesitan crear ni borrar tablas: solo leer, agregar, modificar y borrar filas.
-- Si alguien lograra meter una consulta por las APIs, con este usuario no podria borrar la base.
--
-- ANTES de importarlo: cambiar 'CAMBIAR_ESTA_CLAVE' por una contraseña larga
-- y la IP 192.168.1.200 si el servidor de las APIs (MV1) tiene otra.
-- Despues, en el servidor de las APIs se configuran DB_USER y DB_PASS (ver conexion.php).
-- =====================================================================

CREATE USER IF NOT EXISTS 'sigeru_app'@'192.168.1.200' IDENTIFIED BY 'CAMBIAR_ESTA_CLAVE';
GRANT SELECT, INSERT, UPDATE, DELETE ON sigeru.* TO 'sigeru_app'@'192.168.1.200';

FLUSH PRIVILEGES;
