<?php
// Modelo Notificacion: guarda que vecino reporto cada incidencia.
// En el MER es una entidad debil de Vecino: su clave es (id_usuario, id_notificacion).
// Cada reporte de un vecino genera una incidencia (relacion Genera2).

class Notificacion
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Devuelve true si el vecino ya aviso de ese contenedor y todavia no se resolvio
    public function yaAviso($idUsuario, $idContenedor)
    {
        $stmt = $this->conn->prepare("SELECT n.id_notificacion FROM notificaciones n
                                      JOIN incidencias i ON n.id_incidencia = i.id_incidencia
                                      WHERE n.id_usuario = ? AND i.id_contenedor = ? AND i.estado <> 'Resuelta'");
        $stmt->bind_param("ii", $idUsuario, $idContenedor);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    // Cuando el personal cambia el estado de una incidencia, el aviso del vecino cambia igual
    // (Recibida -> En proceso -> Resuelta)
    public function cambiarEstado($idIncidencia, $estado)
    {
        if ($estado == "Abierta") {
            $estado = "Recibida";
        }
        $stmt = $this->conn->prepare("UPDATE notificaciones SET estado = ? WHERE id_incidencia = ?");
        $stmt->bind_param("si", $estado, $idIncidencia);
        $stmt->execute();
    }

    // Devuelve el id del vecino que aviso de esa incidencia (o null si la registro el personal)
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

    // Guarda el aviso. Como es entidad debil, su numero es el siguiente de ese vecino (1, 2, 3...).
    public function agregar($idUsuario, $idIncidencia, $descripcion)
    {
        // Buscamos el numero mas alto de los avisos de ese vecino
        $stmt = $this->conn->prepare("SELECT MAX(id_notificacion) AS ultimo FROM notificaciones WHERE id_usuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $ultimo = $stmt->get_result()->fetch_assoc()['ultimo'];

        // Si es su primer aviso, el numero es 1
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
