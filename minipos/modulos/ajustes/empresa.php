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
$page_title = 'Configuración de Empresa';

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
    // Obtener datos del formulario
    $nombre_empresa = cleanInput($_POST['nombre_empresa']);
    $ruc = cleanInput($_POST['ruc']);
    $direccion = cleanInput($_POST['direccion']);
    $telefono = cleanInput($_POST['telefono']);
    $email = cleanInput($_POST['email']);
    $moneda = cleanInput($_POST['moneda']);
    $simbolo_moneda = cleanInput($_POST['simbolo_moneda']);
    $tasa_impuesto = (float)$_POST['tasa_impuesto'];
    $pie_factura = cleanInput($_POST['pie_factura']);
    
    // Validar datos
    if (empty($nombre_empresa)) {
        $error = 'El nombre de la empresa es obligatorio.';
    } else {
        try {
            // Actualizar configuración
            $stmt = $conn->prepare("
                UPDATE configuracion 
                SET nombre_empresa = ?, 
                    ruc = ?, 
                    direccion = ?, 
                    telefono = ?, 
                    email = ?, 
                    moneda = ?, 
                    simbolo_moneda = ?, 
                    tasa_impuesto = ?, 
                    pie_factura = ? 
                WHERE id = 1
            ");
            
            $stmt->execute([
                $nombre_empresa,
                $ruc,
                $direccion,
                $telefono,
                $email,
                $moneda,
                $simbolo_moneda,
                $tasa_impuesto,
                $pie_factura
            ]);
            
            $success = 'Configuración actualizada correctamente.';
            
            // Actualizar variable global
            $configuracion = obtenerConfiguracion();
            
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Obtener configuración actual
try {
    $stmt = $conn->prepare("SELECT * FROM configuracion WHERE id = 1");
    $stmt->execute();
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="content-header">
    <h1 class="h3 mb-0"><i class="fas fa-building me-2"></i> Configuración de Empresa</h1>
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
        <h5 class="card-title mb-0">Datos de la Empresa</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nombre_empresa" class="form-label">Nombre de la Empresa *</label>
                        <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" value="<?php echo $empresa['nombre_empresa']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ruc" class="form-label">RUC / Identificación Fiscal</label>
                        <input type="text" class="form-control" id="ruc" name="ruc" value="<?php echo $empresa['ruc']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo $empresa['direccion']; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo $empresa['telefono']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $empresa['email']; ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="moneda" class="form-label">Moneda</label>
                        <input type="text" class="form-control" id="moneda" name="moneda" value="<?php echo $empresa['moneda']; ?>">
                        <small class="form-text text-muted">Ejemplo: USD, EUR, MXN, etc.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="simbolo_moneda" class="form-label">Símbolo de Moneda</label>
                        <input type="text" class="form-control" id="simbolo_moneda" name="simbolo_moneda" value="<?php echo $empresa['simbolo_moneda']; ?>">
                        <small class="form-text text-muted">Ejemplo: $, €, £, etc.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tasa_impuesto" class="form-label">Tasa de Impuesto (%)</label>
                        <input type="number" class="form-control" id="tasa_impuesto" name="tasa_impuesto" step="0.01" min="0" max="100" value="<?php echo $empresa['tasa_impuesto']; ?>">
                        <small class="form-text text-muted">Porcentaje de impuesto a aplicar en las ventas.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pie_factura" class="form-label">Pie de Factura</label>
                        <textarea class="form-control" id="pie_factura" name="pie_factura" rows="3"><?php echo $empresa['pie_factura']; ?></textarea>
                        <small class="form-text text-muted">Texto que aparecerá al pie de las facturas.</small>
                    </div>
                </div>
            </div>
            
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