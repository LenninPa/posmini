<?php
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
        <a href="<?php echo BASE_URL; ?>modulos/inventario/" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Inventario
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="producto_id" class="form-label">Producto</label>
                <select class="form-select" id="producto_id" name="producto_id">
                    <option value="">Todos los productos</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?php echo $producto['id']; ?>" <?php echo $producto_id == $producto['id'] ? 'selected' : ''; ?>>
                            <?php echo $producto['codigo'] . ' - ' . $producto['nombre']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
            </div>
            <div class="col-md-3">
                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($producto_seleccionado): ?>
<!-- Información del producto -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Información del Producto</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Código:</strong> <?php echo $producto_seleccionado['codigo']; ?></p>
                <p><strong>Nombre:</strong> <?php echo $producto_seleccionado['nombre']; ?></p>
                <p><strong>Precio de Compra:</strong> <?php echo formatearPrecio($producto_seleccionado['precio_compra']); ?></p>
                <p><strong>Precio de Venta:</strong> <?php echo formatearPrecio($producto_seleccionado['precio_venta']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Stock Actual:</strong> 
                    <span class="badge <?php echo $producto_seleccionado['stock'] <= $producto_seleccionado['stock_minimo'] ? 'bg-warning text-dark' : 'bg-success'; ?>">
                        <?php echo $producto_seleccionado['stock']; ?>
                    </span>
                </p>
                <p><strong>Stock Mínimo:</strong> <?php echo $producto_seleccionado['stock_minimo']; ?></p>
                <p><strong>Categoría:</strong> 
                    <?php 
                    if ($producto_seleccionado['categoria_id']) {
                        $stmt = $conn->prepare("SELECT nombre FROM categorias WHERE id = ?");
                        $stmt->execute([$producto_seleccionado['categoria_id']]);
                        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo $categoria ? $categoria['nombre'] : 'Sin categoría';
                    } else {
                        echo 'Sin categoría';
                    }
                    ?>
                </p>
                <p><strong>Estado:</strong> 
                    <span class="badge <?php echo $producto_seleccionado['estado'] ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $producto_seleccionado['estado'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </p>
            </div>
        </div>
        
        <div class="mt-3">
            <button class="btn btn-success ajustar-stock" data-id="<?php echo $producto_seleccionado['id']; ?>" data-nombre="<?php echo $producto_seleccionado['nombre']; ?>" data-stock="<?php echo $producto_seleccionado['stock']; ?>">
                <i class="fas fa-layer-group"></i> Ajustar Stock
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de movimientos -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Movimientos de Inventario</h5>
            <div class="input-group" style="width: 300px;">
                <input type="text" id="searchInput" class="form-control" placeholder="Buscar...">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Stock Anterior</th>
                        <th>Stock Actual</th>
                        <th>Referencia</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td><?php echo formatearFecha($movimiento['fecha']); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>modulos/inventario/kardex.php?producto_id=<?php echo $movimiento['producto_id']; ?>">
                                    <?php echo $movimiento['producto_codigo'] . ' - ' . $movimiento['producto_nombre']; ?>
                                </a>
                            </td>
                            <td>
                                <?php 
                                switch ($movimiento['tipo_movimiento']) {
                                    case 'entrada':
                                        echo '<span class="badge bg-success">Entrada</span>';
                                        break;
                                    case 'salida':
                                        echo '<span class="badge bg-danger">Salida</span>';
                                        break;
                                    case 'ajuste':
                                        echo '<span class="badge bg-warning text-dark">Ajuste</span>';
                                        break;
                                }
                                ?>
                            </td>
                            <td><?php echo $movimiento['cantidad']; ?></td>
                            <td><?php echo $movimiento['stock_anterior']; ?></td>
                            <td><?php echo $movimiento['stock_actual']; ?></td>
                            <td><?php echo $movimiento['referencia']; ?></td>
                            <td><?php echo $movimiento['usuario_nombre']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (count($movimientos) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay movimientos que coincidan con los filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span>Total: <strong><?php echo count($movimientos); ?></strong> movimientos</span>
            </div>
            <div>
                <button class="btn btn-success" id="exportExcel">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
                <button class="btn btn-danger" id="exportPDF">
                    <i class="fas fa-file-pdf"></i> Exportar a PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ajustar stock -->
<div class="modal fade" id="ajustarStockModal" tabindex="-1" aria-labelledby="ajustarStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="ajustarStockModalLabel">Ajustar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="ajustarStockForm" method="POST" action="ajustar_stock.php">
                    <input type="hidden" id="producto_id" name="producto_id">
                    
                    <div class="mb-3">
                        <label for="producto_nombre" class="form-label">Producto</label>
                        <input type="text" class="form-control" id="producto_nombre" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stock_actual" class="form-label">Stock Actual</label>
                        <input type="number" class="form-control" id="stock_actual" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo_movimiento" class="form-label">Tipo de Movimiento</label>
                        <select class="form-select" id="tipo_movimiento" name="tipo_movimiento" required>
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                            <option value="ajuste">Ajuste</option>
                        </select>
                        <small class="form-text text-muted">
                            Entrada: Suma al stock actual<br>
                            Salida: Resta del stock actual<br>
                            Ajuste: Establece un nuevo valor de stock
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="referencia" class="form-label">Referencia</label>
                        <input type="text" class="form-control" id="referencia" name="referencia" placeholder="Ej: Compra, Devolución, Ajuste por inventario...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="ajustarStockForm" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar Ajuste
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar modal de ajuste de stock
        const ajustarStockButtons = document.querySelectorAll('.ajustar-stock');
        const ajustarStockModal = new bootstrap.Modal(document.getElementById('ajustarStockModal'));
        
        ajustarStockButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productoId = this.getAttribute('data-id');
                const productoNombre = this.getAttribute('data-nombre');
                const stockActual = this.getAttribute('data-stock');
                
                document.getElementById('producto_id').value = productoId;
                document.getElementById('producto_nombre').value = productoNombre;
                document.getElementById('stock_actual').value = stockActual;
                document.getElementById('cantidad').value = '';
                document.getElementById('referencia').value = '';
                
                ajustarStockModal.show();
            });
        });
        
        // Cambiar comportamiento según tipo de movimiento
        document.getElementById('tipo_movimiento').addEventListener('change', function() {
            const cantidad = document.getElementById('cantidad');
            const stockActual = parseInt(document.getElementById('stock_actual').value) || 0;
            
            if (this.value === 'salida') {
                cantidad.max = stockActual;
                if (parseInt(cantidad.value) > stockActual) {
                    cantidad.value = stockActual;
                }
            } else {
                cantidad.removeAttribute('max');
            }
        });
        
        // Exportar a Excel
        document.getElementById('exportExcel').addEventListener('click', function() {
            window.location.href = '<?php echo BASE_URL; ?>modulos/reportes/export_kardex.php?format=excel&producto_id=<?php echo $producto_id; ?>&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>';
        });
        
        // Exportar a PDF
        document.getElementById('exportPDF').addEventListener('click', function() {
            window.location.href = '<?php echo BASE_URL; ?>modulos/reportes/export_kardex.php?format=pdf&producto_id=<?php echo $producto_id; ?>&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>';
        });
    });
</script>

<?php
// Incluir footer
include_once '../../includes/footer.php';
?>