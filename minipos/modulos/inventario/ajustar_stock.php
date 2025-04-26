<?php
/**
 * Ajustar stock de producto
 */

// Incluir archivos necesarios
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Iniciar sesión
session_start();

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modulos/login/');
    exit;
}

// Verificar permiso
if (!tienePermiso('inventario')) {
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar si se ha enviado el formulario por POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ' . BASE_URL . 'modulos/inventario/');
    exit;
}

// Obtener datos del formulario
$producto_id = (int)$_POST['producto_id'];
$tipo_movimiento = cleanInput($_POST['tipo_movimiento']);
$cantidad = (int)$_POST['cantidad'];
$referencia = cleanInput($_POST['referencia']);

// Validar datos
if ($producto_id <= 0 || $cantidad <= 0 || !in_array($tipo_movimiento, ['entrada', 'salida', 'ajuste'])) {
    $_SESSION['error'] = 'Datos inválidos para el ajuste de stock.';
    header('Location: ' . BASE_URL . 'modulos/inventario/kardex.php');
    exit;
}

// Crear instancia de la base de datos
$database = new Database();
$conn = $database->connect();

try {
    // Verificar si el producto existe
    $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ? AND estado = 1");
    $stmt->execute([$producto_id]);
    
    if ($stmt->rowCount() == 0) {
        throw new Exception('El producto no existe o está inactivo.');
    }
    
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Validar stock para salidas
    if ($tipo_movimiento == 'salida' && $cantidad > $producto['stock']) {
        throw new Exception('No hay suficiente stock para realizar la salida.');
    }
    
    // Registrar movimiento en kardex
    registrarMovimientoKardex(
        $producto_id,
        $tipo_movimiento,
        $cantidad,
        $referencia
    );
    
    // Mensaje de éxito
    $_SESSION['success'] = 'Stock ajustado correctamente.';
    
    // Redireccionar de vuelta
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'kardex.php') !== false) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: ' . BASE_URL . 'modulos/inventario/kardex.php?producto_id=' . $producto_id);
    }
    
} catch (Exception $e) {
    // Mensaje de error
    $_SESSION['error'] = 'Error al ajustar stock: ' . $e->getMessage();
    
    // Redireccionar de vuelta
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'kardex.php') !== false) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: ' . BASE_URL . 'modulos/inventario/kardex.php');
    }
}

exit;