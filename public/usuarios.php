<?php
// CU-10: Gestionar usuarios
require __DIR__ . '/../inc/bootstrap.php';
requiere_rol(['administrador']);

$roles = ['administrador', 'recepcionista', 'medico'];
$f = ['usuario' => '', 'nombre' => '', 'rol' => 'recepcionista', 'especialidad' => ''];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'estado') {
        $uid = (int)$_POST['id'];
        if ($uid === $_SESSION['u']['id']) {
            flash('No puede desactivar su propia cuenta.', 'error');
        } else {
            db()->prepare('UPDATE usuarios SET activo = 1 - activo WHERE id = ?')->execute([$uid]);
            flash('Estado del usuario actualizado.');
        }
        redirigir('usuarios.php');
    }
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');
    $clave = $_POST['clave'] ?? '';
    if ($f['usuario'] === '' || $f['nombre'] === '') $errores[] = 'Usuario y nombre son obligatorios.';
    if (!in_array($f['rol'], $roles, true)) $errores[] = 'Rol inválido.';
    if ($f['rol'] === 'medico' && $f['especialidad'] === '') $errores[] = 'Indique la especialidad del médico.';
    if (strlen($clave) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if (!$errores) {
        $st = db()->prepare('SELECT id FROM usuarios WHERE usuario = ?');
        $st->execute([$f['usuario']]);
        if ($st->fetch()) $errores[] = 'Ese nombre de usuario ya existe.';
    }
    if (!$errores) {
        db()->prepare('INSERT INTO usuarios (usuario, clave_hash, nombre, rol, especialidad) VALUES (?,?,?,?,?)')
            ->execute([$f['usuario'], password_hash($clave, PASSWORD_DEFAULT), $f['nombre'], $f['rol'], $f['rol'] === 'medico' ? $f['especialidad'] : null]);
        flash('Usuario creado.');
        redirigir('usuarios.php');
    }
}
$usuarios = db()->query('SELECT id, usuario, nombre, rol, especialidad, activo FROM usuarios ORDER BY rol, nombre')->fetchAll();

$titulo = 'Usuarios';
require __DIR__ . '/../inc/header.php';
?>
<?php if ($errores): ?><div class="aviso error"><?php foreach ($errores as $e) echo h($e) . '<br>'; ?></div><?php endif; ?>
<form method="post" class="tarjeta">
  <?= csrf_field() ?>
  <strong>Nuevo usuario</strong>
  <div class="fila">
    <div><label>Usuario</label><input name="usuario" value="<?= h($f['usuario']) ?>"></div>
    <div><label>Nombre completo</label><input name="nombre" value="<?= h($f['nombre']) ?>"></div>
    <div><label>Rol</label><select name="rol"><?php foreach ($roles as $r): ?><option <?= $r === $f['rol'] ? 'selected' : '' ?>><?= h($r) ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="fila">
    <div><label>Especialidad (solo médicos)</label><input name="especialidad" value="<?= h($f['especialidad']) ?>"></div>
    <div><label>Contraseña (mín. 8)</label><input type="password" name="clave"></div>
    <div><button>Crear usuario</button></div>
  </div>
</form>
<table><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th></th></tr>
<?php foreach ($usuarios as $u): ?>
  <tr><td><?= h($u['usuario']) ?></td><td><?= h($u['nombre']) ?></td>
      <td><?= h($u['rol'] . ($u['especialidad'] ? ' · ' . $u['especialidad'] : '')) ?></td>
      <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
      <td><form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
        <button class="chico sec"><?= $u['activo'] ? 'Desactivar' : 'Activar' ?></button></form></td></tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../inc/footer.php'; ?>
