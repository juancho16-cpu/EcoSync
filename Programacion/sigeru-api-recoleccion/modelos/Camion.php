<?php

class Camion
{
    private $conn;

    private $consulta = "SELECT id_camion AS id, matricula, estado, disponibilidad, capacidad_carga, latitud, longitud FROM camiones";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerTodos()
    {
        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY id_camion");

        $camiones = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $camiones[] = $row;
        }
        return $camiones;
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE id_camion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function buscarPorMatricula($matricula)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE matricula = ?");
        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function agregar($datos)
    {
        $matricula = strtoupper($datos['matricula']);
        $estado = $datos['estado'];
        $disponibilidad = $datos['disponibilidad'];
        $capacidad = $datos['capacidad_carga'] ?? "";

        if ($capacidad == "") {
            $capacidad = null;
        }

        $stmt = $this->conn->prepare("INSERT INTO camiones (matricula, estado, disponibilidad, capacidad_carga) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $matricula, $estado, $disponibilidad, $capacidad);
        $stmt->execute();

        return $this->obtenerPorId($this->conn->insert_id);
    }

    public function modificar($id, $datos)
    {
        $matricula = strtoupper($datos['matricula']);
        $estado = $datos['estado'];
        $disponibilidad = $datos['disponibilidad'];
        $capacidad = $datos['capacidad_carga'] ?? "";

        if ($capacidad == "") {
            $capacidad = null;
        }

        $stmt = $this->conn->prepare("UPDATE camiones SET matricula = ?, estado = ?, disponibilidad = ?, capacidad_carga = ? WHERE id_camion = ?");
        $stmt->bind_param("ssssi", $matricula, $estado, $disponibilidad, $capacidad, $id);
        $stmt->execute();

        return $this->obtenerPorId($id);
    }

    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM camiones WHERE id_camion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}
