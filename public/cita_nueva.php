<?php
// CU-04: Agendar cita
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/citas.php';
requiere_rol(['recepcionista', 'administrador']);

$q           = trim($_GET['q'] ?? '');
$paciente_id = (int)($_GET['paciente_id'] ?? $_POST['paciente_id'] ?? 0);
$medico_id   = (int)($_GET['medico_id'] ?? $_POST['medico_id'] ?? 0);
$fecha       = $_GET['fecha'] ?? $_POST['fecha'] ?? '';

$paciente = null;
if ($paciente_id) {
    $st = db()->prepare('SELECT * FROM pacientes WHERE id = ?');
    $st->execute([$paciente_id]);
    $paciente = $st->fetch();
}

// Confirmación (pasos 6 y 7)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $paciente) {
    $hora = $_POST['hora'] ?? '';
    $volver = "cita_nueva.php?paciente_id=$paciente_id&medico_id=$medico_id&fecha=" . urlencode($fecha);

    // 5b. El paciente ya tiene cita con ese médico a esa hora
    $st = db()->prepare("SELECT COUNT(*) FROM citas WHERE paciente_id=? AND medico_id=? AND fecha=? AND hora=? AND estado='programada'");
    $st->execute([$paciente_id, $medico_id, $fecha, $hora]);
    if ($st->fetchColumn() > 0) {
        flash('El paciente ya tiene una cita con ese médico a esa hora.', 'error');
        redirigir($volver);
    }
    // 5a. Horario ocupado o fuera del horario del médico
    if (!in_array($hora, slots_disponibles($medico_id, $fecha), true)) {
        flash('Ese horario ya no está disponible. Elija otro.', 'error');
        redirigir($volver);
    }
    try {
        db()->prepare('INSERT INTO citas (paciente_id, medico_id, fecha, hora, creada_por) VALUES (?,?,?,?,?)')
            ->execute([$paciente_id, $medico_id, $fecha, $hora, $_SESSION['u']['id']]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {      // el índice único detectó una cita simultánea
            flash('Ese horario acaba de ser ocupado. Elija otro.', 'error');
            redirigir($volver);
        }
        throw $e;
    }
    flash('Cita programada para ' . $paciente['nombre'] . ' ' . $paciente['apellido'] . ' el ' . $fecha . ' a las ' . $hora . '.');
    redirigir('citas.php?fecha=' . urlencode($fecha));
}

$resultados = [];
if (!$paciente && $q !== '') {
    $like = "%$q%";
    $st = db()->prepare("SELECT * FROM pacientes WHERE documento LIKE ? OR CONCAT(nombre,' ',apellido) LIKE ? ORDER BY apellido LIMIT 30");
    $st->execute([$like, $like]);
    $resultados = $st->fetchAll();
}
$medicos = medicos_activos();
$slots = ($paciente && $medico_id && fecha_valida($fecha)) ? slots_disponibles($medico_id, $fecha) : [];

$titulo = 'Agendar cita';
require __DIR__ . '/../inc/header.php';
?>
<?php if (!$paciente): ?>
  <form method="get" class="fila tarjeta">
    <div><label>1. Buscar paciente (documento o nombre)</label><input name="q" value="<?= h($q) ?>" autofocus></div>
    <div><button>Buscar</button></div>
  </form>
  <?php if ($q !== '' && !$resultados): ?>
    <div class="tarjeta">Paciente no registrado. <a class="btn chico" href="paciente_form.php?volver=cita">Registrar paciente</a></div>
  <?php endif; ?>
  <?php if ($resultados): ?>
    <table>
      <tr><th>Documento</th><th>Nombre</th><th></th></tr>
      <?php foreach ($resultados as $r): ?>
        <tr><td><?= h($r['documento']) ?></td><td><?= h($r['nombre'] . ' ' . $r['apellido']) ?></td>
            <td><a class="btn chico" href="cita_nueva.php?paciente_id=<?= (int)$r['id'] ?>">Seleccionar</a></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php else: ?>
  <div class="tarjeta">
    <strong>Paciente:</strong> <?= h($paciente['nombre'] . ' ' . $paciente['apellido']) ?> · Doc. <?= h($paciente['documento']) ?>
    · Tel. <?= h($paciente['telefono']) ?> <a href="cita_nueva.php">(cambiar)</a>
  </div>

  <form method="get" class="fila tarjeta">
    <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">
    <div><label>2. Médico</label>
      <select name="medico_id" required>
        <option value="">Seleccione…</option>
        <?php foreach ($medicos as $m): ?>
          <option value="<?= (int)$m['id'] ?>" <?= $m['id'] == $medico_id ? 'selected' : '' ?>><?= h($m['nombre'] . ' — ' . $m['especialidad']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div><label>Fecha</label><input type="date" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= h($fecha) ?>" required></div>
    <div><button>Ver horarios</button></div>
  </form>

  <?php if ($medico_id && fecha_valida($fecha)): ?>
    <form method="post" class="tarjeta">
      <?= csrf_field() ?>
      <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">
      <input type="hidden" name="medico_id" value="<?= $medico_id ?>">
      <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
      <strong>3. Horarios disponibles el <?= h($fecha) ?></strong>
      <?php if ($slots): ?>
        <div class="slots">
          <?php foreach ($slots as $i => $s): ?>
            <label><input type="radio" name="hora" value="<?= h($s) ?>" <?= $i === 0 ? 'required' : '' ?>><?= h($s) ?></label>
          <?php endforeach; ?>
        </div>
        <button>Confirmar cita</button> <a class="btn sec" href="citas.php">Cancelar</a>
      <?php else: ?>
        <p>No hay horarios disponibles para ese médico en esa fecha. Pruebe con otra fecha.</p>
      <?php endif; ?>
    </form>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../inc/footer.php'; ?>
