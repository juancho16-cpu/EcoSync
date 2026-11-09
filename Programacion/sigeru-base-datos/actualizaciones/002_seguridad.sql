
SET NAMES utf8mb4;
USE sigeru;

CREATE TABLE IF NOT EXISTS auditoria (
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

INSERT IGNORE INTO parametros (nombre, valor, descripcion) VALUES
('intentos_login', '5', 'Intentos fallidos de contraseña permitidos antes de bloquear el correo (0 = sin límite)'),
('minutos_bloqueo', '15', 'Minutos que queda bloqueado un correo después de muchos intentos fallidos');
