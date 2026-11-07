<?php
// Modelo Mensaje: las notificaciones que le llegan a cada usuario (la campana del menu)

class Mensaje
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Los ultimos 30 mensajes de un usuario, del mas nuevo al mas viejo
    public function obtenerDelUsuario($idUsuario)
    {
        $stmt = $this->conn->prepare("SELECT id_mensaje AS id, texto, enlace, leido, fecha FROM mensajes
                                      WHERE id_usuario = ? ORDER BY fecha DESC, id_mensaje DESC LIMIT 30");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();

        $mensajes = [];
        while ($row = $result->fetch_assoc()) {
            $mensajes[] = $row;
        }
        return $mensajes;
    }

    // Cuantos mensajes no leyo todavia
    public function contarSinLeer($idUsuario)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cantidad FROM mensajes WHERE id_usuario = ? AND leido = FALSE");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['cantidad'];
    }

    // Marca un mensaje como leido (solo si es de ese usuario)
    public function marcarLeido($idMensaje, $idUsuario)
    {
        $stmt = $this->conn->prepare("UPDATE mensajes SET leido = TRUE WHERE id_mensaje = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $idMensaje, $idUsuario);
        $stmt->execute();
    }

    public function marcarTodosLeidos($idUsuario)
    {
        $stmt = $this->conn->prepare("UPDATE mensajes SET leido = TRUE WHERE id_usuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
    }

    // Le manda un mensaje a un usuario
    public function enviarAUsuario($idUsuario, $texto, $enlace)
    {
        $stmt = $this->conn->prepare("INSERT INTO mensajes (id_usuario, texto, enlace, fecha) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $idUsuario, $texto, $enlace);
        $stmt->execute();
    }

    // Le manda el mismo mensaje a todos los usuarios activos de un rol
    public function enviarARol($rol, $texto, $enlace)
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE rol = ? AND estado = 'Activo'");
        $stmt->bind_param("s", $rol);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $this->enviarAUsuario($row['id_usuario'], $texto, $enlace);
        }
    }
}
