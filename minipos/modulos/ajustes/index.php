<?php
// Definir la ruta base si no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', '/minipos/');
}

// Título de la página
$page_title = 'Ajustes del Sistema';

// Incluir header
include_once '../../includes/header.php';

// Verificar autenticación
requireLogin();

// Verificar permiso
if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}
?>

<div class="content-header">
    <h1 class="h3 mb-0"><i class="fas fa-cogs me-2"></i> Ajustes del Sistema</h1>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Usuarios</h5>
                <p class="card-text">Administre los usuarios del sistema, roles y permisos.</p>
                <a href="<?php echo BASE_URL; ?>modulos/ajustes/usuarios.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Gestionar Usuarios
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-building fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Empresa</h5>
                <p class="card-text">Configure los datos de su empresa, logo y detalles de facturación.</p>
                <a href="<?php echo BASE_URL; ?>modulos/ajustes/empresa.php" class="btn btn-success">
                    <i class="fas fa-arrow-right"></i> Configurar Empresa
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-sliders-h fa-3x mb-3 text-info"></i>
                <h5 class="card-title">Configuración</h5>
                <p class="card-text">Ajuste las configuraciones generales del sistema.</p>
                <a href="<?php echo BASE_URL; ?>modulos/ajustes/configuracion.php" class="btn btn-info text-white">
                    <i class="fas fa-arrow-right"></i> Ajustar Configuración
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include_once '../../includes/footer.php';
?>