<?php
// Modelo Recorrido: que ruta hace cada cuadrilla, con que camion y que contenedores le tocan.
// Un recorrido pasa por varias zonas (barrios) y recoge los contenedores de esas zonas.
// En el MER es la agregacion "Cuadrilla recorre Ruta", que "Utiliza" un camion.

class Recorrido
{
    private $conn;

    // Consulta que trae el recorrido con el nombre de la ruta, la cuadrilla y el camion
    private $consulta = "SELECT r.id_recorrido AS id, r.id_cuadrilla, r.id_ruta, r.id_camion,
                                ru.nombre AS ruta, cu.nombre AS cuadrilla, ca.matricula AS camion,
                                r.fecha_asignacion, r.hora_inicio, r.hora_fin
                         FROM recorridos r
                         JOIN rutas ru ON r.id_ruta = ru.id_ruta
                         JOIN cuadrillas cu ON r.id_cuadrilla = cu.id_cuadrilla
                         LEFT JOIN camiones ca ON r.id_camion = ca.id_camion";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Todos los recorridos (para el administrador y el operario)
    public function obtenerTodos()
    {
        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY r.fecha_asignacion DESC, r.id_recorrido");

        $recorridos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $recorridos[] = $this->agregarZonasYContenedores($row);
        }
        return $recorridos;
    }

    // Solo los recorridos de la cuadrilla del recolector
    public function obtenerDelRecolector($idUsuario)
    {
        $stmt = $this->conn->prepare($this->consulta . " JOIN recolectores re ON re.id_cuadrilla = r.id_cuadrilla
                                                        WHERE re.id_usuario = ? ORDER BY r.id_recorrido");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();

        $recorridos = [];
        while ($row = $result->fetch_assoc()) {
            $recorridos[] = $this->agregarZonasYContenedores($row);
        }
        return $recorridos;
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE r.id_recorrido = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $recorrido = $stmt->get_result()->fetch_assoc();

        if ($recorrido == null) {
            return null;
        }
        return $this->agregarZonasYContenedores($recorrido);
    }

    // Listas para los formularios
    public function obtenerCuadrillas()
    {
        $result = mysqli_query($this->conn, "SELECT id_cuadrilla AS id, nombre FROM cuadrillas ORDER BY nombre");

        $cuadrillas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cuadrillas[] = $row;
        }
        return $cuadrillas;
    }

    public function obtenerRutas()
    {
        $result = mysqli_query($this->conn, "SELECT id_ruta AS id, nombre, descripcion, distancia FROM rutas ORDER BY nombre");

        $rutas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rutas[] = $row;
        }
        return $rutas;
    }

    // Devuelve true si existe la cuadrilla
    public function existeCuadrilla($id)
    {
        $stmt = $this->conn->prepare("SELECT id_cuadrilla FROM cuadrillas WHERE id_cuadrilla = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    // Devuelve true si existe la ruta
    public function existeRuta($id)
    {
        $stmt = $this->conn->prepare("SELECT id_ruta FROM rutas WHERE id_ruta = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    // Devuelve el recorrido que ya tiene esa cuadrilla con esa ruta, o null si no hay.
    // (cada cuadrilla puede tener una sola vez cada ruta)
    public function buscarPorCuadrillaYRuta($idCuadrilla, $idRuta)
    {
        $stmt = $this->conn->prepare("SELECT id_recorrido AS id FROM recorridos WHERE id_cuadrilla = ? AND id_ruta = ?");
        $stmt->bind_param("ii", $idCuadrilla, $idRuta);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Todas las zonas, con el recorrido que las recorre (para elegirlas en el formulario)
    public function obtenerZonas()
    {
        $result = mysqli_query($this->conn, "SELECT id_zona AS id, nombre, zona_asignada, id_recorrido FROM zonas ORDER BY nombre");

        $zonas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $zonas[] = $row;
        }
        return $zonas;
    }

    // Devuelve true si existe la zona
    public function existeZona($id)
    {
        $stmt = $this->conn->prepare("SELECT id_zona FROM zonas WHERE id_zona = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() != null;
    }

    public function agregar($datos)
    {
        $stmt = $this->conn->prepare("INSERT INTO recorridos (id_cuadrilla, id_ruta, id_camion, fecha_asignacion, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?)");
        $this->cargarDatos($stmt, $datos, null);
        $stmt->execute();

        $id = $this->conn->insert_id;
        if (isset($datos['zonas'])) {
            $this->guardarZonas($id, $datos['zonas']);
        }
        return $this->obtenerPorId($id);
    }

    public function modificar($id, $datos)
    {
        $stmt = $this->conn->prepare("UPDATE recorridos SET id_cuadrilla = ?, id_ruta = ?, id_camion = ?, fecha_asignacion = ?, hora_inicio = ?, hora_fin = ? WHERE id_recorrido = ?");
        $this->cargarDatos($stmt, $datos, $id);
        $stmt->execute();

        if (isset($datos['zonas'])) {
            $this->guardarZonas($id, $datos['zonas']);
        }
        return $this->obtenerPorId($id);
    }

    // Guarda las zonas que recorre. En el MER cada zona la recorre un solo recorrido,
    // asi que si la zona estaba en otro recorrido pasa a este.
    private function guardarZonas($id, $zonas)
    {
        // Primero se sacan las zonas que tenia
        $stmt = $this->conn->prepare("UPDATE zonas SET id_recorrido = NULL WHERE id_recorrido = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Despues se ponen las elegidas
        foreach ($zonas as $idZona) {
            $stmt = $this->conn->prepare("UPDATE zonas SET id_recorrido = ? WHERE id_zona = ?");
            $stmt->bind_param("ii", $id, $idZona);
            $stmt->execute();
        }
    }

    // Borra el recorrido. Las zonas quedan sin recorrido (ON DELETE SET NULL).
    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM recorridos WHERE id_recorrido = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    // Pone los datos del formulario en la consulta. Las horas vacias se guardan como NULL.
    private function cargarDatos($stmt, $datos, $id)
    {
        $idCuadrilla = $datos['id_cuadrilla'];
        $idRuta = $datos['id_ruta'];
        $idCamion = $datos['id_camion'];
        $fecha = $datos['fecha_asignacion'];
        $horaInicio = $datos['hora_inicio'] ?? "";
        $horaFin = $datos['hora_fin'] ?? "";

        if ($horaInicio == "") $horaInicio = null;
        if ($horaFin == "") $horaFin = null;

        if ($id == null) {
            $stmt->bind_param("iiisss", $idCuadrilla, $idRuta, $idCamion, $fecha, $horaInicio, $horaFin);
        } else {
            $stmt->bind_param("iiisssi", $idCuadrilla, $idRuta, $idCamion, $fecha, $horaInicio, $horaFin, $id);
        }
    }

    // Le agrega al recorrido la lista de zonas y los contenedores que hay en esas zonas
    private function agregarZonasYContenedores($recorrido)
    {
        $stmt = $this->conn->prepare("SELECT nombre FROM zonas WHERE id_recorrido = ? ORDER BY id_zona");
        $stmt->bind_param("i", $recorrido['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        $recorrido['zonas'] = [];
        while ($row = $result->fetch_assoc()) {
            $recorrido['zonas'][] = $row['nombre'];
        }

        $stmt = $this->conn->prepare("SELECT c.id_contenedor AS id, c.calle, c.zona, c.estado, c.latitud, c.longitud
                                      FROM contenedores c
                                      JOIN zonas z ON c.zona = z.nombre
                                      WHERE z.id_recorrido = ? AND c.latitud IS NOT NULL
                                      ORDER BY z.id_zona, c.id_contenedor");
        $stmt->bind_param("i", $recorrido['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        $recorrido['contenedores'] = [];
        while ($row = $result->fetch_assoc()) {
            $recorrido['contenedores'][] = $row;
        }
        return $recorrido;
    }
}
