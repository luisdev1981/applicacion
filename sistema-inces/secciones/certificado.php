<?php
// sistema-inces/secciones/certificado.php
require_once 'auth_middleware.php';
require_once '../configuraciones/bd.php';
require_once '../librerias/fpdf/fpdf.php';

// Obtener IDs de la URL
$id_alumno = filter_input(INPUT_GET, 'idalumno', FILTER_VALIDATE_INT);
$id_curso = filter_input(INPUT_GET, 'idcurso', FILTER_VALIDATE_INT);

if (!$id_alumno || !$id_curso) {
    die("Error: Faltan parámetros para generar el certificado.");
}

// Conectar a la BD
$pdo = getDbConexion();

// Buscar la información requerida
try {
    $sql = "SELECT
                a.nombre, a.apellidos,
                c.nombre_curso, c.duracion_horas,
                ac.fecha_completado
            FROM alumnos_cursos ac
            JOIN alumnos a ON ac.idalumno = a.id
            JOIN cursos c ON ac.idcurso = c.id
            WHERE ac.idalumno = :idalumno
              AND ac.idcurso = :idcurso
              AND ac.estatus = 'Completado'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['idalumno' => $id_alumno, 'idcurso' => $id_curso]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datos) {
        die("Error: No se encontró un registro de curso completado para este alumno o los datos son incorrectos.");
    }

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// --- Generación del PDF con FPDF ---

class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        // Logo (simulado con texto)
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'INCES', 0, 1, 'L');
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Instituto Nacional de Capacitacion y Educacion Socialista', 0, 1, 'L');
        $this->Ln(20);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Certificado generado el ' . date('d/m/Y'), 0, 0, 'L');
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'R');
    }

    // Función para crear el cuerpo del certificado
    function CrearCertificado($datos)
    {
        // Borde de la página
        $this->Rect(5, 5, 287, 200); // A4 Landscape dimensions: 297x210 mm

        // Título principal
        $this->SetFont('Times', 'B', 32);
        $this->Cell(0, 30, 'CERTIFICADO DE CULMINACION', 0, 1, 'C');
        $this->Ln(10);

        // Otorgado a
        $this->SetFont('Arial', '', 14);
        $this->Cell(0, 10, 'Otorgado a:', 0, 1, 'C');
        $this->Ln(5);

        // Nombre del Alumno
        $this->SetFont('Arial', 'B', 24);
        $nombre_completo = ucwords(strtolower($datos['nombre'] . ' ' . $datos['apellidos']));
        $this->Cell(0, 15, utf8_decode($nombre_completo), 0, 1, 'C');
        $this->Ln(10);

        // Texto de culminación
        $this->SetFont('Arial', '', 12);
        $texto_culminacion = "Por haber culminado satisfactoriamente el curso de:";
        $this->Cell(0, 10, utf8_decode($texto_culminacion), 0, 1, 'C');
        $this->Ln(5);

        // Nombre del Curso
        $this->SetFont('Arial', 'BI', 20);
        $this->Cell(0, 15, utf8_decode(strtoupper($datos['nombre_curso'])), 0, 1, 'C');
        $this->Ln(5);

        // Duración
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Con una duracion de ' . $datos['duracion_horas'] . ' horas academicas.', 0, 1, 'C');
        $this->Ln(15);

        // Fecha y Código
        $fecha_formateada = date("d de F de Y", strtotime($datos['fecha_completado']));
        $this->Cell(0, 10, 'Completado en: ' . $fecha_formateada, 0, 1, 'C');
        $codigo_unico = 'CERT-' . date('Ymd', strtotime($datos['fecha_completado'])) . '-' . $GLOBALS['id_alumno'] . '-' . $GLOBALS['id_curso'];
        $this->SetFont('Courier', '', 10);
        $this->Cell(0, 10, 'Codigo de Validacion: ' . $codigo_unico, 0, 1, 'C');
        $this->Ln(10);

        // Firmas (simuladas)
        $this->SetFont('Arial', '', 12);
        $this->Cell(130, 10, '_________________________', 0, 0, 'C');
        $this->Cell(130, 10, '_________________________', 0, 1, 'C');
        $this->Cell(130, 10, 'Coordinador Academico', 0, 0, 'C');
        $this->Cell(130, 10, 'Instructor', 0, 1, 'C');
    }
}

// Crear una instancia del PDF
$pdf = new PDF('L', 'mm', 'A4'); // L para Landscape (apaisado)
$pdf->SetTitle('Certificado - ' . $datos['nombre_curso']);
$pdf->SetAuthor('INCES');
$pdf->AddPage();
$pdf->CrearCertificado($datos);
$pdf->Output('I', 'certificado_' . $id_alumno . '_' . $id_curso . '.pdf'); // I para inline
?>
