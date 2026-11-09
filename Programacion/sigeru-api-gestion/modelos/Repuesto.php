<?php

class Repuesto
{
    private $conn;

    private $consulta = "SELECT r.id_repuesto AS id, r.nombre, r.tipo, r.id_residuo, t.nombre AS residuo,
                                r.cantidad, r.cantidad_minima, r.id_instalacion, i.nombre AS instalacion
                         FROM repuestos r
                         LEFT JOIN tipos_residuo t ON r.id_residuo = t.id_residuo
                         LEFT JOIN instalaciones i ON r.id_instalacion = i.id_instalacion";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerTodos()
    {
        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY r.tipo, r.nombre");

        $repuestos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $repuestos[] = $row;
        }
        return $repuestos;
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE r.id_repuesto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function obtenerConPocoStock()
    {
        $result = mysqli_query($this->conn, $this->consulta . " WHERE r.cantidad <= r.cantidad_minima ORDER BY r.nombre");

        $repuestos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $repuestos[] = $row;
        }
        return $repuestos;
    }

    public function agregar($datos)
    {
        $nombre = $datos['nombre'];
        $tipo = $datos['tipo'];
        $residuo = $this->vacioANull($datos['id_residuo'] ?? "");
        $cantidad = $datos['cantidad'];
        $minima = $datos['cantidad_minima'];
        $instalacion = $this->vacioANull($datos['id_instalacion'] ?? "");

        $stmt = $this->conn->prepare("INSERT INTO repuestos (nombre, tipo, id_residuo, cantidad, cantidad_minima, id_instalacion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiii", $nombre, $tipo, $residuo, $cantidad, $minima, $instalacion);
        $stmt->execute();

        return $this->obtenerPorId($this->conn->insert_id);
    }

    public function modificar($id, $datos)
    {
        $nombre = $datos['nombre'];
        $tipo = $datos['tipo'];
        $residuo = $this->vacioANull($datos['id_residuo'] ?? "");
        $cantidad = $datos['cantidad'];
        $minima = $datos['cantidad_minima'];
        $instalacion = $this->vacioANull($datos['id_instalacion'] ?? "");

        $stmt = $this->conn->prepare("UPDATE repuestos SET nombre = ?, tipo = ?, id_residuo = ?, cantidad = ?, cantidad_minima = ?, id_instalacion = ?
                                      WHERE id_repuesto = ?");
        $stmt->bind_param("ssiiiii", $nombre, $tipo, $residuo, $cantidad, $minima, $instalacion, $id);
        $stmt->execute();

        return $this->obtenerPorId($id);
    }

    public function cambiarCantidad($id, $cambio)
    {
        $stmt = $this->conn->prepare("UPDATE repuestos SET cantidad = cantidad + ? WHERE id_repuesto = ?");
        $stmt->bind_param("ii", $cambio, $id);
        $stmt->execute();

        return $this->obtenerPorId($id);
    }

    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM repuestos WHERE id_repuesto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    private function vacioANull($valor)
    {
        if ($valor == "") {
            return null;
        }
        return $valor;
    }
}
