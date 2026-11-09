<?php

class Mantenimiento
{
    private $conn;

    private $consulta = "SELECT id_camion, nro_mantenimiento AS nro, tipo, costo, descripcion, fecha FROM mantenimientos";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerDelCamion($idCamion)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE id_camion = ? ORDER BY fecha DESC, nro_mantenimiento DESC");
        $stmt->bind_param("i", $idCamion);
        $stmt->execute();
        $result = $stmt->get_result();

        $mantenimientos = [];
        while ($row = $result->fetch_assoc()) {
            $mantenimientos[] = $row;
        }
        return $mantenimientos;
    }

    public function obtenerUno($idCamion, $nro)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE id_camion = ? AND nro_mantenimiento = ?");
        $stmt->bind_param("ii", $idCamion, $nro);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function agregar($datos)
    {
        $idCamion = $datos['id_camion'];

        $stmt = $this->conn->prepare("SELECT MAX(nro_mantenimiento) AS ultimo FROM mantenimientos WHERE id_camion = ?");
        $stmt->bind_param("i", $idCamion);
        $stmt->execute();
        $ultimo = $stmt->get_result()->fetch_assoc()['ultimo'];

        if ($ultimo == null) {
            $nro = 1;
        } else {
            $nro = $ultimo + 1;
        }

        $tipo = $datos['tipo'];
        $costo = $datos['costo'] ?? "";
        $descripcion = $datos['descripcion'] ?? "";
        $fecha = $datos['fecha'];
        if ($costo == "") $costo = null;

        $stmt = $this->conn->prepare("INSERT INTO mantenimientos (id_camion, nro_mantenimiento, tipo, costo, descripcion, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $idCamion, $nro, $tipo, $costo, $descripcion, $fecha);
        $stmt->execute();

        return $this->obtenerUno($idCamion, $nro);
    }

    public function modificar($idCamion, $nro, $datos)
    {
        $tipo = $datos['tipo'];
        $costo = $datos['costo'] ?? "";
        $descripcion = $datos['descripcion'] ?? "";
        $fecha = $datos['fecha'];
        if ($costo == "") $costo = null;

        $stmt = $this->conn->prepare("UPDATE mantenimientos SET tipo = ?, costo = ?, descripcion = ?, fecha = ? WHERE id_camion = ? AND nro_mantenimiento = ?");
        $stmt->bind_param("ssssii", $tipo, $costo, $descripcion, $fecha, $idCamion, $nro);
        $stmt->execute();

        return $this->obtenerUno($idCamion, $nro);
    }

    public function eliminar($idCamion, $nro)
    {
        $stmt = $this->conn->prepare("DELETE FROM mantenimientos WHERE id_camion = ? AND nro_mantenimiento = ?");
        $stmt->bind_param("ii", $idCamion, $nro);
        $stmt->execute();
    }
}
