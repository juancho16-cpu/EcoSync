-- =====================================================================
-- SiGeRU - Sistema de Gestión de Residuos Urbanos
-- DDL de creación de la base de datos y sus tablas (primera versión)
-- Empresa: EcoSync
-- Motor: MySQL 8
--
-- ATENCION: este archivo BORRA la base de datos sigeru y la crea vacia (DROP DATABASE).
-- Se importa solo la PRIMERA VEZ que se instala el sistema en una computadora.
-- Si ya tienen datos cargados (usuarios, incidencias...) NO lo vuelvan a importar:
-- para los cambios nuevos de la base se usan los archivos de la carpeta "actualizaciones",
-- que agregan lo nuevo sin borrar nada.
-- =====================================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS sigeru;
CREATE DATABASE sigeru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigeru;

-- ---------------------------------------------------------------------
-- USUARIOS Y ROLES
-- Usuarios es el supertipo. Segun el rol, el usuario tiene ademas
-- un registro en vecinos, operarios o recolectores (RNE03: un solo rol).
-- ---------------------------------------------------------------------

CREATE TABLE usuarios (
    id_usuario       INT AUTO_INCREMENT PRIMARY KEY,
    primer_nombre    VARCHAR(50)  NOT NULL,
    segundo_nombre   VARCHAR(50),
    primer_apellido  VARCHAR(50)  NOT NULL,
    segundo_apellido VARCHAR(50),
    email            VARCHAR(120) NOT NULL UNIQUE,
    telefono         VARCHAR(255),     -- cifrado con AES-256-GCM (ver sigeru-api-usuarios/cifrado.php)
    contrasena       VARCHAR(255) NOT NULL,
    rol              ENUM('Administrador', 'Operario', 'Recolector', 'Vecino') NOT NULL,
    estado           ENUM('Sin verificar', 'Pendiente', 'Activo', 'Rechazado') NOT NULL DEFAULT 'Activo',
    codigo_verificacion VARCHAR(64),     -- resumen SHA-256 del codigo del enlace (no el codigo)
    fecha_registro   DATETIME DEFAULT CURRENT_TIMESTAMP
);
-- estado: un vecino que se registra queda "Sin verificar" hasta que abre el enlace del correo.
-- Despues queda "Pendiente" hasta que un administrador lo aprueba (o "Activo" si no hace falta aprobar).
-- Solo los usuarios "Activo" pueden iniciar sesion.

CREATE TABLE vecinos (
    id_usuario INT PRIMARY KEY,
    calle      VARCHAR(100),
    zona       VARCHAR(100),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE operarios (
    id_usuario   INT PRIMARY KEY,
    especialidad VARCHAR(100),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE administradores (
    id_usuario INT PRIMARY KEY,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- CUADRILLAS, RUTAS Y RECORRIDOS
-- ---------------------------------------------------------------------

CREATE TABLE cuadrillas (
    id_cuadrilla INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL
);

-- Relacion Integra: un recolector pertenece a una cuadrilla (N:1)
CREATE TABLE recolectores (
    id_usuario    INT PRIMARY KEY,
    id_cuadrilla  INT,
    fecha_ingreso DATE,
    FOREIGN KEY (id_usuario)   REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL
);

CREATE TABLE rutas (
    id_ruta     INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    distancia   DECIMAL(10,2)
);

CREATE TABLE camiones (
    id_camion       INT AUTO_INCREMENT PRIMARY KEY,
    matricula       VARCHAR(20) NOT NULL UNIQUE,
    estado          ENUM('Operativo', 'En mantenimiento', 'Fuera de servicio') NOT NULL DEFAULT 'Operativo',
    disponibilidad  ENUM('Disponible', 'En ruta', 'No disponible') NOT NULL DEFAULT 'Disponible',
    capacidad_carga DECIMAL(10,2),
    latitud         DECIMAL(9,6),
    longitud        DECIMAL(9,6)
);

-- Agregacion: Cuadrilla "recorre" Ruta (N:N).
-- La agregacion "Utiliza" un camion (N:1) con fecha y horario.
CREATE TABLE recorridos (
    id_recorrido      INT AUTO_INCREMENT PRIMARY KEY,
    id_cuadrilla      INT NOT NULL,
    id_ruta           INT NOT NULL,
    id_camion         INT,
    fecha_asignacion  DATE,
    hora_inicio       TIME,
    hora_fin          TIME,
    UNIQUE (id_cuadrilla, id_ruta),
    FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE CASCADE,
    FOREIGN KEY (id_ruta)      REFERENCES rutas(id_ruta) ON DELETE CASCADE,
    FOREIGN KEY (id_camion)    REFERENCES camiones(id_camion) ON DELETE SET NULL
);

-- Relacion Recorre: cada zona la recorre un recorrido (N:1)
CREATE TABLE zonas (
    id_zona       INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    zona_asignada VARCHAR(100),
    id_recorrido  INT,
    FOREIGN KEY (id_recorrido) REFERENCES recorridos(id_recorrido) ON DELETE SET NULL
);

-- Entidad debil Mantenimiento (relacion Tiene con Camion, 1:N)
CREATE TABLE mantenimientos (
    id_camion         INT NOT NULL,
    nro_mantenimiento INT NOT NULL,
    tipo              VARCHAR(80),
    costo             DECIMAL(10,2),
    descripcion       TEXT,
    fecha             DATE,
    PRIMARY KEY (id_camion, nro_mantenimiento),
    FOREIGN KEY (id_camion) REFERENCES camiones(id_camion) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- CONTENEDORES E INCIDENCIAS
-- ---------------------------------------------------------------------

CREATE TABLE tipos_residuo (
    id_residuo  INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80) NOT NULL,
    descripcion TEXT
);

-- Relacion Almacena: cada contenedor guarda un solo tipo de residuo (RNE01)
-- municipio: uno de los 8 municipios de Montevideo
-- latitud y longitud: ubicacion en el mapa
CREATE TABLE contenedores (
    id_contenedor      INT AUTO_INCREMENT PRIMARY KEY,
    calle              VARCHAR(100) NOT NULL,
    zona               VARCHAR(100) NOT NULL,
    municipio          ENUM('A', 'B', 'C', 'CH', 'D', 'E', 'F', 'G'),
    estado             ENUM('Funcional', 'Roto', 'Desbordado') NOT NULL DEFAULT 'Funcional',
    capacidad          DECIMAL(10,2),
    id_residuo         INT NOT NULL,
    latitud            DECIMAL(9,6),
    longitud           DECIMAL(9,6),
    ultima_recoleccion DATETIME,
    FOREIGN KEY (id_residuo) REFERENCES tipos_residuo(id_residuo)
);

-- Relacion Genera1: un contenedor genera muchas incidencias (1:N)
-- Relacion Resuelven: un recorrido (cuadrilla) resuelve muchas incidencias (1:N)
-- RNE02: una incidencia tiene un solo estado a la vez
-- tipo: que problema hay en el contenedor o alrededor (se usa para el top de desbordados)
-- referencia: donde esta exactamente el problema (lo escribe el que lo reporta)
-- fecha: ultimo cambio de estado. fecha_resolucion: dia en que quedo Resuelta
CREATE TABLE incidencias (
    id_incidencia    INT AUTO_INCREMENT PRIMARY KEY,
    id_contenedor    INT NOT NULL,
    fecha_deteccion  DATE,
    zona_incidencia  VARCHAR(100),
    tipo             ENUM('Desbordado', 'Roto', 'Falta de recolección', 'Basura en la calle') NOT NULL DEFAULT 'Desbordado',
    estado           ENUM('Abierta', 'En proceso', 'Resuelta') NOT NULL DEFAULT 'Abierta',
    prioridad        ENUM('Baja', 'Media', 'Alta') NOT NULL DEFAULT 'Media',
    fecha            DATE,
    fecha_resolucion DATE,
    descripcion      TEXT,
    referencia       VARCHAR(200),
    id_recorrido     INT,
    FOREIGN KEY (id_contenedor) REFERENCES contenedores(id_contenedor) ON DELETE CASCADE,
    FOREIGN KEY (id_recorrido)  REFERENCES recorridos(id_recorrido) ON DELETE SET NULL
);

-- Entidad debil Notificaciones (relacion Realizan con Vecino, 1:N)
-- Relacion Genera2: una notificacion genera una incidencia (1:1)
CREATE TABLE notificaciones (
    id_usuario      INT NOT NULL,
    id_notificacion INT NOT NULL,
    estado          VARCHAR(30),
    descripcion     TEXT,
    fecha           DATE,
    id_incidencia   INT UNIQUE,
    PRIMARY KEY (id_usuario, id_notificacion),
    FOREIGN KEY (id_usuario)    REFERENCES vecinos(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE SET NULL
);

-- ---------------------------------------------------------------------
-- INSTALACIONES (CENTROS DE ACOPIO Y VERTEDEROS)
-- Instalacion es el supertipo de CentroAcopio y Vertedero.
-- ---------------------------------------------------------------------

-- Relacion Centro_Asignado: un operario tiene asignadas instalaciones (1:N)
CREATE TABLE instalaciones (
    id_instalacion   INT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    calle            VARCHAR(100),
    zona             VARCHAR(100),
    capacidad_actual DECIMAL(10,2),
    capacidad_max    DECIMAL(10,2),
    id_operario      INT,
    latitud          DECIMAL(9,6),
    longitud         DECIMAL(9,6),
    FOREIGN KEY (id_operario) REFERENCES operarios(id_usuario) ON DELETE SET NULL
);

CREATE TABLE centros_acopio (
    id_instalacion INT PRIMARY KEY,
    FOREIGN KEY (id_instalacion) REFERENCES instalaciones(id_instalacion) ON DELETE CASCADE
);

CREATE TABLE vertederos (
    id_instalacion INT PRIMARY KEY,
    FOREIGN KEY (id_instalacion) REFERENCES instalaciones(id_instalacion) ON DELETE CASCADE
);

CREATE TABLE maquinaria (
    id_maquinaria INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    tipo          VARCHAR(80),
    estado        VARCHAR(30)
);

-- Relacion Posee: instalaciones y maquinaria (N:N)
CREATE TABLE instalacion_maquinaria (
    id_instalacion INT NOT NULL,
    id_maquinaria  INT NOT NULL,
    PRIMARY KEY (id_instalacion, id_maquinaria),
    FOREIGN KEY (id_instalacion) REFERENCES instalaciones(id_instalacion) ON DELETE CASCADE,
    FOREIGN KEY (id_maquinaria)  REFERENCES maquinaria(id_maquinaria) ON DELETE CASCADE
);

-- Relacion Recibe: tipos de residuo que gestiona cada centro (N:N) (RNE05)
CREATE TABLE centro_residuo (
    id_instalacion INT NOT NULL,
    id_residuo     INT NOT NULL,
    PRIMARY KEY (id_instalacion, id_residuo),
    FOREIGN KEY (id_instalacion) REFERENCES centros_acopio(id_instalacion) ON DELETE CASCADE,
    FOREIGN KEY (id_residuo)     REFERENCES tipos_residuo(id_residuo) ON DELETE CASCADE
);

-- Relacion Viaja2: traslados de centros de acopio a vertederos (N:N)
CREATE TABLE centro_vertedero (
    id_centro    INT NOT NULL,
    id_vertedero INT NOT NULL,
    PRIMARY KEY (id_centro, id_vertedero),
    FOREIGN KEY (id_centro)    REFERENCES centros_acopio(id_instalacion) ON DELETE CASCADE,
    FOREIGN KEY (id_vertedero) REFERENCES vertederos(id_instalacion) ON DELETE CASCADE
);

-- Relacion Viaja: cada planta recibe de un centro de acopio (N:1)
CREATE TABLE plantas_procesamiento (
    id_planta INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL,
    calle     VARCHAR(100),
    zona      VARCHAR(100),
    id_centro INT,
    FOREIGN KEY (id_centro) REFERENCES centros_acopio(id_instalacion) ON DELETE SET NULL
);

-- ---------------------------------------------------------------------
-- AGREGADO EN LA 3RA ENTREGA
-- ---------------------------------------------------------------------

-- Relacion Recibe: cada usuario recibe muchos mensajes (notificaciones de la campana) (1:N)
CREATE TABLE mensajes (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    texto      VARCHAR(255) NOT NULL,
    enlace     VARCHAR(100),
    leido      BOOLEAN NOT NULL DEFAULT FALSE,
    fecha      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Parametros del sistema que puede cambiar el administrador (el software es parametrizable)
CREATE TABLE parametros (
    nombre      VARCHAR(50) PRIMARY KEY,
    valor       VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200)
);

-- Inventario de repuestos: contenedores de repuesto, tapas, ruedas...
-- Relacion Guarda: una instalacion guarda muchos repuestos (1:N)
CREATE TABLE repuestos (
    id_repuesto     INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    tipo            ENUM('Contenedor', 'Tapa', 'Rueda', 'Otro') NOT NULL,
    id_residuo      INT,
    cantidad        INT NOT NULL DEFAULT 0,
    cantidad_minima INT NOT NULL DEFAULT 0,
    id_instalacion  INT,
    FOREIGN KEY (id_residuo)     REFERENCES tipos_residuo(id_residuo) ON DELETE SET NULL,
    FOREIGN KEY (id_instalacion) REFERENCES instalaciones(id_instalacion) ON DELETE SET NULL
);

-- Cada vez que se vacia un contenedor se guarda cuantos kilos se recolectaron (datos simulados)
-- Relacion Vacia: un contenedor tiene muchas recolecciones (1:N)
CREATE TABLE recolecciones (
    id_recoleccion INT AUTO_INCREMENT PRIMARY KEY,
    id_contenedor  INT NOT NULL,
    fecha          DATETIME NOT NULL,
    kilos          DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_contenedor) REFERENCES contenedores(id_contenedor) ON DELETE CASCADE
);

-- Auditoria: queda guardado quien entro, quien fallo la contraseña, quien creo, borro o cambio
-- el rol de un usuario, etc. Tambien se usa para bloquear un correo despues de muchos intentos fallidos.
-- Relacion Genera: un usuario genera muchos eventos (1:N). id_usuario es NULL si nadie habia iniciado sesion.
CREATE TABLE auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario   INT,
    email        VARCHAR(100),
    accion       VARCHAR(50) NOT NULL,
    detalle      VARCHAR(255),
    ip           VARCHAR(45),
    INDEX idx_auditoria_email (email, accion),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);
