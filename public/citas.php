<?php
// CU-06: Consultar agenda (recepción/admin) y CU-07: Ver mi agenda (médico)
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/citas.php';
requiere_rol(['recepcionista', 'administrador', 'medico']);

$u = $_SESSION['u'];
$esMedico = $u['rol'] === 'medico';
$fecha = $_GET['fecha'] ?? date('Y-m-d');
if (!fecha_valida($fecha)) $fecha = date('Y-m-d');
$medico_id = $esMedico ? $u['id'] : (int)($_GET['medico_id'] ?? 0);

// CU-05 (parte): cancelar cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$esMedico && ($_POST['accion'] ?? '') === 'cancelar') {
    $st = db()->prepare("UPDATE citas SET estado='cancelada' WHERE id=? AND estado='programada'");
    $st->execute([(int)$_POST['id']]);
    flash($st->rowCount() ? 'Cita cancelada. El horario quedó libre.' : 'La cita no se pudo cancelar.', $st->rowCount() ? 'ok' : 'error');
    redirigir('citas.php?fecha=' . urlencode($fecha) . '&medico_id=' . $medico_id);
}

$sql = "SELECT c.*, CONCAT(p.nombre,' ',p.apellido) AS paciente, p.documento, m.nombre AS medico
        FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN usuarios m ON m.id=c.medico_id
        WHERE c.fecha = ?";
$params = [$fecha];
if ($medico_id) { $sql .= ' AND c.medico_id = ?'; $params[] = $medico_id; }
$st = db()->prepare($sql . ' ORDER BY c.hora');
$st->execute($params);
$citas = $st->fetchAll();

$titulo = $esMedico ? 'Mi agenda' : 'Agenda de citas';
require __DIR__ . '/../inc/header.php';
?>
<form method="get" class="fila tarjeta">
  <div><label>Fecha</label><input type="date" name="fecha" value="<?= h($fecha) ?>"></div>
  <?php if (!$esMedico): ?>
    <div><label>Médico</label>
      <select name="medico_id"><option value="0">Todos</option>
        <?php foreach (medicos_activos() as $m): ?>
          <option value="<?= (int)$m['id'] ?>" <?= $m['id'] == $medico_id ? 'selected' : '' ?>><?= h($m['nombre']) ?></option>
        <?php endforeach; ?>
      </select></div>
  <?php endif; ?>
  <div><button>Consultar</button></div>
</form>
<table>
  <tr><th>Hora</th><th>Paciente</th><th>Documento</th><?php if (!$esMedico): ?><th>Médico</th><?php endif; ?><th>Estado</th><th></th></tr>
  <?php foreach ($citas as $c): ?>
    <tr>
      <td><?= h(substr($c['hora'], 0, 5)) ?></td>
      <td><?= h($c['paciente']) ?></td>
      <td><?= h($c['documento']) ?></td>
      <?php if (!$esMedico): ?><td><?= h($c['medico']) ?></td><?php endif; ?>
      <td><span class="estado <?= h($c['estado']) ?>"><?= h(str_replace('_', ' ', $c['estado'])) ?></span></td>
      <td>
        <?php if ($c['estado'] === 'programada'): ?>
          <?php if ($esMedico): ?>
            <a class="btn chico" href="atencion.php?cita_id=<?= (int)$c['id'] ?>">Atender</a>
          <?php else: ?>
            <a class="btn chico sec" href="cita_reprogramar.php?id=<?= (int)$c['id'] ?>">Reprogramar</a>
            <form method="post" style="display:inline" onsubmit="return confirm('¿Cancelar esta cita?')">
              <?= csrf_field() ?><input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button class="chico peligro">Cancelar</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$citas): ?><tr><td colspan="6">No hay citas para esta fecha.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../inc/footer.php'; ?>
