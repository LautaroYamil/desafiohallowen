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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verificar_token_csrf($_POST['csrf_token'])) {
        $error = "Error de seguridad. Intenta nuevamente.";
    } else {
        // Validar campos
        $nombre = isset($_POST['nombre']) ? limpiar_entrada($con, $_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? limpiar_entrada($con, $_POST['descripcion']) : '';
        
        if (empty($nombre) || empty($descripcion)) {
            $error = "Todos los campos son obligatorios.";
        } elseif (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $error = "Debes subir una foto válida.";
        } else {
            
            // Validar archivo de imagen
            $foto_tmp = $_FILES['foto']['tmp_name'];
            $foto_name = $_FILES['foto']['name'];
            $foto_size = $_FILES['foto']['size'];
            
            // Verificar que el archivo fue subido correctamente
            if (!is_uploaded_file($foto_tmp)) {
                $error = "Error en la carga del archivo.";
            } else {
                
                // Validar tipo de archivo (solo imágenes)
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $foto_tmp);
                finfo_close($finfo);
                
                if (!in_array($mime_type, $allowed_types)) {
                    $error = "Solo se permiten imágenes (JPG, PNG, GIF, WEBP).";
                } elseif ($foto_size > 5 * 1024 * 1024) { // Máximo 5MB
                    $error = "La imagen no debe superar 5MB.";
                } else {
                    
                    // Obtener extensión del archivo
                    $extension = explode(".", $foto_name);
                    $extension = strtolower(end($extension));
                    
                    // Generar nombre único usando timestamp
                    $nombre_unico = time() . '_' . uniqid() . '.' . $extension;
                    $ruta_destino = "fotos/" . $nombre_unico;
                    
                    // Crear directorio si no existe
                    if (!file_exists('fotos')) {
                        mkdir('fotos', 0755, true);
                    }
                    
                    // Mover archivo con copy()
                    if (copy($foto_tmp, $ruta_destino)) {
                        
                        // Insertar en base de datos con prepared statements
                        $query = "INSERT INTO disfraces (nombre, descripcion, votos, foto, eliminado) 
                                  VALUES (?, ?, 0, ?, 0)";
                        $stmt = mysqli_prepare($con, $query);
                        mysqli_stmt_bind_param($stmt, "sss", $nombre, $descripcion, $nombre_unico);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $id_insertado = mysqli_insert_id($con);
                            $mensaje = "Disfraz agregado correctamente con ID: " . $id_insertado;
                            
                            // Redirigir después de 2 segundos
                            header("refresh:2;url=admin.php");
                        } else {
                            $error = "Error al guardar en la base de datos: " . mysqli_error($con);
                            // Eliminar archivo si falla la BD
                            unlink($ruta_destino);
                        }
                        mysqli_stmt_close($stmt);
                        
                    } else {
                        $error = "Error al guardar la imagen en el servidor.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Disfraz</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Agregar Nuevo Disfraz</h1>
        
        <?php if ($mensaje): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <a href="admin.php">← Volver al panel de administración</a>
    </div>
</body>
</html>