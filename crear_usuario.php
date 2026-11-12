<form action="registro.php" method="POST">
<label for="nombre">Nombre completo</label>
<input type="text"
id="nombre"
name="nombre"
maxlength="150"
required>
<label for="email">Correo electrónico</label>
<input type="email"
id="email"
name="email"
maxlength="254"
required>
<label for="contrasena">Contraseña</label>
<input type="contrasena"
id="contrasena"
name="contrasena"
maxlength="255"
required>
<label for="confirmar">Confirmar contraseña</label>
<input type="confirmar"
id="confirmar"
name="confirmar"
maxlength="255"
required>
<button type="submit">Crear cuenta</button>
</form>


<?php
$nombre = trim($_POST['nombre'] ?? '');
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

#Inserción preparada (protegida contra SQL injection)
$sql = 'INSERT INTO usuarios
(nombre, email, password_hash, rol)
VALUES (:nombre, :email, :password_hash, :rol)';
$stmt = $conexion->prepare($sql); 
$stmt->execute([ #siempre establece 'rol' => 'usuario'. El cliente no puede modificarlo.
'nombre' => $nombre,
'email' => $email,
'password_hash' => $passwordHash,
'rol' => 'usuario'
]);