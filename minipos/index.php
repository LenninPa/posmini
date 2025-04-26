<?php
// Definir la ruta base
define('BASE_URL', '/minipos/');

// Título de la página
$page_title = 'Dashboard';

// Incluir JS adicional para gráficos
$extra_js = '<script src="' . BASE_URL . 'assets/js/chart.js"></script>';

// Incluir header
include_once 'includes/header.php';

// Verificar autenticación
requireLogin();

// Obtener estadísticas para el dashboard
try {
    // Ventas del día
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_ventas, 
               SUM(total) as total_ingresos 
        FROM ventas 
        WHERE DATE(fecha) = CURDATE() 
        AND estado = 'pagada'
    ");
    $stmt->execute();
    $ventas_hoy = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ventas de la semana
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_ventas, 
               SUM(total) as total_ingresos 
        FROM ventas 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND estado = 'pagada'
    ");
    $stmt->execute();
    $ventas_semana = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Productos con stock bajo
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM productos 
        WHERE stock <= stock_minimo 
        AND estado = 1
    ");
    $stmt->execute();
    $productos_stock_bajo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total productos
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM productos 
        WHERE estado = 1
    ");
    $stmt->execute();
    $total_productos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Datos para gráfico de ventas por día (últimos 7 días)
    $stmt = $conn->prepare("
        SELECT DATE(fecha) as dia, 
               SUM(total) as total 
        FROM ventas 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND estado = 'pagada' 
        GROUP BY DATE(fecha) 
        ORDER BY dia
    ");
    $stmt->execute();
    $ventas_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Datos para gráfico de productos más vendidos
    $stmt = $conn->prepare("
        SELECT p.nombre, 
               SUM(d.cantidad) as cantidad 
        FROM detalle_ventas d 
        JOIN productos p ON d.producto_id = p.id 
        JOIN ventas v ON d.venta_id = v.id 
        WHERE v.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND v.estado = 'pagada' 
        GROUP BY p.id 
        ORDER BY cantidad DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $productos_mas_vendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// HTML del dashboard
?>

<div class="content-header">
    <h1 class="h3 mb-0"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</h1>
    <div>
        <span class="text-muted"><?php echo formatearFecha(date('Y-m-d H:i:s')); ?></span>
    </div>
</div>

<!-- Tarjetas de resumen -->
<div class="row">
    <div class="col-md-3">
        <div class="dashboard-card primary">
            <div class="card-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="card-title">Ventas Hoy</div>
            <div class="card-value">
                <?php echo formatearPrecio($ventas_hoy['total_ingresos'] ?? 0); ?>
            </div>
            <div class="mt-2">
                <?php echo $ventas_hoy['total_ventas'] ?? 0; ?> transacciones
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card success">
            <div class="card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="card-title">Ventas esta Semana</div>
            <div class="card-value">
                <?php echo formatearPrecio($ventas_semana['total_ingresos'] ?? 0); ?>
            </div>
            <div class="mt-2">
                <?php echo $ventas_semana['total_ventas'] ?? 0; ?> transacciones
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card warning">
            <div class="card-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="card-title">Stock Bajo</div>
            <div class="card-value">
                <?php echo $productos_stock_bajo['total'] ?? 0; ?>
            </div>
            <div class="mt-2">
                <a href="<?php echo BASE_URL; ?>modulos/inventario/?filter=low" class="text-dark">Ver productos</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card danger">
            <div class="card-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="card-title">Total Productos</div>
            <div class="card-value">
                <?php echo $total_productos['total'] ?? 0; ?>
            </div>
            <div class="mt-2">
                <a href="<?php echo BASE_URL; ?>modulos/inventario/" class="text-white">Ver inventario</a>
            </div>
        </div>
    </div>
</div>

<!-- Acciones rápidas -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>modulos/ventas/nueva_venta.php" class="btn btn-primary w-100 btn-lg">
                            <i class="fas fa-plus-circle me-2"></i> Nueva Venta
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>modulos/inventario/nuevo_producto.php" class="btn btn-success w-100 btn-lg">
                            <i class="fas fa-plus-circle me-2"></i> Nuevo Producto
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>modulos/reportes/ventas_diarias.php" class="btn btn-info w-100 btn-lg text-white">
                            <i class="fas fa-chart-bar me-2"></i> Reportes
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>modulos/inventario/kardex.php" class="btn btn-secondary w-100 btn-lg">
                            <i class="fas fa-exchange-alt me-2"></i> Kardex
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resto del código del dashboard (gráficos y tablas) -->
<!-- ... -->

<?php
// Incluir footer
include_once 'includes/footer.php';
?>