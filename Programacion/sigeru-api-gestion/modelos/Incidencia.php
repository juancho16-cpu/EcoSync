<?php

class Incidencia
{
    private $conn;

    private $consulta = "SELECT i.id_incidencia AS id, i.id_contenedor, i.tipo, i.estado, i.prioridad,
                                i.fecha_deteccion, i.fecha, i.fecha_resolucion, i.descripcion, i.referencia,
                                i.zona_incidencia, i.id_recorrido, c.calle, c.municipio, c.latitud, c.longitud,
                                cu.nombre AS cuadrilla, ru.nombre AS ruta,
                                n.id_usuario AS id_vecino, CONCAT(uv.primer_nombre, ' ', uv.primer_apellido) AS vecino
                         FROM incidencias i
                         JOIN contenedores c ON i.id_contenedor = c.id_contenedor
                         LEFT JOIN recorridos r ON i.id_recorrido = r.id_recorrido
                         LEFT JOIN cuadrillas cu ON r.id_cuadrilla = cu.id_cuadrilla
                         LEFT JOIN rutas ru ON r.id_ruta = ru.id_ruta
                         LEFT JOIN notificaciones n ON n.id_incidencia = i.id_incidencia
                         LEFT JOIN usuarios uv ON n.id_usuario = uv.id_usuario";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerTodas($estado = "", $idContenedor = "", $desde = "", $hasta = "", $idVecino = "")
    {
        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY i.fecha_deteccion DESC, i.id_incidencia DESC");

        $incidencias = [];
        while ($row = mysqli_fetch_assoc($result)) {
            
            if ($estado != "" && $row['estado'] != $estado) continue;
            if ($idContenedor != "" && $row['id_contenedor'] != $idContenedor) continue;
            if ($desde != "" && $row['fecha_deteccion'] < $desde) continue;
            if ($hasta != "" && $row['fecha_deteccion'] > $hasta) continue;
            if ($idVecino != "" && $row['id_vecino'] != $idVecino) continue;

            $incidencias[] = $row;
        }
        return $incidencias;
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE i.id_incidencia = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function agregar($idContenedor, $zona, $tipo, $prioridad, $descripcion, $referencia = "")
    {
        if ($referencia == "") {
            $referencia = null;
        }
        $stmt = $this->conn->prepare("INSERT INTO incidencias (id_contenedor, fecha_deteccion, zona_incidencia, tipo, estado, prioridad, fecha, descripcion, referencia)
                                      VALUES (?, CURDATE(), ?, ?, 'Abierta', ?, CURDATE(), ?, ?)");
        $stmt->bind_param("isssss", $idContenedor, $zona, $tipo, $prioridad, $descripcion, $referencia);
        $stmt->execute();

        return $this->obtenerPorId($this->conn->insert_id);
    }

    public function modificarSeguimiento($id, $estado, $prioridad, $idRecorrido)
    {
        if ($estado == "Resuelta") {
            $stmt = $this->conn->prepare("UPDATE incidencias SET estado = ?, prioridad = ?, id_recorrido = ?, fecha = CURDATE(), fecha_resolucion = CURDATE()
                                          WHERE id_incidencia = ?");
        } else {
            $stmt = $this->conn->prepare("UPDATE incidencias SET estado = ?, prioridad = ?, id_recorrido = ?, fecha = CURDATE(), fecha_resolucion = NULL
                                          WHERE id_incidencia = ?");
        }
        $stmt->bind_param("ssii", $estado, $prioridad, $idRecorrido, $id);
        $stmt->execute();

        return $this->obtenerPorId($id);
    }

    public function contarSinResolver($idContenedor)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cantidad FROM incidencias WHERE id_contenedor = ? AND estado <> 'Resuelta'");
        $stmt->bind_param("i", $idContenedor);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['cantidad'];
    }

    public function existeRecorrido($idRecorrido)
    {
        $stmt = $this->conn->prepare("SELECT id_recorrido FROM recorridos WHERE id_recorrido = ?");
        $stmt->bind_param("i", $idRecorrido);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    public function cuadrillaDelRecorrido($idRecorrido)
    {
        $stmt = $this->conn->prepare("SELECT id_cuadrilla FROM recorridos WHERE id_recorrido = ?");
        $stmt->bind_param("i", $idRecorrido);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['id_cuadrilla'];
    }

    public function esRecorridoDelRecolector($idRecorrido, $idUsuario)
    {
        $stmt = $this->conn->prepare("SELECT r.id_recorrido FROM recorridos r
                                      JOIN recolectores re ON r.id_cuadrilla = re.id_cuadrilla
                                      WHERE r.id_recorrido = ? AND re.id_usuario = ?");
        $stmt->bind_param("ii", $idRecorrido, $idUsuario);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }
}
