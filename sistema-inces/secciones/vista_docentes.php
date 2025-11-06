<?php
// sistema-inces/secciones/vista_docentes.php
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

// Manejo de acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Acción: ELIMINAR
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                // (Opcional) Aquí iría la lógica para verificar si el docente tiene cursos asignados
                $stmt = $pdo->prepare("DELETE FROM docentes WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $mensaje = "Docente eliminado correctamente.";
            } catch (PDOException $e) {
                $error = "Error al eliminar el docente. Es posible que esté asignado a cursos activos.";
            }
        }
    }
    // Acción: CREAR o ACTUALIZAR
    elseif (isset($_POST['nombre'], $_POST['apellido'], $_POST['cedula'], $_POST['email'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $cedula = trim($_POST['cedula']);
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $especialidad = trim($_POST['especialidad']);
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $telefono = trim($_POST['telefono']);
        $estatus = $_POST['estatus'] ?? 'Inactivo';

        if ($cedula && $nombre && $apellido && $email) {
            try {
                if ($id) { // Actualizar
                    $sql = "UPDATE docentes SET cedula=:cedula, nombre=:nombre, apellido=:apellido, especialidad=:especialidad, email=:email, telefono=:telefono, estatus=:estatus WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(compact('cedula', 'nombre', 'apellido', 'especialidad', 'email', 'telefono', 'estatus', 'id'));
                    $mensaje = "Docente actualizado correctamente.";
                } else { // Crear
                    $sql = "INSERT INTO docentes (cedula, nombre, apellido, especialidad, email, telefono, estatus) VALUES (:cedula, :nombre, :apellido, :especialidad, :email, :telefono, :estatus)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(compact('cedula', 'nombre', 'apellido', 'especialidad', 'email', 'telefono', 'estatus'));
                    $mensaje = "Docente creado correctamente.";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Error: La cédula o el correo electrónico ya están registrados.";
                } else {
                    $error = "Error al procesar la solicitud: " . $e->getMessage();
                }
            }
        } else {
            $error = "Por favor, complete todos los campos obligatorios correctamente.";
        }
    }
}

// Obtener lista de docentes
$docentes = $pdo->query("SELECT * FROM docentes ORDER BY apellido, nombre")->fetchAll(PDO::FETCH_ASSOC);

include '../templates/cabecera.php';
?>

<div class="container">
    <h1 class="mt-4 mb-4"><i class="fas fa-chalkboard-teacher"></i> Gestión de Docentes</h1>

    <?php if ($mensaje): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $mensaje; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>

    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Listado de Docentes</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#docenteModal" onclick="limpiarModal()">
                <i class="fas fa-plus"></i> Añadir Docente
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre Completo</th>
                            <th>Especialidad</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($docentes as $docente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($docente['cedula']); ?></td>
                            <td><?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($docente['especialidad']); ?></td>
                            <td><?php echo htmlspecialchars($docente['email']); ?></td>
                            <td><?php echo htmlspecialchars($docente['telefono']); ?></td>
                            <td><span class="badge bg-<?php echo $docente['estatus'] === 'Activo' ? 'success' : ($docente['estatus'] === 'Inactivo' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars($docente['estatus']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#docenteModal" onclick='editarDocente(<?php echo json_encode($docente); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmarEliminacion(<?php echo $docente['id']; ?>)">
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

<!-- Modal para Crear/Editar Docente -->
<div class="modal fade" id="docenteModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="docenteForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Añadir Docente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="docenteId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control" id="cedula" name="cedula" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="especialidad" class="form-label">Especialidad</label>
                            <input type="text" class="form-control" id="especialidad" name="especialidad" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estatus" class="form-label">Estatus</label>
                            <select class="form-select" id="estatus" name="estatus">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                                <option value="Vacaciones">Vacaciones</option>
                            </select>
                        </div>
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
<form id="deleteForm" method="POST" style="display: none;"><input type="hidden" name="id" id="deleteId"><input type="hidden" name="accion" value="eliminar"></form>

<script>
function limpiarModal() {
    document.getElementById('docenteForm').reset();
    document.getElementById('docenteId').value = '';
    document.getElementById('modalLabel').textContent = 'Añadir Docente';
}

function editarDocente(docente) {
    limpiarModal();
    document.getElementById('modalLabel').textContent = 'Editar Docente';
    document.getElementById('docenteId').value = docente.id;
    document.getElementById('cedula').value = docente.cedula;
    document.getElementById('nombre').value = docente.nombre;
    document.getElementById('apellido').value = docente.apellido;
    document.getElementById('especialidad').value = docente.especialidad;
    document.getElementById('email').value = docente.email;
    document.getElementById('telefono').value = docente.telefono;
    document.getElementById('estatus').value = docente.estatus;
}

function confirmarEliminacion(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este docente?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../templates/pie.php'; ?>
