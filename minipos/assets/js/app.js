/**
 * Script principal del MiniPOS
 */

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Manejo de la barra lateral en dispositivos móviles
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    }
    
    // Configuración de DataTables si está disponible
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
            }
        });
    }
    
    // Manejo de eliminación con confirmación
    document.querySelectorAll('.btn-delete').forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
    
    // Manejo de formularios con validación
    document.querySelectorAll('form.needs-validation').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Manejo de búsqueda en tablas
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.querySelector('.table');
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(function(row) {
                    const rowText = row.textContent.toLowerCase();
                    if (rowText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    }
    
    // Función para formatear números como moneda
    window.formatCurrency = function(amount) {
        return '$ ' + parseFloat(amount).toFixed(2);
    };
    
    // Función para formatear fechas
    window.formatDate = function(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    };
    
    // Manejo de select2 si está disponible
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
});

/**
 * Función para mostrar notificaciones
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, warning, danger, info)
 * @param {number} duration - Duración en milisegundos
 */
function showNotification(message, type = 'info', duration = 3000) {
    // Crear el elemento de notificación
    const notification = document.createElement('div');
    notification.className = `toast align-items-center text-white bg-${type} border-0`;
    notification.setAttribute('role', 'alert');
    notification.setAttribute('aria-live', 'assertive');
    notification.setAttribute('aria-atomic', 'true');
    
    // Contenido de la notificación
    notification.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Contenedor de notificaciones
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    // Añadir notificación al contenedor
    container.appendChild(notification);
    
    // Mostrar notificación
    const toast = new bootstrap.Toast(notification, {
        autohide: true,
        delay: duration
    });
    toast.show();
    
    // Eliminar del DOM cuando se oculte
    notification.addEventListener('hidden.bs.toast', function() {
        notification.remove();
    });
}

/**
 * Función para realizar peticiones AJAX
 * @param {string} url - URL de la petición
 * @param {string} method - Método HTTP (GET, POST, PUT, DELETE)
 * @param {object} data - Datos a enviar
 * @param {function} successCallback - Función a ejecutar en caso de éxito
 * @param {function} errorCallback - Función a ejecutar en caso de error
 */
function ajaxRequest(url, method, data, successCallback, errorCallback) {
    // Crear objeto XMLHttpRequest
    const xhr = new XMLHttpRequest();
    
    // Configurar petición
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    // Manejar respuesta
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (successCallback) successCallback(response);
            } catch (e) {
                if (errorCallback) errorCallback('Error parsing JSON response');
            }
        } else {
            if (errorCallback) errorCallback(xhr.statusText);
        }
    };
    
    // Manejar errores de red
    xhr.onerror = function() {
        if (errorCallback) errorCallback('Network error');
    };
    
    // Enviar petición
    xhr.send(JSON.stringify(data));
}

// Código para el menú móvil
document.addEventListener('DOMContentLoaded', function() {
  const menuToggle = document.querySelector('.navbar-toggler');
  const sidebar = document.querySelector('.sidebar');
  const content = document.querySelector('.content');
  
  // Crear overlay para cerrar el menú
  const overlay = document.createElement('div');
  overlay.className = 'sidebar-overlay';
  document.body.appendChild(overlay);
  
  // Toggle del menú
  if (menuToggle) {
    menuToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
    });
  }
  
  // Cerrar menú al tocar el overlay
  overlay.addEventListener('click', function() {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
  });
  
  // Cerrar menú al seleccionar una opción
  const menuItems = document.querySelectorAll('.sidebar a');
  menuItems.forEach(item => {
    item.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
      }
    });
  });
});