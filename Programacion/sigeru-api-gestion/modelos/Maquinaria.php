<?php

class Maquinaria
{
    private $conn;

    private $consulta = "SELECT m.id_maquinaria AS id, m.nombre, m.tipo, m.estado,
                                im.id_instalacion, i.nombre AS instalacion
                         FROM maquinaria m
                         LEFT JOIN instalacion_maquinaria im ON m.id_maquinaria = im.id_maquinaria
                         LEFT JOIN instalaciones i ON im.id_instalacion = i.id_instalacion";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerTodos()
    {
        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY m.id_maquinaria");

        $maquinas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $maquinas[] = $row;
        }
        return $maquinas;
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE m.id_maquinaria = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function obtenerInstalaciones()
    {
        $result = mysqli_query($this->conn, "SELECT i.id_instalacion AS id, i.nombre, i.calle, i.zona,
                                                    i.capacidad_actual, i.capacidad_max, i.latitud, i.longitud,
                                                    IF(v.id_instalacion IS NULL, 'Centro de acopio', 'Vertedero') AS tipo
                                             FROM instalaciones i
                                             LEFT JOIN vertederos v ON i.id_instalacion = v.id_instalacion
                                             ORDER BY i.nombre");

        $instalaciones = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $instalaciones[] = $row;
        }
        return $instalaciones;
    }

    public function existeInstalacion($id)
    {
        $stmt = $this->conn->prepare("SELECT id_instalacion FROM instalaciones WHERE id_instalacion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    public function agregar($datos)
    {
        $nombre = $datos['nombre'];
        $tipo = $datos['tipo'] ?? "";
        $estado = $datos['estado'];

        $stmt = $this->conn->prepare("INSERT INTO maquinaria (nombre, tipo, estado) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $tipo, $estado);
        $stmt->execute();

        $id = $this->conn->insert_id;
        $this->guardarInstalacion($id, $datos['id_instalacion'] ?? "");
        return $this->obtenerPorId($id);
    }

    public function modificar($id, $datos)
    {
        $nombre = $datos['nombre'];
        $tipo = $datos['tipo'] ?? "";
        $estado = $datos['estado'];

        $stmt = $this->conn->prepare("UPDATE maquinaria SET nombre = ?, tipo = ?, estado = ? WHERE id_maquinaria = ?");
        $stmt->bind_param("sssi", $nombre, $tipo, $estado, $id);
        $stmt->execute();

        $this->guardarInstalacion($id, $datos['id_instalacion'] ?? "");
        return $this->obtenerPorId($id);
    }

    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM maquinaria WHERE id_maquinaria = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    private function guardarInstalacion($id, $idInstalacion)
    {
        $stmt = $this->conn->prepare("DELETE FROM instalacion_maquinaria WHERE id_maquinaria = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($idInstalacion != "") {
            $stmt = $this->conn->prepare("INSERT INTO instalacion_maquinaria (id_instalacion, id_maquinaria) VALUES (?, ?)");
            $stmt->bind_param("ii", $idInstalacion, $id);
            $stmt->execute();
        }
    }
}
