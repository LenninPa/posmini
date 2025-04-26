<?php
/**
 * Anular una venta
 */

// Incluir archivos necesarios
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Iniciar sesión
session_start();

// Verificar autenticación
if (!isLoggedIn()) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        // Si es una petición AJAX, devolver respuesta JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    } else {
        header('Location: ' . BASE_URL . 'modulos/login/');
        exit;
    }
}

// Verificar si es administrador
if (!isAdmin()) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        // Si es una petición AJAX, devolver respuesta JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para realizar esta acción']);
        exit;
    } else {
        header('Location: ' . BASE_URL);
        exit;
    }
}

// Verificar que se proporcionó un ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        // Si es una petición AJAX, devolver respuesta JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado']);
        exit;
    } else {
        header('Location: ' . BASE_URL . 'modulos/ventas/');
        exit;
    }
}

// Obtener datos
$venta_id = (int)$_POST['id'];
$motivo = isset($_POST['motivo']) ? cleanInput($_POST['motivo']) : 'Anulación de venta';

// Crear instancia de la base de datos
$database = new Database();
$conn = $database->connect();

try {
    // Verificar si la venta existe y no está anulada
    $stmt = $conn->prepare("SELECT * FROM ventas WHERE id = ? AND estado = 'pagada'");
    $stmt->execute([$venta_id]);
    
    if ($stmt->rowCount() == 0) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            // Si es una petición AJAX, devolver respuesta JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'La venta no existe o ya está anulada']);
            exit;
        } else {
            $_SESSION['error'] = 'La venta no existe o ya está anulada.';
            header('Location: ' . BASE_URL . 'modulos/ventas/');
            exit;
        }
    }
    
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Obtener detalles de la venta
    $stmt = $conn->prepare("SELECT * FROM detalle_ventas WHERE venta_id = ?");
    $stmt->execute([$venta_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizar estado de la venta
    $stmt = $conn->prepare("UPDATE ventas SET estado = 'anulada', notas = CONCAT(notas, ' | Anulada: ', ?) WHERE id = ?");
    $stmt->execute([$motivo, $venta_id]);
    
    // Devolver productos al inventario
    foreach ($detalles as $detalle) {
        // Registrar movimiento en kardex y actualizar stock
        registrarMovimientoKardex(
            $detalle['producto_id'], 
            'entrada', 
            $detalle['cantidad'], 
            "Anulación de venta: " . $venta['numero_factura']
        );
    }
    
    // Confirmar transacción
    $conn->commit();
    
    // Registrar en log
    logSistema('Anulación de venta', "Venta ID: $venta_id, Factura: " . $venta['numero_factura'] . ", Motivo: $motivo");
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        // Si es una petición AJAX, devolver respuesta JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Venta anulada correctamente']);
        exit;
    } else {
        // Mensaje de éxito
        $_SESSION['success'] = 'Venta anulada correctamente.';
        
        // Redireccionar a ver venta
        header('Location: ' . BASE_URL . 'modulos/ventas/ver_venta.php?id=' . $venta_id);
        exit;
    }
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollBack();
    
    // Registrar error
    logSistema('Error al anular venta', $e->getMessage());
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        // Si es una petición AJAX, devolver respuesta JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al anular la venta: ' . $e->getMessage()]);
        exit;
    } else {
        // Mensaje de error
        $_SESSION['error'] = 'Error al anular la venta: ' . $e->getMessage();
        
        // Redireccionar a ver venta
        header('Location: ' . BASE_URL . 'modulos/ventas/ver_venta.php?id=' . $venta_id);
        exit;
    }
}