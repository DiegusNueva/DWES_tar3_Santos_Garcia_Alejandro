<?php

function conectarBD()
{
    $cadena_conexion = 'mysql:dbname=dwes_t3;host=127.0.0.1';
    $usuario = "root";
    $clave = "";

    try {
        $bd = new PDO($cadena_conexion, $usuario, $clave);
        return $bd;
    } catch (PDOException $e) {
        echo "Error conectando a la bd: " . $e->getMessage();
    }
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $usuario = $_POST["usuario"];
    $nombre = $_POST["nombre"];
    $clave = $_POST["clave"];
    $correo = $_POST["correo"];
    $rol = 2; // Asignamos rol 2 (cliente) por defecto

    // Conectar a la base de datos
    $conn = conectarBD();

    // Verificar si el usuario ya existe
    $stmt_verificar = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario");
    $stmt_verificar->bindParam(':usuario', $usuario);
    $stmt_verificar->execute();
    // fetchColumn() devuelve el número de filas obtenido
    $usuario_existente = $stmt_verificar->fetchColumn();

    if ($usuario_existente > 0) {
        echo "El usuario ya existe. Por favor, elija otro o inicie sesión.";
    } else {
        // Insertar el nuevo usuario en la base de datos
        $stmt = $conn->prepare("INSERT INTO usuarios (usuario, nombre, clave, correo, rol) VALUES (:usuario, :nombre, :clave, :correo, :rol)");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':clave', $clave);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':rol', $rol);

        try {
            $stmt->execute();
            // Redirigimos a la página de pedido.php con la sesion iniciada
            session_start();
            $_SESSION["usuario"] = $usuario;
            $_SESSION["rol"] = $rol;
            $_SESSION["nombre"] = $nombre;
            header("Location: pedido.php");
        } catch (PDOException $e) {
            echo "Error al crear el usuario: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domino's Pizza - Registrar</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/nuevo_usuario.css">
</head>

<body>
    <div class="container">
        <h2>Resgistro de nuevo usuario</h2>
        <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
            <input type="text" name="usuario" required placeholder="Usuario">
            <input type="text" name="nombre" required placeholder="Nombre">
            <input type="password" name="clave" required placeholder="Contraseña">
            <input type="email" name="correo" required placeholder="Correo">
            <input type="submit" value="Registrarse">
            <a href="index.php">Volver</a>
        </form>
    </div>
</body>

</html>