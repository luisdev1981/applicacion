<?php
// sistema-inces/secciones/vista_reportes.php
require_once 'auth_middleware.php';
require_once '../configuraciones/bd.php';

// Verificación de rol: Todos los roles tienen acceso a reportes según la definición
if (!in_array($ROL_USUARIO_LOGUEADO, ['Administrador', 'Coordinador', 'Instructor'])) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getDbConexion();

// Obtener todos los cursos con sus alumnos inscritos
try {
    $sql = "SELECT
                c.id as id_curso,
                c.nombre_curso,
                a.id as id_alumno,
                a.nombre,
                a.apellidos,
                ac.estatus as estatus_inscripcion
            FROM cursos c
            LEFT JOIN alumnos_cursos ac ON c.id = ac.idcurso
            LEFT JOIN alumnos a ON ac.idalumno = a.id
            ORDER BY c.nombre_curso, a.apellidos, a.nombre";

    $stmt = $pdo->query($sql);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar resultados por curso
    $reporte = [];
    foreach ($resultados as $fila) {
        $reporte[$fila['id_curso']]['nombre_curso'] = $fila['nombre_curso'];
        if ($fila['id_alumno']) {
            $reporte[$fila['id_curso']]['alumnos'][] = [
                'nombre_completo' => $fila['apellidos'] . ', ' . $fila['nombre'],
                'estatus' => $fila['estatus_inscripcion']
            ];
        } else {
            $reporte[$fila['id_curso']]['alumnos'] = [];
        }
    }

} catch (PDOException $e) {
    $error = "Error al generar el reporte: " . $e->getMessage();
}


include '../templates/cabecera.php';
?>

<div class="container">
    <h1 class="mt-4 mb-4"><i class="fas fa-chart-line"></i> Reportes del Sistema</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Reporte de Alumnos por Curso</h5>
            </div>
            <div class="card-body">
                <p>Este reporte muestra todos los cursos y los alumnos inscritos en cada uno.</p>
                <div class="accordion" id="accordionReportes">
                    <?php foreach ($reporte as $id_curso => $curso_data): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?php echo $id_curso; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $id_curso; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $id_curso; ?>">
                                    <?php echo htmlspecialchars($curso_data['nombre_curso']); ?>
                                    <span class="badge bg-primary ms-2"><?php echo count($curso_data['alumnos']); ?> Alumnos</span>
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $id_curso; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $id_curso; ?>" data-bs-parent="#accordionReportes">
                                <div class="accordion-body">
                                    <?php if (!empty($curso_data['alumnos'])): ?>
                                        <ul class="list-group">
                                            <?php foreach ($curso_data['alumnos'] as $alumno): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?php echo htmlspecialchars($alumno['nombre_completo']); ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($alumno['estatus']); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">No hay alumnos inscritos en este curso.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../templates/pie.php'; ?>
