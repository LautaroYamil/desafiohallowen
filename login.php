<?php
session_start();
include('db.php');

$error = "";

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verificar_token_csrf($_POST['csrf_token'])) {
        $error = "Error de seguridad. Intenta nuevamente.";
    } else {
        // Validar y limpiar entradas
        $nombre = isset($_POST['nombre']) ? limpiar_entrada($con, $_POST['nombre']) : '';
        $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
        
        if (empty($nombre) || empty($clave)) {
            $error = "Todos los campos son obligatorios.";
        } else {
            // Usar prepared statements para evitar SQL Injection
            $query = "SELECT id, nombre, clave FROM usuarios WHERE nombre = ?";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt, "s", $nombre);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            
            if ($user && password_verify($clave, $user['clave'])) {
                // Regenerar ID de sesión para prevenir session fixation
                session_regenerate_id(true);
                
                // Guardar datos en sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                $_SESSION['tiempo_login'] = time();
                
                // Redirigir según el tipo de usuario
                if ($user['nombre'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Generar token CSRF
$csrf_token = generar_token_csrf();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Halloween</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>🎃 Iniciar Sesión 🎃</h1>
        
        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="nombre">Nombre de Usuario:</label>
                <input type="text" name="nombre" id="nombre" required
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="clave">Contraseña:</label>
                <input type="password" name="clave" id="clave" required>
            </div>

            <button type="submit">Iniciar Sesión</button>
        </form>
        
        <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>
</body>
</html>