<?php $u = $_SESSION['u'] ?? null; $aviso = flash(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($titulo ?? 'MediCitas') ?> · MediCitas</title>
  <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<header class="barra">
  <a class="logo" href="index.php">MediCitas</a>
  <?php if ($u): ?>
    <nav>
      <?php foreach (menu_por_rol($u['rol']) as [$url, $txt]): ?>
        <a href="<?= h($url) ?>"><?= h($txt) ?></a>
      <?php endforeach; ?>
    </nav>
    <span class="sesion"><?= h($u['nombre']) ?> (<?= h($u['rol']) ?>) · <a href="logout.php">Salir</a></span>
  <?php endif; ?>
</header>
<main>
  <h1><?= h($titulo ?? '') ?></h1>
  <?php if ($aviso): ?><div class="aviso <?= h($aviso[1]) ?>"><?= h($aviso[0]) ?></div><?php endif; ?>
