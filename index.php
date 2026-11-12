<?php
require_once __DIR__ . '/conexion.php';

// Si ya hay sesión iniciada, redirigir
if (isset($_SESSION['usuario_id'])) {
    if (($_SESSION['usuario_rol'] ?? '') === 'admin') {
        header('Location: admin_usuarios.php');
    } else {
        header('Location: panel.php');
    }
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Completá tu correo electrónico y contraseña.';
    } else {
        $sql = 'SELECT id, nombre, apellido, email, password_hash, rol
                FROM usuarios
                WHERE email = :email AND archivado_en IS NULL
                LIMIT 1';
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            if ($usuario['rol'] === 'admin') {
                header('Location: admin_usuarios.php');
            } else {
                header('Location: panel.php');
            }
            exit;
        } else {
            $error = 'Credenciales incorrectas o cuenta inactiva.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="contenedor-centrado">
    <div class="tarjeta">
        <h1>Iniciar Sesión</h1>

        <?php if ($error): ?>
            <div class="alerta alerta-error"><?= escapar($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="grupo-campo">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="<?= escapar($email) ?>" required>
            </div>

            <div class="grupo-campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primario btn-bloque">Ingresar</button>
        </form>

        <p class="texto-pie">¿No tenés cuenta? <a href="registro.php">Registrate acá</a></p>
    </div>
</main>
</body>
</html>