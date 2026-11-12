<?php
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

if (!validar_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Token de seguridad inválido.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1 || $id === (int)$_SESSION['usuario_id']) {
    http_response_code(400);
    exit('Operación no válida.');
}

$sql = 'UPDATE usuarios SET archivado_en = CURRENT_TIMESTAMP WHERE id = :id AND archivado_en IS NULL';
$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $id]);

header('Location: admin_usuarios.php?msg=' . urlencode('Usuario archivado correctamente.'));
exit;
?>