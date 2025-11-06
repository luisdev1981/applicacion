<?php
// sistema-inces/secciones/vista_alumnos.php
require_once 'auth_middleware.php';
require_once '../configuraciones/bd.php';

// Todos los roles pueden ver, pero solo Admin y Coordinador pueden modificar
$puede_modificar = in_array($ROL_USUARIO_LOGUEADO, ['Administrador', 'Coordinador']);

$mensaje = '';
$error = '';
$pdo = getDbConexion();

// Manejo de acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puede_modificar) {
    // Acción: ELIMINAR
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM alumnos WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $mensaje = "Alumno eliminado correctamente.";
            } catch (PDOException $e) {
                $error = "Error al eliminar el alumno: " . $e->getMessage();
            }
        }
    }
    // Acción: CREAR o ACTUALIZAR
    elseif (isset($_POST['nombre'], $_POST['apellidos'], $_POST['cedula'], $_POST['email'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $cedula = trim($_POST['cedula']);
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $telefono = trim($_POST['telefono']);
        $estatus = $_POST['estatus'] ?? 'Inactivo';

        if ($cedula && $nombre && $apellidos && $email) {
            try {
                if ($id) { // Actualizar
                    $sql = "UPDATE alumnos SET cedula=:cedula, nombre=:nombre, apellidos=:apellidos, email=:email, telefono=:telefono, estatus=:estatus WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(compact('cedula', 'nombre', 'apellidos', 'email', 'telefono', 'estatus', 'id'));
                    $mensaje = "Alumno actualizado correctamente.";
                } else { // Crear
                    $sql = "INSERT INTO alumnos (cedula, nombre, apellidos, email, telefono, estatus) VALUES (:cedula, :nombre, :apellidos, :email, :telefono, :estatus)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(compact('cedula', 'nombre', 'apellidos', 'email', 'telefono', 'estatus'));
                    $mensaje = "Alumno creado correctamente.";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Error: La cédula o el correo electrónico ya están registrados.";
                } else {
                    $error = "Error al procesar la solicitud: " . $e->getMessage();
                }
            }
        } else {
            $error = "Por favor, complete todos los campos obligatorios.";
        }
    }
    // Acción: INSCRIBIR ALUMNO A CURSO
    elseif (isset($_POST['accion']) && $_POST['accion'] === 'inscribir') {
        $idalumno = filter_input(INPUT_POST, 'idalumno', FILTER_VALIDATE_INT);
        $idcurso = filter_input(INPUT_POST, 'idcurso', FILTER_VALIDATE_INT);

        if ($idalumno && $idcurso) {
            try {
                // Verificar que no esté ya inscrito
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM alumnos_cursos WHERE idalumno = :idalumno AND idcurso = :idcurso");
                $stmt_check->execute(['idalumno' => $idalumno, 'idcurso' => $idcurso]);
                if ($stmt_check->fetchColumn() > 0) {
                    $error = "El alumno ya está inscrito en este curso.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO alumnos_cursos (idalumno, idcurso, estatus) VALUES (:idalumno, :idcurso, 'Inscrito')");
                    $stmt->execute(['idalumno' => $idalumno, 'idcurso' => $idcurso]);
                    $mensaje = "Inscripción realizada correctamente.";
                }
            } catch (PDOException $e) {
                $error = "Error al inscribir al alumno: " . $e->getMessage();
            }
        }
    }
}

// Obtener lista de alumnos
$alumnos = $pdo->query("SELECT * FROM alumnos ORDER BY apellidos, nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de cursos activos para el modal de inscripción
$cursos_activos = $pdo->query("SELECT id, nombre_curso FROM cursos WHERE estatus = 'Activo' ORDER BY nombre_curso")->fetchAll(PDO::FETCH_ASSOC);

include '../templates/cabecera.php';
?>

<div class="container">
    <h1 class="mt-4 mb-4"><i class="fas fa-user-graduate"></i> Gestión de Alumnos</h1>

    <?php if ($mensaje): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $mensaje; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>

    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Listado de Alumnos</h5>
            <?php if ($puede_modificar): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#alumnoModal" onclick="limpiarModal()">
                <i class="fas fa-plus"></i> Añadir Alumno
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $alumno): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($alumno['cedula']); ?></td>
                            <td><?php echo htmlspecialchars($alumno['apellidos'] . ', ' . $alumno['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($alumno['email']); ?></td>
                            <td><?php echo htmlspecialchars($alumno['telefono']); ?></td>
                            <td><span class="badge bg-<?php echo $alumno['estatus'] === 'Activo' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($alumno['estatus']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#inscripcionModal" onclick="cargarInscripciones(<?php echo $alumno['id']; ?>, '<?php echo htmlspecialchars(addslashes($alumno['nombre'] . ' ' . $alumno['apellidos'])); ?>')">
                                    <i class="fas fa-book-reader"></i> Cursos
                                </button>
                                <?php if ($puede_modificar): ?>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#alumnoModal" onclick='editarAlumno(<?php echo json_encode($alumno); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmarEliminacion(<?php echo $alumno['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Alumno -->
<div class="modal fade" id="alumnoModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="alumnoForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Añadir Alumno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="alumnoId">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="cedula">Cédula</label><input type="text" class="form-control" id="cedula" name="cedula" required></div>
                        <div class="col-md-4 mb-3"><label for="nombre">Nombre</label><input type="text" class="form-control" id="nombre" name="nombre" required></div>
                        <div class="col-md-4 mb-3"><label for="apellidos">Apellidos</label><input type="text" class="form-control" id="apellidos" name="apellidos" required></div>
                        <div class="col-md-6 mb-3"><label for="email">Email</label><input type="email" class="form-control" id="email" name="email" required></div>
                        <div class="col-md-6 mb-3"><label for="telefono">Teléfono</label><input type="tel" class="form-control" id="telefono" name="telefono"></div>
                        <div class="col-md-6 mb-3"><label for="estatus_alumno">Estatus</label><select class="form-select" id="estatus_alumno" name="estatus"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Inscripciones -->
<div class="modal fade" id="inscripcionModal" tabindex="-1" aria-labelledby="inscripcionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inscripcionModalLabel">Inscripciones de Alumno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 id="nombreAlumnoInscripcion"></h6>
                <hr>
                <h5>Cursos Inscritos</h5>
                <div id="listaCursosInscritos" class="mb-4">
                    <!-- Contenido cargado por AJAX -->
                    <p>Cargando...</p>
                </div>

                <?php if ($puede_modificar): ?>
                <hr>
                <h5>Inscribir a Nuevo Curso</h5>
                <form id="inscripcionForm" method="POST">
                    <input type="hidden" name="accion" value="inscribir">
                    <input type="hidden" name="idalumno" id="inscripcionAlumnoId">
                    <div class="input-group">
                        <select name="idcurso" class="form-select" required>
                            <option value="">Seleccione un curso...</option>
                            <?php foreach ($cursos_activos as $curso): ?>
                            <option value="<?php echo $curso['id']; ?>"><?php echo htmlspecialchars($curso['nombre_curso']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i> Inscribir</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminación -->
<form id="deleteForm" method="POST" style="display: none;"><input type="hidden" name="id" id="deleteId"><input type="hidden" name="accion" value="eliminar"></form>

<script>
function limpiarModal() {
    document.getElementById('alumnoForm').reset();
    document.getElementById('alumnoId').value = '';
    document.getElementById('modalLabel').textContent = 'Añadir Alumno';
}

function editarAlumno(alumno) {
    limpiarModal();
    document.getElementById('modalLabel').textContent = 'Editar Alumno';
    document.getElementById('alumnoId').value = alumno.id;
    document.getElementById('cedula').value = alumno.cedula;
    document.getElementById('nombre').value = alumno.nombre;
    document.getElementById('apellidos').value = alumno.apellidos;
    document.getElementById('email').value = alumno.email;
    document.getElementById('telefono').value = alumno.telefono;
    document.getElementById('estatus_alumno').value = alumno.estatus;
}

function confirmarEliminacion(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este alumno? Esta acción también eliminará todas sus inscripciones.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Función para cargar los cursos de un alumno vía AJAX
function cargarInscripciones(alumnoId, nombreAlumno) {
    document.getElementById('nombreAlumnoInscripcion').textContent = `Alumno: ${nombreAlumno}`;
    document.getElementById('inscripcionAlumnoId').value = alumnoId;
    const listaDiv = document.getElementById('listaCursosInscritos');
    listaDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

    fetch(`ajax_handler.php?accion=getCursosInscritos&id=${alumnoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.data.length > 0) {
                    let html = '<table class="table table-sm table-bordered"><thead><tr><th>Curso</th><th>Estatus</th><th>Inscripción</th><th>Acciones</th></tr></thead><tbody>';
                    data.data.forEach(curso => {
                        let boton = '';
                        if (curso.estatus === 'Completado') {
                            boton = `<a href="certificado.php?idalumno=${alumnoId}&idcurso=${curso.id_curso}" target="_blank" class="btn btn-xs btn-success"><i class="fas fa-certificate"></i> Ver Certificado</a>`;
                        }
                        html += `<tr>
                                    <td>${curso.nombre_curso}</td>
                                    <td><span class="badge bg-primary">${curso.estatus}</span></td>
                                    <td>${new Date(curso.fecha_inscripcion).toLocaleDateString()}</td>
                                    <td>${boton}</td>
                                 </tr>`;
                    });
                    html += '</tbody></table>';
                    listaDiv.innerHTML = html;
                } else {
                    listaDiv.innerHTML = '<p class="text-muted">Este alumno no está inscrito en ningún curso.</p>';
                }
            } else {
                listaDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            listaDiv.innerHTML = '<div class="alert alert-danger">Error al cargar los datos.</div>';
            console.error('Error:', error);
        });
}
</script>


<?php include '../templates/pie.php'; ?>
