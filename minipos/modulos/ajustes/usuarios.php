<?php
// Definir la ruta base si no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', '/minipos/');
}

// Mostrar errores para depuración (mantener esto en OFF para producción)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Incluir JS adicional para la página
$extra_js = '<script src="' . BASE_URL . 'assets/js/usuarios.js"></script>';

// Título de la página
$page_title = 'Gestión de Usuarios';

// Incluir header
include_once '../../includes/header.php';

// Verificar autenticación
requireLogin();

// Verificar permiso
if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Manejar acciones AJAX
if (isset($_GET['action'])) {
    // Obtener datos de un usuario
    if ($_GET['action'] == 'get_user' && isset($_GET['id'])) {
        $user_id = (int)$_GET['id'];
        
        try {
            // Asegurarse de que no haya salida previa
            ob_clean();
            
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$user_id]);
            
            header('Content-Type: application/json; charset=UTF-8');
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            }
        } catch (PDOException $e) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        exit;
    }
    
    // Eliminar usuario
    if ($_GET['action'] == 'delete' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $user_id = (int)$_POST['id'];
        
        // No permitir eliminar el propio usuario
        if ($user_id == $_SESSION['usuario_id']) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'No puede eliminar su propio usuario']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE usuarios SET estado = 0 WHERE id = ?");
            $stmt->execute([$user_id]);
            
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        exit;
    }
}

// Manejar POST para crear/editar usuarios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Crear nuevo usuario
    if (isset($_POST['create'])) {
        $nombre = cleanInput($_POST['nombre']);
        $usuario = cleanInput($_POST['usuario']);
        $password = $_POST['password'];
        $rol = cleanInput($_POST['rol']);
        $estado = isset($_POST['estado']) ? 1 : 0;
        
        // Validaciones
        if (empty($nombre) || empty($usuario) || empty($password) || empty($rol)) {
            $error = "Todos los campos son obligatorios";
        } else {
            try {
                // Verificar que el usuario no exista
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
                $stmt->execute([$usuario]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "El nombre de usuario ya existe";
                } else {
                    // Crear hash de la contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insertar usuario
                    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, estado) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $usuario, $password_hash, $rol, $estado]);
                    
                    $success = "Usuario creado correctamente";
                }
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
    
    // Editar usuario existente
    if (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $nombre = cleanInput($_POST['nombre']);
        $usuario = cleanInput($_POST['usuario']);
        $password = $_POST['password']; // Podría estar vacío si no se cambia
        $rol = cleanInput($_POST['rol']);
        $estado = isset($_POST['estado']) ? 1 : 0;
        
        // Validaciones
        if (empty($nombre) || empty($usuario) || empty($rol)) {
            $error = "Nombre, usuario y rol son obligatorios";
        } else {
            try {
                // Verificar que el usuario no exista (excepto este mismo)
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
                $stmt->execute([$usuario, $id]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "El nombre de usuario ya existe";
                } else {
                    // Actualizar con o sin contraseña
                    if (!empty($password)) {
                        // Con nueva contraseña
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, password = ?, rol = ?, estado = ? WHERE id = ?");
                        $stmt->execute([$nombre, $usuario, $password_hash, $rol, $estado, $id]);
                    } else {
                        // Sin cambiar contraseña
                        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, estado = ? WHERE id = ?");
                        $stmt->execute([$nombre, $usuario, $rol, $estado, $id]);
                    }
                    
                    $success = "Usuario actualizado correctamente";
                }
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Obtener lista de usuarios
try {
    $stmt = $conn->prepare("SELECT * FROM usuarios ORDER BY id ASC");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
}
?>

<!-- Indicador de ruta de API para JavaScript -->
<script>
    var apiUrl = '<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>';
</script>

<div class="content-header">
    <h1 class="h3 mb-0"><i class="fas fa-users me-2"></i> Usuarios del Sistema</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="fas fa-plus-circle"></i> Nuevo Usuario
    </button>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Tabla de usuarios -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Usuarios del Sistema</h5>
            <div class="input-group" style="width: 300px;">
                <input type="text" id="searchInput" class="form-control" placeholder="Buscar usuario...">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Login</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo $usuario['nombre']; ?></td>
                        <td><?php echo $usuario['usuario']; ?></td>
                        <td>
                            <?php if ($usuario['rol'] == 'admin'): ?>
                                <span class="badge bg-primary">Administrador</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Cajero</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($usuario['estado'] == 1): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $usuario['ultimo_login'] ? formatearFecha($usuario['ultimo_login']) : 'Nunca'; ?>
                        </td>
                        <td>
                            <a href="#" class="action-edit btn btn-primary btn-sm" data-id="<?php echo $usuario['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                            <a href="#" class="action-delete btn btn-danger btn-sm" data-id="<?php echo $usuario['id']; ?>" data-name="<?php echo $usuario['nombre']; ?>">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay usuarios registrados</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createUserModalLabel">Crear Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="admin">Administrador</option>
                            <option value="cajero" selected>Cajero</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="estado" name="estado" checked>
                        <label class="form-check-label" for="estado">Usuario Activo</label>
                    </div>
                    <button type="submit" name="create" class="btn btn-primary">Crear Usuario</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_usuario" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="edit_usuario" name="usuario" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Contraseña (dejar en blanco para mantener actual)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <small class="form-text text-muted">Si no desea cambiar la contraseña, deje este campo en blanco.</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_rol" class="form-label">Rol</label>
                        <select class="form-select" id="edit_rol" name="rol" required>
                            <option value="admin">Administrador</option>
                            <option value="cajero">Cajero</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_estado" name="estado">
                        <label class="form-check-label" for="edit_estado">Usuario Activo</label>
                    </div>
                    <button type="submit" name="edit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include_once '../../includes/footer.php';
?>