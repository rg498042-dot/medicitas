<?php
// CU-11: Gestionar médicos y horarios (los médicos se crean en "Usuarios")
require __DIR__ . '/../inc/bootstrap.php';
requiere_rol(['administrador']);

$dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
$medicos = db()->query("SELECT id, nombre, especialidad, activo FROM usuarios WHERE rol='medico' ORDER BY nombre")->fetchAll();
$mid = (int)($_GET['medico_id'] ?? $_POST['medico_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mid) {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'agregar') {
        $dia = (int)($_POST['dia'] ?? 0); $ini = $_POST['inicio'] ?? ''; $fin = $_POST['fin'] ?? '';
        $fmt = '/^\d{2}:\d{2}$/';
        if (!isset($dias[$dia]) || !preg_match($fmt, $ini) || !preg_match($fmt, $fin) || $ini >= $fin) {
            flash('Revise el día y que la hora de inicio sea menor que la de fin.', 'error');
        } else {
            $st = db()->prepare('SELECT COUNT(*) FROM horarios WHERE medico_id=? AND dia_semana=? AND hora_inicio < ? AND hora_fin > ?');
            $st->execute([$mid, $dia, $fin, $ini]);
            if ($st->fetchColumn() > 0) {
                flash('Ese horario se cruza con otro ya registrado.', 'error');
            } else {
                db()->prepare('INSERT INTO horarios (medico_id, dia_semana, hora_inicio, hora_fin) VALUES (?,?,?,?)')->execute([$mid, $dia, $ini, $fin]);
                flash('Horario agregado.');
            }
        }
    } elseif ($accion === 'eliminar') {
        db()->prepare('DELETE FROM horarios WHERE id=? AND medico_id=?')->execute([(int)$_POST['id'], $mid]);
        flash('Horario eliminado.');
    }
    redirigir('medicos.php?medico_id=' . $mid);
}

$horarios = [];
if ($mid) {
    $st = db()->prepare('SELECT * FROM horarios WHERE medico_id=? ORDER BY dia_semana, hora_inicio');
    $st->execute([$mid]);
    $horarios = $st->fetchAll();
}

$titulo = 'Médicos y horarios';
require __DIR__ . '/../inc/header.php';
?>
<form method="get" class="fila tarjeta">
  <div><label>Médico</label>
    <select name="medico_id" onchange="this.form.submit()">
      <option value="0">Seleccione…</option>
      <?php foreach ($medicos as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= $m['id'] == $mid ? 'selected' : '' ?>><?= h($m['nombre'] . ' — ' . $m['especialidad'] . ($m['activo'] ? '' : ' (inactivo)')) ?></option>
      <?php endforeach; ?>
    </select></div>
</form>
<?php if (!$medicos): ?><div class="tarjeta">Aún no hay médicos. Créelos en <a href="usuarios.php">Usuarios</a> con el rol médico.</div><?php endif; ?>
<?php if ($mid): ?>
  <form method="post" class="fila tarjeta">
    <?= csrf_field() ?><input type="hidden" name="medico_id" value="<?= $mid ?>"><input type="hidden" name="accion" value="agregar">
    <div><label>Día</label><select name="dia"><?php foreach ($dias as $n => $t): ?><option value="<?= $n ?>"><?= $t ?></option><?php endforeach; ?></select></div>
    <div><label>Desde</label><input type="time" name="inicio" required></div>
    <div><label>Hasta</label><input type="time" name="fin" required></div>
    <div><button>Agregar horario</button></div>
  </form>
  <table><tr><th>Día</th><th>Horario</th><th></th></tr>
  <?php foreach ($horarios as $hr): ?>
    <tr><td><?= h($dias[$hr['dia_semana']]) ?></td><td><?= h(substr($hr['hora_inicio'], 0, 5) . ' – ' . substr($hr['hora_fin'], 0, 5)) ?></td>
      <td><form method="post" onsubmit="return confirm('¿Eliminar este horario?')">
        <?= csrf_field() ?><input type="hidden" name="medico_id" value="<?= $mid ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$hr['id'] ?>">
        <button class="chico peligro">Eliminar</button></form></td></tr>
  <?php endforeach; ?>
  <?php if (!$horarios): ?><tr><td colspan="3">Este médico aún no tiene horarios.</td></tr><?php endif; ?>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/../inc/footer.php'; ?>
