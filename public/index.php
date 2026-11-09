<?php
require __DIR__ . '/../inc/bootstrap.php';
requiere_rol();
$titulo = 'Bienvenido, ' . $_SESSION['u']['nombre'];
require __DIR__ . '/../inc/header.php';
?>
<div class="menu">
  <?php foreach (menu_por_rol($_SESSION['u']['rol']) as [$url, $txt]): ?>
    <a href="<?= h($url) ?>"><?= h($txt) ?></a>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
