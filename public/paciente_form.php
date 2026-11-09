<?php
// CU-02: Registrar paciente (también permite editar)
require __DIR__ . '/../inc/bootstrap.php';
requiere_rol(['recepcionista', 'administrador']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = ['documento' => '', 'nombre' => '', 'apellido' => '', 'telefono' => '', 'email' => '', 'fecha_nacimiento' => ''];
if ($id) {
    $st = db()->prepare('SELECT * FROM pacientes WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { flash('Paciente no encontrado.', 'error'); redirigir('pacientes.php'); }
    $p = array_merge($p, array_map(fn($v) => $v ?? '', $row));
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($p) as $k) $p[$k] = trim($_POST[$k] ?? '');
    if ($p['documento'] === '') $errores[] = 'El documento es obligatorio.';
    if ($p['nombre'] === '')    $errores[] = 'El nombre es obligatorio.';
    if ($p['apellido'] === '')  $errores[] = 'El apellido es obligatorio.';
    if ($p['email'] !== '' && !filter_var($p['email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
    if ($p['fecha_nacimiento'] !== '' && !fecha_ok($p['fecha_nacimiento'])) $errores[] = 'La fecha de nacimiento no es válida.';
    if (!$errores) {
        $st = db()->prepare('SELECT id FROM pacientes WHERE documento = ? AND id <> ?');
        $st->execute([$p['documento'], $id]);
        if ($st->fetch()) $errores[] = 'Ya existe un paciente con ese documento.';
    }
    if (!$errores) {
        $vals = [$p['documento'], $p['nombre'], $p['apellido'], $p['telefono'] ?: null, $p['email'] ?: null, $p['fecha_nacimiento'] ?: null];
        if ($id) {
            db()->prepare('UPDATE pacientes SET documento=?, nombre=?, apellido=?, telefono=?, email=?, fecha_nacimiento=? WHERE id=?')->execute([...$vals, $id]);
            flash('Paciente actualizado.');
            redirigir('pacientes.php');
        }
        db()->prepare('INSERT INTO pacientes (documento, nombre, apellido, telefono, email, fecha_nacimiento) VALUES (?,?,?,?,?,?)')->execute($vals);
        $nuevo = (int)db()->lastInsertId();
        flash('Paciente registrado.');
        // Viene del flujo "Agendar cita": continúa en el paso de elegir médico y horario
        if (($_GET['volver'] ?? '') === 'cita') redirigir('cita_nueva.php?paciente_id=' . $nuevo);
        redirigir('pacientes.php');
    }
}
function fecha_ok(string $f): bool { $d = DateTime::createFromFormat('Y-m-d', $f); return $d && $d->format('Y-m-d') === $f; }

$titulo = $id ? 'Editar paciente' : 'Registrar paciente';
require __DIR__ . '/../inc/header.php';
?>
<?php if ($errores): ?><div class="aviso error"><?php foreach ($errores as $e) echo h($e) . '<br>'; ?></div><?php endif; ?>
<form method="post" class="tarjeta" action="paciente_form.php<?= isset($_GET['volver']) ? '?volver=' . h($_GET['volver']) : '' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <label>Documento *</label><input name="documento" value="<?= h($p['documento']) ?>">
  <div class="fila">
    <div><label>Nombre *</label><input name="nombre" value="<?= h($p['nombre']) ?>"></div>
    <div><label>Apellido *</label><input name="apellido" value="<?= h($p['apellido']) ?>"></div>
  </div>
  <div class="fila">
    <div><label>Teléfono</label><input name="telefono" value="<?= h($p['telefono']) ?>"></div>
    <div><label>Correo</label><input name="email" value="<?= h($p['email']) ?>"></div>
    <div><label>Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" value="<?= h($p['fecha_nacimiento']) ?>"></div>
  </div>
  <p><button>Guardar</button> <a class="btn sec" href="pacientes.php">Cancelar</a></p>
</form>
<?php require __DIR__ . '/../inc/footer.php'; ?>
