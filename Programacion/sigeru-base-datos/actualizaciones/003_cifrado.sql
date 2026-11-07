-- =====================================================================
-- Actualizacion 003 - Cifrado y hashing de datos de los usuarios
-- Se importa UNA vez en phpMyAdmin, con la base "sigeru" elegida. NO borra lo que cargaron ustedes.
--
-- 1. El telefono ahora se guarda cifrado con AES-256-GCM, que ocupa mas lugar: la columna pasa a 255.
--    Los telefonos que ya estaban se cifran solos la primera vez que el administrador abre Usuarios.
-- 2. Del codigo del enlace de verificacion se guarda el resumen SHA-256 y no el codigo.
--    Los codigos que estaban esperando verificacion se pasan a su resumen (los enlaces que ya se mandaron siguen andando).
-- =====================================================================

SET NAMES utf8mb4;
USE sigeru;

ALTER TABLE usuarios MODIFY telefono VARCHAR(255);

UPDATE usuarios SET codigo_verificacion = SHA2(codigo_verificacion, 256)
WHERE codigo_verificacion IS NOT NULL AND LENGTH(codigo_verificacion) = 32;
