</main>
<footer class="pie">
  <strong>MediCitas</strong> · Sistema de gestión de citas médicas<br>
  <?= h($config['institucion'] ?? '') ?> · <?= h($config['curso'] ?? '') ?><br>
  <?php if (!empty($config['integrantes'])): ?>Integrantes: <?= h($config['integrantes']) ?><?php endif; ?>
  <?php if (!empty($config['docente'])): ?> · Docente: <?= h($config['docente']) ?><?php endif; ?>
</footer>
</body>
</html>
