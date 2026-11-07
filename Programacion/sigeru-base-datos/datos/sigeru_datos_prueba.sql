-- =====================================================================
-- SiGeRU - Datos de prueba
-- Importar DESPUES de ddl/sigeru_ddl.sql
--
-- Usuarios de prueba:
--   admin@sigeru.uy      / admin123  (Administrador)
--   operario@sigeru.uy   / 123456    (Operario)
--   recolector@sigeru.uy / 123456    (Recolector)
--   vecino@sigeru.uy     / 123456    (Vecino)
-- =====================================================================

USE sigeru;
SET NAMES utf8mb4;

-- ---------- Usuarios ----------
INSERT INTO usuarios (id_usuario, primer_nombre, primer_apellido, email, telefono, contrasena, rol) VALUES
(1, 'Usuario', 'Administrador', 'admin@sigeru.uy', '099111111', '$2y$10$eIUne.OuwuHmfbKqMtfv..EDwPH7ITgsi3PCJm4yns3BUxOrhR1b.', 'Administrador'),
(2, 'Martín', 'Pereira', 'operario@sigeru.uy', '099222222', '$2y$10$yTNFUbFLVtSgsCdnvGteAuECBj8uIGpJy4g1MoPYL2iK.8KFXEW8O', 'Operario'),
(3, 'Lucía', 'Fernández', 'recolector@sigeru.uy', '099333333', '$2y$10$yTNFUbFLVtSgsCdnvGteAuECBj8uIGpJy4g1MoPYL2iK.8KFXEW8O', 'Recolector'),
(4, 'Sofía', 'González', 'vecino@sigeru.uy', '099444444', '$2y$10$yTNFUbFLVtSgsCdnvGteAuECBj8uIGpJy4g1MoPYL2iK.8KFXEW8O', 'Vecino');

INSERT INTO administradores (id_usuario) VALUES (1);
INSERT INTO operarios (id_usuario, especialidad) VALUES (2, 'Mantenimiento de contenedores');
INSERT INTO vecinos (id_usuario, calle, zona) VALUES (4, 'Av. Brasil 2500', 'Pocitos');

-- ---------- Cuadrillas y rutas ----------
INSERT INTO cuadrillas (id_cuadrilla, nombre) VALUES
(1, 'Cuadrilla Centro'),
(2, 'Cuadrilla Este');

INSERT INTO recolectores (id_usuario, id_cuadrilla, fecha_ingreso) VALUES (3, 1, '2026-03-01');

INSERT INTO rutas (id_ruta, nombre, descripcion, distancia) VALUES
(1, 'Ruta Centro', 'Ciudad Vieja, Centro y Tres Cruces', 12.50),
(2, 'Ruta Costa', 'Punta Carretas, Pocitos, Malvín y Carrasco', 18.00);

-- ---------- Camiones (latitud y longitud: posicion simulada en el mapa) ----------
INSERT INTO camiones (id_camion, matricula, estado, disponibilidad, capacidad_carga, latitud, longitud) VALUES
(1, 'SBA 1234', 'Operativo', 'Disponible', 10000, -34.874000, -56.169000),
(2, 'SBC 5678', 'En mantenimiento', 'No disponible', 8000, -34.872000, -56.172000),
(3, 'SBD 9012', 'Operativo', 'En ruta', 12000, -34.887000, -56.131000),
(4, 'SBF 3344', 'Operativo', 'En ruta', 10000, -34.905500, -56.195000);

INSERT INTO mantenimientos (id_camion, nro_mantenimiento, tipo, costo, descripcion, fecha) VALUES
(2, 1, 'Preventivo', 15000, 'Cambio de aceite y filtros', '2026-10-20');

-- La cuadrilla Centro (donde esta la recolectora de prueba) hace la Ruta Centro con el camion SBF 3344
INSERT INTO recorridos (id_recorrido, id_cuadrilla, id_ruta, id_camion, fecha_asignacion, hora_inicio, hora_fin) VALUES
(1, 1, 1, 4, '2026-11-06', '06:00', '12:00'),
(2, 2, 2, 3, '2026-11-06', '07:00', '13:00');

-- Zonas (barrios) que recorre cada recorrido
INSERT INTO zonas (id_zona, nombre, zona_asignada, id_recorrido) VALUES
(1, 'Ciudad Vieja', 'Municipio B', 1),
(2, 'Centro', 'Municipio B', 1),
(3, 'Tres Cruces', 'Municipio CH', 1),
(4, 'Punta Carretas', 'Municipio CH', 2),
(5, 'Pocitos', 'Municipio CH', 2),
(6, 'Malvín', 'Municipio E', 2),
(7, 'Carrasco', 'Municipio E', 2);

-- ---------- Tipos de residuo y contenedores ----------
INSERT INTO tipos_residuo (id_residuo, nombre, descripcion) VALUES
(1, 'Orgánico', 'Restos de comida y residuos biodegradables'),
(2, 'Reciclable', 'Plástico, papel, cartón, vidrio y metal'),
(3, 'No reciclable', 'Residuos generales sin clasificar'),
(4, 'Peligroso', 'Pilas, baterías y productos químicos');

-- Contenedores en distintos barrios y municipios de Montevideo
INSERT INTO contenedores (id_contenedor, calle, zona, municipio, estado, capacidad, id_residuo, latitud, longitud, ultima_recoleccion) VALUES
(1, 'Av. 18 de Julio 1200', 'Centro', 'B', 'Funcional', 1000, 2, -34.905500, -56.188900, '2026-11-05 07:30'),
(2, 'Rambla República de México 5500', 'Carrasco', 'E', 'Funcional', 1200, 3, -34.892800, -56.056500, '2026-11-01 08:00'),
(3, 'Bv. Artigas 1500', 'Tres Cruces', 'CH', 'Funcional', 800, 1, -34.894400, -56.166900, '2026-11-04 09:10'),
(4, '25 de Mayo 500', 'Ciudad Vieja', 'B', 'Funcional', 1000, 3, -34.906700, -56.207400, '2026-11-05 06:45'),
(5, 'Plaza Independencia', 'Centro', 'B', 'Funcional', 1200, 2, -34.906900, -56.199600, '2026-11-05 07:00'),
(6, 'Av. Brasil 2500', 'Pocitos', 'CH', 'Funcional', 1000, 3, -34.908300, -56.150800, '2026-11-02 10:00'),
(7, 'Rambla Gandhi 600', 'Punta Carretas', 'CH', 'Funcional', 800, 2, -34.922100, -56.158800, '2026-11-05 08:15'),
(8, 'Av. Italia 4500', 'Malvín', 'E', 'Funcional', 1000, 1, -34.885500, -56.111000, '2026-11-04 11:00'),
(9, 'Av. Agraciada 3000', 'Paso Molino', 'C', 'Funcional', 1000, 3, -34.867600, -56.206900, '2026-11-05 06:30'),
(10, 'Av. Millán 4000', 'Prado', 'C', 'Funcional', 800, 1, -34.854500, -56.207800, '2026-11-03 07:20'),
(11, 'Av. Carlos María Ramírez 1500', 'Cerro', 'A', 'Funcional', 1200, 3, -34.883100, -56.250300, '2026-10-31 09:00'),
(12, 'Av. Gral. Flores 4500', 'Piedras Blancas', 'D', 'Funcional', 1000, 3, -34.835900, -56.155300, '2026-11-05 10:30'),
(13, 'Camino Maldonado 6000', 'Punta de Rieles', 'F', 'Funcional', 1000, 2, -34.832600, -56.104100, '2026-11-04 12:00'),
(14, 'Av. Garzón 1500', 'Colón', 'G', 'Funcional', 800, 3, -34.798700, -56.226000, '2026-11-02 08:40'),
(15, 'Av. 8 de Octubre 3500', 'Unión', 'E', 'Funcional', 1000, 4, -34.876800, -56.144900, '2026-11-05 09:00'),
(16, 'Av. Rivera 3000', 'Pocitos', 'CH', 'Funcional', 1000, 4, -34.897900, -56.159500, '2026-11-05 09:30');

-- No hay incidencias de prueba: se cargan desde el sistema (las reporta el personal o los vecinos).

-- ---------- Instalaciones ----------
INSERT INTO instalaciones (id_instalacion, nombre, calle, zona, capacidad_actual, capacidad_max, id_operario, latitud, longitud) VALUES
(1, 'Centro de Acopio Norte', 'Camino Carrasco 4500', 'Malvín Norte', 3500, 10000, 2, -34.870900, -56.106600),
(2, 'Vertedero Felipe Cardoso', 'Camino Felipe Cardoso', 'Punta de Rieles', 50000, 200000, 2, -34.817600, -56.121400),
(3, 'Centro de Acopio Oeste', 'Ruta 1 km 8', 'Paso de la Arena', 6800, 8000, NULL, -34.838000, -56.290000);

INSERT INTO centros_acopio (id_instalacion) VALUES (1), (3);
INSERT INTO vertederos (id_instalacion) VALUES (2);

INSERT INTO maquinaria (id_maquinaria, nombre, tipo, estado) VALUES
(1, 'Prensa compactadora', 'Compactadora', 'Operativa'),
(2, 'Cinta clasificadora', 'Clasificadora', 'En reparación');

INSERT INTO instalacion_maquinaria (id_instalacion, id_maquinaria) VALUES (1, 1), (1, 2);
INSERT INTO centro_residuo (id_instalacion, id_residuo) VALUES (1, 1), (1, 2), (3, 2), (3, 3);
INSERT INTO centro_vertedero (id_centro, id_vertedero) VALUES (1, 2), (3, 2);

INSERT INTO plantas_procesamiento (id_planta, nombre, calle, zona, id_centro) VALUES
(1, 'Planta de Reciclaje Oeste', 'Ruta 1 km 10', 'Paso de la Arena', 1);

-- ---------- Agregado en la 3ra entrega ----------

INSERT INTO parametros (nombre, valor, descripcion) VALUES
('dias_alerta_desborde', '2', 'Días que puede estar un contenedor desbordado sin atender antes de avisar'),
('porcentaje_centro_lleno', '80', 'Porcentaje de capacidad desde el que se avisa que un centro está casi lleno'),
('aprobar_registros', 'si', 'Si un administrador tiene que aprobar a los vecinos que se registran (si / no)'),
('idioma', 'es', 'Idioma del sistema (es = español, en = inglés)'),
('kilos_por_litro', '0.18', 'Kilos que pesa cada litro de residuos (para simular lo recolectado)'),
('intentos_login', '5', 'Intentos fallidos de contraseña permitidos antes de bloquear el correo (0 = sin límite)'),
('minutos_bloqueo', '15', 'Minutos que queda bloqueado un correo después de muchos intentos fallidos');

INSERT INTO repuestos (id_repuesto, nombre, tipo, id_residuo, cantidad, cantidad_minima, id_instalacion) VALUES
(1, 'Contenedor orgánico 1000 L', 'Contenedor', 1, 6, 3, 1),
(2, 'Contenedor reciclable 1200 L', 'Contenedor', 2, 2, 3, 1),
(3, 'Contenedor no reciclable 800 L', 'Contenedor', 3, 4, 2, 3),
(4, 'Tapa de contenedor', 'Tapa', NULL, 15, 5, 1),
(5, 'Rueda de contenedor', 'Rueda', NULL, 3, 8, 3);
-- Recolecciones simuladas de octubre y noviembre (kilos que se sacaron cada vez que se vacio un contenedor)
INSERT INTO recolecciones (id_contenedor, fecha, kilos) VALUES
(1, '2026-10-01 07:15:00', 170.53),
(1, '2026-10-04 12:45:00', 157.59),
(1, '2026-10-07 13:15:00', 90.23),
(1, '2026-10-10 07:00:00', 115.85),
(1, '2026-10-13 07:45:00', 91.03),
(1, '2026-10-16 13:30:00', 108.92),
(1, '2026-10-19 10:30:00', 178.19),
(1, '2026-10-22 11:45:00', 179.97),
(1, '2026-10-25 07:30:00', 98.07),
(1, '2026-10-28 10:30:00', 131.17),
(1, '2026-10-31 10:00:00', 166.3),
(1, '2026-11-03 11:30:00', 131.5),
(2, '2026-10-01 13:00:00', 153.95),
(2, '2026-10-05 13:45:00', 207.01),
(2, '2026-10-09 10:45:00', 168.91),
(2, '2026-10-13 13:00:00', 170.16),
(2, '2026-10-17 08:00:00', 211.97),
(2, '2026-10-21 07:45:00', 158.76),
(2, '2026-10-25 13:15:00', 199.76),
(2, '2026-10-29 07:45:00', 180.82),
(2, '2026-11-02 10:45:00', 180.33),
(3, '2026-10-02 12:45:00', 86.58),
(3, '2026-10-06 10:00:00', 74.38),
(3, '2026-10-10 13:45:00', 97.92),
(3, '2026-10-14 09:30:00', 121.87),
(3, '2026-10-18 13:45:00', 130.17),
(3, '2026-10-22 06:30:00', 94.8),
(3, '2026-10-26 07:45:00', 121.63),
(3, '2026-10-30 10:45:00', 138.38),
(3, '2026-11-03 08:45:00', 132.66),
(4, '2026-10-02 10:45:00', 123.31),
(4, '2026-10-04 09:45:00', 103.17),
(4, '2026-10-06 06:00:00', 90.02),
(4, '2026-10-08 07:15:00', 112.31),
(4, '2026-10-10 06:30:00', 150.16),
(4, '2026-10-12 11:15:00', 90.46),
(4, '2026-10-14 13:15:00', 139.12),
(4, '2026-10-16 13:15:00', 176.24),
(4, '2026-10-18 10:00:00', 127.6),
(4, '2026-10-20 08:15:00', 160.51),
(4, '2026-10-22 10:45:00', 125.14),
(4, '2026-10-24 08:15:00', 123.84),
(4, '2026-10-26 07:45:00', 169.9),
(4, '2026-10-28 06:30:00', 148.16),
(4, '2026-10-30 06:00:00', 135.93),
(4, '2026-11-01 11:30:00', 130.51),
(4, '2026-11-03 07:45:00', 175.39),
(4, '2026-11-05 08:15:00', 143.21),
(5, '2026-10-01 11:45:00', 172.61),
(5, '2026-10-05 07:45:00', 111.69),
(5, '2026-10-09 08:00:00', 178.01),
(5, '2026-10-13 06:45:00', 201.75),
(5, '2026-10-17 09:45:00', 184.78),
(5, '2026-10-21 06:15:00', 177.73),
(5, '2026-10-25 09:15:00', 125.32),
(5, '2026-10-29 08:45:00', 137.46),
(5, '2026-11-02 12:00:00', 149.38),
(6, '2026-10-01 08:30:00', 131.75),
(6, '2026-10-04 12:30:00', 132.09),
(6, '2026-10-07 11:45:00', 130.01),
(6, '2026-10-10 10:00:00', 166.31),
(6, '2026-10-13 09:00:00', 175.83),
(6, '2026-10-16 13:30:00', 126.72),
(6, '2026-10-19 08:00:00', 121.68),
(6, '2026-10-22 08:30:00', 140.58),
(6, '2026-10-25 09:00:00', 145.87),
(6, '2026-10-28 10:15:00', 141.29),
(6, '2026-10-31 10:15:00', 99.13),
(6, '2026-11-03 10:00:00', 175.94),
(7, '2026-10-01 06:30:00', 124.01),
(7, '2026-10-03 08:00:00', 108.48),
(7, '2026-10-05 10:00:00', 92.95),
(7, '2026-10-07 11:15:00', 90.78),
(7, '2026-10-09 07:00:00', 101.64),
(7, '2026-10-11 10:45:00', 141.51),
(7, '2026-10-13 06:00:00', 88.94),
(7, '2026-10-15 10:00:00', 78.76),
(7, '2026-10-17 11:30:00', 128.14),
(7, '2026-10-19 06:45:00', 94.76),
(7, '2026-10-21 09:45:00', 102.68),
(7, '2026-10-23 07:30:00', 134.01),
(7, '2026-10-25 10:45:00', 93.02),
(7, '2026-10-27 11:00:00', 124.95),
(7, '2026-10-29 12:00:00', 92.26),
(7, '2026-10-31 11:30:00', 124.35),
(7, '2026-11-02 07:30:00', 127.29),
(7, '2026-11-04 11:00:00', 111.04),
(8, '2026-10-02 06:00:00', 104.59),
(8, '2026-10-05 07:00:00', 93.36),
(8, '2026-10-08 11:30:00', 92.59),
(8, '2026-10-11 08:30:00', 127.38),
(8, '2026-10-14 12:15:00', 159.89),
(8, '2026-10-17 09:15:00', 106.51),
(8, '2026-10-20 06:45:00', 170.02),
(8, '2026-10-23 08:15:00', 92.1),
(8, '2026-10-26 10:45:00', 127.61),
(8, '2026-10-29 11:15:00', 174.98),
(8, '2026-11-01 07:15:00', 139.77),
(8, '2026-11-04 10:30:00', 146.97),
(9, '2026-10-03 06:00:00', 146.51),
(9, '2026-10-06 09:45:00', 95.25),
(9, '2026-10-09 09:15:00', 173.45),
(9, '2026-10-12 12:30:00', 175.59),
(9, '2026-10-15 09:30:00', 120.93),
(9, '2026-10-18 09:30:00', 122.32),
(9, '2026-10-21 13:45:00', 170.73),
(9, '2026-10-24 10:15:00', 104.25),
(9, '2026-10-27 12:15:00', 133.64),
(9, '2026-10-30 06:30:00', 163.24),
(9, '2026-11-02 12:00:00', 147.52),
(9, '2026-11-05 07:30:00', 123.55),
(10, '2026-10-03 08:00:00', 143.91),
(10, '2026-10-06 06:15:00', 133.19),
(10, '2026-10-09 10:00:00', 140.8),
(10, '2026-10-12 12:15:00', 141.49),
(10, '2026-10-15 07:00:00', 82.22),
(10, '2026-10-18 12:30:00', 111.42),
(10, '2026-10-21 10:00:00', 125.09),
(10, '2026-10-24 09:15:00', 136.72),
(10, '2026-10-27 10:30:00', 97.72),
(10, '2026-10-30 12:15:00', 142.28),
(10, '2026-11-02 06:45:00', 139.62),
(10, '2026-11-05 11:15:00', 123.98),
(11, '2026-10-01 11:15:00', 125.71),
(11, '2026-10-04 07:00:00', 198.93),
(11, '2026-10-07 07:30:00', 140.69),
(11, '2026-10-10 08:15:00', 121.66),
(11, '2026-10-13 11:45:00', 119.6),
(11, '2026-10-16 07:45:00', 136.36),
(11, '2026-10-19 10:00:00', 145.56),
(11, '2026-10-22 13:00:00', 168.0),
(11, '2026-10-25 09:30:00', 181.82),
(11, '2026-10-28 13:30:00', 116.92),
(11, '2026-10-31 07:45:00', 192.02),
(11, '2026-11-03 10:15:00', 126.18),
(12, '2026-10-02 11:45:00', 126.28),
(12, '2026-10-05 08:15:00', 162.57),
(12, '2026-10-08 09:30:00', 158.29),
(12, '2026-10-11 11:45:00', 164.85),
(12, '2026-10-14 12:15:00', 103.89),
(12, '2026-10-17 08:45:00', 114.09),
(12, '2026-10-20 07:30:00', 113.98),
(12, '2026-10-23 13:30:00', 103.08),
(12, '2026-10-26 10:15:00', 119.86),
(12, '2026-10-29 08:00:00', 114.11),
(12, '2026-11-01 13:45:00', 125.95),
(12, '2026-11-04 11:45:00', 120.32),
(13, '2026-10-03 09:45:00', 121.69),
(13, '2026-10-07 11:00:00', 173.06),
(13, '2026-10-11 08:00:00', 114.03),
(13, '2026-10-15 10:15:00', 120.06),
(13, '2026-10-19 12:45:00', 166.62),
(13, '2026-10-23 13:30:00', 91.06),
(13, '2026-10-27 09:00:00', 149.46),
(13, '2026-10-31 10:30:00', 91.6),
(13, '2026-11-04 09:45:00', 136.34),
(14, '2026-10-03 07:15:00', 110.81),
(14, '2026-10-06 06:00:00', 83.22),
(14, '2026-10-09 09:45:00', 86.86),
(14, '2026-10-12 08:30:00', 78.07),
(14, '2026-10-15 12:15:00', 79.78),
(14, '2026-10-18 10:45:00', 75.2),
(14, '2026-10-21 09:45:00', 119.37),
(14, '2026-10-24 11:30:00', 97.01),
(14, '2026-10-27 13:30:00', 107.16),
(14, '2026-10-30 11:30:00', 74.64),
(14, '2026-11-02 13:15:00', 73.57),
(14, '2026-11-05 11:30:00', 132.23),
(15, '2026-10-02 11:30:00', 136.11),
(15, '2026-10-05 12:30:00', 109.65),
(15, '2026-10-08 06:45:00', 167.66),
(15, '2026-10-11 10:45:00', 145.48),
(15, '2026-10-14 11:45:00', 118.61),
(15, '2026-10-17 11:30:00', 106.3),
(15, '2026-10-20 08:30:00', 176.76),
(15, '2026-10-23 12:00:00', 173.3),
(15, '2026-10-26 09:00:00', 131.74),
(15, '2026-10-29 09:45:00', 135.72),
(15, '2026-11-01 08:15:00', 161.25),
(15, '2026-11-04 10:45:00', 102.3),
(16, '2026-10-03 11:45:00', 137.94),
(16, '2026-10-06 09:15:00', 144.61),
(16, '2026-10-09 08:15:00', 101.81),
(16, '2026-10-12 06:00:00', 115.23),
(16, '2026-10-15 10:15:00', 102.53),
(16, '2026-10-18 11:15:00', 131.16),
(16, '2026-10-21 08:00:00', 157.62),
(16, '2026-10-24 08:45:00', 147.71),
(16, '2026-10-27 13:00:00', 117.06),
(16, '2026-10-30 11:00:00', 131.91),
(16, '2026-11-02 12:15:00', 171.01),
(16, '2026-11-05 08:00:00', 132.38);
