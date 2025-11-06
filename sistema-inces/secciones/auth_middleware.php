<?php
// sistema-inces/secciones/auth_middleware.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    // Si no está logueado, redirigir a la página de login
    // Usamos una ruta absoluta para asegurar que funcione desde cualquier subdirectorio
    $base_url = '/sistema-inces/'; // Asegúrate que esta sea la ruta correcta
    header('Location: ' . $base_url . 'index.php');
    exit;
}

// Opcional: Podríamos añadir aquí una verificación de actividad de sesión
// para cerrarla después de un tiempo de inactividad.

$ID_USUARIO_LOGUEADO = $_SESSION['usuario_id'];
$NOMBRE_USUARIO_LOGUEADO = $_SESSION['usuario_nombre'];
$ROL_USUARIO_LOGUEADO = $_SESSION['usuario_rol'];

?>