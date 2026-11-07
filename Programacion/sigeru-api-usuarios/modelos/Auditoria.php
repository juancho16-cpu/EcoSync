<?php
// Modelo Auditoria: guarda lo que pasa en el sistema (quien entro, quien fallo la contraseña,
// quien creo o borro un usuario, quien cambio un rol...). Sirve para saber quien hizo que y cuando.

class Auditoria
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Guarda un evento. $idUsuario puede ser null (por ejemplo un login fallido)
    public function registrar($idUsuario, $email, $accion, $detalle)
    {
        // La IP de la computadora o celular que hizo el pedido
        $ip = $_SERVER['REMOTE_ADDR'] ?? "local";

        $stmt = $this->conn->prepare("INSERT INTO auditoria (id_usuario, email, accion, detalle, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $idUsuario, $email, $accion, $detalle, $ip);
        $stmt->execute();
    }

    // Devuelve los ultimos eventos (los mas nuevos primero). Si viene $accion, solo los de esa accion.
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

    // Cuenta los intentos fallidos de un correo en los ultimos $minutos.
    // Solo cuenta los que pasaron despues del ultimo inicio de sesion correcto.
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
