/**
 * JavaScript para la página de gestión de usuarios
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initComponents();
    
    // Configurar botones de acción
    setupActionButtons();
    
    // Configurar búsqueda
    setupSearch();
});

/**
 * Inicializar componentes de la interfaz
 */
function initComponents() {
    // Inicializar tooltips si Bootstrap está disponible
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Configurar botones de acción en la tabla
 */
function setupActionButtons() {
    // Botones de edición
    const editButtons = document.querySelectorAll('.action-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userId = this.getAttribute('data-id');
            
            // Obtener datos del usuario mediante AJAX usando XMLHttpRequest
            // (más confiable que fetch para debugging en este caso)
            const xhr = new XMLHttpRequest();
            
            // Mostrar indicador de carga
            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    // Restaurar botón
                    button.innerHTML = originalContent;
                    
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            
                            if (data.success) {
                                // Llenar el formulario con los datos del usuario
                                document.getElementById('edit_id').value = data.user.id;
                                document.getElementById('edit_nombre').value = data.user.nombre;
                                document.getElementById('edit_usuario').value = data.user.usuario;
                                document.getElementById('edit_rol').value = data.user.rol;
                                document.getElementById('edit_estado').checked = data.user.estado == 1;
                                
                                // Mostrar el modal
                                const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                                editModal.show();
                            } else {
                                alert('Error: ' + (data.message || 'No se pudo cargar los datos del usuario'));
                            }
                        } catch (e) {
                            console.error('Error al parsear JSON:', e);
                            console.log('Respuesta recibida:', xhr.responseText);
                            alert('Error al procesar la respuesta del servidor. Consulte la consola para más detalles.');
                        }
                    } else {
                        alert('Error del servidor: ' + xhr.status);
                    }
                }
            };
            
            xhr.open('GET', '?action=get_user&id=' + userId, true);
            xhr.send();
        });
    });
    
   // Botones de edición
const editButtons = document.querySelectorAll('.action-edit');
editButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        const userId = this.getAttribute('data-id');
        
        // Mostrar indicador de carga
        const originalContent = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Hacer la petición Ajax
        const xhr = new XMLHttpRequest();
        
        // Para depuración: agrega un console.log para ver la URL completa
        const url = window.location.pathname + '?action=get_user&id=' + userId;
        console.log('URL de petición:', url);
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                // Restaurar botón
                button.innerHTML = originalContent;
                
                // Para depuración: muestra la respuesta en la consola
                console.log('Respuesta del servidor:', xhr.responseText);
                
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        
                        if (data.success) {
                            // Llenar el formulario con los datos del usuario
                            document.getElementById('edit_id').value = data.user.id;
                            document.getElementById('edit_nombre').value = data.user.nombre;
                            document.getElementById('edit_usuario').value = data.user.usuario;
                            document.getElementById('edit_rol').value = data.user.rol;
                            document.getElementById('edit_estado').checked = data.user.estado == 1;
                            
                            // Mostrar el modal
                            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                            editModal.show();
                        } else {
                            alert('Error: ' + (data.message || 'No se pudo cargar los datos del usuario'));
                        }
                    } catch (e) {
                        console.error('Error al parsear JSON:', e);
                        console.log('Respuesta recibida:', xhr.responseText);
                        alert('Error al procesar la respuesta del servidor. Consulte la consola para más detalles.');
                    }
                } else {
                    alert('Error del servidor: ' + xhr.status);
                }
            }
        };
        
        xhr.open('GET', url, true);
        xhr.send();
    });
});

/**
 * Configurar búsqueda en la tabla
 */
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.querySelector('table tbody');
            
            if (table) {
                const rows = table.querySelectorAll('tr');
                
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
}