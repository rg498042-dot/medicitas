<?php
// CU-09: Consultar historial del paciente
require __DIR__ . '/../inc/bootstrap.php';
requiere_rol(['medico']);

$q = trim($_GET['q'] ?? '');
$pid = (int)($_GET['paciente_id'] ?? 0);
$detalle = (int)($_GET['detalle'] ?? 0);

$paciente = null; $atenciones = []; $resultados = [];
if ($pid) {
    $st = db()->prepare('SELECT * FROM pacientes WHERE id = ?');
    $st->execute([$pid]);
    $paciente = $st->fetch();
    if ($paciente) {
        $st = db()->prepare("SELECT a.*, m.nombre AS medico, c.fecha, c.hora
            FROM atenciones a JOIN citas c ON c.id=a.cita_id JOIN usuarios m ON m.id=a.medico_id
            WHERE a.paciente_id = ? ORDER BY c.fecha DESC, c.hora DESC");
        $st->execute([$pid]);
        $atenciones = $st->fetchAll();
    }
} elseif ($q !== '') {
    $like = "%$q%";
    $st = db()->prepare("SELECT * FROM pacientes WHERE documento LIKE ? OR CONCAT(nombre,' ',apellido) LIKE ? ORDER BY apellido LIMIT 30");
    $st->execute([$like, $like]);
    $resultados = $st->fetchAll();
}

$titulo = 'Historial de pacientes';
require __DIR__ . '/../inc/header.php';
?>
<?php if (!$paciente): ?>
  <form method="get" class="fila tarjeta">
    <div><label>Buscar paciente (documento o nombre)</label><input name="q" value="<?= h($q) ?>" autofocus></div>
    <div><button>Buscar</button></div>
  </form>
  <?php if ($q !== '' && !$resultados): ?><div class="tarjeta">No hay coincidencias. Intente de nuevo.</div><?php endif; ?>
  <?php if ($resultados): ?>
    <table><tr><th>Documento</th><th>Nombre</th><th></th></tr>
    <?php foreach ($resultados as $r): ?>
      <tr><td><?= h($r['documento']) ?></td><td><?= h($r['nombre'] . ' ' . $r['apellido']) ?></td>
          <td><a class="btn chico" href="historial.php?paciente_id=<?= (int)$r['id'] ?>">Ver historial</a></td></tr>
    <?php endforeach; ?></table>
  <?php endif; ?>
<?php else: ?>
  <div class="tarjeta">
    <strong><?= h($paciente['nombre'] . ' ' . $paciente['apellido']) ?></strong> · Doc. <?= h($paciente['documento']) ?>
    · Nac. <?= h($paciente['fecha_nacimiento']) ?> · <a href="historial.php">Buscar otro</a>
  </div>
  <?php if (!$atenciones): ?>
    <div class="tarjeta">Sin historial registrado.</div>
  <?php else: ?>
    <table><tr><th>Fecha</th><th>Médico</th><th>Diagnóstico</th><th></th></tr>
    <?php foreach ($atenciones as $a): ?>
      <tr><td><?= h($a['fecha']) ?></td><td><?= h($a['medico']) ?></td><td><?= h($a['diagnostico']) ?></td>
          <td><a class="btn chico sec" href="historial.php?paciente_id=<?= $pid ?>&detalle=<?= (int)$a['id'] ?>">Detalle</a></td></tr>
      <?php if ($a['id'] == $detalle): ?>
        <tr><td colspan="4" class="tarjeta">
          <strong>Motivo:</strong> <?= nl2br(h($a['motivo'])) ?><br>
          <strong>Diagnóstico:</strong> <?= nl2br(h($a['diagnostico'])) ?><br>
          <strong>Tratamiento:</strong> <?= nl2br(h($a['tratamiento'])) ?><br>
          <strong>Observaciones:</strong> <?= nl2br(h($a['observaciones'])) ?>
        </td></tr>
      <?php endif; ?>
    <?php endforeach; ?></table>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../inc/footer.php'; ?>
