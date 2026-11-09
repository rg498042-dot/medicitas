<?php
// CU-01: Iniciar sesión
require __DIR__ . '/../inc/bootstrap.php';
if (!empty($_SESSION['u'])) redirigir('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';
    $ok = false;

    if ($usuario === '' || $clave === '') {                       // 2a. campos vacíos
        $error = 'El usuario y la contraseña son obligatorios.';
    } else {
        $st = db()->prepare('SELECT * FROM usuarios WHERE usuario = ?');
        $st->execute([$usuario]);
        $u = $st->fetch();

        if ($u && !$u['activo']) {                                // 3b. usuario inactivo
            $error = 'La cuenta está desactivada. Contacte al administrador.';
        } elseif ($u && $u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time()) {
            $error = 'Cuenta bloqueada temporalmente por intentos fallidos. Intente más tarde.';
        } elseif ($u && password_verify($clave, $u['clave_hash'])) {
            $ok = true;
        } else {                                                  // 3a. credenciales incorrectas
            $error = 'Usuario o contraseña incorrectos.';
            if ($u) {
                $n = $u['intentos_fallidos'] + 1;
                if ($n >= 3) {
                    db()->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?')->execute([$u['id']]);
                    $error = 'Tres intentos fallidos: la cuenta quedó bloqueada por 15 minutos.';
                } else {
                    db()->prepare('UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?')->execute([$n, $u['id']]);
                }
            }
        }
    }

    // Postcondición: el intento queda registrado
    db()->prepare('INSERT INTO log_accesos (usuario, exito, ip) VALUES (?, ?, ?)')
        ->execute([$usuario, $ok ? 1 : 0, $_SERVER['REMOTE_ADDR'] ?? '']);

    if ($ok) {
        if (password_needs_rehash($u['clave_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE usuarios SET clave_hash = ? WHERE id = ?')->execute([password_hash($clave, PASSWORD_DEFAULT), $u['id']]);
        }
        db()->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?')->execute([$u['id']]);
        session_regenerate_id(true);
        $_SESSION['u'] = ['id' => (int)$u['id'], 'nombre' => $u['nombre'], 'rol' => $u['rol']];
        redirigir('index.php');
    }
}
$titulo = 'Iniciar sesión';
require __DIR__ . '/../inc/header.php';
?>
<div class="tarjeta login">
  <?php if ($error): ?><div class="aviso error"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Usuario</label>
    <input name="usuario" value="<?= h($_POST['usuario'] ?? '') ?>" autofocus>
    <label>Contraseña</label>
    <input type="password" name="clave">
    <p><button>Ingresar</button></p>
  </form>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
