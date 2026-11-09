<?php

class Centro
{
    private $conn;

    private $consulta = "SELECT i.id_instalacion AS id, i.nombre, i.calle, i.zona, i.capacidad_actual,
                                i.capacidad_max, i.id_operario,
                                CONCAT(u.primer_nombre, ' ', u.primer_apellido) AS operario
                         FROM centros_acopio c
                         JOIN instalaciones i ON c.id_instalacion = i.id_instalacion
                         LEFT JOIN usuarios u ON i.id_operario = u.id_usuario";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerTodos()
    {
        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY i.nombre");

        $centros = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['residuos'] = $this->obtenerResiduos($row['id']);
            $centros[] = $row;
        }
        return $centros;
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE i.id_instalacion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $centro = $stmt->get_result()->fetch_assoc();

        if ($centro != null) {
            $centro['residuos'] = $this->obtenerResiduos($id);
        }
        return $centro;
    }

    public function obtenerResiduos($id)
    {
        $stmt = $this->conn->prepare("SELECT t.id_residuo, t.nombre FROM centro_residuo cr
                                      JOIN tipos_residuo t ON cr.id_residuo = t.id_residuo
                                      WHERE cr.id_instalacion = ? ORDER BY t.nombre");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $residuos = [];
        while ($row = $result->fetch_assoc()) {
            $residuos[] = $row;
        }
        return $residuos;
    }

    public function obtenerOperarios()
    {
        $result = mysqli_query($this->conn, "SELECT o.id_usuario AS id, CONCAT(u.primer_nombre, ' ', u.primer_apellido) AS nombre
                                             FROM operarios o JOIN usuarios u ON o.id_usuario = u.id_usuario
                                             ORDER BY u.primer_nombre");
        $operarios = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $operarios[] = $row;
        }
        return $operarios;
    }

    public function esOperario($idUsuario)
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM operarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    public function agregar($datos)
    {
        $stmt = $this->conn->prepare("INSERT INTO instalaciones (nombre, calle, zona, capacidad_actual, capacidad_max, id_operario) VALUES (?, ?, ?, ?, ?, ?)");
        $this->cargarDatos($stmt, $datos, null);
        $stmt->execute();

        $id = $this->conn->insert_id;

        $stmt = $this->conn->prepare("INSERT INTO centros_acopio (id_instalacion) VALUES (?)");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $this->guardarResiduos($id, $datos['residuos'] ?? []);
        return $this->obtenerPorId($id);
    }

    public function modificar($id, $datos)
    {
        $stmt = $this->conn->prepare("UPDATE instalaciones SET nombre = ?, calle = ?, zona = ?, capacidad_actual = ?, capacidad_max = ?, id_operario = ? WHERE id_instalacion = ?");
        $this->cargarDatos($stmt, $datos, $id);
        $stmt->execute();

        $this->guardarResiduos($id, $datos['residuos'] ?? []);
        return $this->obtenerPorId($id);
    }

    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM instalaciones WHERE id_instalacion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    private function cargarDatos($stmt, $datos, $id)
    {
        $nombre = $datos['nombre'];
        $calle = $datos['calle'] ?? "";
        $zona = $datos['zona'] ?? "";
        $capacidadActual = $datos['capacidad_actual'] ?? "";
        $capacidadMax = $datos['capacidad_max'] ?? "";
        $idOperario = $datos['id_operario'] ?? "";

        if ($capacidadActual == "") $capacidadActual = null;
        if ($capacidadMax == "") $capacidadMax = null;
        if ($idOperario == "") $idOperario = null;

        if ($id == null) {
            $stmt->bind_param("sssssi", $nombre, $calle, $zona, $capacidadActual, $capacidadMax, $idOperario);
        } else {
            $stmt->bind_param("sssssii", $nombre, $calle, $zona, $capacidadActual, $capacidadMax, $idOperario, $id);
        }
    }

    private function guardarResiduos($id, $residuos)
    {
        $stmt = $this->conn->prepare("DELETE FROM centro_residuo WHERE id_instalacion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        foreach ($residuos as $idResiduo) {
            $stmt = $this->conn->prepare("INSERT INTO centro_residuo (id_instalacion, id_residuo) VALUES (?, ?)");
            $stmt->bind_param("ii", $id, $idResiduo);
            $stmt->execute();
        }
    }
}
