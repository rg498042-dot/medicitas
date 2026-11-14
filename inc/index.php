<?php
require __DIR__ . '/../inc/bootstrap.php';
requiere_rol();

$u = $_SESSION['u'];
$hoy = date('Y-m-d');
$cuenta = function (string $sql, array $p = []): int {
    $st = db()->prepare($sql);
    $st->execute($p);
    return (int)$st->fetchColumn();
};

// Indicadores rápidos según el rol
if ($u['rol'] === 'medico') {
    $stats = [
        ['reloj', 'Citas pendientes hoy', $cuenta("SELECT COUNT(*) FROM citas WHERE medico_id=? AND fecha=? AND estado='programada'", [$u['id'], $hoy])],
        ['check', 'Atendidas hoy',        $cuenta("SELECT COUNT(*) FROM citas WHERE medico_id=? AND fecha=? AND estado='atendida'", [$u['id'], $hoy])],
    ];
} else {
    $stats = [
        ['agenda',    'Citas programadas hoy', $cuenta("SELECT COUNT(*) FROM citas WHERE fecha=? AND estado='programada'", [$hoy])],
        ['pacientes', 'Pacientes registrados', $cuenta('SELECT COUNT(*) FROM pacientes')],
        ['medicos',   'Médicos activos',       $cuenta("SELECT COUNT(*) FROM usuarios WHERE rol='medico' AND activo=1")],
    ];
}

$banner = file_exists(__DIR__ . '/img/inicio-banner.jpg') ? 'img/inicio-banner.jpg' : 'img/ilustracion-citas.svg';
$titulo = 'Bienvenido, ' . $u['nombre'];
require __DIR__ . '/../inc/header.php';
?>
<section class="hero">
  <div>
    <p class="hero-fecha"><?= h(date('d/m/Y')) ?></p>
    <p>Gestione citas, pacientes e historial clínico desde un solo lugar. Elija una opción para comenzar.</p>
  </div>
  <img src="<?= h($banner) ?>" alt="" class="<?= $banner === 'img/inicio-banner.jpg' ? 'foto' : '' ?>" width="200">
</section>

<div class="stats">
  <?php foreach ($stats as [$ic, $txt, $n]): ?>
    <div class="stat"><?= icono($ic, 30) ?><div><strong><?= $n ?></strong><span><?= h($txt) ?></span></div></div>
  <?php endforeach; ?>
</div>

<div class="menu">
  <?php foreach (menu_por_rol($u['rol']) as [$url, $txt, $ic, $desc]): ?>
    <a href="<?= h($url) ?>"><?= icono($ic, 34) ?><strong><?= h($txt) ?></strong><small><?= h($desc) ?></small></a>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
