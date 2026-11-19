<?php
require_once __DIR__ . '/conexion.php';

// Comprobar que el usuario haya iniciado sesión como administrador.
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado. Se requieren permisos de administrador.');
}

// La eliminación solamente se permite mediante POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// Validar el token de seguridad.
if (!validar_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud inválida. Volvé a intentarlo.');
}

// Validar el ID recibido.
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: admin_usuarios.php?msg=' . urlencode('El usuario seleccionado no es válido.'));
    exit;
}

// Evitar que el administrador elimine su propia cuenta.
if ((int)$id === (int)$_SESSION['usuario_id']) {
    header('Location: admin_usuarios.php?msg=' . urlencode('No podés eliminar tu propia cuenta.'));
    exit;
}

try {
    // Comprobar que el usuario exista.
    $stmtBuscar = $conexion->prepare(
        'SELECT id, rol FROM usuarios WHERE id = :id'
    );
    $stmtBuscar->execute([':id' => $id]);
    $usuario = $stmtBuscar->fetch();

    if (!$usuario) {
        header('Location: admin_usuarios.php?msg=' . urlencode('El usuario no existe o ya fue eliminado.'));
        exit;
    }

    // Evitar eliminar al último administrador del sistema.
    if ($usuario['rol'] === 'admin') {
        $stmtAdmins = $conexion->query(
            "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'"
        );

        if ((int)$stmtAdmins->fetchColumn() <= 1) {
            header('Location: admin_usuarios.php?msg=' . urlencode('No se puede eliminar al último administrador.'));
            exit;
        }
    }

    // Eliminar definitivamente el registro de la tabla usuarios.
    $stmtEliminar = $conexion->prepare(
        'DELETE FROM usuarios WHERE id = :id'
    );
    $stmtEliminar->execute([':id' => $id]);

    if ($stmtEliminar->rowCount() > 0) {
        header('Location: admin_usuarios.php?msg=' . urlencode('El usuario fue eliminado correctamente.'));
        exit;
    }

    header('Location: admin_usuarios.php?msg=' . urlencode('No se pudo eliminar el usuario.'));
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    header('Location: admin_usuarios.php?msg=' . urlencode(
        'No se pudo eliminar el usuario. Puede tener información relacionada en otras tablas.'
    ));
    exit;
}
?>
