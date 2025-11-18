<?php
session_start();
include('db.php');

$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verificar_token_csrf($_POST['csrf_token'])) {
        $error = "Error de seguridad. Intenta nuevamente.";
    } else {
        // Validar y limpiar entradas
        $nombre = isset($_POST['nombre']) ? limpiar_entrada($con, $_POST['nombre']) : '';
        $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
        
        // Validaciones
        if (empty($nombre) || empty($clave)) {
            $error = "Todos los campos son obligatorios.";
        } elseif (strlen($nombre) < 3 || strlen($nombre) > 50) {
            $error = "El nombre debe tener entre 3 y 50 caracteres.";
        } elseif (strlen($clave) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } else {
            // Verificar si el usuario ya existe
            $check_query = "SELECT id FROM usuarios WHERE nombre = ?";
            $stmt = mysqli_prepare($con, $check_query);
            mysqli_stmt_bind_param($stmt, "s", $nombre);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "El nombre de usuario ya está en uso.";
            } else {
                // Hash de la contraseña
                $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
                
                // Insertar usuario usando prepared statements
                $insert_query = "INSERT INTO usuarios (nombre, clave) VALUES (?, ?)";
                $stmt_insert = mysqli_prepare($con, $insert_query);
                mysqli_stmt_bind_param($stmt_insert, "ss", $nombre, $clave_hash);
                
                if (mysqli_stmt_execute($stmt_insert)) {
                    $mensaje = "Usuario registrado correctamente. <a href='login.php'>Iniciar sesión</a>";
                } else {
                    $error = "Error al registrar usuario: " . mysqli_error($con);
                }
                mysqli_stmt_close($stmt_insert);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Generar nuevo token CSRF
$csrf_token = generar_token_csrf();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Halloween</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>🎃 Registro de Usuario 🎃</h1>
        
        <?php if ($mensaje): ?>
            <div class="mensaje-exito"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="nombre">Nombre de Usuario:</label>
                <input type="text" name="nombre" id="nombre" required 
                       minlength="3" maxlength="50"
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="clave">Contraseña:</label>
                <input type="password" name="clave" id="clave" required minlength="6">
                <small>Mínimo 6 caracteres</small>
            </div>

            <button type="submit">Registrar</button>
        </form>
        
        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
</body>
</html>