<?php

class Estadistica
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

    public function incidenciasPorEstado()
    {
        $result = mysqli_query($this->conn, "SELECT estado, COUNT(*) AS cantidad FROM incidencias GROUP BY estado");
        return $this->hacerLista($result);
    }

    public function topDesbordados($mes)
    {
        $stmt = $this->conn->prepare("SELECT c.id_contenedor AS id, c.calle, c.zona, c.municipio, COUNT(*) AS cantidad
                                      FROM incidencias i
                                      JOIN contenedores c ON i.id_contenedor = c.id_contenedor
                                      WHERE i.tipo = 'Desbordado' AND DATE_FORMAT(i.fecha_deteccion, '%Y-%m') = ?
                                      GROUP BY c.id_contenedor, c.calle, c.zona, c.municipio
                                      ORDER BY cantidad DESC, c.id_contenedor
                                      LIMIT 5");
        $stmt->bind_param("s", $mes);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function sinResolverPorMunicipio()
    {
        $result = mysqli_query($this->conn, "SELECT c.municipio, COUNT(*) AS cantidad
                                             FROM incidencias i
                                             JOIN contenedores c ON i.id_contenedor = c.id_contenedor
                                             WHERE i.estado <> 'Resuelta'
                                             GROUP BY c.municipio
                                             ORDER BY cantidad DESC");
        return $this->hacerLista($result);
    }

    public function porTipo($mes)
    {
        $stmt = $this->conn->prepare("SELECT tipo, COUNT(*) AS cantidad FROM incidencias
                                      WHERE DATE_FORMAT(fecha_deteccion, '%Y-%m') = ?
                                      GROUP BY tipo");
        $stmt->bind_param("s", $mes);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function promedioDiasResolucion()
    {
        $result = mysqli_query($this->conn, "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_deteccion)) AS promedio
                                             FROM incidencias WHERE estado = 'Resuelta' AND fecha_resolucion IS NOT NULL");
        $promedio = mysqli_fetch_assoc($result)['promedio'];

        if ($promedio == null) {
            return null;
        }
        return round($promedio, 1);
    }

    public function totalRecolectado($desde, $hasta)
    {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(kilos), 0) AS kilos, COUNT(*) AS vaciados, COUNT(DISTINCT id_contenedor) AS contenedores
                                      FROM recolecciones WHERE DATE(fecha) BETWEEN ? AND ?");
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function kilosPorMunicipio($desde, $hasta)
    {
        $stmt = $this->conn->prepare("SELECT c.municipio, ROUND(SUM(r.kilos)) AS cantidad, COUNT(*) AS vaciados
                                      FROM recolecciones r
                                      JOIN contenedores c ON r.id_contenedor = c.id_contenedor
                                      WHERE DATE(r.fecha) BETWEEN ? AND ?
                                      GROUP BY c.municipio ORDER BY cantidad DESC");
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function kilosPorResiduo($desde, $hasta)
    {
        $stmt = $this->conn->prepare("SELECT t.nombre AS residuo, ROUND(SUM(r.kilos)) AS cantidad
                                      FROM recolecciones r
                                      JOIN contenedores c ON r.id_contenedor = c.id_contenedor
                                      JOIN tipos_residuo t ON c.id_residuo = t.id_residuo
                                      WHERE DATE(r.fecha) BETWEEN ? AND ?
                                      GROUP BY t.nombre ORDER BY cantidad DESC");
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function vaciadosPorContenedor($desde, $hasta)
    {
        $stmt = $this->conn->prepare("SELECT c.id_contenedor AS id, c.calle, c.zona, c.municipio, COUNT(r.id_recoleccion) AS vaciados,
                                             COALESCE(ROUND(SUM(r.kilos)), 0) AS kilos, MAX(r.fecha) AS ultima
                                      FROM contenedores c
                                      LEFT JOIN recolecciones r ON r.id_contenedor = c.id_contenedor AND DATE(r.fecha) BETWEEN ? AND ?
                                      GROUP BY c.id_contenedor, c.calle, c.zona, c.municipio
                                      ORDER BY vaciados DESC, c.id_contenedor");
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        return $this->hacerLista($stmt->get_result());
    }

    public function resueltasDelMes($mes)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cantidad FROM incidencias
                                      WHERE estado = 'Resuelta' AND DATE_FORMAT(fecha_resolucion, '%Y-%m') = ?");
        $stmt->bind_param("s", $mes);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['cantidad'];
    }
}
