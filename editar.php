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
    die("ID de disfraz no válido. <a href='admin.php'>Volver</a>");
}

$id_disfraz = (int)$_GET['id'];

// Consultar el disfraz
$query = "SELECT * FROM disfraces WHERE id = ? AND eliminado = 0";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $id_disfraz);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$disfraz = mysqli_fetch_assoc($result);

if (!$disfraz) {
    die("Disfraz no encontrado. <a href='admin.php'>Volver</a>");
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verificar_token_csrf($_POST['csrf_token'])) {
        $error = "Error de seguridad. Intenta nuevamente.";
    } else {
        
        $nombre = isset($_POST['nombre']) ? limpiar_entrada($con, $_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? limpiar_entrada($con, $_POST['descripcion']) : '';
        
        if (empty($nombre) || empty($descripcion)) {
            $error = "Todos los campos son obligatorios.";
        } else {
            
            $foto_actual = $disfraz['foto'];
            $nueva_foto = $foto_actual;
            
            // Si se sube una nueva foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                
                $foto_tmp = $_FILES['foto']['tmp_name'];
                $foto_name = $_FILES['foto']['name'];
                $foto_size = $_FILES['foto']['size'];
                
                // Verificar que es un archivo subido
                if (!is_uploaded_file($foto_tmp)) {
                    $error = "Error en la carga del archivo.";
                } else {
                    
                    // Validar tipo MIME
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $foto_tmp);
                    finfo_close($finfo);
                    
                    if (!in_array($mime_type, $allowed_types)) {
                        $error = "Solo se permiten imágenes (JPG, PNG, GIF, WEBP).";
                    } elseif ($foto_size > 5 * 1024 * 1024) {
                        $error = "La imagen no debe superar 5MB.";
                    } else {
                        
                        // Obtener extensión
                        $extension = explode(".", $foto_name);
                        $extension = strtolower(end($extension));
                        
                        // Generar nombre único
                        $nombre_unico = time() . '_' . uniqid() . '.' . $extension;
                        $ruta_destino = "fotos/" . $nombre_unico;
                        
                        // Mover archivo
                        if (copy($foto_tmp, $ruta_destino)) {
                            
                            // Eliminar foto antigua
                            if (file_exists('fotos/' . $foto_actual)) {
                                unlink('fotos/' . $foto_actual);
                            }
                            
                            $nueva_foto = $nombre_unico;
                        } else {
                            $error = "Error al guardar la nueva imagen.";
                        }
                    }
                }
            }
            
            // Actualizar en la base de datos si no hay errores
            if (empty($error)) {
                $update_query = "UPDATE disfraces SET nombre = ?, descripcion = ?, foto = ? WHERE id = ?";
                $stmt_update = mysqli_prepare($con, $update_query);
                mysqli_stmt_bind_param($stmt_update, "sssi", $nombre, $descripcion, $nueva_foto, $id_disfraz);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    $mensaje = "Disfraz actualizado correctamente.";
                    
                    // Actualizar datos locales
                    $disfraz['nombre'] = $nombre;
                    $disfraz['descripcion'] = $descripcion;
                    $disfraz['foto'] = $nueva_foto;
                    
                    // Redirigir después de 2 segundos
                    header("refresh:2;url=admin.php");
                } else {
                    $error = "Error al actualizar: " . mysqli_error($con);
                }
                mysqli_stmt_close($stmt_update);
            }
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
    <title>Editar Disfraz - Halloween</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="header">
        <h1>✏️ Editar Disfraz</h1>
        <div class="user-info">
            <a href="admin.php" class="btn-login">← Volver al Panel</a>
        </div>
    </div>

    <div class="container">
        
        <?php if ($mensaje): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Vista previa actual -->
        <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 10px; margin-bottom: 30px; border: 2px solid #ff6600;">
            <h3 style="color: #ff9933; margin-bottom: 15px;">Vista Previa Actual:</h3>
            <div style="display: flex; gap: 20px; align-items: start;">
                <?php if (file_exists("fotos/" . $disfraz['foto'])): ?>
                    <img src="fotos/<?php echo htmlspecialchars($disfraz['foto']); ?>" 
                         alt="<?php echo htmlspecialchars($disfraz['nombre']); ?>" 
                         style="width: 200px; border-radius: 10px; border: 2px solid #ff6600;">
                <?php endif; ?>
                <div>
                    <p><strong style="color: #ff9933;">Nombre:</strong> <?php echo htmlspecialchars($disfraz['nombre']); ?></p>
                    <p><strong style="color: #ff9933;">Descripción:</strong> <?php echo htmlspecialchars($disfraz['descripcion']); ?></p>
                    <p><strong style="color: #ff9933;">Votos:</strong> <?php echo $disfraz['votos']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Formulario de edición -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="nombre">🎭 Nombre del Disfraz:</label>
                <input type="text" 
                       name="nombre" 
                       id="nombre" 
                       value="<?php echo htmlspecialchars($disfraz['nombre']); ?>" 
                       required 
                       maxlength="50">
            </div>

            <div class="form-group">
                <label for="descripcion">📝 Descripción:</label>
                <textarea name="descripcion" 
                          id="descripcion" 
                          required 
                          rows="5"
                          maxlength="500"><?php echo htmlspecialchars($disfraz['descripcion']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="foto">📸 Nueva Foto (opcional - Deja vacío para mantener la actual):</label>
                <input type="file" 
                       name="foto" 
                       id="foto" 
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <small>Solo JPG, PNG, GIF o WEBP. Máximo 5MB.</small>
            </div>

            <button type="submit" style="background: linear-gradient(135deg, #0066ff, #0044cc);">
                💾 Guardar Cambios
            </button>
            
            <a href="admin.php" 
               style="display: inline-block; margin-left: 10px; padding: 12px 25px; background: #666; color: white; text-decoration: none; border-radius: 8px;">
                ❌ Cancelar
            </a>
        </form>
    </div>

    <footer>
        <p>🎃 Edición de Disfraz | Concurso de Halloween 2024</p>
    </footer>
</body>
</html>

<?php
mysqli_close($con);
?>