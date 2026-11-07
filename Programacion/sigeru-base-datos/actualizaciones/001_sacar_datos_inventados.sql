-- =====================================================================
-- Actualizacion 001 - Sacar los datos inventados y poner al dia la tabla de incidencias
-- Se importa UNA vez en phpMyAdmin, con la base "sigeru" elegida. NO borra lo que cargaron ustedes.
--
-- 1. Agrega a incidencias lo que se sumo despues (el lugar exacto y el tipo "Basura en la calle").
-- 2. Borra SOLO las 9 incidencias de prueba que venian inventadas (se reconocen por su numero
--    y su descripcion exacta), el aviso inventado de la vecina y sus notificaciones.
-- 3. Los contenedores que no tienen problemas sin resolver vuelven a estar "Funcional".
-- 4. Saca del agua al camion SBD 9012 (estaba guardado en un punto del Rio de la Plata).
-- =====================================================================

SET NAMES utf8mb4;
USE sigeru;

-- 1. Columnas nuevas de incidencias (si ya estaban, no pasa nada)
ALTER TABLE incidencias ADD COLUMN IF NOT EXISTS referencia VARCHAR(200) AFTER descripcion;
ALTER TABLE incidencias MODIFY tipo ENUM('Desbordado', 'Roto', 'Falta de recolección', 'Basura en la calle') NOT NULL DEFAULT 'Desbordado';

-- 2. Incidencias inventadas (y lo que dependia de ellas)
DELETE FROM notificaciones WHERE id_usuario = 4 AND descripcion = 'El contenedor de la rambla está lleno hace dos días';

DELETE FROM incidencias WHERE
    (id_incidencia = 1 AND descripcion = 'Contenedor desbordado, hay basura en la vereda') OR
    (id_incidencia = 2 AND descripcion = 'La tapa del contenedor está rota') OR
    (id_incidencia = 3 AND descripcion = 'Contenedor desbordado desde el fin de semana') OR
    (id_incidencia = 4 AND descripcion = 'Contenedor desbordado, bolsas en la calle') OR
    (id_incidencia = 5 AND descripcion = 'Tapa rota') OR
    (id_incidencia = 6 AND descripcion = 'Una rueda del contenedor está rota') OR
    (id_incidencia = 7 AND descripcion = 'Falta de recolección') OR
    (id_incidencia = 8 AND descripcion = 'Contenedor desbordado') OR
    (id_incidencia = 9 AND descripcion = 'Contenedor desbordado otra vez');

DELETE FROM mensajes WHERE fecha = '2026-11-01 09:30:00' AND (
    texto = '📣 Sofía González avisó un problema en el contenedor de Rambla República de México 5500' OR
    texto = '✅ Recibimos tu aviso sobre el contenedor de Rambla República de México 5500');

-- 3. Contenedores sin problemas pendientes: Funcional
UPDATE contenedores SET estado = 'Funcional'
WHERE estado <> 'Funcional'
  AND id_contenedor NOT IN (SELECT id_contenedor FROM incidencias WHERE estado <> 'Resuelta');

-- 4. El camion SBD 9012 pasa a un punto en tierra (Av. Italia, Malvin)
UPDATE camiones SET latitud = -34.887000, longitud = -56.131000
WHERE matricula = 'SBD 9012' AND latitud = -34.901000 AND longitud = -56.118000;
