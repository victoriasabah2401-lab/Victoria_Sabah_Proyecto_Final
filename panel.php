<?php
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$sql = 'SELECT id, nombre, apellido, dni, email, rol, creado_en
        FROM usuarios
        WHERE id = :id AND archivado_en IS NULL
        LIMIT 1';
$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel personal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="barra-superior">
    <div class="contenedor barra-contenido">
        <span class="marca">Información sobre tu cuenta</span>
        <nav>
            <?php if ($usuario['rol'] === 'admin'): ?>
                <a href="admin_usuarios.php" class="btn btn-secundario">Panel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-peligro">Cerrar sesión</a>
        </nav>
    </div>
</header>

<main class="contenedor">
    <div class="tarjeta">
        <h1>Bienvenido/a, <?= escapar($usuario['nombre']) ?></h1>
        <p class="subtitulo">Datos personales:</p>

        <div class="datos-perfil">
            <p><strong>Nombre completo:</strong> <?= escapar($usuario['nombre'] . ' ' . $usuario['apellido']) ?></p>
            <p><strong>DNI:</strong> <?= escapar($usuario['dni']) ?></p>
            <p><strong>Correo electrónico:</strong> <?= escapar($usuario['email']) ?></p>
            <p><strong>Rol de tu cuenta:</strong> <span class="etiqueta"><?= escapar($usuario['rol']) ?></span></p>
            <p><strong>Fecha de registro:</strong> <?= escapar($usuario['creado_en']) ?></p>
        </div>
    </div>
</main>
</body>
</html>
