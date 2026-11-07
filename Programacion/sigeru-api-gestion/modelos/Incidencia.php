<?php
// Modelo Incidencia: problemas en los contenedores (roto, desbordado, falta de recoleccion)

class Incidencia
{
    private $conn;

    // Consulta que trae la incidencia con los datos de su contenedor, del recorrido (cuadrilla) asignado
    // y del vecino que la reporto (si la reporto un vecino; la relacion esta en la tabla notificaciones)
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

    // Lista de incidencias, de la mas nueva a la mas vieja.
    // Se puede filtrar por estado, por contenedor, por fecha de deteccion (desde / hasta)
    // y por el vecino que las reporto. Si un filtro viene vacio no se usa.
    public function obtenerTodas($estado = "", $idContenedor = "", $desde = "", $hasta = "", $idVecino = "")
    {
        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY i.fecha_deteccion DESC, i.id_incidencia DESC");

        $incidencias = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Si no cumple con algun filtro, la salteamos
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

    // Registra una incidencia nueva. Queda Abierta y con la fecha de hoy.
    // $referencia es donde esta exactamente el problema (puede venir vacia)
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

    // Seguimiento: cambia el estado, la prioridad y el recorrido (cuadrilla) que la resuelve.
    // Si queda Resuelta se guarda la fecha de resolucion.
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

    // Cuantas incidencias sin resolver tiene un contenedor
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

    // Devuelve la cuadrilla de un recorrido
    public function cuadrillaDelRecorrido($idRecorrido)
    {
        $stmt = $this->conn->prepare("SELECT id_cuadrilla FROM recorridos WHERE id_recorrido = ?");
        $stmt->bind_param("i", $idRecorrido);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['id_cuadrilla'];
    }

    // Devuelve true si ese recorrido es de la cuadrilla del recolector
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
