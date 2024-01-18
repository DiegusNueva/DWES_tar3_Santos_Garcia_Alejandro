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

$conn = conectarBD();

function listarPizzas($conn)
{
    $consulta = $conn->prepare("SELECT nombre, precio, ingredientes FROM pizzas");
    $consulta->execute();

    echo "<table border='1'>";
    echo "<tr><th>Nombre</th><th>Precio</th><th>Ingredientes</th></tr>";

    foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "<tr>";
        echo "<td>" . $row["nombre"] . "</td>";
        echo "<td>" . $row["precio"] . "€</td>";
        echo "<td><i>" . $row["ingredientes"] . "</i></td>";
        echo "</tr>";
    }

    echo "</table>";
}

session_start();

// FUNCIÓN PARA AGREGAR PIZZAS A PEDIDOS
function agregarPedido($conn, $id_cliente, $fecha_pedido, $detalle_pedido, $total)
{
    try {
        $consulta = $conn->prepare("INSERT INTO pedidos (id_cliente, fecha_pedido, detalle_pedido, total) VALUES (:id_cliente, :fecha_pedido, :detalle_pedido, :total)");
        $consulta->bindParam(":id_cliente", $id_cliente);
        $consulta->bindParam(":fecha_pedido", $fecha_pedido);
        $consulta->bindParam(":detalle_pedido", $detalle_pedido);
        $consulta->bindParam(":total", $total);
        $consulta->execute();
        header("Location: gracias.php");
    } catch (Exception $e) {
        echo "Error al agregar el pedido: " . $e->getMessage();
    }
}

// Lógica para agregar pedido
if (isset($_POST["pizzas"]) && isset($_POST["cantidades"])) {
    session_start();
    $id_cliente = $_SESSION["id"];
    $fecha_pedido = date("Y-m-d H:i:s");
    $detalle_pedido = "";
    $total = 0;

    $pizzas = $_POST["pizzas"];
    $cantidades = $_POST["cantidades"];

    // Verificar que las arrays tengan la misma longitud
    if (count($pizzas) === count($cantidades)) {
        foreach ($pizzas as $key => $pizza_id) {
            $consulta = $conn->prepare("SELECT nombre, precio FROM pizzas WHERE id = :id");
            $consulta->bindParam(":id", $pizza_id);
            $consulta->execute();
            $row = $consulta->fetch(PDO::FETCH_ASSOC);

            // Detalle pedido: id_pizza x cantidad
            $detalle_pedido .= $row["nombre"] . " x " . $cantidades[$key] . ", ";
            $total += $row["precio"] * $cantidades[$key];
        }

        // Eliminar la última coma y espacio en blanco
        $detalle_pedido = rtrim($detalle_pedido, ", ");

        agregarPedido($conn, $id_cliente, $fecha_pedido, $detalle_pedido, $total);
        header("Location: gracias.php");
    } else {
        echo "Error al procesar pedido.";
    }
}

// Cerrar sesión al hacer clic en "Cerrar sesión"
if (isset($_GET['cerrar_sesion'])) {
    session_start();
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
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/admin_pedido.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body>

    <div class="container">
        <header>
            <img width="250px" src="assets/DominosLogo.png" alt="Domino's Logo">
            <div>
                <?php
                //session_start();
                if (isset($_SESSION["nombre"])) {
                    echo "<h2>Bienvenido " . $_SESSION["nombre"] . "<span class='arrow-down'>▼</span></h2>";
                }
                ?>
                <a href="?cerrar_sesion" id="cerrar-sesion" style="display: none;">Cerrar sesión</a>
            </div>
        </header>
        <img src="assets/oferta.gif" alt="Domino's Oferta" class="oferta-img">
        <input type="button" value="Comenzar pedido" id="mostrar-pedido">
        <div class="pedido" style="display: none;">
            <form action="" method="post">
                <div id="pizzas-pedido">
                    <div class="pizza-item">
                        <select name="pizzas[]" class="pizza-select">
                            <option value="0">Selecciona una pizza</option>
                            <?php
                            $consulta = $conn->prepare("SELECT id, nombre FROM pizzas");
                            $consulta->execute();
                            foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                echo "<option value=" . $row["id"] . ">" . $row["nombre"] . "</option>";
                            }
                            ?>
                        </select>
                        <input type="number" name="cantidades[]" class="pizza-cantidad" min="1" max="10" value="1" style="margin-left: 6px;">
                    </div>
                </div>
                <input type="button" value="Agregar pizza" onclick="agregarPizza()" class="agregar-pizza">
                <input type="submit" value="Pagar" class="pagar-boton">
            </form>
        </div>
        <h1>Nuestras pizzas</h1>
        <?php
        listarPizzas($conn);
        ?>
    </div>

    <script>
        // MOSTRAR/OCULTAR PEDIDO
        const mostrarPedidoBtn = document.getElementById("mostrar-pedido");
        const pizzasPedidoDiv = document.getElementById("pizzas-pedido");

        const agregarPizza = () => {
            const pizzaDiv = document.createElement("div");
            pizzaDiv.classList.add("pizza-item");

            const nuevoSelect = document.createElement("select");
            nuevoSelect.name = "pizzas[]";
            nuevoSelect.classList.add("pizza-select");
            nuevoSelect.innerHTML = document.querySelector(".pizza-select").innerHTML;
            pizzaDiv.appendChild(nuevoSelect);

            const nuevoInput = document.createElement("input");
            nuevoInput.type = "number";
            nuevoInput.name = "cantidades[]";
            nuevoInput.classList.add("pizza-cantidad");
            nuevoInput.min = "1";
            nuevoInput.max = "10";
            nuevoInput.value = "1";
            pizzaDiv.appendChild(nuevoInput);

            const eliminarPizzaSpan = document.createElement("span");
            eliminarPizzaSpan.classList.add("eliminar-pizza");
            eliminarPizzaSpan.textContent = "❌";
            eliminarPizzaSpan.onclick = function() {
                eliminarPizza(this);
            };
            pizzaDiv.appendChild(eliminarPizzaSpan);

            pizzasPedidoDiv.appendChild(pizzaDiv);
        };

        const eliminarPizza = (element) => {
            const pizzaItem = element.parentElement;
            pizzaItem.remove();
        };

        const mostrarPedido = () => {
            const pedido = document.querySelector(".pedido");
            pedido.style.display = pedido.style.display === "none" ? "block" : "none";

            mostrarPedidoBtn.value = mostrarPedidoBtn.value === "Comenzar pedido" ? "Ocultar pedido" : "Comenzar pedido";
        };

        mostrarPedidoBtn.addEventListener("click", mostrarPedido);

        // MOSTRAR/OCULTAR CERRAR SESIÓN
        const arrowDown = document.querySelector(".arrow-down");
        const cerrarSesion = document.getElementById("cerrar-sesion");

        function toggleCerrarSesion() {
            cerrarSesion.style.display = cerrarSesion.style.display === "none" ? "flex" : "none";
        }

        arrowDown.addEventListener("click", toggleCerrarSesion);
    </script>
</body>

</html>