<?php
session_start();
include('db.php');

// Verificar autenticación y permisos de admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nombre'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = "";
$mensaje = "";

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje_error'] = "ID de disfraz no válido.";
    header("Location: admin.php");
    exit();
}

// Verificar token CSRF
if (!isset($_GET['csrf_token']) || !verificar_token_csrf($_GET['csrf_token'])) {
    $_SESSION['mensaje_error'] = "Error de seguridad. Token CSRF inválido.";
    header("Location: admin.php");
    exit();
}

$id_disfraz = (int)$_GET['id'];

// Verificar que el disfraz existe
$query = "SELECT id, nombre, foto FROM disfraces WHERE id = ? AND eliminado = 0";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $id_disfraz);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$disfraz = mysqli_fetch_assoc($result);

if (!$disfraz) {
    $_SESSION['mensaje_error'] = "Disfraz no encontrado o ya fue eliminado.";
    header("Location: admin.php");
    exit();
}

// Iniciar transacción
mysqli_begin_transaction($con);

try {
    // Marcar como eliminado (soft delete)
    $update_query = "UPDATE disfraces SET eliminado = 1 WHERE id = ?";
    $stmt_update = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt_update, "i", $id_disfraz);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Error al marcar como eliminado");
    }
    
    // Opcional: Eliminar todos los votos asociados
    $delete_votos = "DELETE FROM votos WHERE id_disfraz = ?";
    $stmt_votos = mysqli_prepare($con, $delete_votos);
    mysqli_stmt_bind_param($stmt_votos, "i", $id_disfraz);
    mysqli_stmt_execute($stmt_votos);
    
    // Confirmar transacción
    mysqli_commit($con);
    
    // Opcional: Eliminar la foto física del servidor
    // NOTA: Comentado por seguridad, podrías descomentar si deseas eliminar físicamente
    /*
    if (file_exists('fotos/' . $disfraz['foto'])) {
        unlink('fotos/' . $disfraz['foto']);
    }
    */
    
    $_SESSION['mensaje_exito'] = "Disfraz '" . htmlspecialchars($disfraz['nombre']) . "' eliminado correctamente.";
    
    mysqli_stmt_close($stmt_update);
    mysqli_stmt_close($stmt_votos);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($con);
    $_SESSION['mensaje_error'] = "Error al eliminar el disfraz: " . $e->getMessage();
}

// Redirigir al panel de admin
header("Location: admin.php");
exit();

?>