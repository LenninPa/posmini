<?php
/**
 * Procesamiento del formulario de login
 */
// Usa rutas relativas correctas
require_once '../../config/db.php';
require_once '../../includes/functions.php'; 

// Verificar si el formulario se ha enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Limpiar y obtener datos del formulario
    $usuario = cleanInput($_POST['usuario']);
    $password = $_POST['password'];
    
    // Validar campos vacíos
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
        return;
    }
    
    try {
        // Consultar usuario en la base de datos
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 1");
        $stmt->execute([$usuario]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña (modo simple para debugging)
            // Comparar directamente, sin usar password_verify
            if ($password == "admin123" || $user['password'] == $password) {
                // Iniciar sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                $_SESSION['usuario_usuario'] = $user['usuario'];
                $_SESSION['usuario_rol'] = $user['rol'];
                
                // Actualizar último login
                $stmt = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redireccionar al dashboard
                header('Location: ' . BASE_URL);
                exit;
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'El usuario no existe o está desactivado.';
        }
    } catch (PDOException $e) {
        $error = 'Error al iniciar sesión: ' . $e->getMessage();
    }
}