<?php
// CU-12: Generar reporte de citas
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/citas.php';
requiere_rol(['administrador']);

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
if (!fecha_valida($desde)) $desde = date('Y-m-01');
if (!fecha_valida($hasta)) $hasta = date('Y-m-d');

$st = db()->prepare('SELECT estado, COUNT(*) AS n FROM citas WHERE fecha BETWEEN ? AND ? GROUP BY estado');
$st->execute([$desde, $hasta]);
$porEstado = array_column($st->fetchAll(), 'n', 'estado');

$st = db()->prepare("SELECT m.nombre, COUNT(*) AS total,
        SUM(c.estado='atendida') AS atendidas, SUM(c.estado='cancelada') AS canceladas, SUM(c.estado='no_asistio') AS no_asistio
    FROM citas c JOIN usuarios m ON m.id = c.medico_id
    WHERE c.fecha BETWEEN ? AND ? GROUP BY m.id, m.nombre ORDER BY total DESC");
$st->execute([$desde, $hasta]);
$porMedico = $st->fetchAll();

$st = db()->prepare("SELECT c.fecha, c.hora, c.estado, CONCAT(p.nombre,' ',p.apellido) AS paciente, m.nombre AS medico
    FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN usuarios m ON m.id=c.medico_id
    WHERE c.fecha BETWEEN ? AND ? ORDER BY c.fecha, c.hora LIMIT 500");
$st->execute([$desde, $hasta]);
$detalle = $st->fetchAll();

$titulo = 'Reporte de citas';
require __DIR__ . '/../inc/header.php';
?>
<form method="get" class="fila tarjeta">
  <div><label>Desde</label><input type="date" name="desde" value="<?= h($desde) ?>"></div>
  <div><label>Hasta</label><input type="date" name="hasta" value="<?= h($hasta) ?>"></div>
  <div><button>Generar</button> <button type="button" class="sec" onclick="window.print()">Imprimir</button></div>
</form>
<div class="tarjeta">
  <strong>Total:</strong> <?= array_sum($porEstado) ?> ·
  Programadas: <?= (int)($porEstado['programada'] ?? 0) ?> ·
  Atendidas: <?= (int)($porEstado['atendida'] ?? 0) ?> ·
  Canceladas: <?= (int)($porEstado['cancelada'] ?? 0) ?> ·
  No asistió: <?= (int)($porEstado['no_asistio'] ?? 0) ?>
</div>
<h2>Por médico</h2>
<table><tr><th>Médico</th><th>Total</th><th>Atendidas</th><th>Canceladas</th><th>No asistió</th></tr>
<?php foreach ($porMedico as $r): ?>
  <tr><td><?= h($r['nombre']) ?></td><td><?= (int)$r['total'] ?></td><td><?= (int)$r['atendidas'] ?></td><td><?= (int)$r['canceladas'] ?></td><td><?= (int)$r['no_asistio'] ?></td></tr>
<?php endforeach; ?>
<?php if (!$porMedico): ?><tr><td colspan="5">Sin datos en el rango.</td></tr><?php endif; ?>
</table>
<h2>Detalle</h2>
<table><tr><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Médico</th><th>Estado</th></tr>
<?php foreach ($detalle as $r): ?>
  <tr><td><?= h($r['fecha']) ?></td><td><?= h(substr($r['hora'], 0, 5)) ?></td><td><?= h($r['paciente']) ?></td><td><?= h($r['medico']) ?></td>
      <td><span class="estado <?= h($r['estado']) ?>"><?= h(str_replace('_', ' ', $r['estado'])) ?></span></td></tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../inc/footer.php'; ?>
