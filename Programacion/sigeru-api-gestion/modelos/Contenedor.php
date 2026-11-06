<?php
// Modelo Contenedor: guarda, busca, modifica y borra contenedores en la base de datos

class Contenedor
{
    private $conn;

    // Consulta que trae el contenedor con el nombre de su tipo de residuo
    private $consulta = "SELECT c.id_contenedor AS id, c.calle, c.zona, c.municipio, c.estado, c.capacidad,
                                c.id_residuo, t.nombre AS residuo, c.latitud, c.longitud, c.ultima_recoleccion
                         FROM contenedores c
                         LEFT JOIN tipos_residuo t ON c.id_residuo = t.id_residuo";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerTodos()
    {
        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY c.id_contenedor");

        $contenedores = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $contenedores[] = $row;
        }
        return $contenedores;
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE c.id_contenedor = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Tipos de residuo (regla RNE01 del MER: cada contenedor guarda un solo tipo)
    public function obtenerTiposResiduo()
    {
        $result = mysqli_query($this->conn, "SELECT id_residuo, nombre FROM tipos_residuo ORDER BY nombre");

        $tipos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $tipos[] = $row;
        }
        return $tipos;
    }

    // Devuelve true si existe ese tipo de residuo
    public function existeResiduo($idResiduo)
    {
        $stmt = $this->conn->prepare("SELECT id_residuo FROM tipos_residuo WHERE id_residuo = ?");
        $stmt->bind_param("i", $idResiduo);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    // Devuelve true si el contenedor tiene incidencias registradas
    public function tieneIncidencias($id)
    {
        $stmt = $this->conn->prepare("SELECT id_incidencia FROM incidencias WHERE id_contenedor = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    public function agregar($datos)
    {
        $stmt = $this->conn->prepare("INSERT INTO contenedores (calle, zona, municipio, estado, capacidad, id_residuo, latitud, longitud) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $this->cargarDatos($stmt, $datos, null);
        $stmt->execute();

        return $this->obtenerPorId($this->conn->insert_id);
    }

    public function modificar($id, $datos)
    {
        $stmt = $this->conn->prepare("UPDATE contenedores SET calle = ?, zona = ?, municipio = ?, estado = ?, capacidad = ?, id_residuo = ?, latitud = ?, longitud = ? WHERE id_contenedor = ?");
        $this->cargarDatos($stmt, $datos, $id);
        $stmt->execute();

        return $this->obtenerPorId($id);
    }

    // Cambia solo el estado del contenedor (por ejemplo cuando reportan que esta roto)
    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->conn->prepare("UPDATE contenedores SET estado = ? WHERE id_contenedor = ?");
        $stmt->bind_param("si", $estado, $id);
        $stmt->execute();
    }

    // El recolector lo vacio: queda Funcional y se guarda la fecha y hora de ahora
    // Guarda cuantos kilos se sacaron al vaciar el contenedor (datos simulados)
    public function registrarRecoleccion($id, $kilos)
    {
        $stmt = $this->conn->prepare("INSERT INTO recolecciones (id_contenedor, fecha, kilos) VALUES (?, NOW(), ?)");
        $stmt->bind_param("id", $id, $kilos);
        $stmt->execute();
    }

    public function vaciar($id)
    {
        $stmt = $this->conn->prepare("UPDATE contenedores SET estado = 'Funcional', ultima_recoleccion = NOW() WHERE id_contenedor = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $this->obtenerPorId($id);
    }

    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM contenedores WHERE id_contenedor = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    // Pone los datos del formulario en la consulta. Los campos vacios se guardan como NULL.
    private function cargarDatos($stmt, $datos, $id)
    {
        $calle = $datos['calle'];
        $zona = $datos['zona'];
        $municipio = $datos['municipio'] ?? "";
        $estado = $datos['estado'];
        $capacidad = $datos['capacidad'] ?? "";
        $idResiduo = $datos['id_residuo'];
        $latitud = $datos['latitud'] ?? "";
        $longitud = $datos['longitud'] ?? "";

        if ($municipio == "") $municipio = null;
        if ($capacidad == "") $capacidad = null;
        if ($latitud == "") $latitud = null;
        if ($longitud == "") $longitud = null;

        if ($id == null) {
            $stmt->bind_param("sssssiss", $calle, $zona, $municipio, $estado, $capacidad, $idResiduo, $latitud, $longitud);
        } else {
            $stmt->bind_param("sssssissi", $calle, $zona, $municipio, $estado, $capacidad, $idResiduo, $latitud, $longitud, $id);
        }
    }
}
