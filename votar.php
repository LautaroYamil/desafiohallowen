<?php
session_start();
include('db.php');

$error = "";
$mensaje = "";

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verificar_token_csrf($_POST['csrf_token'])) {
        $error = "Error de seguridad. Intenta nuevamente.";
    } else {
        
        // Validar datos recibidos
        if (!isset($_POST['disfraz_id']) || !is_numeric($_POST['disfraz_id'])) {
            $error = "ID de disfraz inválido.";
        } else {
            
            $disfraz_id = (int)$_POST['disfraz_id'];
            $usuario_id = (int)$_SESSION['usuario_id'];
            
            // Verificar que el disfraz existe y no está eliminado
            $check_disfraz = "SELECT id FROM disfraces WHERE id = ? AND eliminado = 0";
            $stmt = mysqli_prepare($con, $check_disfraz);
            mysqli_stmt_bind_param($stmt, "i", $disfraz_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) === 0) {
                $error = "El disfraz no existe o fue eliminado.";
            } else {
                
                // Verificar si el usuario ya votó por este disfraz
                $check_vote = "SELECT id FROM votos WHERE id_usuario = ? AND id_disfraz = ?";
                $stmt_vote = mysqli_prepare($con, $check_vote);
                mysqli_stmt_bind_param($stmt_vote, "ii", $usuario_id, $disfraz_id);
                mysqli_stmt_execute($stmt_vote);
                mysqli_stmt_store_result($stmt_vote);
                
                if (mysqli_stmt_num_rows($stmt_vote) > 0) {
                    $error = "Ya has votado por este disfraz.";
                } else {
                    
                    // Iniciar transacción
                    mysqli_begin_transaction($con);
                    
                    try {
                        // Insertar voto
                        $insert_vote = "INSERT INTO votos (id_usuario, id_disfraz) VALUES (?, ?)";
                        $stmt_insert = mysqli_prepare($con, $insert_vote);
                        mysqli_stmt_bind_param($stmt_insert, "ii", $usuario_id, $disfraz_id);
                        
                        if (!mysqli_stmt_execute($stmt_insert)) {
                            throw new Exception("Error al registrar el voto");
                        }
                        
                        // Incrementar contador de votos
                        $update_votes = "UPDATE disfraces SET votos = votos + 1 WHERE id = ?";
                        $stmt_update = mysqli_prepare($con, $update_votes);
                        mysqli_stmt_bind_param($stmt_update, "i", $disfraz_id);
                        
                        if (!mysqli_stmt_execute($stmt_update)) {
                            throw new Exception("Error al actualizar contador de votos");
                        }
                        
                        // Confirmar transacción
                        mysqli_commit($con);
                        
                        $mensaje = "¡Voto registrado correctamente! Gracias por participar.";
                        
                        // Redirigir después de 2 segundos
                        header("refresh:2;url=index.php");
                        
                        mysqli_stmt_close($stmt_insert);
                        mysqli_stmt_close($stmt_update);
                        
                    } catch (Exception $e) {
                        // Revertir transacción en caso de error
                        mysqli_rollback($con);
                        $error = "Error al procesar el voto: " . $e->getMessage();
                    }
                }
                mysqli_stmt_close($stmt_vote);
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Si no es POST, redirigir al index
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votar - Halloween</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>🎃 Sistema de Votación 🎃</h1>
        
        <?php if ($mensaje): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <p><a href="index.php">← Volver a la lista de disfraces</a></p>
    </div>
</body>
</html>