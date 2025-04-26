<?php
// Definir la ruta base si no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', '/minipos/');
}

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Título de la página
$page_title = 'Configuración General';

// Incluir header
include_once '../../includes/header.php';

// Verificar autenticación
requireLogin();

// Verificar permiso
if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Manejar formulario enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Aquí puedes agregar otras configuraciones según necesites
    $success = 'Configuración actualizada correctamente.';
}
?>

<div class="content-header">
    <h1 class="h3 mb-0"><i class="fas fa-sliders-h me-2"></i> Configuración General</h1>
    <a href="<?php echo BASE_URL; ?>modulos/ajustes/" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver a Ajustes
    </a>
</div>

<?php
// Mostrar mensajes
if (isset($success)) {
    echo '<div class="alert alert-success">' . $success . '</div>';
}

if (isset($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Opciones Generales</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Esta sección está en desarrollo. Próximamente más opciones de configuración.
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="backup_auto" name="backup_auto" checked>
                <label class="form-check-label" for="backup_auto">Habilitar copias de seguridad automáticas</label>
                <small class="form-text text-muted d-block">El sistema realizará copias de seguridad automáticas de la base de datos.</small>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="modo_oscuro" name="modo_oscuro">
                <label class="form-check-label" for="modo_oscuro">Modo oscuro (próximamente)</label>
                <small class="form-text text-muted d-block">Habilitar el modo oscuro para la interfaz del sistema.</small>
            </div>
            
            <div class="mb-3">
                <label for="idioma" class="form-label">Idioma del sistema (próximamente)</label>
                <select class="form-select" id="idioma" name="idioma">
                    <option value="es">Español</option>
                    <option value="en">Inglés</option>
                </select>
            </div>
            
            <hr>
            
            <hr>
            
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Incluir footer
include_once '../../includes/footer.php';
?>    