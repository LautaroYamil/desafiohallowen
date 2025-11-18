# 🎃 Concurso de Disfraces de Halloween

Sistema web de votación para disfraces de Halloween desarrollado en PHP y MySQL.

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

---

## 📋 Descripción

Aplicación web que permite a los usuarios:
- 👥 Registrarse y autenticarse de forma segura
- 🎭 Ver disfraces participantes con fotos y descripciones
- 🗳️ Votar por su disfraz favorito (un voto por disfraz)
- 👑 Ver el ranking de disfraces más votados

Los administradores pueden:
- ➕ Agregar nuevos disfraces con imágenes
- ✏️ Editar disfraces existentes
- 🗑️ Eliminar disfraces
- 📊 Ver estadísticas del concurso

---

## ✨ Características

### 🔐 Seguridad
- **Protección contra SQL Injection** mediante Prepared Statements
- **Protección contra XSS** con `htmlspecialchars()`
- **Protección CSRF** mediante tokens en formularios
- **Contraseñas hasheadas** con `password_hash()` (bcrypt)
- **Validación de archivos** por tipo MIME y tamaño
- **Sesiones seguras** con regeneración de ID

### 🎨 Funcionalidades
- Sistema de autenticación completo
- Registro de usuarios con validaciones
- Panel de administración (CRUD completo)
- Sistema de votación con prevención de duplicados
- Carga y validación de imágenes
- Soft delete (eliminación lógica)
- Transacciones en operaciones críticas
- Diseño responsive con tema Halloween

---

## 🛠️ Tecnologías

- **Backend:** PHP 7.4+
- **Base de Datos:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3
- **Servidor:** Apache (XAMPP, WAMP, LAMP)

---

## 📦 Requisitos Previos

- Apache 2.4+
- PHP 7.4 o superior
- MySQL 5.7+ o MariaDB
- Extensión PHP: `mysqli`
- Extensión PHP: `gd` (para imágenes)

---

## 🚀 Instalación

### 1. Clonar o descargar el proyecto

```bash
git clone https://github.com/tuusuario/halloween-contest.git
cd halloween-contest
```

O descarga el ZIP y extráelo en la carpeta de tu servidor:
- **XAMPP:** `C:\xampp\htdocs\halloween\`
- **WAMP:** `C:\wamp64\www\halloween\`
- **LAMP:** `/var/www/html/halloween/`

### 2. Crear la base de datos

1. Abre phpMyAdmin: `http://localhost/phpmyadmin`
2. Haz clic en "Nueva" para crear una base de datos
3. Nombre: `halloween`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Haz clic en la pestaña "SQL"
6. Ejecuta el script `setup_halloween.sql` (contenido más abajo)

### 3. Configurar la conexión (opcional)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        
define('DB_PASS', '');            
define('DB_NAME', 'halloween');
```

### 4. Crear carpeta de fotos

Crea la carpeta `fotos/` en la raíz del proyecto:

```bash
mkdir fotos
chmod 755 fotos  # En Linux/Mac
```

### 5. Acceder a la aplicación

- **Página principal:** `http://localhost/halloween/`
- **Panel admin:** `http://localhost/halloween/admin.php`


## 📁 Estructura del Proyecto

```
halloween/
├── admin.php           # Panel de administración
├── agregar.php         # Agregar nuevo disfraz
├── db.php              # Conexión a base de datos
├── editar.php          # Editar disfraz existente
├── eliminar.php        # Eliminar disfraz
├── index.php           # Página principal (listado)
├── login.php           # Inicio de sesión
├── logout.php          # Cerrar sesión
├── registro.php        # Registro de usuarios
├── votar.php           # Procesar voto
├── votaciones.php      # Ver resultados (admin)
├── styles.css          # Estilos CSS
├── fotos/              # Carpeta para imágenes
├── setup_halloween.sql # Script SQL de instalación
└── README.md           # Este archivo
```

---

## 💾 Script SQL

```sql
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS halloween CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE halloween;

-- Tabla usuarios
CREATE TABLE usuarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    clave TEXT NOT NULL,
    rol VARCHAR(20) DEFAULT 'usuario',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla disfraces
CREATE TABLE disfraces (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,
    votos INT(11) NOT NULL DEFAULT 0,
    foto VARCHAR(100) NOT NULL,
    foto_blob BLOB NULL,
    eliminado INT(11) NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla votos
CREATE TABLE votos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_usuario INT(11) NOT NULL,
    id_disfraz INT(11) NOT NULL,
    fecha_voto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY voto_unico (id_usuario, id_disfraz),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_disfraz) REFERENCES disfraces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario administrador (admin / admin123)
INSERT INTO usuarios (nombre, clave, rol) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
```

---

## 📖 Uso

### Para Usuarios

1. **Registrarse:**
   - Ve a `registro.php`
   - Completa el formulario
   - Inicia sesión

2. **Votar:**
   - Navega por los disfraces en `index.php`
   - Haz clic en "🎃 Votar" en tu favorito
   - Solo puedes votar una vez por disfraz

### Para Administradores

1. **Inicia sesión:**
   - Ve a `login.php`
   - Usuario: `admin`
   - Contraseña: `admin123`

2. **Agregar disfraces:**
   - Accede al panel admin
   - Completa el formulario
   - Sube una imagen (JPG, PNG, GIF, WEBP)
   - Máximo 5MB por imagen

3. **Gestionar disfraces:**
   - Edita: Modifica nombre, descripción o foto
   - Elimina: Eliminación lógica (soft delete)

---

## 🔒 Características de Seguridad

### Implementadas

✅ **Prepared Statements** - Prevención de SQL Injection  
✅ **Password Hashing** - bcrypt con `password_hash()`  
✅ **CSRF Tokens** - Protección contra Cross-Site Request Forgery  
✅ **XSS Prevention** - Escapado de HTML con `htmlspecialchars()`  
✅ **File Validation** - Validación de tipo MIME y tamaño  
✅ **Session Security** - Regeneración de ID después del login  
✅ **Input Sanitization** - Limpieza de datos de entrada  
✅ **Transacciones** - Integridad en operaciones críticas  

### Recomendaciones Adicionales

- [ ] Implementar HTTPS en producción
- [ ] Agregar límite de intentos de login
- [ ] Implementar recuperación de contraseña
- [ ] Agregar logs de seguridad
- [ ] Implementar rate limiting
- [ ] Usar variables de entorno para credenciales

---

## 🎨 Capturas de Pantalla

### Página Principal
> Listado de disfraces con sistema de votación

### Panel de Administración
> Gestión completa de disfraces (CRUD)

### Sistema de Login
> Autenticación segura de usuarios

---

## 🐛 Solución de Problemas

### Error: "No se puede conectar a la base de datos"
**Solución:** Verifica las credenciales en `db.php` y que MySQL esté corriendo.

### Error: "Call to undefined function mysqli_connect()"
**Solución:** Activa la extensión mysqli en `php.ini`:
```ini
extension=mysqli
```

### Las imágenes no se muestran
**Solución:** 
- Verifica que la carpeta `fotos/` existe
- Verifica permisos: `chmod 755 fotos/`

### Error 404 al acceder a la aplicación
**Solución:** 
- Verifica que los archivos estén en la carpeta correcta
- Verifica que Apache esté corriendo
- Prueba: `http://localhost/halloween/index.php`

---

## 📝 Funciones PHP Utilizadas (Según la Guía)

| Función | Uso en el Proyecto |
|---------|-------------------|
| `mysqli_connect()` | Conexión a la base de datos |
| `mysqli_query()` | Consultas SQL (usar con precaución) |
| `mysqli_prepare()` | Prepared statements (RECOMENDADO) |
| `mysqli_num_rows()` | Contar resultados de consultas |
| `mysqli_insert_id()` | ID del último registro insertado |
| `mysqli_real_escape_string()` | Escapar strings (backup) |
| `password_hash()` | Hashear contraseñas |
| `password_verify()` | Verificar contraseñas |
| `$_FILES['foto']['name']` | Nombre del archivo subido |
| `explode()` | Separar string por delimitador |
| `end()` | Obtener último elemento de array |
| `is_uploaded_file()` | Verificar archivo subido |
| `time()` | Timestamp actual |
| `copy()` | Copiar archivos |
| `unlink()` | Eliminar archivos |
| `isset()` | Verificar si variable existe |
| `file_exists()` | Verificar si archivo existe |
| `number_format()` | Formatear números |

---

## 📄 Licencia

Este proyecto fue desarrollado como parte del curso **Paradigmas y Lenguajes de Programación III**.

---

## 👨‍💻 Autor

**Lautaro**  
Proyecto: Desafío de Halloween 2025 
Curso: Paradigmas y Lenguajes de Programación III

---

</div>