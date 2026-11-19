<form action="registro.php" method="POST">
<label for="nombre">Nombre completo</label>
<input type="text"
id="nombre"
name="nombre"
maxlength="150"
required>
<label for="apellido">Apellido</label>
<input type="text"
id="apellido"
name="apellido"
maxlength="150"
required>
<label for="DNI">DNI</label>
<input type="text"
id="DNI"
name="DNI"
maxlength="20"
required>
<label for="email">Correo electrónico</label>
<input type="email"
id="email"
name="email"
maxlength="254"
required>
<label for="contrasena">Contraseña</label>
<input type="password"
id="contrasena"
name="contrasena"
maxlength="255"
required>
<label for="confirmar">Confirmar contraseña</label>
<input type="password"
id="confirmar"
name="confirmar"
maxlength="255"
required>
<button type="submit">Crear cuenta</button>
</form>


<?php
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$DNI = trim($_POST['DNI'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmar= $_POST['confirmar'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$errores[] = 'El correo no es válido.';
}
if ($password !== $confirmar) {
$errores[] = 'Las contraseñas no coinciden.';
}

#Hash de contraseña
$passwordHash = password_hash(
$password,
PASSWORD_DEFAULT
);


$sql = 'INSERT INTO usuarios
(nombre, email, password_hash, rol)
VALUES (:nombre, :email, :password_hash, :rol)';
$stmt = $conexion->prepare($sql); 
$stmt->execute([ #siempre establece 'rol' => 'usuario'. El cliente no puede modificarlo.
'nombre' => $nombre,
'apellido' => $apellido,
'DNI' => $DNI,
'email' => $email,
'password_hash' => $passwordHash,
'rol' => 'usuario'
]);

/*Buscar usuario*/
$sql = 'SELECT id, nombre, email, password_hash, rol
FROM usuarios
WHERE email = :email
AND archivado_en IS NULL
LIMIT 1';
$stmt = $conexion->prepare($sql);
$stmt->execute(['email' => $email]);
$usuario = $stmt->fetch();

/*Verificación de contraseña y creación de sesión*/
if ($usuario !== false &&
password_verify($password, $usuario['password_hash']))
{
session_start();
session_regenerate_id(true);
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_rol'] = $usuario['rol'];
#Redirigir según rol
if ($usuario['rol'] === 'admin') {
header('Location: admin_usuarios.php');
} else {
header('Location: panel.php');
}
exit;
}