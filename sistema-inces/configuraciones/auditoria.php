<?php
// sistema-inces/configuraciones/auditoria.php
require_once 'bd.php';

/**
 * Registra una acción en la tabla de auditoría.
 *
 * @param string $accion Descripción de la acción realizada (e.g., 'CREAR_USUARIO', 'LOGIN_EXITOSO').
 * @param int|null $usuario_id ID del usuario que realiza la acción. Se toma de la sesión si es null.
 * @param string|null $tabla_afectada Nombre de la tabla principal afectada.
 * @param int|null $registro_id ID del registro afectado en la tabla.
 */
function registrar_accion($accion, $usuario_id = null, $tabla_afectada = null, $registro_id = null) {
    // Si no se pasa un usuario_id, intentamos obtenerlo de la sesión
    if ($usuario_id === null && isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];
    }

    // Recopilar información del entorno
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    try {
        $pdo = getDbConexion();
        $sql = "INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, ip_address, user_agent)
                VALUES (:usuario_id, :accion, :tabla_afectada, :registro_id, :ip_address, :user_agent)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':accion' => $accion,
            ':tabla_afectada' => $tabla_afectada,
            ':registro_id' => $registro_id,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        ]);

    } catch (PDOException $e) {
        // En un caso real, esto debería ir a un log de errores separado
        // para no interrumpir la experiencia del usuario.
        error_log("Error en el sistema de auditoria: " . $e->getMessage());
    }
}
?>
