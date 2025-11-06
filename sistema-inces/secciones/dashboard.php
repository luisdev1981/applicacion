<?php
// sistema-inces/secciones/dashboard.php
require_once 'auth_middleware.php';
require_once '../configuraciones/bd.php';

$stats = [
    'alumnos' => 0,
    'cursos' => 0,
    'docentes' => 0,
    'certificados' => 0
];

try {
    $conexion = getDbConexion();

    // Contar alumnos activos
    $stmt_alumnos = $conexion->query("SELECT COUNT(*) FROM alumnos WHERE estatus = 'Activo'");
    $stats['alumnos'] = $stmt_alumnos->fetchColumn();

    // Contar cursos activos
    $stmt_cursos = $conexion->query("SELECT COUNT(*) FROM cursos WHERE estatus = 'Activo'");
    $stats['cursos'] = $stmt_cursos->fetchColumn();

    // Contar docentes activos
    $stmt_docentes = $conexion->query("SELECT COUNT(*) FROM docentes WHERE estatus = 'Activo'");
    $stats['docentes'] = $stmt_docentes->fetchColumn();

    // Contar certificados (alumnos con cursos completados)
    $stmt_certificados = $conexion->query("SELECT COUNT(*) FROM alumnos_cursos WHERE estatus = 'Completado'");
    $stats['certificados'] = $stmt_certificados->fetchColumn();

} catch (PDOException $e) {
    // Manejo de error de base de datos
    $error_db = "Error al cargar las estadísticas: " . $e->getMessage();
}

include '../templates/cabecera.php';
?>

<div class="container">
    <h1 class="mt-4 mb-4"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <p>Bienvenido de nuevo, <strong><?php echo htmlspecialchars($NOMBRE_USUARIO_LOGUEADO); ?></strong>. Tu rol es: <strong><?php echo htmlspecialchars($ROL_USUARIO_LOGUEADO); ?></strong>.</p>

    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger"><?php echo $error_db; ?></div>
    <?php endif; ?>

    <hr>

    <h4>Resumen del Sistema</h4>
    <div class="row mt-4">
        <!-- Tarjeta de Estudiantes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Estudiantes Activos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['alumnos']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Cursos -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Cursos Disponibles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['cursos']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Docentes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Docentes Activos
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $stats['docentes']; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Certificados -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Certificados Emitidos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['certificados']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-certificate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Accesos Rápidos</h6>
                </div>
                <div class="card-body">
                    <p>Utilice el menú de la izquierda para navegar por las diferentes secciones del sistema.</p>
                    <!-- Se podrían agregar botones de acceso rápido aquí según el rol -->
                    <?php if ($ROL_USUARIO_LOGUEADO === 'Administrador'): ?>
                        <a href="vista_usuarios.php" class="btn btn-primary"><i class="fas fa-users-cog"></i> Gestionar Usuarios</a>
                    <?php endif; ?>
                    <?php if (in_array($ROL_USUARIO_LOGUEADO, ['Administrador', 'Coordinador'])): ?>
                        <a href="vista_cursos.php" class="btn btn-success"><i class="fas fa-book"></i> Gestionar Cursos</a>
                        <a href="vista_alumnos.php" class="btn btn-info"><i class="fas fa-user-graduate"></i> Gestionar Alumnos</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.card .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.card .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.card .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
.text-gray-300 { color: #dddfeb !important; }
.text-gray-800 { color: #5a5c69 !important; }
</style>

<?php include '../templates/pie.php'; ?>
