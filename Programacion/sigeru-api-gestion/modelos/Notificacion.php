<?php

class Notificacion
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function yaAviso($idUsuario, $idContenedor)
    {
        $stmt = $this->conn->prepare("SELECT n.id_notificacion FROM notificaciones n
                                      JOIN incidencias i ON n.id_incidencia = i.id_incidencia
                                      WHERE n.id_usuario = ? AND i.id_contenedor = ? AND i.estado <> 'Resuelta'");
        $stmt->bind_param("ii", $idUsuario, $idContenedor);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    public function cambiarEstado($idIncidencia, $estado)
    {
        if ($estado == "Abierta") {
            $estado = "Recibida";
        }
        $stmt = $this->conn->prepare("UPDATE notificaciones SET estado = ? WHERE id_incidencia = ?");
        $stmt->bind_param("si", $estado, $idIncidencia);
        $stmt->execute();
    }

    public function vecinoDeLaIncidencia($idIncidencia)
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM notificaciones WHERE id_incidencia = ?");
        $stmt->bind_param("i", $idIncidencia);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();

        if ($fila == null) {
            return null;
        }
        return $fila['id_usuario'];
    }

    public function agregar($idUsuario, $idIncidencia, $descripcion)
    {
        
        $stmt = $this->conn->prepare("SELECT MAX(id_notificacion) AS ultimo FROM notificaciones WHERE id_usuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $ultimo = $stmt->get_result()->fetch_assoc()['ultimo'];

        if ($ultimo == null) {
            $numero = 1;
        } else {
            $numero = $ultimo + 1;
        }

        $stmt = $this->conn->prepare("INSERT INTO notificaciones (id_usuario, id_notificacion, estado, descripcion, fecha, id_incidencia)
                                      VALUES (?, ?, 'Recibida', ?, CURDATE(), ?)");
        $stmt->bind_param("iisi", $idUsuario, $numero, $descripcion, $idIncidencia);
        $stmt->execute();

        return $numero;
    }
}
