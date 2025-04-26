<?php
define('BASE_URL', '/minipos/');

// Título de la página
$page_title = 'Kardex';

// Incluir JS adicional
$extra_js = '<script src="' . BASE_URL . 'assets/js/inventario.js"></script>';

// Incluir header
include_once '../../includes/header.php';

// Verificar autenticación
requireLogin();

// Verificar permiso
if (!tienePermiso('kardex')) {
    header('Location: ' . BASE_URL);
    exit;
}

// Obtener parámetros de filtrado
$producto_id = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01'); // Primer día del mes actual
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d'); // Día actual

try {
    // Obtener lista de productos para el filtro
    $stmt = $conn->prepare("SELECT id, codigo, nombre FROM productos WHERE estado = 1 ORDER BY nombre");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir consulta base
    $sql = "
        SELECT k.*, p.codigo as producto_codigo, p.nombre as producto_nombre, u.nombre as usuario_nombre
        FROM kardex k
        JOIN productos p ON k.producto_id = p.id
        JOIN usuarios u ON k.usuario_id = u.id
        WHERE 1=1
    ";
    $params = [];
    
    // Aplicar filtros
    if ($producto_id > 0) {
        $sql .= " AND k.producto_id = ?";
        $params[] = $producto_id;
    }
    
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $sql .= " AND DATE(k.fecha) BETWEEN ? AND ?";
        $params[] = $fecha_inicio;
        $params[] = $fecha_fin;
    }
    
    // Ordenar por fecha y producto
    $sql .= " ORDER BY k.fecha DESC, p.nombre ASC";
    
    // Ejecutar consulta
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si hay un producto seleccionado, obtener sus datos
    $producto_seleccionado = null;
    if ($producto_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto_seleccionado = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="content-header">
    <h1 class="h3 mb-0"><i class="fas fa-exchange-alt me-2"></i> Kardex</h1>
    <div>
        <a href="<?php echo BASE_URL; ?>mo