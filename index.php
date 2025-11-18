<?php
session_start();
include('db.php');

// Verificar si el usuario está logueado
$logueado = isset($_SESSION['usuario_id']);
$usuario_nombre = $logueado ? $_SESSION['usuario_nombre'] : '';

// Generar token CSRF si está logueado
if ($logueado) {
    $csrf_token = generar_token_csrf();
}

// Consultar disfraces con prepared statement
$query = "SELECT id, nombre, descripcion, votos, foto FROM disfraces WHERE eliminado = 0 ORDER BY votos DESC";
$result = mysqli_query($con, $query);

if (!$result) {
    die("Error en la consulta: " . mysqli_error($con));
}

$num_disfraces = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disfraces de Halloween - Votación</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="header">
        <h1>🎃 Concurso de Disfraces de Halloween 🎃</h1>
        
        <div class="user-info">
            <?php if ($logueado): ?>
                <span>Bienvenido, <strong><?php echo htmlspecialchars($usuario_nombre); ?></strong></span>
                
                <?php if ($usuario_nombre === 'admin'): ?>
                    <a href="admin.php" class="btn-admin">Panel Admin</a>
                <?php endif; ?>
                
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            <?php else: ?>
                <a href="login.php" class="btn-login">Iniciar Sesión</a>
                <a href="registro.php" class="btn-registro">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <?php if (!$logueado): ?>
            <div class="mensaje-info">
                ⚠️ Debes <a href="login.php">iniciar sesión</a> para poder votar por tu disfraz favorito.
            </div>
        <?php endif; ?>

        <h2>Disfraces Participantes (<?php echo $num_disfraces; ?>)</h2>

        <?php if ($num_disfraces > 0): ?>
            <div class="disfraces-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="disfraz-card">
                        
                        <?php if (file_exists("fotos/" . $row['foto'])): ?>
                            <img src="fotos/<?php echo htmlspecialchars($row['foto']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['nombre']); ?>" 
                                 class="disfraz-foto">
                        <?php else: ?>
                            <div class="no-foto">Sin imagen</div>
                        <?php endif; ?>
                        
                        <div class="disfraz-info">
                            <h3><?php echo htmlspecialchars($row['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($row['descripcion']); ?></p>
                            
                            <div class="votos-info">
                                <span class="votos-numero">
                                    👻 <?php echo number_format($row['votos'], 0, ',', '.'); ?> 
                                    voto<?php echo $row['votos'] != 1 ? 's' : ''; ?>
                                </span>
                            </div>
                            
                            <?php if ($logueado): ?>
                                <form method="POST" action="votar.php" class="form-votar">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="disfraz_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-votar">🎃 Votar</button>
                                </form>
                            <?php else: ?>
                                <button class="btn-votar-disabled" disabled>
                                    🔒 Inicia sesión para votar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="mensaje-vacio">
                <p>😢 No hay disfraces disponibles en este momento.</p>
                <p>¡Vuelve pronto para ver los participantes!</p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>🎃 Concurso de Halloween 2024 | Paradigmas y Lenguajes de Programación III</p>
    </footer>
</body>
</html>

<?php
// Liberar resultado
mysqli_free_result($result);
// Cerrar conexión
mysqli_close($con);
?>