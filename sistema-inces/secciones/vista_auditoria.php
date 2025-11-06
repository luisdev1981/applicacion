<?php
// sistema-inces/secciones/vista_auditoria.php
require_once 'auth_middleware.php';
require_once '../configuraciones/bd.php';

// Verificación de rol: solo Administrador puede acceder
if ($ROL_USUARIO_LOGUEADO !== 'Administrador') {
    header('Location: dashboard.php');
    exit;
}

$pdo = getDbConexion();

// Paginación
$registros_por_pagina = 15;
$pagina_actual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Contar total de registros para la paginación
$total_registros_stmt = $pdo->query("SELECT COUNT(*) FROM auditoria");
$total_registros = $total_registros_stmt->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros de auditoría con el nombre del usuario
$stmt = $pdo->prepare("SELECT a.*, u.nombre as nombre_usuario
                       FROM auditoria a
                       LEFT JOIN usuarios u ON a.usuario_id = u.id
                       ORDER BY a.creado_en DESC
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$auditorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/cabecera.php';
?>

<div class="container">
    <h1 class="mt-4 mb-4"><i class="fas fa-history"></i> Registros de Auditoría</h1>

    <div class="card shadow">
        <div class="card-header">
            <h5 class="mb-0">Historial de Acciones del Sistema</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Tabla Afectada</th>
                            <th>Registro ID</th>
                            <th>Dirección IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditorias as $auditoria): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($auditoria['creado_en'])); ?></td>
                            <td><?php echo $auditoria['nombre_usuario'] ? htmlspecialchars($auditoria['nombre_usuario']) : 'Sistema'; ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($auditoria['accion']); ?></span></td>
                            <td><?php echo htmlspecialchars($auditoria['tabla_afectada']); ?></td>
                            <td><?php echo htmlspecialchars($auditoria['registro_id']); ?></td>
                            <td><?php echo htmlspecialchars($auditoria['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php include '../templates/pie.php'; ?>
