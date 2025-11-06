<?php
// sistema-inces/templates/cabecera.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = '/sistema-inces/'; // Ajusta si el proyecto no está en la raíz del servidor

// Definimos $show_navbar por defecto para evitar errores si no se establece antes de incluir el archivo
if (!isset($show_navbar)) {
    $show_navbar = true;
}

$rol = $_SESSION['usuario_rol'] ?? '';

// Definición de permisos por rol (centralizado para la navegación)
$permisos_nav = [
    'Administrador' => ['dashboard', 'usuarios', 'cursos', 'alumnos', 'docentes', 'reportes'],
    'Coordinador' => ['dashboard', 'cursos', 'alumnos', 'docentes', 'reportes'],
    'Instructor' => ['dashboard', 'alumnos', 'reportes']
];

function tiene_permiso($rol, $seccion) {
    global $permisos_nav;
    return in_array($seccion, $permisos_nav[$rol] ?? []);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Educativa - INCES</title>
    <!-- Bootstrap 5.2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <style>
        :root {
            --inces-primary: #003366; /* Azul oscuro institucional */
            --inces-secondary: #f0ad4e; /* Naranja/amarillo institucional */
        }
        body {
            background-color: #f8f9fa;
        }
        .navbar-dark {
            background-color: var(--inces-primary);
        }
        .btn-primary {
            background-color: var(--inces-primary);
            border-color: var(--inces-primary);
        }
        .btn-primary:hover {
            background-color: #002244;
            border-color: #002244;
        }
        .card-header {
            background-color: var(--inces-primary);
        }
        .text-primary {
            color: var(--inces-primary) !important;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding-top: 56px; /* Altura del navbar */
            background-color: #343a40;
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>

<?php if ($show_navbar): ?>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $base_url; ?>secciones/dashboard.php">
            <i class="fas fa-graduation-cap"></i> INCES
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo $base_url; ?>logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="sidebar">
    <div class="p-3">
        <h5>Menú Principal</h5>
        <ul class="nav flex-column">
            <?php if (tiene_permiso($rol, 'dashboard')): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo $base_url; ?>secciones/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <?php endif; ?>

            <?php if (tiene_permiso($rol, 'usuarios')): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo $base_url; ?>secciones/vista_usuarios.php"><i class="fas fa-users-cog"></i> Usuarios</a>
            </li>
            <?php endif; ?>

            <?php if (tiene_permiso($rol, 'cursos')): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo $base_url; ?>secciones/vista_cursos.php"><i class="fas fa-book"></i> Cursos</a>
            </li>
            <?php endif; ?>

            <?php if (tiene_permiso($rol, 'alumnos')): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo $base_url; ?>secciones/vista_alumnos.php"><i class="fas fa-user-graduate"></i> Alumnos</a>
            </li>
            <?php endif; ?>

            <?php if (tiene_permiso($rol, 'docentes')): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo $base_url; ?>secciones/vista_docentes.php"><i class="fas fa-chalkboard-teacher"></i> Docentes</a>
            </li>
            <?php endif; ?>

            <?php if (tiene_permiso($rol, 'reportes')): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo $base_url; ?>secciones/vista_reportes.php"><i class="fas fa-chart-line"></i> Reportes</a>
            </li>
            <?php endif; ?>
            <?php if (tiene_permiso($rol, 'auditoria')): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo $base_url; ?>secciones/vista_auditoria.php"><i class="fas fa-history"></i> Auditoría</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="main-content">
<?php endif; ?>
