<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir la ruta base

define('BASE_PATH', dirname(__DIR__));

// Incluir archivo de configuración de la base de datos
require_once BASE_PATH . '/config/db.php';

// Crear instancia de la base de datos
$database = new Database();
$conn = $database->connect();
// Incluir funciones
require_once BASE_PATH . '/includes/functions.php';

// Obtener configuración del sistema
$configuracion = obtenerConfiguracion();

// Verificar autenticación
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php' && basename(dirname($_SERVER['PHP_SELF'])) != 'login') {
    header('Location: ' . BASE_URL . 'modulos/login/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>MiniPOS</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/custom.css">
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>
<?php if (isLoggedIn()): ?>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <?php echo $configuracion['nombre_empresa']; ?>
            </a>
            <button class="navbar-toggler" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="navbar-menu" id="navbarMenu">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <?php if (tienePermiso('ventas')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>modulos/ventas/">
                            <i class="fas fa-cash-register"></i> Ventas
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (tienePermiso('inventario')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>modulos/inventario/">
                            <i class="fas fa-boxes"></i> Inventario
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (tienePermiso('kardex')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>modulos/inventario/kardex.php">
                            <i class="fas fa-exchange-alt"></i> Kardex
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (tienePermiso('reportes')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>modulos/reportes/">
                            <i class="fas fa-chart-bar"></i> Reportes
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (tienePermiso('ajustes')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>modulos/ajustes/">
                            <i class="fas fa-cogs"></i> Ajustes
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav navbar-right">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nombre']; ?>
                        </a>
                        <ul class="dropdown-menu" id="userDropdownMenu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modulos/ajustes/usuarios.php?action=profile">
                                <i class="fas fa-user-edit"></i> Mi Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modulos/login/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal con sidebar -->
    <div class="main-container">
        <div class="sidebar-container">
            <?php include_once BASE_PATH . '/includes/sidebar.php'; ?>
            <main class="content">
<?php else: ?>
    <!-- Si no está logueado, no mostrar navbar ni sidebar -->
    <div class="container login-container">
<?php endif; ?>