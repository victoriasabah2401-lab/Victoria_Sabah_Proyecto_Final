<?php
require_once __DIR__ . '/conexion.php';

$errores = [];
$mensaje = '';
$nombre = '';
$apellido = '';
$dni = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if ($apellido === '') {
        $errores[] = 'El apellido es obligatorio.';
    }
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) {
        $errores[] = 'El DNI debe contener entre 7 y 8 números sin puntos.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresá un correo electrónico válido.';
    }
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($password !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = 'INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                    VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)';
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':dni' => $dni,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':rol' => 'usuario'
            ]);

            $mensaje = 'Cuenta creada con éxito. Ya podés iniciar sesión.';
            $nombre = $apellido = $dni = $email = '';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'El DNI o el correo electrónico ya se encuentran registrados.';
            } else {
                error_log($e->getMessage());
                $errores[] = 'Ocurrió un error al guardar los datos.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="contenedor-centrado">
    <div class="tarjeta">
        <h1>Crear una cuenta</h1>

        <?php if ($mensaje): ?>
            <div class="alerta alerta-exito"><?= escapar($mensaje) ?></div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alerta alerta-error">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= escapar($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="registro.php" method="POST">
            <div class="grupo-campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="80" value="<?= escapar($nombre) ?>" required>
            </div>

            <div class="grupo-campo">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" maxlength="80" value="<?= escapar($apellido) ?>" required>
            </div>

            <div class="grupo-campo">
                <label for="dni">DNI</label>
                <input type="text" id="dni" name="dni" inputmode="numeric" pattern="[0-9]{7,8}" maxlength="8" value="<?= escapar($dni) ?>" required>
            </div>

            <div class="grupo-campo">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" maxlength="254" value="<?= escapar($email) ?>" required>
            </div>

            <div class="grupo-campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </div>

            <div class="grupo-campo">
                <label for="confirmar">Confirmar contraseña</label>
                <input type="password" id="confirmar" name="confirmar" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-primario btn-bloque">Registrarse</button>
        </form>

        <p class="texto-pie"> Ya tengo cuenta <a href="index.php">Iniciar sesión</a></p>
    </div>
</main>
</body>
</html>