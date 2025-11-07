<?php
// sistema-inces/secciones/ajax_handler.php
require_once 'auth_middleware.php';
require_once '../configuraciones/bd.php';

header('Content-Type: application/json');

$accion = $_GET['accion'] ?? null;
$response = ['status' => 'error', 'message' => 'Acción no válida.'];

if ($accion === 'getCursosInscritos') {
    $id_alumno = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id_alumno) {
        try {
            $pdo = getDbConexion();
            $sql = "SELECT ac.id as inscripcion_id, c.nombre_curso, ac.estatus, ac.fecha_inscripcion, ac.fecha_completado, c.id as id_curso, ac.calificacion
                    FROM alumnos_cursos ac
                    JOIN cursos c ON ac.idcurso = c.id
                    WHERE ac.idalumno = :idalumno
                    ORDER BY c.nombre_curso";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['idalumno' => $id_alumno]);
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['status' => 'success', 'data' => $cursos];
        } catch (PDOException $e) {
            $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'ID de alumno no válido.';
    }
}

echo json_encode($response);
exit;
