<?php
include('db.php');

// Verificar si el usuario es administrador
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$query = "SELECT nombre FROM usuarios WHERE id = '$usuario_id'";
$result = mysqli_query($con, $query);
$user = mysqli_fetch_assoc($result);

if ($user['nombre'] != 'admin') {
    echo "Acceso denegado.";
    exit();
}

// Consultar los disfraces con sus votos
$query = "SELECT * FROM disfraces WHERE eliminado = 0";
$result = mysqli_query($con, $query);

echo "<h1>Votaciones de Disfraces</h1>";
echo "<table border='1'>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Votos</th>
        </tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['nombre'] . "</td>";
    echo "<td>" . $row['descripcion'] . "</td>";
    echo "<td>" . $row['votos'] . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
