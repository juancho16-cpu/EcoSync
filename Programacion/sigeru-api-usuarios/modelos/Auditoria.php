<?php

class Auditoria
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function registrar($idUsuario, $email, $accion, $detalle)
    {
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? "local";

        $stmt = $this->conn->prepare("INSERT INTO auditoria (id_usuario, email, accion, detalle, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $idUsuario, $email, $accion, $detalle, $ip);
        $stmt->execute();
    }

    public function obtenerUltimos($limite, $accion)
    {
        $sql = "SELECT a.id_auditoria, a.fecha, a.email, a.accion, a.detalle, a.ip,
                       CONCAT(u.primer_nombre, ' ', u.primer_apellido) AS usuario
                FROM auditoria a
                LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario";
        if ($accion != "") {
            $sql .= " WHERE a.accion = ?";
        }
        $sql .= " ORDER BY a.id_auditoria DESC LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        if ($accion != "") {
            $stmt->bind_param("si", $accion, $limite);
        } else {
            $stmt->bind_param("i", $limite);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $eventos = [];
        while ($row = $result->fetch_assoc()) {
            $eventos[] = $row;
        }
        return $eventos;
    }

    public function contarIntentosFallidos($email, $minutos)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cantidad FROM auditoria
            WHERE email = ? AND accion = 'Login fallido'
              AND fecha > NOW() - INTERVAL ? MINUTE
              AND id_auditoria > (SELECT IFNULL(MAX(id_auditoria), 0) FROM auditoria WHERE email = ? AND accion = 'Inicio de sesión')");
        $stmt->bind_param("sis", $email, $minutos, $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['cantidad'];
    }
}
