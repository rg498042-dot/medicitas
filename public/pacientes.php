<?php
// CU-03: Consultar paciente
require __DIR__ . '/../inc/bootstrap.php';
requiere_rol(['recepcionista', 'administrador']);

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = "%$q%";
    $st = db()->prepare("SELECT * FROM pacientes WHERE documento LIKE ? OR CONCAT(nombre, ' ', apellido) LIKE ? ORDER BY apellido, nombre LIMIT 100");
    $st->execute([$like, $like]);
} else {
    $st = db()->query('SELECT * FROM pacientes ORDER BY id DESC LIMIT 50');
}
$pacientes = $st->fetchAll();

$titulo = 'Pacientes';
require __DIR__ . '/../inc/header.php';
?>
<form method="get" class="fila tarjeta">
  <div><label>Buscar por documento o nombre</label><input name="q" value="<?= h($q) ?>"></div>
  <div><button>Buscar</button> <a class="btn" href="paciente_form.php">+ Registrar paciente</a></div>
</form>
<table>
  <tr><th>Documento</th><th>Nombre</th><th>Teléfono</th><th>Correo</th><th></th></tr>
  <?php foreach ($pacientes as $p): ?>
    <tr>
      <td><?= h($p['documento']) ?></td>
      <td><?= h($p['nombre'] . ' ' . $p['apellido']) ?></td>
      <td><?= h($p['telefono']) ?></td>
      <td><?= h($p['email']) ?></td>
      <td>
        <a class="btn chico sec" href="paciente_form.php?id=<?= (int)$p['id'] ?>">Editar</a>
        <a class="btn chico" href="cita_nueva.php?paciente_id=<?= (int)$p['id'] ?>">Agendar cita</a>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$pacientes): ?><tr><td colspan="5">No se encontraron pacientes.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../inc/footer.php'; ?>
