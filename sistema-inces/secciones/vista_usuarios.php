<?php
// sistema-inces/secciones/vista_usuarios.php
require_once 'auth_middleware.php';
require_once '../configuraciones/bd.php';
require_once '../configuraciones/auditoria.php';

// Verificación de rol: solo Administrador puede acceder
if ($ROL_USUARIO_LOGUEADO !== 'Administrador') {
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
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute(['id' => $id]);
                registrar_accion('ELIMINAR_USUARIO', null, 'usuarios', $id);
                $mensaje = "Usuario eliminado correctamente.";
            } catch (PDOException $e) {
                $error = "Error al eliminar el usuario: " . $e->getMessage();
            }
        }
    }
    // Acción: CREAR o ACTUALIZAR
    elseif (isset($_POST['nombre'], $_POST['email'], $_POST['rol_id'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nombre = trim($_POST['nombre']);
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $rol_id = filter_var($_POST['rol_id'], FILTER_VALIDATE_INT);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $password = $_POST['password'];

        if ($email && $nombre && $rol_id) {
            try {
                if ($id) { // Actualizar
                    $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, rol_id = :rol_id, activo = :activo";
                    if (!empty($password)) {
                        $sql .= ", password = :password";
                    }
                    $sql .= " WHERE id = :id";
                    $stmt = $pdo->prepare($sql);

                    $params = ['nombre' => $nombre, 'email' => $email, 'rol_id' => $rol_id, 'activo' => $activo, 'id' => $id];
                    if (!empty($password)) {
                        $params['password'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $stmt->execute($params);
                    registrar_accion('ACTUALIZAR_USUARIO', null, 'usuarios', $id);
                    $mensaje = "Usuario actualizado correctamente.";

                } else { // Crear
                    if (empty($password)) {
                        $error = "La contraseña es obligatoria para nuevos usuarios.";
                    } else {
                        $sql = "INSERT INTO usuarios (nombre, email, password, rol_id, activo) VALUES (:nombre, :email, :password, :rol_id, :activo)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'nombre' => $nombre,
                            'email' => $email,
                            'password' => password_hash($password, PASSWORD_DEFAULT),
                            'rol_id' => $rol_id,
                            'activo' => $activo
                        ]);
                        $nuevo_id = $pdo->lastInsertId();
                        registrar_accion('CREAR_USUARIO', null, 'usuarios', $nuevo_id);
                        $mensaje = "Usuario creado correctamente.";
                    }
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Error de duplicado
                    $error = "Error: El correo electrónico ya está registrado.";
                } else {
                    $error = "Error al procesar la solicitud: " . $e->getMessage();
                }
            }
        } else {
            $error = "Por favor, complete todos los campos obligatorios correctamente.";
        }
    }
}

// Obtener lista de usuarios y roles para la vista
$stmt_usuarios = $pdo->query("SELECT u.*, r.nombre_rol FROM usuarios u JOIN roles r ON u.rol_id = r.id ORDER BY u.nombre");
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

$stmt_roles = $pdo->query("SELECT * FROM roles WHERE activo = 1 ORDER BY nombre_rol");
$roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

include '../templates/cabecera.php';
?>

<div class="container">
    <h1 class="mt-4 mb-4"><i class="fas fa-users-cog"></i> Gestión de Usuarios</h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Listado de Usuarios</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal" onclick="limpiarModal()">
                <i class="fas fa-plus"></i> Añadir Usuario
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último Login</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($usuario['nombre_rol']); ?></span></td>
                            <td>
                                <?php if ($usuario['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#usuarioModal" onclick='editarUsuario(<?php echo json_encode($usuario); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmarEliminacion(<?php echo $usuario['id']; ?>)">
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

<!-- Modal para Crear/Editar Usuario -->
<div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="usuarioForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Añadir Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="usuarioId">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Dejar en blanco para no cambiar">
                        <small class="form-text text-muted">Mínimo 8 caracteres. Rellenar solo para crear o cambiar la contraseña.</small>
                    </div>
                    <div class="mb-3">
                        <label for="rol_id" class="form-label">Rol</label>
                        <select class="form-select" id="rol_id" name="rol_id" required>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="activo" name="activo" checked>
                        <label class="form-check-label" for="activo">
                            Usuario Activo
                        </label>
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
    document.getElementById('usuarioForm').reset();
    document.getElementById('usuarioId').value = '';
    document.getElementById('modalLabel').textContent = 'Añadir Usuario';
    document.getElementById('password').placeholder = 'Contraseña (obligatoria)';
}

function editarUsuario(usuario) {
    limpiarModal();
    document.getElementById('modalLabel').textContent = 'Editar Usuario';
    document.getElementById('usuarioId').value = usuario.id;
    document.getElementById('nombre').value = usuario.nombre;
    document.getElementById('email').value = usuario.email;
    document.getElementById('rol_id').value = usuario.rol_id;
    document.getElementById('activo').checked = usuario.activo == 1;
    document.getElementById('password').placeholder = 'Dejar en blanco para no cambiar';
}

function confirmarEliminacion(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../templates/pie.php'; ?>
