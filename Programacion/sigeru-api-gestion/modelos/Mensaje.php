<?php

class Mensaje
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function enviarAUsuario($idUsuario, $texto, $enlace)
    {
        $stmt = $this->conn->prepare("INSERT INTO mensajes (id_usuario, texto, enlace, fecha) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $idUsuario, $texto, $enlace);
        $stmt->execute();
    }

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
