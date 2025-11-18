<?php
session_start();
include('db.php');

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar si es administrador
$usuario_id = (int)$_SESSION['usuario_id'];
$query = "SELECT nombre, rol FROM usuarios WHERE id = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user || $user['nombre'] !== 'admin') {
    die("<h1>❌ Acceso Denegado</h1><p>Solo el administrador puede acceder a esta página.</p><a href='index.php'>Volver al inicio</a>");
}

// Consultar disfraces
$query_disfraces = "SELECT * FROM disfraces WHERE eliminado = 0 ORDER BY votos DESC";
$result_disfraces = mysqli_query($con, $query_disfraces);

if (!$result_disfraces) {
    die("Error al cargar disfraces: " . mysqli_error($con));
}

$total_disfraces = mysqli_num_rows($result_disfraces);

// Calcular estadísticas
$query_stats = "SELECT 
    COUNT(*) as total_usuarios,
    (SELECT COUNT(*) FROM votos) as total_votos,
    (SELECT SUM(votos) FROM disfraces WHERE eliminado = 0) as votos_totales
FROM usuarios";
$result_stats = mysqli_query($con, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Generar token CSRF
$csrf_token = generar_token_csrf();

// Capturar mensajes de sesión
$mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : '';
$mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : '';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Halloween</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 102, 0, 0.2);
            border: 2px solid #ff6600;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            color: #ff9933;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            color: #fff;
            font-weight: bold;
        }
        
        .admin-table {
            overflow-x: auto;
        }
        
        .admin-table table {
            width: 100%;
            min-width: 800px;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn-editar, .btn-eliminar, .btn-agregar {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .btn-editar {
            background: #0066ff;
            color: white;
        }
        
        .btn-editar:hover {
            background: #0052cc;
        }
        
        .btn-eliminar {
            background: #ff0000;
            color: white;
        }
        
        .btn-eliminar:hover {
            background: #cc0000;
        }
        
        .btn-agregar {
            background: linear-gradient(135deg, #00cc00, #009900);
            color: white;
            padding: 12px 30px;
            font-size: 1.1rem;
        }
        
        .btn-agregar:hover {
            background: linear-gradient(135deg, #00ff00, #00cc00);
        }
        
        .form-agregar {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #ff6600;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .form-agregar h2 {
            text-align: left;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎃 Panel de Administración 🎃</h1>
        <div class="user-info">
            <span>Admin: <strong><?php echo htmlspecialchars($user['nombre']); ?></strong></span>
            <a href="index.php" class="btn-login">Ver Sitio</a>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        
        <!-- Mensajes -->
        <?php if ($mensaje_exito): ?>
            <div class="mensaje-exito"><?php echo $mensaje_exito; ?></div>
        <?php endif; ?>
        
        <?php if ($mensaje_error): ?>
            <div class="mensaje-error"><?php echo $mensaje_error; ?></div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>👻 Disfraces</h3>
                <div class="number"><?php echo $total_disfraces; ?></div>
            </div>
            <div class="stat-card">
                <h3>👥 Usuarios</h3>
                <div class="number"><?php echo $stats['total_usuarios']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🗳️ Votos Totales</h3>
                <div class="number"><?php echo $stats['votos_totales'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Formulario para agregar disfraz -->
        <div class="form-agregar">
            <h2>➕ Agregar Nuevo Disfraz</h2>
            <form method="POST" action="agregar.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="nombre">🎭 Nombre del Disfraz:</label>
                    <input type="text" name="nombre" id="nombre" required maxlength="50">
                </div>

                <div class="form-group">
                    <label for="descripcion">📝 Descripción:</label>
                    <textarea name="descripcion" id="descripcion" required rows="4" maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label for="foto">📸 Foto (JPG, PNG, GIF, WEBP - Máx. 5MB):</label>
                    <input type="file" name="foto" id="foto" required accept="image/jpeg,image/png,image/gif,image/webp">
                </div>

                <button type="submit" class="btn-agregar">🎃 Agregar Disfraz</button>
            </form>
        </div>

        <!-- Tabla de disfraces -->
        <h2 style="margin-top: 40px;">📋 Gestión de Disfraces</h2>
        
        <?php if ($total_disfraces > 0): ?>
            <div class="admin-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Foto</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Votos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_disfraces)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <?php if (file_exists("fotos/" . $row['foto'])): ?>
                                        <img src="fotos/<?php echo htmlspecialchars($row['foto']); ?>" 
                                             alt="<?php echo htmlspecialchars($row['nombre']); ?>" 
                                             width="80" 
                                             style="border-radius: 5px;">
                                    <?php else: ?>
                                        <span style="color: #999;">Sin imagen</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($row['descripcion'], 0, 100)); ?><?php echo strlen($row['descripcion']) > 100 ? '...' : ''; ?></td>
                                <td>
                                    <strong style="color: #ff9933;">
                                        <?php echo number_format($row['votos'], 0, ',', '.'); ?>
                                    </strong>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn-editar">✏️ Editar</a>
                                        <a href="eliminar.php?id=<?php echo $row['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" 
                                           class="btn-eliminar"
                                           onclick="return confirm('¿Estás seguro de eliminar este disfraz?')">
                                           🗑️ Eliminar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="mensaje-vacio">
                <p>😢 No hay disfraces registrados todavía.</p>
                <p>¡Usa el formulario de arriba para agregar el primero!</p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>🎃 Panel de Administración | Concurso de Halloween 2024</p>
    </footer>
</body>
</html>

<?php
mysqli_close($con);
?>