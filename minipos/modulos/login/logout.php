<?php
/**
 * Cierre de sesión
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir ruta base
define('BASE_URL', '/minipos/');

// Registrar en log si hay sesión
if (isset($_SESSION['usuario_id'])) {
    // Incluir archivo de configuración de la base de datos
    require_once '../../config/db.php';
    require_once '../../includes/functions.php';
    
    // Crear instancia de la base de datos
    $database = new Database();
    $conn = $database->connect();
    
    // Registrar en log
    logSistema('Logout', 'Usuario: ' . $_SESSION['usuario_usuario']);
}

// Destruir todas las variables de sesión
$_SESSION = [];

// Destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redireccionar a la página de login
header('Location: ' . BASE_URL . 'modulos/login/');
exit;