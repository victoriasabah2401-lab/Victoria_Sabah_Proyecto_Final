<?php
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado. Se requieren permisos de administrador.');
}

$errores = [];
$mensaje = '';
$token_csrf = obtener_token_csrf();

// Alta de usuario desde el panel administrativo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    if (!validar_csrf($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Solicitud inválida (token de seguridad incorrecto).';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $dni = trim($_POST['dni'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = ($_POST['rol'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';

        if ($nombre === '' || $apellido === '') {
            $errores[] = 'Nombre y apellido son obligatorios.';
        }
        if (!preg_match('/^[0-9]{7,8}$/', $dni)) {
            $errores[] = 'El DNI debe tener entre 7 y 8 números.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }
        if (strlen($password) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        }

        if (empty($errores)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sqlInsert = 'INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                              VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)';
                $stmtInsert = $conexion->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':dni' => $dni,
                    ':email' => $email,
                    ':password_hash' => $hash,
                    ':rol' => $rol
                ]);
                $mensaje = 'Usuario registrado correctamente.';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errores[] = 'El DNI o correo electrónico ya está registrado.';
                } else {
                    error_log($e->getMessage());
                    $errores[] = 'Error al registrar el usuario.';
                }
            }
        }
    }
}

// Búsqueda de usuarios activos
$buscar = trim($_GET['buscar'] ?? '');
$sql = 'SELECT id, nombre, apellido, dni, email, rol, creado_en
        FROM usuarios
        WHERE archivado_en IS NULL';
$parametros = [];

if ($buscar !== '') {
    $sql .= ' AND (nombre LIKE :nombre OR apellido LIKE :apellido OR dni LIKE :dni OR email LIKE :email)';
    $termino = '%' . $buscar . '%';
    $parametros = [
        ':nombre' => $termino,
        ':apellido' => $termino,
        ':dni' => $termino,
        ':email' => $termino
    ];
}

$sql .= ' ORDER BY id DESC';
$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="barra-superior">
    <div class="contenedor barra-contenido">
        <span class="marca">Administración de Usuarios</span>
        <nav>
            <a href="panel.php" class="btn btn-secundario">Mi Perfil</a>
            <a href="usuarios_archivados.php" class="btn btn-secundario">Ver Archivados</a>
            <a href="logout.php" class="btn btn-peligro">Cerrar sesión</a>
        </nav>
    </div>
</header>

<main class="contenedor">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alerta alerta-exito"><?= escapar($_GET['msg']) ?></div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-exito"><?= escapar($mensaje) ?></div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="alerta alerta-error">
            <ul>
                <?php foreach ($errores as $err): ?>
                    <li><?= escapar($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Formulario de registro administrativo -->
    <section class="tarjeta">
        <h2>Registrar nuevo usuario</h2>
        <form action="admin_usuarios.php" method="POST" class="form-grid">
            <input type="hidden" name="accion" value="crear">
            <input type="hidden" name="csrf_token" value="<?= escapar($token_csrf) ?>">

            <div class="grupo-campo">
                <label>Nombre</label>
                <input type="text" name="nombre" maxlength="80" required>
            </div>
            <div class="grupo-campo">
                <label>Apellido</label>
                <input type="text" name="apellido" maxlength="80" required>
            </div>
            <div class="grupo-campo">
                <label>DNI</label>
                <input type="text" name="dni" pattern="[0-9]{7,8}" maxlength="8" required>
            </div>
            <div class="grupo-campo">
                <label>Correo electrónico</label>
                <input type="email" name="email" maxlength="254" required>
            </div>
            <div class="grupo-campo">
                <label>Contraseña</label>
                <input type="password" name="password" minlength="6" required>
            </div>
            <div class="grupo-campo">
                <label>Rol</label>
                <select name="rol">
                    <option value="usuario">Usuario</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="grupo-campo-completo">
                <button type="submit" class="btn btn-primario">Guardar Usuario</button>
            </div>
        </form>
    </section>

    <!-- Listado y buscador -->
    <section class="tarjeta">
        <div class="cabecera-lista">
            <h2>Usuarios activos (<?= count($usuarios) ?>)</h2>
            <form method="GET" action="admin_usuarios.php" class="form-buscador">
                <input type="search" name="buscar" maxlength="100" placeholder="Nombre, apellido, DNI o correo" value="<?= escapar($buscar) ?>">
                <button type="submit" class="btn btn-primario">Buscar</button>
                <?php if ($buscar !== ''): ?>
                    <a href="admin_usuarios.php" class="btn btn-secundario">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($usuarios)): ?>
            <p>No se encontraron usuarios activos.</p>
        <?php else: ?>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre y Apellido</th>
                            <th>DNI</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= escapar((string)$u['id']) ?></td>
                                <td><?= escapar($u['nombre'] . ' ' . $u['apellido']) ?></td>
                                <td><?= escapar($u['dni']) ?></td>
                                <td><?= escapar($u['email']) ?></td>
                                <td><span class="etiqueta"><?= escapar($u['rol']) ?></span></td>
                                <td class="acciones">
                                    <a href="editar_usuarios.php?id=<?= (int)$u['id'] ?>" class="btn btn-secundario btn-sm">Editar</a>

                                    <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                        <form action="archivar_usuarios.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseás archivar esta cuenta?');">
                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= escapar($token_csrf) ?>">
                                            <button type="submit" class="btn btn-peligro btn-sm">Archivar</button>
                                        </form>
                                        <form action="eliminar_usuario.php" method="POST"
                                          style="display:inline;"
      o                                   nsubmit="return confirm('¿Estás seguro/a de que querés eliminar definitivamente este usuario? Esta acción no se puede deshacer.');">

                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                                         <input type="hidden" name="csrf_token"
                                          value="<?= escapar($token_csrf) ?>">

                                        <button type="submit" class="btn btn-peligro btn-sm">
                                         Eliminar
                                         </button>
                                        </form>
                                    <?php endif; ?>
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