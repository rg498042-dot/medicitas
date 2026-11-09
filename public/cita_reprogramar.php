<?php
// CU-05: Reprogramar cita
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/citas.php';
requiere_rol(['recepcionista', 'administrador']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = db()->prepare("SELECT c.*, CONCAT(p.nombre,' ',p.apellido) AS paciente, m.nombre AS medico
    FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN usuarios m ON m.id=c.medico_id
    WHERE c.id=? AND c.estado='programada'");
$st->execute([$id]);
$c = $st->fetch();
if (!$c) { flash('La cita no existe o ya no está programada.', 'error'); redirigir('citas.php'); }

$fecha = $_GET['fecha'] ?? $_POST['fecha'] ?? $c['fecha'];
if (!fecha_valida($fecha)) $fecha = $c['fecha'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hora'])) {
    $hora = $_POST['hora'];
    if (!in_array($hora, slots_disponibles((int)$c['medico_id'], $fecha), true)) {
        flash('Ese horario no está disponible. Elija otro.', 'error');
        redirigir("cita_reprogramar.php?id=$id&fecha=" . urlencode($fecha));
    }
    try {
        db()->prepare("UPDATE citas SET fecha=?, hora=? WHERE id=? AND estado='programada'")->execute([$fecha, $hora, $id]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') { flash('Ese horario acaba de ser ocupado.', 'error'); redirigir("cita_reprogramar.php?id=$id&fecha=" . urlencode($fecha)); }
        throw $e;
    }
    flash("Cita reprogramada para el $fecha a las $hora.");
    redirigir('citas.php?fecha=' . urlencode($fecha));
}

$slots = slots_disponibles((int)$c['medico_id'], $fecha);
$titulo = 'Reprogramar cita';
require __DIR__ . '/../inc/header.php';
?>
<div class="tarjeta">
  <strong><?= h($c['paciente']) ?></strong> con <?= h($c['medico']) ?><br>
  Cita actual: <?= h($c['fecha']) ?> a las <?= h(substr($c['hora'], 0, 5)) ?>
</div>
<form method="get" class="fila tarjeta">
  <input type="hidden" name="id" value="<?= $id ?>">
  <div><label>Nueva fecha</label><input type="date" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= h($fecha) ?>"></div>
  <div><button>Ver horarios</button></div>
</form>
<form method="post" class="tarjeta">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="fecha" value="<?= h($fecha) ?>">
  <strong>Horarios disponibles el <?= h($fecha) ?></strong>
  <?php if ($slots): ?>
    <div class="slots"><?php foreach ($slots as $i => $s): ?>
      <label><input type="radio" name="hora" value="<?= h($s) ?>" <?= $i === 0 ? 'required' : '' ?>><?= h($s) ?></label>
    <?php endforeach; ?></div>
    <button>Confirmar cambio</button> <a class="btn sec" href="citas.php">Volver</a>
  <?php else: ?>
    <p>No hay horarios disponibles en esa fecha.</p><a class="btn sec" href="citas.php">Volver</a>
  <?php endif; ?>
</form>
<?php require __DIR__ . '/../inc/footer.php'; ?>
