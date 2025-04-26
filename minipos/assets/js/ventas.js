/**
 * Scripts para el módulo de ventas
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initVentasComponents();
});

/**
 * Inicializar componentes del módulo de ventas
 */
function initVentasComponents() {
    // Inicializar datepickers en filtros
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    
    if (fechaInicio && fechaFin) {
        // Validar que fecha fin no sea menor a fecha inicio
        fechaFin.addEventListener('change', function() {
            if (fechaInicio.value && this.value && this.value < fechaInicio.value) {
                showNotification('La fecha final no puede ser menor a la fecha inicial', 'warning');
                this.value = fechaInicio.value;
            }
        });
        
        fechaInicio.addEventListener('change', function() {
            if (fechaFin.value && this.value && fechaFin.value < this.value) {
                fechaFin.value = this.value;
            }
        });
    }
    
    // Inicializar búsqueda en tablas
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            filterTable(this.value);
        });
    }
    
    // Inicializar búsqueda de productos en nueva venta
    const searchProducto = document.getElementById('searchProducto');
    if (searchProducto) {
        searchProducto.addEventListener('keyup', function() {
            filterProducts(this.value);
        });
    }
    
    // Inicializar selección de categoría
    const categoriaFilter = document.getElementById('categoriaFilter');
    if (categoriaFilter) {
        categoriaFilter.addEventListener('change', function() {
            filterProductsByCategory(this.value);
        });
    }
}

/**
 * Filtrar tabla por término de búsqueda
 * @param {string} searchTerm - Término de búsqueda
 */
function filterTable(searchTerm) {
    const term = searchTerm.toLowerCase();
    const table = document.querySelector('table tbody');
    
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    let results = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(term)) {
            row.style.display = '';
            results++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Mostrar mensaje si no hay resultados
    const emptyMessage = document.getElementById('emptyResults');
    if (results === 0) {
        if (!emptyMessage) {
            const message = document.createElement('tr');
            message.id = 'emptyResults';
            message.innerHTML = '<td colspan="100%" class="text-center">No se encontraron resultados para la búsqueda.</td>';
            table.appendChild(message);
        }
    } else if (emptyMessage) {
        emptyMessage.remove();
    }
}

/**
 * Filtrar productos por término de búsqueda
 * @param {string} searchTerm - Término de búsqueda
 */
function filterProducts(searchTerm) {
    const term = searchTerm.toLowerCase();
    const productGrid = document.getElementById('productGrid');
    
    if (!productGrid) return;
    
    const products = productGrid.querySelectorAll('.product-item');
    let results = 0;
    
    products.forEach(product => {
        const text = product.textContent.toLowerCase();
        if (text.includes(term)) {
            product.style.display = '';
            results++;
        } else {
            product.style.display = 'none';
        }
    });
    
    // Mostrar mensaje si no hay resultados
    const emptyMessage = document.getElementById('emptyProductResults');
    if (results === 0) {
        if (!emptyMessage) {
            const message = document.createElement('div');
            message.id = 'emptyProductResults';
            message.className = 'alert alert-warning w-100 text-center';
            message.textContent = 'No se encontraron productos para la búsqueda.';
            productGrid.appendChild(message);
        }
    } else if (emptyMessage) {
        emptyMessage.remove();
    }
}

/**
 * Filtrar productos por categoría
 * @param {string} categoryId - ID de la categoría
 */
function filterProductsByCategory(categoryId) {
    const productGrid = document.getElementById('productGrid');
    
    if (!productGrid) return;
    
    const products = productGrid.querySelectorAll('.product-item');
    
    products.forEach(product => {
        const productCategory = product.getAttribute('data-categoria');
        
        if (!categoryId || productCategory === categoryId) {
            product.style.display = '';
        } else {
            product.style.display = 'none';
        }
    });
}

/**
 * Anular una venta
 * @param {number} ventaId - ID de la venta
 * @param {string} motivo - Motivo de anulación
 * @returns {Promise} - Promesa con el resultado
 */
function anularVenta(ventaId, motivo) {
    return new Promise((resolve, reject) => {
        if (!ventaId) {
            reject('ID de venta no válido');
            return;
        }
        
        if (!motivo || motivo.trim() === '') {
            reject('El motivo es obligatorio');
            return;
        }
        
        // Crear formulario para envío
        const formData = new FormData();
        formData.append('id', ventaId);
        formData.append('motivo', motivo);
        
        // Enviar petición AJAX
        fetch('anular_venta.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resolve(data.message);
            } else {
                reject(data.message || 'Error al anular la venta');
            }
        })
        .catch(error => {
            reject('Error de conexión: ' + error);
        });
    });
}