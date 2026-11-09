
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS sigeru;
CREATE DATABASE sigeru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigeru;

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

CREATE TABLE cuadrillas (
    id_cuadrilla INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL
);

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

CREATE TABLE zonas (
    id_zona       INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    zona_asignada VARCHAR(100),
    id_recorrido  INT,
    FOREIGN KEY (id_recorrido) REFERENCES recorridos(id_recorrido) ON DELETE SET NULL
);

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

CREATE TABLE tipos_residuo (
    id_residuo  INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80) NOT NULL,
    descripcion TEXT
);

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

CREATE TABLE instalacion_maquinaria (
    id_instalacion INT NOT NULL,
    id_maquinaria  INT NOT NULL,
    PRIMARY KEY (id_instalacion, id_maquinaria),
    FOREIGN KEY (id_instalacion) REFERENCES instalaciones(id_instalacion) ON DELETE CASCADE,
    FOREIGN KEY (id_maquinaria)  REFERENCES maquinaria(id_maquinaria) ON DELETE CASCADE
);

CREATE TABLE centro_residuo (
    id_instalacion INT NOT NULL,
    id_residuo     INT NOT NULL,
    PRIMARY KEY (id_instalacion, id_residuo),
    FOREIGN KEY (id_instalacion) REFERENCES centros_acopio(id_instalacion) ON DELETE CASCADE,
    FOREIGN KEY (id_residuo)     REFERENCES tipos_residuo(id_residuo) ON DELETE CASCADE
);

CREATE TABLE centro_vertedero (
    id_centro    INT NOT NULL,
    id_vertedero INT NOT NULL,
    PRIMARY KEY (id_centro, id_vertedero),
    FOREIGN KEY (id_centro)    REFERENCES centros_acopio(id_instalacion) ON DELETE CASCADE,
    FOREIGN KEY (id_vertedero) REFERENCES vertederos(id_instalacion) ON DELETE CASCADE
);

CREATE TABLE plantas_procesamiento (
    id_planta INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL,
    calle     VARCHAR(100),
    zona      VARCHAR(100),
    id_centro INT,
    FOREIGN KEY (id_centro) REFERENCES centros_acopio(id_instalacion) ON DELETE SET NULL
);

CREATE TABLE mensajes (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    texto      VARCHAR(255) NOT NULL,
    enlace     VARCHAR(100),
    leido      BOOLEAN NOT NULL DEFAULT FALSE,
    fecha      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE parametros (
    nombre      VARCHAR(50) PRIMARY KEY,
    valor       VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200)
);

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

CREATE TABLE recolecciones (
    id_recoleccion INT AUTO_INCREMENT PRIMARY KEY,
    id_contenedor  INT NOT NULL,
    fecha          DATETIME NOT NULL,
    kilos          DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_contenedor) REFERENCES contenedores(id_contenedor) ON DELETE CASCADE
);

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
