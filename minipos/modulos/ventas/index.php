<?php
define('BASE_URL', '/minipos/');

// Título de la página
$page_title = 'Listado de Ventas';

// Incluir JS adicional
$extra_js = '<script src="' . BASE_URL . 'assets/js/ventas.js"></script>';

// Incluir header
include_once '../../includes/header.php';

// Verificar autenticación
requireLogin();

// Verificar permiso
if (!tienePermiso('ventas')) {
    header('Location: ' . BASE_URL);
    exit;
}

// Obtener parámetros de filtrado
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

try {
    // Base de la consulta
    $sql = "
        SELECT v.id, v.numero_factura, c.nombre as cliente, 
        v.fecha, v.subtotal, v.impuesto, v.total, v.estado, v.metodo_pago,
        u.nombre as usuario 
        FROM ventas v 
        LEFT JOIN clientes c ON v.cliente_id = c.id 
        LEFT JOIN usuarios u ON v.usuario_id = u.id 
        WHERE 1=1
    ";
    $params = [];
    
    // Aplicar filtros
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $sql .= " AND DATE(v.fecha) BETWEEN ? AND ?";
        $params[] = $fecha_inicio;
        $params[] = $fecha_fin;
    }
    
    if (!empty($estado)) {
        $sql .= " AND v.estado = ?";
        $params[] = $estado;
    }
    
    // Ordenar por fecha descendente
    $sql .= " ORDER BY v.fecha DESC";
    
    // Ejecutar consulta
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="content-header">
    <h1 class="h3 mb-0"><i class="fas fa-cash-register me-2"></i> Ventas</h1>
    <a href="<?php echo BASE_URL; ?>modulos/ventas/nueva_venta.php" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Nueva Venta
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
            </div>
            <div class="col-md-3">
                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="pagada" <?php echo $estado == 'pagada' ? 'selected' : ''; ?>>Pagada</option>
                    <option value="anulada" <?php echo $estado == 'anulada' ? 'selected' : ''; ?>>Anulada</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="<?php echo BASE_URL; ?>modulos/ventas/" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Resetear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de ventas -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Listado de Ventas</h5>
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
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Subtotal</th>
                        <th>Impuesto</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Método de Pago</th>
                        <th>Usuario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $venta): ?>
                        <tr>
                            <td><?php echo $venta['numero_factura']; ?></td>
                            <td><?php echo $venta['cliente']; ?></td>
                            <td><?php echo formatearFecha($venta['fecha']); ?></td>
                            <td><?php echo formatearPrecio($venta['subtotal']); ?></td>
                            <td><?php echo formatearPrecio($venta['impuesto']); ?></td>
                            <td><strong><?php echo formatearPrecio($venta['total']); ?></strong></td>
                            <td>
                                <?php if ($venta['estado'] == 'pagada'): ?>
                                    <span class="badge bg-success">Pagada</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Anulada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                switch ($venta['metodo_pago']) {
                                    case 'efectivo':
                                        echo '<span class="badge bg-primary">Efectivo</span>';
                                        break;
                                    case 'tarjeta':
                                        echo '<span class="badge bg-info">Tarjeta</span>';
                                        break;
                                    case 'transferencia':
                                        echo '<span class="badge bg-warning text-dark">Transferencia</span>';
                                        break;
                                }
                                ?>
                            </td>
                            <td><?php echo $venta['usuario']; ?></td>
                            <td class="table-actions">
                                <a href="<?php echo BASE_URL; ?>modulos/ventas/ver_venta.php?id=<?php echo $venta['id']; ?>" class="btn btn-sm btn-info text-white" data-bs-toggle="tooltip" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($venta['estado'] == 'pagada' && isAdmin()): ?>
                                    <button class="btn btn-sm btn-danger anular-venta" data-id="<?php echo $venta['id']; ?>" data-bs-toggle="tooltip" title="Anular">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <a href="<?php echo BASE_URL; ?>modulos/ventas/ver_venta.php?id=<?php echo $venta['id']; ?>&print=1" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Imprimir">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (count($ventas) == 0): ?>
                        <tr>
                            <td colspan="10" class="text-center">No hay ventas que coincidan con los filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Totales:</th>
                        <th>
                            <?php 
                            $total_ventas = array_reduce($ventas, function($carry, $item) {
                                if ($item['estado'] == 'pagada') {
                                    $carry += $item['total'];
                                }
                                return $carry;
                            }, 0);
                            echo formatearPrecio($total_ventas); 
                            ?>
                        </th>
                        <th colspan="4"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span>Total: <strong><?php echo count($ventas); ?></strong> ventas</span>
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
                
                <form id="anularVentaForm">
                    <input type="hidden" id="ventaId" name="ventaId">
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo de anulación</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarAnulacion">
                    <i class="fas fa-ban"></i> Anular Venta
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript para anular venta
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar modal de anulación
        const anularButtons = document.querySelectorAll('.anular-venta');
        const anularVentaModal = new bootstrap.Modal(document.getElementById('anularVentaModal'));
        const ventaIdInput = document.getElementById('ventaId');
        
        anularButtons.forEach(button => {
            button.addEventListener('click', function() {
                const ventaId = this.getAttribute('data-id');
                ventaIdInput.value = ventaId;
                anularVentaModal.show();
            });
        });
        
        // Procesar anulación
        const confirmarAnulacion = document.getElementById('confirmarAnulacion');
        
        confirmarAnulacion.addEventListener('click', function() {
            const ventaId = ventaIdInput.value;
            const motivo = document.getElementById('motivo').value;
            
            if (motivo.trim() === '') {
                alert('Por favor, ingrese un motivo para la anulación.');
                return;
            }
            
            // Enviar solicitud AJAX para anular venta
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo BASE_URL; ?>modulos/ventas/anular_venta.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            showNotification('Venta anulada correctamente', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error: ' + response.message, 'danger');
                        }
                    } catch (e) {
                        showNotification('Error en la respuesta del servidor', 'danger');
                    }
                } else {
                    showNotification('Error en la solicitud', 'danger');
                }
            };
            
            xhr.onerror = function() {
                showNotification('Error de red', 'danger');
            };
            
            xhr.send('id=' + encodeURIComponent(ventaId) + '&motivo=' + encodeURIComponent(motivo));
        });
        
        // Exportar a Excel
        document.getElementById('exportExcel').addEventListener('click', function() {
            window.location.href = '<?php echo BASE_URL; ?>modulos/reportes/export_ventas.php?format=excel&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&estado=<?php echo $estado; ?>';
        });
        
        // Exportar a PDF
        document.getElementById('exportPDF').addEventListener('click', function() {
            window.location.href = '<?php echo BASE_URL; ?>modulos/reportes/export_ventas.php?format=pdf&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&estado=<?php echo $estado; ?>';
        });
    });
</script>

<?php
// Incluir footer
include_once '../../includes/footer.php';
?>