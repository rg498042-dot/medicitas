<?php
// CU-08: Registrar atención
require __DIR__ . '/../inc/bootstrap.php';
requiere_rol(['medico']);

$u = $_SESSION['u'];
$id = (int)($_GET['cita_id'] ?? $_POST['cita_id'] ?? 0);
$st = db()->prepare("SELECT c.*, p.documento, p.nombre, p.apellido, p.telefono, p.fecha_nacimiento
    FROM citas c JOIN pacientes p ON p.id = c.paciente_id
    WHERE c.id = ? AND c.medico_id = ? AND c.estado = 'programada'");
$st->execute([$id, $u['id']]);
$c = $st->fetch();
if (!$c) { flash('No se encontró una cita programada suya con ese código.', 'error'); redirigir('citas.php'); }

$d = ['motivo' => '', 'diagnostico' => '', 'tratamiento' => '', 'observaciones' => ''];
$faltan = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1a. El paciente no asistió
    if (($_POST['accion'] ?? '') === 'no_asistio') {
        db()->prepare("UPDATE citas SET estado='no_asistio' WHERE id=?")->execute([$id]);
        flash('Cita marcada como "no asistió".');
        redirigir('citas.php?fecha=' . urlencode($c['fecha']));
    }
    foreach ($d as $k => $_) $d[$k] = trim($_POST[$k] ?? '');
    // 6a. Campos obligatorios
    foreach (['motivo' => 'motivo de consulta', 'diagnostico' => 'diagnóstico', 'tratamiento' => 'tratamiento'] as $k => $n) {
        if ($d[$k] === '') $faltan[] = $n;
    }
    if (!$faltan) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO atenciones (cita_id, paciente_id, medico_id, motivo, diagnostico, tratamiento, observaciones) VALUES (?,?,?,?,?,?,?)')
            ->execute([$id, $c['paciente_id'], $u['id'], $d['motivo'], $d['diagnostico'], $d['tratamiento'], $d['observaciones'] ?: null]);
        $pdo->prepare("UPDATE citas SET estado='atendida' WHERE id=?")->execute([$id]);
        $pdo->commit();
        flash('Atención registrada en el historial del paciente.');
        redirigir('citas.php?fecha=' . urlencode($c['fecha']));
    }
}

$titulo = 'Registrar atención';
require __DIR__ . '/../inc/header.php';
?>
<div class="tarjeta">
  <strong><?= h($c['nombre'] . ' ' . $c['apellido']) ?></strong> · Doc. <?= h($c['documento']) ?> · Tel. <?= h($c['telefono']) ?><br>
  Cita: <?= h($c['fecha']) ?> a las <?= h(substr($c['hora'], 0, 5)) ?>
  · <a href="historial.php?paciente_id=<?= (int)$c['paciente_id'] ?>" target="_blank">Ver historial</a>
</div>
<?php if ($faltan): ?><div class="aviso error">Faltan campos obligatorios: <?= h(implode(', ', $faltan)) ?>.</div><?php endif; ?>
<form method="post" class="tarjeta" id="f-atencion">
  <?= csrf_field() ?><input type="hidden" name="cita_id" value="<?= $id ?>">
  <label>Motivo de consulta *</label><textarea name="motivo" rows="2" required><?= h($d['motivo']) ?></textarea>
  <label>Diagnóstico *</label><textarea name="diagnostico" rows="3" required><?= h($d['diagnostico']) ?></textarea>
  <label>Tratamiento *</label><textarea name="tratamiento" rows="3" required><?= h($d['tratamiento']) ?></textarea>
  <label>Observaciones</label><textarea name="observaciones" rows="2"><?= h($d['observaciones']) ?></textarea>
  <p>
    <button>Guardar atención</button>
    <button class="peligro" name="accion" value="no_asistio" formnovalidate onclick="return confirm('¿Marcar como no asistió?')">El paciente no asistió</button>
    <a class="btn sec" href="citas.php">Volver</a>
  </p>
</form>
<script>
// 5a. Pide confirmación antes de descartar datos sin guardar
let sucio = false;
const f = document.getElementById('f-atencion');
f.addEventListener('input', () => sucio = true);
f.addEventListener('submit', () => sucio = false);
window.addEventListener('beforeunload', e => { if (sucio) { e.preventDefault(); e.returnValue = ''; } });
</script>
<?php require __DIR__ . '/../inc/footer.php'; ?>
