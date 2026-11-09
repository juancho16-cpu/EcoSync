<?php

require_once __DIR__ . "/../cifrado.php";

class Usuario
{
    private $conn;

    private $consulta = "SELECT u.id_usuario AS id, u.primer_nombre, u.segundo_nombre, u.primer_apellido,
                                u.segundo_apellido, u.email, u.telefono, u.rol, u.estado, u.fecha_registro,
                                v.calle, v.zona, o.especialidad
                         FROM usuarios u
                         LEFT JOIN vecinos v ON u.id_usuario = v.id_usuario
                         LEFT JOIN operarios o ON u.id_usuario = o.id_usuario";

    private $tablaDelRol = [
        "Administrador" => "administradores",
        "Operario" => "operarios",
        "Recolector" => "recolectores",
        "Vecino" => "vecinos"
    ];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function obtenerTodos()
    {
        
        $this->cifrarTelefonosViejos();

        $result = mysqli_query($this->conn, $this->consulta . " ORDER BY u.id_usuario");

        $usuarios = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['telefono'] = descifrar($row['telefono']);
            $usuarios[] = $row;
        }
        return $usuarios;
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare($this->consulta . " WHERE u.id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $this->descifrarFila($stmt->get_result()->fetch_assoc());
    }

    public function buscarPorEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $this->descifrarFila($stmt->get_result()->fetch_assoc());
    }

    public function agregar($datos, $estado = "Activo", $codigo = null)
    {
        $nombre = $datos['primer_nombre'];
        $apellido = $datos['primer_apellido'];
        $email = $datos['email'];
        $telefono = $datos['telefono'] ?? "";
        $rol = $datos['rol'];
        
        $contrasena = password_hash($datos['contrasena'], PASSWORD_DEFAULT);

        $telefono = cifrar($telefono);

        if ($codigo != null) {
            $codigo = resumen($codigo);
        }

        $stmt = $this->conn->prepare("INSERT INTO usuarios (primer_nombre, primer_apellido, email, telefono, contrasena, rol, estado, codigo_verificacion)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $nombre, $apellido, $email, $telefono, $contrasena, $rol, $estado, $codigo);
        $stmt->execute();

        $id = $this->conn->insert_id;

        $this->guardarDatosDelRol($id, $rol, $datos);
        return $this->obtenerPorId($id);
    }

    public function modificar($id, $datos)
    {
        $anterior = $this->obtenerPorId($id);

        $nombre = $datos['primer_nombre'];
        $apellido = $datos['primer_apellido'];
        $email = $datos['email'];
        $telefono = $datos['telefono'] ?? "";
        $rol = $datos['rol'];

        $telefono = cifrar($telefono);

        $stmt = $this->conn->prepare("UPDATE usuarios SET primer_nombre = ?, primer_apellido = ?, email = ?, telefono = ?, rol = ? WHERE id_usuario = ?");
        $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $rol, $id);
        $stmt->execute();

        if (!empty($datos['contrasena'])) {
            $contrasena = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
            $stmt->bind_param("si", $contrasena, $id);
            $stmt->execute();
        }

        if ($anterior['rol'] != $rol) {
            
            $tabla = $this->tablaDelRol[$anterior['rol']];
            $stmt = $this->conn->prepare("DELETE FROM $tabla WHERE id_usuario = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $this->guardarDatosDelRol($id, $rol, $datos);
        } else {
            
            if ($rol == "Vecino") {
                $calle = $datos['calle'] ?? "";
                $zona = $datos['zona'] ?? "";
                $stmt = $this->conn->prepare("UPDATE vecinos SET calle = ?, zona = ? WHERE id_usuario = ?");
                $stmt->bind_param("ssi", $calle, $zona, $id);
                $stmt->execute();
            }
            if ($rol == "Operario") {
                $especialidad = $datos['especialidad'] ?? "";
                $stmt = $this->conn->prepare("UPDATE operarios SET especialidad = ? WHERE id_usuario = ?");
                $stmt->bind_param("si", $especialidad, $id);
                $stmt->execute();
            }
        }

        return $this->obtenerPorId($id);
    }

    public function buscarPorCodigo($codigo)
    {
        
        $resumenCodigo = resumen($codigo);
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE codigo_verificacion = ? AND estado = 'Sin verificar'");
        $stmt->bind_param("s", $resumenCodigo);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->conn->prepare("UPDATE usuarios SET estado = ?, codigo_verificacion = NULL WHERE id_usuario = ?");
        $stmt->bind_param("si", $estado, $id);
        $stmt->execute();
    }

    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    private function guardarDatosDelRol($id, $rol, $datos)
    {
        if ($rol == "Vecino") {
            $calle = $datos['calle'] ?? "";
            $zona = $datos['zona'] ?? "";
            $stmt = $this->conn->prepare("INSERT INTO vecinos (id_usuario, calle, zona) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id, $calle, $zona);
            $stmt->execute();
        }
        if ($rol == "Operario") {
            $especialidad = $datos['especialidad'] ?? "";
            $stmt = $this->conn->prepare("INSERT INTO operarios (id_usuario, especialidad) VALUES (?, ?)");
            $stmt->bind_param("is", $id, $especialidad);
            $stmt->execute();
        }
        if ($rol == "Administrador") {
            $stmt = $this->conn->prepare("INSERT INTO administradores (id_usuario) VALUES (?)");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        if ($rol == "Recolector") {
            $stmt = $this->conn->prepare("INSERT INTO recolectores (id_usuario) VALUES (?)");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }

    private function descifrarFila($fila)
    {
        if ($fila != null) {
            $fila['telefono'] = descifrar($fila['telefono']);
        }
        return $fila;
    }

    public function cifrarTelefonosViejos()
    {
        $result = mysqli_query($this->conn, "SELECT id_usuario, telefono FROM usuarios WHERE telefono IS NOT NULL AND telefono NOT LIKE 'enc:%'");
        while ($row = mysqli_fetch_assoc($result)) {
            $cifrado = cifrar($row['telefono']);
            $stmt = $this->conn->prepare("UPDATE usuarios SET telefono = ? WHERE id_usuario = ?");
            $stmt->bind_param("si", $cifrado, $row['id_usuario']);
            $stmt->execute();
        }
    }
}
