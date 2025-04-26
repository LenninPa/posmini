<?php
define('BASE_URL', '/minipos/');

// Título de la página
$page_title = 'Nueva Venta';

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

// Obtener clientes
try {
    $stmt = $conn->prepare("SELECT id, nombre, documento FROM clientes WHERE estado = 1 ORDER BY nombre");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener productos
    $stmt = $conn->prepare("SELECT id, codigo, nombre, precio_venta, stock FROM productos WHERE estado = 1 AND stock > 0 ORDER BY nombre");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener categorías para filtrado
    $stmt = $conn->prepare("SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY nombre");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Generar número de factura
$numero_factura = generarNumeroFactura();
?>

<div class="content-header">
    <h1 class="h3 mb-0"><i class="fas fa-cart-plus me-2"></i> Nueva Venta</h1>
    <a href="<?php echo BASE_URL; ?>modulos/ventas/" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver al Listado
    </a>
</div>

<div class="row">
    <!-- Panel izquierdo: productos -->
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Productos</h5>
                    <div class="d-flex">
                        <select id="categoriaFilter" class="form-select me-2">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>"><?php echo $categoria['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-group" style="width: 200px;">
                            <input type="text" id="searchProducto" class="form-control" placeholder="Buscar...">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="product-grid" id="productGrid">
                    <?php foreach ($productos as $producto): ?>
                        <div class="product-item" data-id="<?php echo $producto['id']; ?>" data-categoria="<?php echo $producto['categoria_id'] ?? ''; ?>">
                            <div class="product-icon">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                            <div class="product-name"><?php echo $producto['nombre']; ?></div>
                            <div class="product-price"><?php echo formatearPrecio($producto['precio_venta']); ?></div>
                            <div class="product-stock">Stock: <?php echo $producto['stock']; ?></div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($productos) == 0): ?>
                        <div class="alert alert-warning" role="alert">
                            No hay productos disponibles con stock.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Productos más vendidos -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Productos Más Vendidos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Agregar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Obtener productos más vendidos
                            $stmt = $conn->prepare("
                                SELECT p.id, p.codigo, p.nombre, p.precio_venta, p.stock, SUM(d.cantidad) as total_vendido
                                FROM productos p
                                JOIN detalle_ventas d ON p.id = d.producto_id
                                JOIN ventas v ON d.venta_id = v.id
                                WHERE p.estado = 1 AND p.stock > 0 AND v.estado = 'pagada'
                                GROUP BY p.id
                                ORDER BY total_vendido DESC
                                LIMIT 5
                            ");
                            $stmt->execute();
                            $mas_vendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($mas_vendidos as $producto):
                            ?>
                                <tr>
                                    <td><?php echo $producto['codigo']; ?></td>
                                    <td><?php echo $producto['nombre']; ?></td>
                                    <td><?php echo formatearPrecio($producto['precio_venta']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary add-product-fast" data-id="<?php echo $producto['id']; ?>" data-nombre="<?php echo $producto['nombre']; ?>" data-precio="<?php echo $producto['precio_venta']; ?>" data-stock="<?php echo $producto['stock']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (count($mas_vendidos) == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No hay datos disponibles.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel derecho: carrito y finalización -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Detalle de Venta</h5>
            </div>
            <div class="card-body">
                <form id="ventaForm" method="POST" action="guardar_venta.php">
                    <input type="hidden" name="numero_factura" value="<?php echo $numero_factura; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cliente_id" class="form-label">Cliente</label>
                            <select class="form-select" id="cliente_id" name="cliente_id" required>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>" <?php echo $cliente['id'] == 1 ? 'selected' : ''; ?>>
                                        <?php echo $cliente['nombre']; ?> <?php echo !empty($cliente['documento']) ? '(' . $cliente['documento'] . ')' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="datetime-local" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="metodo_pago" class="form-label">Método de Pago</label>
                            <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="impuesto" class="form-label">Impuesto (%)</label>
                            <input type="number" class="form-control" id="impuesto" name="impuesto" min="0" max="100" step="0.01" value="<?php echo $configuracion['tasa_impuesto']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="2"></textarea>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Productos en el Carrito</h6>
                            <button type="button" class="btn btn-sm btn-danger" id="clearCart">
                                <i class="fas fa-trash"></i> Vaciar Carrito
                            </button>
                        </div>
                        
                        <div class="cart-items">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cant.</th>
                                        <th>Precio</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cartItems">
                                    <!-- Aquí se agregarán los productos dinámicamente -->
                                    <tr id="emptyCart">
                                        <td colspan="5" class="text-center">No hay productos en el carrito</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="cart-totals">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotal">$0.00</span>
                            <input type="hidden" name="subtotal" id="subtotal_input" value="0">
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Impuesto:</span>
                            <span id="impuesto_valor">$0.00</span>
                            <input type="hidden" name="impuesto_valor" id="impuesto_valor_input" value="0">
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total:</span>
                            <span id="total">$0.00</span>
                            <input type="hidden" name="total" id="total_input" value="0">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="finalizarVenta">
                            <i class="fas fa-check-circle"></i> Finalizar Venta
                        </button>
                        <a href="<?php echo BASE_URL; ?>modulos/ventas/" class="btn btn-outline-secondary">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar producto al carrito -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addProductModalLabel">Agregar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm">
                    <input type="hidden" id="producto_id" name="producto_id">
                    <div class="mb-3">
                        <label for="producto_nombre" class="form-label">Producto</label>
                        <input type="text" class="form-control" id="producto_nombre" name="producto_nombre" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="producto_precio" class="form-label">Precio Unitario</label>
                        <input type="number" class="form-control" id="producto_precio" name="producto_precio" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="producto_cantidad" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="producto_cantidad" name="producto_cantidad" value="1" min="1" required>
                        <small class="text-muted">Stock disponible: <span id="stock_disponible">0</span></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="addToCart">
                    <i class="fas fa-cart-plus"></i> Agregar al Carrito
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let cart = [];
    const taxRate = parseFloat(document.getElementById('impuesto').value) || 0;
    const productos = <?php echo json_encode($productos); ?>;
    const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    
    // Manejar clic en productos
    document.querySelectorAll('.product-item').forEach(item => {
        item.addEventListener('click', function() {
            const productoId = this.getAttribute('data-id');
            const producto = productos.find(p => p.id == productoId);
            
            if (producto) {
                document.getElementById('producto_id').value = producto.id;
                document.getElementById('producto_nombre').value = producto.nombre;
                document.getElementById('producto_precio').value = producto.precio_venta;
                document.getElementById('producto_cantidad').value = 1;
                document.getElementById('stock_disponible').textContent = producto.stock;
                
                // Establecer máximo según stock
                document.getElementById('producto_cantidad').max = producto.stock;
                
                addProductModal.show();
            }
        });
    });
    
    // Manejar clic en botones de agregar rápido
    document.querySelectorAll('.add-product-fast').forEach(button => {
        button.addEventListener('click', function() {
            const productoId = this.getAttribute('data-id');
            const nombreProducto = this.getAttribute('data-nombre');
            const precioProducto = parseFloat(this.getAttribute('data-precio'));
            const stockProducto = parseInt(this.getAttribute('data-stock'));
            
            // Verificar si el producto ya está en el carrito
            const existingItem = cart.find(item => item.id == productoId);
            
            if (existingItem) {
                // Si ya existe, aumentar la cantidad en 1 si hay stock
                if (existingItem.cantidad < stockProducto) {
                    existingItem.cantidad += 1;
                    existingItem.total = existingItem.precio * existingItem.cantidad;
                    updateCart();
                } else {
                    showNotification('No hay suficiente stock para este producto', 'warning');
                }
            } else {
                // Si no existe, agregar al carrito
                const newItem = {
                    id: productoId,
                    nombre: nombreProducto,
                    precio: precioProducto,
                    cantidad: 1,
                    stock: stockProducto,
                    total: precioProducto
                };
                
                cart.push(newItem);
                updateCart();
            }
        });
    });
    
    // Agregar producto desde modal
    document.getElementById('addToCart').addEventListener('click', function() {
        const productoId = document.getElementById('producto_id').value;
        const nombreProducto = document.getElementById('producto_nombre').value;
        const precioProducto = parseFloat(document.getElementById('producto_precio').value);
        const cantidadProducto = parseInt(document.getElementById('producto_cantidad').value);
        const stockProducto = parseInt(document.getElementById('stock_disponible').textContent);
        
        if (!productoId || isNaN(precioProducto) || isNaN(cantidadProducto) || cantidadProducto <= 0 || cantidadProducto > stockProducto) {
            showNotification('Por favor, ingrese datos válidos', 'warning');
            return;
        }
        
        // Verificar si el producto ya está en el carrito
        const existingItem = cart.find(item => item.id == productoId);
        
        if (existingItem) {
            // Si ya existe, actualizar cantidad
            const newCantidad = existingItem.cantidad + cantidadProducto;
            
            if (newCantidad <= stockProducto) {
                existingItem.cantidad = newCantidad;
                existingItem.precio = precioProducto;
                existingItem.total = precioProducto * newCantidad;
            } else {
                showNotification('No hay suficiente stock para este producto', 'warning');
                addProductModal.hide();
                return;
            }
        } else {
            // Si no existe, agregar al carrito
            const newItem = {
                id: productoId,
                nombre: nombreProducto,
                precio: precioProducto,
                cantidad: cantidadProducto,
                stock: stockProducto,
                total: precioProducto * cantidadProducto
            };
            
            cart.push(newItem);
        }
        
        updateCart();
        addProductModal.hide();
    });
    
    // Actualizar carrito y totales
    function updateCart() {
        const cartItems = document.getElementById('cartItems');
        const emptyCart = document.getElementById('emptyCart');
        
        if (cart.length > 0) {
            emptyCart.style.display = 'none';
            
            // Limpiar tabla y volver a llenar
            let cartHtml = '';
            
            cart.forEach((item, index) => {
                cartHtml += `
                    <tr>
                        <td>${item.nombre}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-sm decrement-qty" data-index="${index}">-</button>
                                <input type="number" class="form-control form-control-sm text-center item-qty" value="${item.cantidad}" min="1" max="${item.stock}" data-index="${index}" style="width: 50px">
                                <button type="button" class="btn btn-outline-secondary btn-sm increment-qty" data-index="${index}">+</button>
                            </div>
                        </td>
                        <td>${formatCurrency(item.precio)}</td>
                        <td>${formatCurrency(item.total)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                            <input type="hidden" name="items[${index}][id]" value="${item.id}">
                            <input type="hidden" name="items[${index}][cantidad]" value="${item.cantidad}">
                            <input type="hidden" name="items[${index}][precio]" value="${item.precio}">
                            <input type="hidden" name="items[${index}][total]" value="${item.total}">
                        </td>
                    </tr>
                `;
            });
            
            cartItems.innerHTML = cartHtml;
            
            // Añadir event listeners a los botones
            document.querySelectorAll('.decrement-qty').forEach(button => {
                button.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    if (cart[index].cantidad > 1) {
                        cart[index].cantidad--;
                        cart[index].total = cart[index].precio * cart[index].cantidad;
                        updateCart();
                    }
                });
            });
            
            document.querySelectorAll('.increment-qty').forEach(button => {
                button.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    if (cart[index].cantidad < cart[index].stock) {
                        cart[index].cantidad++;
                        cart[index].total = cart[index].precio * cart[index].cantidad;
                        updateCart();
                    }
                });
            });
            
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    cart.splice(index, 1);
                    updateCart();
                });
            });
            
            document.querySelectorAll('.item-qty').forEach(input => {
                input.addEventListener('change', function() {
                    const index = this.getAttribute('data-index');
                    const newQty = parseInt(this.value);
                    
                    if (!isNaN(newQty) && newQty > 0 && newQty <= cart[index].stock) {
                        cart[index].cantidad = newQty;
                        cart[index].total = cart[index].precio * newQty;
                        updateCart();
                    } else {
                        this.value = cart[index].cantidad;
                    }
                });
            });
        } else {
            emptyCart.style.display = '';
        }
        
        // Calcular totales
        calculateTotals();
    }
    
    // Calcular totales
    function calculateTotals() {
        const subtotal = cart.reduce((total, item) => total + item.total, 0);
        const impuesto = subtotal * (taxRate / 100);
        const total = subtotal + impuesto;
        
        document.getElementById('subtotal').textContent = formatCurrency(subtotal);
        document.getElementById('impuesto_valor').textContent = formatCurrency(impuesto);
        document.getElementById('total').textContent = formatCurrency(total);
        
        document.getElementById('subtotal_input').value = subtotal.toFixed(2);
        document.getElementById('impuesto_valor_input').value = impuesto.toFixed(2);
        document.getElementById('total_input').value = total.toFixed(2);
    }
    
    // Formatear moneda
    function formatCurrency(value) {
        return '$ ' + (parseFloat(value) || 0).toFixed(2);
    }
    
    // Vaciar carrito
    document.getElementById('clearCart').addEventListener('click', function() {
        if (confirm('¿Está seguro de que desea vaciar el carrito?')) {
            cart = [];
            updateCart();
        }
    });
    
    // Manejar cambio en la tasa de impuesto
    document.getElementById('impuesto').addEventListener('change', function() {
        taxRate = parseFloat(this.value) || 0;
        calculateTotals();
    });
    
    // Filtrar productos por categoría
    document.getElementById('categoriaFilter').addEventListener('change', function() {
        const categoriaId = this.value;
        const items = document.querySelectorAll('.product-item');
        
        items.forEach(item => {
            if (!categoriaId || item.getAttribute('data-categoria') == categoriaId) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Buscar productos
    document.getElementById('searchProducto').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const items = document.querySelectorAll('.product-item');
        
        items.forEach(item => {
            const productName = item.querySelector('.product-name').textContent.toLowerCase();
            
            if (productName.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Validar formulario antes de enviar
    document.getElementById('ventaForm').addEventListener('submit', function(e) {
        if (cart.length === 0) {
            e.preventDefault();
            showNotification('No hay productos en el carrito', 'warning');
        }
    });
});
</script>

<?php
// Incluir footer
include_once '../../includes/footer.php';
?>