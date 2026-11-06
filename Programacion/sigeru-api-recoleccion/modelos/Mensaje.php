<?php
// Modelo Mensaje: manda notificaciones a los usuarios (les aparecen en la campana del menu)

class Mensaje
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Le manda un mensaje a un usuario
    public function enviarAUsuario($idUsuario, $texto, $enlace)
    {
        $stmt = $this->conn->prepare("INSERT INTO mensajes (id_usuario, texto, enlace, fecha) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $idUsuario, $texto, $enlace);
        $stmt->execute();
    }

    // Le manda el mismo mensaje a todos los usuarios activos de un rol.
    // $excepto es el id de un usuario que no lo recibe (por ejemplo, el que hizo la accion).
    public function enviarARol($rol, $texto, $enlace, $excepto = 0)
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE rol = ? AND estado = 'Activo' AND id_usuario <> ?");
        $stmt->bind_param("si", $rol, $excepto);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $this->enviarAUsuario($row['id_usuario'], $texto, $enlace);
        }
    }

    // Le manda el mensaje a los recolectores de una cuadrilla
    public function enviarACuadrilla($idCuadrilla, $texto, $enlace)
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM recolectores WHERE id_cuadrilla = ?");
        $stmt->bind_param("i", $idCuadrilla);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $this->enviarAUsuario($row['id_usuario'], $texto, $enlace);
        }
    }
}
