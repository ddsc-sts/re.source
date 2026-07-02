<?php
$flashMessages = pull_flashes();
if (!$flashMessages) {
    return;
}

$flashIcons = [
    'success' => 'check-circle',
    'error' => 'circle-alert',
    'warning' => 'triangle-alert',
    'info' => 'info',
];
?>
<div class="flash-container" aria-live="polite" aria-atomic="true">
  <?php foreach ($flashMessages as $flashMessage): ?>
    <?php
    $flashType = in_array($flashMessage['type'] ?? '', ['success', 'error', 'warning', 'info'], true)
        ? $flashMessage['type']
        : 'info';
    ?>
    <div class="flash-message flash-<?= $flashType ?>" role="alert">
      <i data-lucide="<?= $flashIcons[$flashType] ?>" aria-hidden="true"></i>
      <span><?= htmlspecialchars((string) ($flashMessage['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
      <button type="button" class="flash-close" aria-label="Fechar mensagem">&times;</button>
    </div>
  <?php endforeach; ?>
</div>
<script>
document.addEventListener('click', function (event) {
  const closeButton = event.target.closest('.flash-close');
  if (closeButton) closeButton.closest('.flash-message')?.remove();
});
</script>
