<?php
// Definir la ruta base si no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', '/minipos/');
}

// Título de la página
$page_title = 'Iniciar Sesión';

// Incluir CSS adicional		
$extra_css = '<link rel="stylesheet" href="' . BASE_URL . 'assets/css/login.css">';

// Incluir header (sin navbar para login)
include_once '../../includes/header.php';

// El resto del código permanece igual...

// Título de la página
$page_title = 'Iniciar Sesión';

// Incluir CSS adicional
$extra_css = '<link rel="stylesheet" href="' . BASE_URL . 'assets/css/login.css">';

// Incluir header (sin navbar para login)
include_once '../../includes/header.php';

// Verificar si ya está autenticado y redirigir al dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar si se ha enviado el formulario
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Incluir archivo de procesamiento de login
    require_once 'login.php';
}
?>

<div class="text-center">
    <div class="card card-login">
        <div class="card-header">
            <h1 class="h3">Gestion Interna</h1>
            <p class="mb-0">Sistema de Punto de Venta</p>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form class="form-signin" method="POST" action="">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required autofocus>

                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>

                </div>
                
                <button class="w-100 btn btn-lg btn-primary btn-login" type="submit">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
                
                <div class="login-footer">
                    <?php echo date('Y'); ?> &copy; <?php echo $configuracion['nombre_empresa']; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include_once '../../includes/footer.php';
?>