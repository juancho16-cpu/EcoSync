<?php
// Modelo Usuario: guarda, busca, modifica y borra usuarios en la base de datos
// El telefono se guarda cifrado con AES-256-GCM y el codigo del enlace de verificacion como resumen SHA-256 (ver cifrado.php)

require_once __DIR__ . "/../cifrado.php";

class Usuario
{
    private $conn;

    // Consulta que trae los datos del usuario y los de su rol (vecino u operario)
    private $consulta = "SELECT u.id_usuario AS id, u.primer_nombre, u.segundo_nombre, u.primer_apellido,
                                u.segundo_apellido, u.email, u.telefono, u.rol, u.estado, u.fecha_registro,
                                v.calle, v.zona, o.especialidad
                         FROM usuarios u
                         LEFT JOIN vecinos v ON u.id_usuario = v.id_usuario
                         LEFT JOIN operarios o ON u.id_usuario = o.id_usuario";

    // Tabla donde se guardan los datos de cada rol
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

    // Devuelve todos los usuarios (sin la contraseña)
    public function obtenerTodos()
    {
        // Los telefonos que se cargaron antes del cifrado (por ejemplo los datos de prueba) se cifran ahora
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

    // Este si devuelve la contraseña, porque se usa para el login
    public function buscarPorEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $this->descifrarFila($stmt->get_result()->fetch_assoc());
    }

    // Guarda un usuario nuevo y devuelve sus datos.
    // $estado es "Activo" cuando lo crea el administrador, o "Sin verificar" cuando se registra desde la landing
    // (en ese caso tambien se guarda el codigo que va en el enlace del correo).
    public function agregar($datos, $estado = "Activo", $codigo = null)
    {
        $nombre = $datos['primer_nombre'];
        $apellido = $datos['primer_apellido'];
        $email = $datos['email'];
        $telefono = $datos['telefono'] ?? "";
        $rol = $datos['rol'];
        // La contraseña se guarda encriptada
        $contrasena = password_hash($datos['contrasena'], PASSWORD_DEFAULT);

        // El telefono es un dato personal: se guarda cifrado (si viene vacio queda NULL)
        $telefono = cifrar($telefono);

        // Del codigo de verificacion se guarda solo su resumen SHA-256
        if ($codigo != null) {
            $codigo = resumen($codigo);
        }

        $stmt = $this->conn->prepare("INSERT INTO usuarios (primer_nombre, primer_apellido, email, telefono, contrasena, rol, estado, codigo_verificacion)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $nombre, $apellido, $email, $telefono, $contrasena, $rol, $estado, $codigo);
        $stmt->execute();

        // Numero de id que le dio la base de datos al usuario nuevo
        $id = $this->conn->insert_id;

        $this->guardarDatosDelRol($id, $rol, $datos);
        return $this->obtenerPorId($id);
    }

    // Cambia los datos de un usuario. La contraseña solo se cambia si escribieron una nueva.
    public function modificar($id, $datos)
    {
        $anterior = $this->obtenerPorId($id);

        $nombre = $datos['primer_nombre'];
        $apellido = $datos['primer_apellido'];
        $email = $datos['email'];
        $telefono = $datos['telefono'] ?? "";
        $rol = $datos['rol'];

        // El telefono es un dato personal: se guarda cifrado (si viene vacio queda NULL)
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
            // Si le cambiaron el rol, se borra de la tabla del rol viejo y se guarda en la del nuevo
            $tabla = $this->tablaDelRol[$anterior['rol']];
            $stmt = $this->conn->prepare("DELETE FROM $tabla WHERE id_usuario = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $this->guardarDatosDelRol($id, $rol, $datos);
        } else {
            // Si sigue con el mismo rol, se actualizan los datos de su tabla
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

    // Busca al usuario que tiene ese codigo de verificacion (el que va en el enlace del correo)
    public function buscarPorCodigo($codigo)
    {
        // En la base esta guardado el resumen SHA-256 del codigo, no el codigo
        $resumenCodigo = resumen($codigo);
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE codigo_verificacion = ? AND estado = 'Sin verificar'");
        $stmt->bind_param("s", $resumenCodigo);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Cambia el estado del usuario (Sin verificar, Pendiente, Activo o Rechazado).
    // El codigo de verificacion se borra porque ya no sirve.
    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->conn->prepare("UPDATE usuarios SET estado = ?, codigo_verificacion = NULL WHERE id_usuario = ?");
        $stmt->bind_param("si", $estado, $id);
        $stmt->execute();
    }

    // Borra un usuario (sus datos del rol se borran solos por el ON DELETE CASCADE)
    public function eliminar($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    // Guarda los datos que van en la tabla de cada rol
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

    // Descifra el telefono de una fila (si la fila existe)
    private function descifrarFila($fila)
    {
        if ($fila != null) {
            $fila['telefono'] = descifrar($fila['telefono']);
        }
        return $fila;
    }

    // Cifra los telefonos que todavia estan guardados como texto (los que no empiezan con "enc:")
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
