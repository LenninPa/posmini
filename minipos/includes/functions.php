<?php
// Definir BASE_PATH si no existe
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(__DIR__ . '/../..'));
}

// El resto de tu código...

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

// Verificar si el usuario es administrador
function isAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 'admin';
}

// Redireccionar si no está autenticado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modulos/login/');
        exit;
    }
}

// Redireccionar si no es administrador
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL);
        exit;
    }
}

// Limpiar entrada de datos
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generar un número de factura único
function generarNumeroFactura() {
    return 'FAC-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
}

// Formatear fecha
function formatearFecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

// Formatear precio
function formatearPrecio($precio) {
    global $configuracion;
    return $configuracion['simbolo_moneda'] . ' ' . number_format($precio, 2);
}

// Obtener configuración del sistema
function obtenerConfiguracion() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM configuracion WHERE id = 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Registrar movimiento en kardex
function registrarMovimientoKardex($producto_id, $tipo_movimiento, $cantidad, $referencia = '') {
    global $conn;
    
    // Obtener stock actual
    $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    $stock_anterior = $producto['stock'];
    
    // Calcular stock actual según el tipo de movimiento
    if ($tipo_movimiento == 'entrada') {
        $stock_actual = $stock_anterior + $cantidad;
    } else if ($tipo_movimiento == 'salida') {
        $stock_actual = $stock_anterior - $cantidad;
    } else { // ajuste
        $stock_actual = $cantidad;
        $cantidad = $cantidad - $stock_anterior;
    }
    
    // Actualizar stock en tabla productos
    $stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
    $stmt->execute([$stock_actual, $producto_id]);
    
    // Registrar movimiento en kardex
    $stmt = $conn->prepare("INSERT INTO kardex (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $producto_id,
        $tipo_movimiento,
        abs($cantidad),
        $stock_anterior,
        $stock_actual,
        $referencia,
        $_SESSION['usuario_id']
    ]);
    
    return true;
}

// Validar permiso según rol
function tienePermiso($modulo) {
    $permisos = [
        'admin' => ['ventas', 'inventario', 'reportes', 'ajustes', 'kardex'],
        'cajero' => ['ventas', 'inventario']
    ];
    
    $rol = $_SESSION['usuario_rol'];
    
    if (isset($permisos[$rol]) && in_array($modulo, $permisos[$rol])) {
        return true;
    }
    
    return false;
}

// Generar reporte en PDF (placeholder - requiere librería externa como FPDF)
function generarPDF($html, $nombre_archivo) {
    // Implementación con FPDF o librería similar
    // Esta es una función placeholder
    return true;
}

// Generar reporte en Excel (placeholder - requiere librería externa como PhpSpreadsheet)
function generarExcel($datos, $nombre_archivo) {
    // Implementación con PhpSpreadsheet o librería similar
    // Esta es una función placeholder
    return true;
}

// Escribir en log del sistema
function logSistema($accion, $detalles = '') {
    $logDir = BASE_PATH . '/logs';
    $logFile = $logDir . '/sistema.log';

    // Crear carpeta logs si no existe
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $archivo = fopen($logFile, 'a');
    if ($archivo) {
        $fecha = date('Y-m-d H:i:s');
        $usuario = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Sistema';
        $ip = $_SERVER['REMOTE_ADDR'];
        $log = "[$fecha] $usuario ($ip): $accion - $detalles\n";
        fwrite($archivo, $log);
        fclose($archivo);
    }
}
