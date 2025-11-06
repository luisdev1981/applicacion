<?php
// sistema-inces/index.php
session_start();
require_once 'configuraciones/bd.php';
require_once 'configuraciones/auditoria.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: secciones/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $error = 'Por favor, complete ambos campos.';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        try {
            $conexion = getDbConexion();
            $sql = "SELECT u.id, u.nombre, u.password, r.nombre_rol
                    FROM usuarios u
                    JOIN roles r ON u.rol_id = r.id
                    WHERE u.email = :email AND u.activo = 1";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($password, $usuario['password'])) {
                // Credenciales correctas
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_rol'] = $usuario['nombre_rol'];

                // Actualizar último login
                $updateSql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id";
                $updateStmt = $conexion->prepare($updateSql);
                $updateStmt->bindParam(':id', $usuario['id'], PDO::PARAM_INT);
                $updateStmt->execute();

                // Registrar auditoría de login exitoso
                registrar_accion('LOGIN_EXITOSO', $usuario['id']);

                header('Location: secciones/dashboard.php');
                exit;
            } else {
                // Registrar auditoría de login fallido
                registrar_accion('LOGIN_FALLIDO', null);
                $error = 'Email o contraseña incorrectos.';
            }

        } catch (PDOException $e) {
            error_log('Error en login: ' . $e->getMessage());
            $error = 'Ocurrió un error en el servidor. Intente más tarde.';
        }
    }
}

// Para la vista, incluimos la cabecera del login
$show_navbar = false; // Variable para no mostrar el menú en el login
include 'templates/cabecera.php';
?>

<div class="container-fluid vh-100 d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow" style="width: 100%; max-width: 400px;">
        <div class="card-header text-center bg-primary text-white">
            <h3 class="mb-0">Sistema INCES</h3>
            <p class="mb-0">Gestión Educativa</p>
        </div>
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="fas fa-user-circle fa-4x text-primary"></i>
            </div>
            <h5 class="card-title text-center mb-4">Iniciar Sesión</h5>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Correo Electrónico
                    </label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="admin@inces.gob.ve" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Acceder
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center py-3">
            <small class="text-muted">&copy; <?php echo date('Y'); ?> INCES. Todos los derechos reservados.</small>
        </div>
    </div>
</div>

<?php
// No incluimos el pie de página completo para tener una página de login limpia
// Pero sí cerramos las etiquetas HTML abiertas en la cabecera.
?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
