
SET NAMES utf8mb4;
USE sigeru;

ALTER TABLE incidencias ADD COLUMN IF NOT EXISTS referencia VARCHAR(200) AFTER descripcion;
ALTER TABLE incidencias MODIFY tipo ENUM('Desbordado', 'Roto', 'Falta de recolección', 'Basura en la calle') NOT NULL DEFAULT 'Desbordado';

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

UPDATE contenedores SET estado = 'Funcional'
WHERE estado <> 'Funcional'
  AND id_contenedor NOT IN (SELECT id_contenedor FROM incidencias WHERE estado <> 'Resuelta');

UPDATE camiones SET latitud = -34.887000, longitud = -56.131000
WHERE matricula = 'SBD 9012' AND latitud = -34.901000 AND longitud = -56.118000;
