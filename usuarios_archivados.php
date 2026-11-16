<?php
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado.');
}

$token_csrf = obtener_token_csrf();

$sql = 'SELECT id, nombre, apellido, dni, email, rol, archivado_en
        FROM usuarios
        WHERE archivado_en IS NOT NULL
        ORDER BY archivado_en DESC';
$stmt = $conexion->query($sql);
$archivados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios Archivados</title>
    <link rel="stylesheet" href="/Victoria_Sabah_proyec_login/style.css">
</head>
<body>
<header class="barra-superior">
    <div class="contenedor barra-contenido">
        <span class="marca">Usuarios Archivados</span>
        <nav>
            <a href="admin_usuarios.php" class="btn btn-secundario">Volver a Activos</a>
            <a href="logout.php" class="btn btn-peligro">Cerrar sesión</a>
        </nav>
    </div>
</header>

<main class="contenedor">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alerta alerta-exito"><?= escapar($_GET['msg']) ?></div>
    <?php endif; ?>

    <section class="tarjeta">
        <h2>Cuentas archivadas (<?= count($archivados) ?>)</h2>

        <?php if (empty($archivados)): ?>
            <p>No hay usuarios archivados en este momento.</p>
        <?php else: ?>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre y Apellido</th>
                            <th>DNI</th>
                            <th>Correo</th>
                            <th>Fecha de archivado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archivados as $u): ?>
                            <tr>
                                <td><?= escapar((string)$u['id']) ?></td>
                                <td><?= escapar($u['nombre'] . ' ' . $u['apellido']) ?></td>
                                <td><?= escapar($u['dni']) ?></td>
                                <td><?= escapar($u['email']) ?></td>
                                <td><?= escapar($u['archivado_en']) ?></td>
                                <td>
                                    <form action="reactivar_usuarios.php" method="POST" onsubmit="return confirm('¿Reactivar la cuenta de este usuario?');">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= escapar($token_csrf) ?>">
                                        <button type="submit" class="btn btn-primario btn-sm">Reactivar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>