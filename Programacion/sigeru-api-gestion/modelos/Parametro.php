<?php
// Modelo Parametro: lee los valores del sistema que puede cambiar el administrador

class Parametro
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Devuelve el valor de un parametro. Si no existe devuelve $porDefecto.
    public function obtener($nombre, $porDefecto)
    {
        $stmt = $this->conn->prepare("SELECT valor FROM parametros WHERE nombre = ?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();

        if ($fila == null) {
            return $porDefecto;
        }
        return $fila['valor'];
    }
}
