
SET NAMES utf8mb4;
USE sigeru;

ALTER TABLE usuarios MODIFY telefono VARCHAR(255);

UPDATE usuarios SET codigo_verificacion = SHA2(codigo_verificacion, 256)
WHERE codigo_verificacion IS NOT NULL AND LENGTH(codigo_verificacion) = 32;
