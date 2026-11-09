<?php

class Alerta
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function hacerLista($result)
    {
        $lista = [];
        while ($row = $result->fetch_assoc()) {
            $lista[] = $row;
        }
        return $lista;
    }

    public function prioridadAltaAbiertas($dias)
    {
        $stmt = $this->conn->prepare("SELECT i.id_incidencia AS id, c.calle, c.zona
                                      FROM incidencias i
                                      JOIN contenedores c ON i.id_contenedor = c.id_contenedor
                                      WHERE i.prioridad = 'Alta' AND i.estado = 'Abierta'
                                        AND NOT (i.tipo = 'Desbordado' AND DATEDIFF(CURDATE(), i.fecha_deteccion) >= ?)
                                      ORDER BY i.fecha_deteccion");
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function desbordadosSinAtender($dias)
    {
        $stmt = $this->conn->prepare("SELECT i.id_incidencia AS id, c.calle, c.zona,
                                             DATEDIFF(CURDATE(), i.fecha_deteccion) AS dias
                                      FROM incidencias i
                                      JOIN contenedores c ON i.id_contenedor = c.id_contenedor
                                      WHERE i.tipo = 'Desbordado' AND i.estado = 'Abierta'
                                        AND DATEDIFF(CURDATE(), i.fecha_deteccion) >= ?
                                      ORDER BY dias DESC");
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function instalacionesCasiLlenas($porcentaje)
    {
        $stmt = $this->conn->prepare("SELECT id_instalacion AS id, nombre,
                                             ROUND(capacidad_actual * 100 / capacidad_max) AS porcentaje
                                      FROM instalaciones
                                      WHERE capacidad_max > 0 AND capacidad_actual * 100 >= capacidad_max * ?");
        $stmt->bind_param("d", $porcentaje);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function repuestosConPocoStock()
    {
        $result = mysqli_query($this->conn, "SELECT id_repuesto AS id, nombre, cantidad, cantidad_minima
                                             FROM repuestos WHERE cantidad <= cantidad_minima ORDER BY nombre");
        return $this->hacerLista($result);
    }

    public function avisosSinAtender()
    {
        $result = mysqli_query($this->conn, "SELECT i.id_incidencia AS id, c.calle
                                             FROM notificaciones n
                                             JOIN incidencias i ON n.id_incidencia = i.id_incidencia
                                             JOIN contenedores c ON i.id_contenedor = c.id_contenedor
                                             WHERE i.estado = 'Abierta'");
        return $this->hacerLista($result);
    }

    public function delRecolector($idUsuario)
    {
        $stmt = $this->conn->prepare("SELECT i.id_incidencia AS id, i.estado, i.prioridad, c.calle, c.zona
                                      FROM incidencias i
                                      JOIN contenedores c ON i.id_contenedor = c.id_contenedor
                                      JOIN recorridos r ON i.id_recorrido = r.id_recorrido
                                      JOIN recolectores re ON r.id_cuadrilla = re.id_cuadrilla
                                      WHERE re.id_usuario = ? AND i.estado <> 'Resuelta'
                                      ORDER BY i.fecha_deteccion");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function avisosDelVecino($idUsuario)
    {
        $stmt = $this->conn->prepare("SELECT i.id_incidencia AS id, i.estado, c.calle
                                      FROM notificaciones n
                                      JOIN incidencias i ON n.id_incidencia = i.id_incidencia
                                      JOIN contenedores c ON i.id_contenedor = c.id_contenedor
                                      WHERE n.id_usuario = ? AND i.estado <> 'Abierta'
                                        AND i.fecha >= CURDATE() - INTERVAL 7 DAY");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }
}
