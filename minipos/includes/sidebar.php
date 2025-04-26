<?php

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<div class="sidebar">
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo $current_dir == 'minipos' && $current_page == 'index.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <?php if (tienePermiso('ventas')): ?>
            <li class="sidebar-item">
                <a class="sidebar-link sidebar-dropdown-toggle <?php echo $current_dir == 'ventas' ? 'active' : ''; ?>" href="#">
                    <i class="fas fa-cash-register"></i> Ventas <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="sidebar-submenu <?php echo $current_dir == 'ventas' ? 'show' : ''; ?>">
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'index.php' && $current_dir == 'ventas' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/ventas/">
                            <i class="fas fa-list"></i> Listar Ventas
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'nueva_venta.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/ventas/nueva_venta.php">
                            <i class="fas fa-plus"></i> Nueva Venta
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if (tienePermiso('inventario')): ?>
            <li class="sidebar-item">
                <a class="sidebar-link sidebar-dropdown-toggle <?php echo $current_dir == 'inventario' ? 'active' : ''; ?>" href="#">
                    <i class="fas fa-boxes"></i> Inventario <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="sidebar-submenu <?php echo $current_dir == 'inventario' ? 'show' : ''; ?>">
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'index.php' && $current_dir == 'inventario' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/inventario/">
                            <i class="fas fa-list"></i> Listar Productos
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'nuevo_producto.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/inventario/nuevo_producto.php">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'kardex.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/inventario/kardex.php">
                            <i class="fas fa-exchange-alt"></i> Kardex
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if (tienePermiso('reportes')): ?>
            <li class="sidebar-item">
                <a class="sidebar-link sidebar-dropdown-toggle <?php echo $current_dir == 'reportes' ? 'active' : ''; ?>" href="#">
                    <i class="fas fa-chart-bar"></i> Reportes <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="sidebar-submenu <?php echo $current_dir == 'reportes' ? 'show' : ''; ?>">
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'ventas_diarias.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/reportes/ventas_diarias.php">
                            <i class="fas fa-calendar-day"></i> Ventas Diarias
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'ventas_mensuales.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/reportes/ventas_mensuales.php">
                            <i class="fas fa-calendar-alt"></i> Ventas Mensuales
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'inventario.php' && $current_dir == 'reportes' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/reportes/inventario.php">
                            <i class="fas fa-warehouse"></i> Reporte de Inventario
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if (tienePermiso('ajustes')): ?>
            <li class="sidebar-item">
                <a class="sidebar-link sidebar-dropdown-toggle <?php echo $current_dir == 'ajustes' ? 'active' : ''; ?>" href="#">
                    <i class="fas fa-cogs"></i> Ajustes <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="sidebar-submenu <?php echo $current_dir == 'ajustes' ? 'show' : ''; ?>">
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'usuarios.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/ajustes/usuarios.php">
                            <i class="fas fa-users"></i> Usuarios
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'empresa.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/ajustes/empresa.php">
                            <i class="fas fa-building"></i> Empresa
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?php echo $current_page == 'configuracion.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>modulos/ajustes/configuracion.php">
                            <i class="fas fa-sliders-h"></i> Configuración
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>