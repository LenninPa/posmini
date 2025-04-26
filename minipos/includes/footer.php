<?php if (isLoggedIn()): ?>
            </main>
        </div>
    </div>
<?php else: ?>
    </div>
<?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                © <?php echo date('Y'); ?> <?php echo $configuracion['nombre_empresa']; ?> - MiniPOS
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Manejo de menú móvil
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const navbarMenu = document.getElementById('navbarMenu');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navbarMenu.classList.toggle('show');
                });
            }
            
            // Dropdown de usuario
            const userDropdown = document.getElementById('userDropdown');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            if (userDropdown) {
                userDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    userDropdownMenu.classList.toggle('show');
                });
                
                // Cerrar dropdown al hacer clic fuera
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });
            }
            
            // Submenús en la barra lateral
            const dropdownToggles = document.querySelectorAll('.sidebar-dropdown-toggle');
            
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                    submenu.classList.toggle('show');
                    this.classList.toggle('active');
                });
            });
        });
        
        // Función para formatear números como moneda
        function formatCurrency(amount) {
            return '$ ' + parseFloat(amount).toFixed(2);
        }
        
        // Función para formatear fechas
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('es-ES', options);
        }
    </script>
    
    <?php if (isset($extra_js)): echo $extra_js; endif; ?>
</body>
</html>