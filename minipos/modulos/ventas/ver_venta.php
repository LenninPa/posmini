<?php
// Título de la página
$page_title = 'Detalle de Venta';

// Incluir CSS adicional para impresión
$extra_css = '
<style media="print">
    @page { size: auto; margin: 10mm; }
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { font-size: 12pt; }
    .container { width: 100%; max-width: 100%; }
</style>
';

// Incluir header
include_once '../../includes/header.php';

// Verificar autenticación
requireLogin();

// Verificar permiso
if (!tienePermiso('ventas')) {
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . 'modulos/ventas/');
    exit;
}

$venta_id = (int)$_GET['id'];
$print_mode = isset($_GET['print']) && $_GET['print'] == 1;

try {
    // Obtener datos de la venta
    $stmt = $conn->prepare("
        SELECT v.*, c.nombre as cliente_nombre, c.documento as cliente_documento, 
        c.direccion as cliente_direccion, c.telefono as cliente_telefono,
        u.nombre as usuario_nombre
        FROM ventas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.id = ?
    ");
    $stmt->execute([$venta_id]);
    
    if ($stmt->rowCount() == 0) {
        // Si no existe la venta, redirigir
        header('Location: ' . BASE_URL . 'modulos/ventas/');
        exit;
    }
    
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener detalles de la venta
    $stmt = $conn->prepare("
        SELECT d.*, p.nombre as producto_nombre, p.codigo as producto_codigo
        FROM detalle_ventas d
        JOIN productos p ON d.producto_id = p.id
        WHERE d.venta_id = ?
    ");
    $stmt->execute([$venta_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener configuración de la empresa
    $stmt = $conn->prepare("SELECT * FROM configuracion WHERE id = 1");
    $stmt->execute();
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Si estamos en modo impresión, mostrar solo la factura
if ($print_mode) {
    ?>
    <div class="container mt-4 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2><?php echo $empresa['nombre_empresa']; ?></h2>
                        <p>
                            RUC: <?php echo $empresa['ruc']; ?><br>
                            Dirección: <?php echo $empresa['direccion']; ?><br>
                            Teléfono: <?php echo $empresa['telefono']; ?><br>
                            Email: <?php echo $empresa['email']; ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h1 class="text-primary">FACTURA</h1>
                        <h4>N°: <?php echo $venta['numero_factura']; ?></h4>
                        <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Cliente</h5>
                        <p>
                            Nombre: <?php echo $venta['cliente_nombre']; ?><br>
                            <?php if (!empty($venta['cliente_documento'])): ?>
                                Documento: <?php echo $venta['cliente_documento']; ?><br>
                            <?php endif; ?>
                            <?php if (!empty($venta['cliente_direccion'])): ?>
                                Dirección: <?php echo $venta['cliente_direccion']; ?><br>
                            <?php endif; ?>
                            <?php if (!empty($venta['cliente_telefono'])): ?>
                                Teléfono: <?php echo $venta['cliente_telefono']; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h5>Detalles de Pago</h5>
                        <p>
                            Método de Pago: <?php echo ucfirst($venta['metodo_pago']); ?><br>
                            Estado: <?php echo ucfirst($venta['estado']); ?><br>
                            Cajero: <?php echo $venta['usuario_nombre']; ?>
                        </p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td><?php echo $detalle['producto_codigo']; ?></td>
                                    <td><?php echo $detalle['producto_nombre']; ?></td>
                                    <td class="text-end"><?php echo $detalle['cantidad']; ?></td>
                                    <td class="text-end"><?php echo formatearPrecio($detalle['precio_unitario']); ?></td>
                                    <td class="text-end"><?php echo formatearPrecio($detalle['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Subtotal</strong></td>
                                <td class="text-end"><?php echo formatearPrecio($venta['subtotal']); ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Impuesto (<?php echo $venta['impuesto'] / $venta['subtotal'] * 100; ?>%)</strong></td>
                                <td class="text-end"><?php echo formatearPrecio($venta['impuesto']); ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Total</strong></td>
                                <td class="text-end"><strong><?php echo formatearPrecio($venta['total']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if (!empty($venta['notas'])): ?>
                    <div class="mt-4">
                        <h5>Notas</h5>
                        <p><?php echo $venta['notas']; ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 text-center">
                    <?php if (!empty($empresa['pie_factura'])): ?>
                        <p><?php echo $empresa['pie_factura']; ?></p>
                    <?php endif; ?>
                    <p><strong>¡Gracias por su compra!</strong></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Imprimir automáticamente
        window.onload = function() {
            window.print();
            setTimeout(function() {
                window.location.href = '<?php echo BASE_URL; ?>modulos/ventas/ver_venta.php?id=<?php echo $venta_id; ?>';
            }, 1000);
        };
    </script>
    <?php
} else {
    // Mostrar vista normal con opciones de acciones
    ?>
    <div class="content-header">
        <h1 class="h3 mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Detalle de Venta</h1>
        <div class="no-print">
            <a href="<?php echo BASE_URL; ?>modulos/ventas/" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
            <a href="<?php echo BASE_URL; ?>modulos/ventas/ver_venta.php?id=<?php echo $venta_id; ?>&print=1" class="btn btn-info text-white">
                <i class="fas fa-print"></i> Imprimir Factura
            </a>
        </div>
    </div>

    <?php
    // Mostrar mensajes de éxito o error
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success no-print">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger no-print">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Productos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $detalle): ?>
                                    <tr>
                                        <td><?php echo $detalle['producto_codigo']; ?></td>
                                        <td><?php echo $detalle['producto_nombre']; ?></td>
                                        <td><?php echo $detalle['cantidad']; ?></td>
                                        <td><?php echo formatearPrecio($detalle['precio_unitario']); ?></td>
                                        <td><?php echo formatearPrecio($detalle['total']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal</strong></td>
                                    <td><?php echo formatearPrecio($venta['subtotal']); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Impuesto</strong></td>
                                    <td><?php echo formatearPrecio($venta['impuesto']); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total</strong></td>
                                    <td><strong><?php echo formatearPrecio($venta['total']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Información de la Venta</h5>
                </div>
                <div class="card-body">
                    <p><strong>Factura:</strong> <?php echo $venta['numero_factura']; ?></p>
                    <p><strong>Fecha:</strong> <?php echo formatearFecha($venta['fecha']); ?></p>
                    <p><strong>Estado:</strong> 
                        <?php if ($venta['estado'] == 'pagada'): ?>
                            <span class="badge bg-success">Pagada</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Anulada</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Método de Pago:</strong> <?php echo ucfirst($venta['metodo_pago']); ?></p>
                    <p><strong>Usuario:</strong> <?php echo $venta['usuario_nombre']; ?></p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cliente</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nombre:</strong> <?php echo $venta['cliente_nombre']; ?></p>
                    <?php if (!empty($venta['cliente_documento'])): ?>
                        <p><strong>Documento:</strong> <?php echo $venta['cliente_documento']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($venta['cliente_direccion'])): ?>
                        <p><strong>Dirección:</strong> <?php echo $venta['cliente_direccion']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($venta['cliente_telefono'])): ?>
                        <p><strong>Teléfono:</strong> <?php echo $venta['cliente_telefono']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($venta['notas'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Notas</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo $venta['notas']; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($venta['estado'] == 'pagada' && isAdmin()): ?>
                <div class="card mb-4 no-print">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">Acciones</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-danger w-100" id="anularVentaBtn">
                            <i class="fas fa-ban"></i> Anular Venta
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para anular venta -->
    <div class="modal fade" id="anularVentaModal" tabindex="-1" aria-labelledby="anularVentaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="anularVentaModalLabel">Anular Venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea anular esta venta? Esta acción no se puede deshacer.</p>
                    <p><strong>Nota:</strong> Al anular la venta, se devolverán los productos al inventario.</p>
                    
                    <form id="anularVentaForm" method="POST" action="anular_venta.php">
                        <input type="hidden" name="id" value="<?php echo $venta_id; ?>">
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo de anulación</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="anularVentaForm" class="btn btn-danger">
                        <i class="fas fa-ban"></i> Anular Venta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar modal de anulación
            const anularBtn = document.getElementById('anularVentaBtn');
            if (anularBtn) {
                const anularVentaModal = new bootstrap.Modal(document.getElementById('anularVentaModal'));
                anularBtn.addEventListener('click', function() {
                    anularVentaModal.show();
                });
            }
        });
    </script>

    <?php
}

// Incluir footer
include_once '../../includes/footer.php';
?>