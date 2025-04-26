<?php
/**
 * Procesar y guardar una nueva venta
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

// Verificar si se ha enviado el formulario por POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ' . BASE_URL . 'modulos/ventas/');
    exit;
}

// Crear instancia de la base de datos
$database = new Database();
$conn = $database->connect();

// Obtener datos del formulario
$numero_factura = cleanInput($_POST['numero_factura']);
$cliente_id = (int)$_POST['cliente_id'];
$usuario_id = $_SESSION['usuario_id'];
$fecha = $_POST['fecha'];
$metodo_pago = cleanInput($_POST['metodo_pago']);
$notas = cleanInput($_POST['notas']);
$subtotal = (float)$_POST['subtotal'];
$impuesto_tasa = (float)$_POST['impuesto'];
$impuesto_valor = (float)$_POST['impuesto_valor'];
$total = (float)$_POST['total'];

// Verificar que haya items en la venta
if (!isset($_POST['items']) || empty($_POST['items'])) {
    $_SESSION['error'] = 'No hay productos en la venta.';
    header('Location: ' . BASE_URL . 'modulos/ventas/nueva_venta.php');
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Insertar venta
    $stmt = $conn->prepare("
        INSERT INTO ventas (
            numero_factura, cliente_id, usuario_id, fecha, 
            impuesto, subtotal, total, estado, metodo_pago, notas
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 'pagada', ?, ?
        )
    ");
    
    $stmt->execute([
        $numero_factura,
        $cliente_id,
        $usuario_id,
        $fecha,
        $impuesto_valor,
        $subtotal,
        $total,
        $metodo_pago,
        $notas
    ]);
    
    // Obtener el ID de la venta insertada
    $venta_id = $conn->lastInsertId();
    
    // Insertar detalle de venta y actualizar inventario
    foreach ($_POST['items'] as $item) {
        $producto_id = (int)$item['id'];
        $cantidad = (int)$item['cantidad'];
        $precio_unitario = (float)$item['precio'];
        $total_item = (float)$item['total'];
        
        // Verificar stock disponible
        $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($producto['stock'] < $cantidad) {
            throw new Exception("No hay suficiente stock para el producto ID: $producto_id");
        }
        
        // Insertar detalle
        $stmt = $conn->prepare("
            INSERT INTO detalle_ventas (
                venta_id, producto_id, cantidad, precio_unitario, total
            ) VALUES (
                ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $venta_id,
            $producto_id,
            $cantidad,
            $precio_unitario,
            $total_item
        ]);
        
        // Registrar movimiento en kardex y actualizar stock
        registrarMovimientoKardex(
            $producto_id, 
            'salida', 
            $cantidad, 
            "Venta: $numero_factura"
        );
    }
    
    // Confirmar transacción
    $conn->commit();
    
    // Registrar en log
    logSistema('Nueva venta', "Venta ID: $venta_id, Factura: $numero_factura, Total: $total");
    
    // Mensaje de éxito
    $_SESSION['success'] = 'Venta registrada correctamente.';
    
    // Redireccionar a ver venta
    header('Location: ' . BASE_URL . 'modulos/ventas/ver_venta.php?id=' . $venta_id);
    exit;
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollBack();
    
    // Registrar error
    logSistema('Error al registrar venta', $e->getMessage());
    
    // Mensaje de error
    $_SESSION['error'] = 'Error al registrar la venta: ' . $e->getMessage();
    
    // Redireccionar a nueva venta
    header('Location: ' . BASE_URL . 'modulos/ventas/nueva_venta.php');
    exit;
}