<?php

session_start();

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

$conn = conectarBD();

function listarPizzas($conn)
{
    $consulta = $conn->prepare("SELECT nombre, coste, precio, ingredientes FROM pizzas");
    $consulta->execute();

    echo "<table border='1'>";
    echo "<tr><th>Nombre</th><th>Coste</th><th>Precio</th><th>Ingredientes</th><th>Acciones</th></tr>";

    foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "<td>" . $row["nombre"] . "</td>";
        echo "<td>" . $row["coste"] . "€</td>";
        echo "<td>" . $row["precio"] . "€</td>";
        echo "<td><i>" . $row["ingredientes"] . "</i></td>";
        echo "<td><a class='edit-btn' href='?editar=" . $row["nombre"] . "'>Editar</a> <a class='delete-btn' href='#' onclick='confirmarEliminar(\"" . $row["nombre"] . "\")'>Eliminar</a></td>";

        echo "</tr>";
    }

    echo "</table>";
}

function pizzasMasVendidas($conn)
{
    $consultaPedidos = $conn->prepare("SELECT detalle_pedido FROM pedidos");
    $consultaPedidos->execute();

    $pizzasVendidas = [];

    foreach ($consultaPedidos->fetchAll(PDO::FETCH_ASSOC) as $row) {
        // Separamos el detalle del pedido por las comas, para obtener cada pizza y su cantidad
        $detalles = explode(", ", $row['detalle_pedido']);

        foreach ($detalles as $detalle) {
            // Separamos el nombre de la pizza de la cantidad y lo almacenamos en una variable
            list($nombrePizza, $cantidad) = explode(" x ", $detalle);

            if (isset($pizzasVendidas[$nombrePizza])) {
                // Si la pizza ya existe en el array, sumamos la cantidad
                $pizzasVendidas[$nombrePizza] += (int)$cantidad;
            } else {
                // Si la pizza no existe en el array, la agregamos
                $pizzasVendidas[$nombrePizza] = (int)$cantidad;
            }
        }
    }

    // Ordenamos el array de pizzas vendidas de mayor a menor
    arsort($pizzasVendidas);

    echo "<div class='pizzas-top'>";
    echo "<h1>Pizzas más vendidas</h1>";
    echo "<br>";
    echo "<ol>";

    foreach ($pizzasVendidas as $nombrePizza => $cantidad) {
        echo "<li>{$nombrePizza} x {$cantidad}</li>";
    }

    echo "</ol>";
    echo "</div>";
}

// METODO PARA INSERTAR NUEVA PIZZA
function agregarPizza($conn, $nombre, $coste, $precio, $ingredientes)
{
    $insertar = $conn->prepare("INSERT INTO pizzas (nombre, coste, precio, ingredientes) VALUES (:nombre, :coste, :precio, :ingredientes)");

    $insertar->bindParam(':nombre', $nombre);
    $insertar->bindParam(':coste', $coste);
    $insertar->bindParam(':precio', $precio);
    $insertar->bindParam(':ingredientes', $ingredientes);

    if ($insertar->execute()) {
        header("Location: " . $_SERVER["PHP_SELF"]);
        exit();
    } else {
        return false;
    }
}

// METODO PARA MODIFICAR PIZZA EXISTENTE
function modificarPizza($conn, $nombre, $coste, $precio, $ingredientes, $nombreOriginal)
{
    $modificar = $conn->prepare("UPDATE pizzas SET nombre = :nombre, coste = :coste, ingredientes = :ingredientes, precio = :precio WHERE nombre = :nombreOriginal");

    $modificar->bindParam(':nombre', $nombre);
    $modificar->bindParam(':coste', $coste);
    $modificar->bindParam(':precio', $precio);
    $modificar->bindParam(':ingredientes', $ingredientes);
    $modificar->bindParam(':nombreOriginal', $nombreOriginal);

    return $modificar->execute();
}

// METODO PARA BORRAR PIZZA EXISTENTE
function eliminarPizza($conn, $nombre)
{
    $eliminar = $conn->prepare("DELETE FROM pizzas WHERE nombre = :nombre");

    $eliminar->bindParam(':nombre', $nombre);

    return $eliminar->execute();
}

// Lógica para agregar/modificar nueva pizza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'], $_POST['coste'], $_POST['precio'], $_POST['ingredientes'])) {
    $nombre = $_POST['nombre'];
    $coste = $_POST['coste'];
    $precio = $_POST['precio'];
    $ingredientes = $_POST['ingredientes'];

    if (empty($nombre) || empty($coste) || empty($precio) || empty($ingredientes)) {
        echo "<p class='error-message'>Por favor, complete todos los campos.</p>";
    } else {
        // Si existe el campo "nombre_original" significa que se está modificando una pizza existente
        if (isset($_POST['nombre_original'])) {
            $nombreOriginal = $_POST['nombre_original'];
            if (modificarPizza($conn, $nombre, $coste, $precio, $ingredientes, $nombreOriginal)) {
                echo "<p class='success-message'>Pizza modificada con éxito.</p>";
            } else {
                echo "<p class='error-message'>Error al modificar la pizza.</p>";
            }
        } else {
            if (agregarPizza($conn, $nombre, $coste, $precio, $ingredientes)) {
                echo "<p class='success-message'>Pizza agregada con éxito.</p>";
            } else {
                echo "<p class='error-message'>Error al agregar la pizza.</p>";
            }
        }
    }
}

// Lógica para rellenar el formulario con los datos de la pizza a modificar
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['editar'])) {
    $nombrePizzaEditar = $_GET['editar'];

    // Obtener datos de la pizza a editar
    $consultaEditar = $conn->prepare("SELECT nombre, coste, precio, ingredientes FROM pizzas WHERE nombre = :nombre");
    $consultaEditar->bindParam(':nombre', $nombrePizzaEditar);
    $consultaEditar->execute();

    $pizzaEditar = $consultaEditar->fetch(PDO::FETCH_ASSOC);

    // Asignar valores a variables para rellenar el formulario
    $nombreEditar = $pizzaEditar['nombre'];
    $costeEditar = $pizzaEditar['coste'];
    $precioEditar = $pizzaEditar['precio'];
    $ingredientesEditar = $pizzaEditar['ingredientes'];
}

// Lógica para eliminar pizza
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar'])) {
    $nombrePizzaEliminar = $_GET['eliminar'];

    if (eliminarPizza($conn, $nombrePizzaEliminar)) {
        echo "<p class='success-message'>Pizza eliminada con éxito.</p>";
    } else {
        echo "<p class='error-message'>Error al eliminar la pizza.</p>";
    }
}

// Cerrar sesión al hacer clic en "Cerrar sesión"
if (isset($_GET['cerrar_sesion'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domino's Pizza - Pedido</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/admin_pedido.css">
</head>

<body>
    <div class="container">
        <header>
            <img width="250px" src="assets/DominosLogo.png" alt="Domino's Logo">
            <div>
                <?php
                session_start();
                if (isset($_SESSION["nombre"])) {
                    echo "<h2>Bienvenido " . $_SESSION["nombre"] . "<span class='arrow-down'>▼</span></h2>";
                }
                ?>
                <a href="?cerrar_sesion" id="cerrar-sesion" style="display: none;">Cerrar sesión</a>
            </div>
        </header>
        <h1>Nuestras pizzas</h1>
        <?php
        listarPizzas($conn);
        ?>

        <div class="pizzas-data">
            <!-- Formulario para agregar/editar pizzas -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post">
                <h3><?php echo isset($nombreEditar) ? 'Editar pizza' : 'Agregar pizza'; ?></h3>
                <?php if (isset($nombreEditar)) : ?>
                    <!-- Campo oculto para almacenar el nombre original de la pizza que se está editando -->
                    <input type="hidden" name="nombre_original" value="<?php echo $nombreEditar; ?>">
                <?php endif; ?>
                <label for="nombre">Nombre: </label>
                <input type="text" name="nombre" value="<?php echo isset($nombreEditar) ? $nombreEditar : ''; ?>"><br>
                <label for="coste">Coste: </label>
                <input type="text" name="coste" value="<?php echo isset($costeEditar) ? $costeEditar : ''; ?>"><br>
                <label for="precio">Precio:</label>
                <input type="text" name="precio" value="<?php echo isset($precioEditar) ? $precioEditar : ''; ?>"><br>
                <label for="ingredientes">Ingredientes: </label>
                <input type="text" name="ingredientes" value="<?php echo isset($ingredientesEditar) ? $ingredientesEditar : ''; ?>"><br>
                <input type="submit" value="<?php echo isset($nombreEditar) ? 'Actualizar' : 'Agregar'; ?>">
                <?php if (isset($nombreEditar)) : ?>
                    <!-- Botón de cancelar que redirige a la misma página sin parámetros de edición -->
                    <a class="cancel-btn" href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">Cancelar</a>
                <?php endif; ?>
            </form>

            <!-- Mostrar pizzas mas vendidas -->
            <?php
            pizzasMasVendidas($conn);
            ?>

        </div>
    </div>

    <script>
        // MOSTRAR/OCULTAR CERRAR SESIÓN
        const arrowDown = document.querySelector(".arrow-down");
        const cerrarSesion = document.getElementById("cerrar-sesion");

        function toggleCerrarSesion() {
            cerrarSesion.style.display = cerrarSesion.style.display === "none" ? "flex" : "none";
        }

        arrowDown.addEventListener("click", toggleCerrarSesion);

        // CONFIRMAR ELIMINAR PIZZA
        function confirmarEliminar(nombrePizza) {
            if (confirm("¿Estás seguro de que deseas eliminar la pizza " + nombrePizza + "?")) {
                window.location.href = "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?eliminar=" + nombrePizza;
            }
        }
    </script>
</body>

</html>
</body>

</html>