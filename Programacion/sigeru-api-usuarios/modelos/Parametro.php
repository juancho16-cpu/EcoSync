<?php
// Modelo Parametro: valores del sistema que el administrador puede cambiar
// (dias para avisar de un desborde, si hay que aprobar los registros, idioma...)

class Parametro
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerTodos()
    {
        $result = mysqli_query($this->conn, "SELECT nombre, valor, descripcion FROM parametros ORDER BY nombre");

        $parametros = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $parametros[] = $row;
        }
        return $parametros;
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

    public function modificar($nombre, $valor)
    {
        $stmt = $this->conn->prepare("UPDATE parametros SET valor = ? WHERE nombre = ?");
        $stmt->bind_param("ss", $valor, $nombre);
        $stmt->execute();
    }
}
