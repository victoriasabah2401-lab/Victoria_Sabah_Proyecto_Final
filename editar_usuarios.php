<?php
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Identificador de usuario inválido.');
}

$stmt = $conexion->prepare('SELECT id, nombre, apellido, dni, email FROM usuarios WHERE id = :id AND archivado_en IS NULL LIMIT 1');
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(404);
    exit('El usuario no existe o se encuentra archivado.');
}

$errores = [];
$token_csrf = obtener_token_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $dni = trim($_POST['dni'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        }
        if ($apellido === '') {
            $errores[] = 'El apellido es obligatorio.';
        }
        if (!preg_match('/^[0-9]{7,8}$/', $dni)) {
            $errores[] = 'El DNI debe contener entre 7 y 8 números.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Ingresá un correo válido.';
        }

        if (empty($errores)) {
            try {
                $sqlUpdate = 'UPDATE usuarios
                              SET nombre = :nombre, apellido = :apellido, dni = :dni, email = :email
                              WHERE id = :id AND archivado_en IS NULL';
                $stmtUpdate = $conexion->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':dni' => $dni,
                    ':email' => $email,
                    ':id' => $id
                ]);

                header('Location: admin_usuarios.php?msg=' . urlencode('Datos actualizados correctamente.'));
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errores[] = 'Ya existe otro usuario con ese DNI o correo electrónico.';
                } else {
                    error_log($e->getMessage());
                    $errores[] = 'No se pudieron actualizar los datos.';
                }
            }
        }
        // Mantener lo ingresado si hubo error
        $usuario['nombre'] = $nombre;
        $usuario['apellido'] = $apellido;
        $usuario['dni'] = $dni;
        $usuario['email'] = $email;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<main class="contenedor-centrado">
    <div class="tarjeta">
        <h1>Editar Usuario #<?= escapar((string)$usuario['id']) ?></h1>

        <?php if (!empty($errores)): ?>
            <div class="alerta alerta-error">
                <ul>
                    <?php foreach ($errores as $err): ?>
                        <li><?= escapar($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="editar_usuario.php?id=<?= (int)$usuario['id'] ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= escapar($token_csrf) ?>">

            <div class="grupo-campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="80" value="<?= escapar($usuario['nombre']) ?>" required>
            </div>

            <div class="grupo-campo">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" maxlength="80" value="<?= escapar($usuario['apellido']) ?>" required>
            </div>

            <div class="grupo-campo">
                <label for="dni">DNI</label>
                <input type="text" id="dni" name="dni" pattern="[0-9]{7,8}" maxlength="8" value="<?= escapar($usuario['dni']) ?>" required>
            </div>

            <div class="grupo-campo">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" maxlength="254" value="<?= escapar($usuario['email']) ?>" required>
            </div>

            <div class="acciones-form">
                <button type="submit" class="btn btn-primario">Guardar cambios</button>
                <a href="admin_usuarios.php" class="btn btn-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>