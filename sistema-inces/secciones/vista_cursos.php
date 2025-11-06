<?php
// sistema-inces/secciones/vista_cursos.php
require_once 'auth_middleware.php';
require_once '../configuraciones/bd.php';

// Verificación de rol: Administrador o Coordinador
if (!in_array($ROL_USUARIO_LOGUEADO, ['Administrador', 'Coordinador'])) {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$error = '';
$pdo = getDbConexion();

// Manejo de acciones POST (Crear, Actualizar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Acción: ELIMINAR
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                // Verificar si el curso tiene alumnos inscritos
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM alumnos_cursos WHERE idcurso = :id");
                $stmt_check->execute(['id' => $id]);
                if ($stmt_check->fetchColumn() > 0) {
                    $error = "No se puede eliminar el curso porque tiene alumnos inscritos.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM cursos WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $mensaje = "Curso eliminado correctamente.";
                }
            } catch (PDOException $e) {
                $error = "Error al eliminar el curso: " . $e->getMessage();
            }
        }
    }
    // Acción: CREAR o ACTUALIZAR
    elseif (isset($_POST['nombre_curso'], $_POST['duracion_horas'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nombre_curso = trim($_POST['nombre_curso']);
        $descripcion = trim($_POST['descripcion']);
        $duracion_horas = filter_var($_POST['duracion_horas'], FILTER_VALIDATE_INT);
        $estatus = $_POST['estatus'] ?? 'Inactivo';

        if ($nombre_curso && $duracion_horas > 0) {
            try {
                if ($id) { // Actualizar
                    $sql = "UPDATE cursos SET nombre_curso = :nombre, descripcion = :desc, duracion_horas = :dura, estatus = :estatus WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['nombre' => $nombre_curso, 'desc' => $descripcion, 'dura' => $duracion_horas, 'estatus' => $estatus, 'id' => $id]);
                    $mensaje = "Curso actualizado correctamente.";
                } else { // Crear
                    $sql = "INSERT INTO cursos (nombre_curso, descripcion, duracion_horas, estatus) VALUES (:nombre, :desc, :dura, :estatus)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['nombre' => $nombre_curso, 'desc' => $descripcion, 'dura' => $duracion_horas, 'estatus' => $estatus]);
                    $mensaje = "Curso creado correctamente.";
                }
            } catch (PDOException $e) {
                $error = "Error al procesar la solicitud: " . $e->getMessage();
            }
        } else {
            $error = "Por favor, complete todos los campos obligatorios correctamente.";
        }
    }
}

// Obtener lista de cursos
$stmt_cursos = $pdo->query("SELECT * FROM cursos ORDER BY nombre_curso");
$cursos = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);

include '../templates/cabecera.php';
?>

<div class="container">
    <h1 class="mt-4 mb-4"><i class="fas fa-book"></i> Gestión de Cursos</h1>

    <?php if ($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $mensaje; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Listado de Cursos</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cursoModal" onclick="limpiarModal()">
                <i class="fas fa-plus"></i> Añadir Curso
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Curso</th>
                            <th>Descripción</th>
                            <th>Duración (Horas)</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursos as $curso): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($curso['id']); ?></td>
                            <td><?php echo htmlspecialchars($curso['nombre_curso']); ?></td>
                            <td><?php echo htmlspecialchars(substr($curso['descripcion'], 0, 50)) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($curso['duracion_horas']); ?></td>
                            <td>
                                <span class="badge <?php echo $curso['estatus'] === 'Activo' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($curso['estatus']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#cursoModal" onclick='editarCurso(<?php echo json_encode($curso); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmarEliminacion(<?php echo $curso['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Curso -->
<div class="modal fade" id="cursoModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cursoForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Añadir Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="cursoId">
                    <div class="mb-3">
                        <label for="nombre_curso" class="form-label">Nombre del Curso</label>
                        <input type="text" class="form-control" id="nombre_curso" name="nombre_curso" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="duracion_horas" class="form-label">Duración (en horas)</label>
                        <input type="number" class="form-control" id="duracion_horas" name="duracion_horas" required min="1">
                    </div>
                    <div class="mb-3">
                        <label for="estatus" class="form-label">Estatus</label>
                        <select class="form-select" id="estatus" name="estatus">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminación -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="accion" value="eliminar">
</form>

<script>
function limpiarModal() {
    document.getElementById('cursoForm').reset();
    document.getElementById('cursoId').value = '';
    document.getElementById('modalLabel').textContent = 'Añadir Curso';
}

function editarCurso(curso) {
    limpiarModal();
    document.getElementById('modalLabel').textContent = 'Editar Curso';
    document.getElementById('cursoId').value = curso.id;
    document.getElementById('nombre_curso').value = curso.nombre_curso;
    document.getElementById('descripcion').value = curso.descripcion;
    document.getElementById('duracion_horas').value = curso.duracion_horas;
    document.getElementById('estatus').value = curso.estatus;
}

function confirmarEliminacion(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este curso?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../templates/pie.php'; ?>
