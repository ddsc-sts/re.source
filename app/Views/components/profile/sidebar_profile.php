<?php
$profileCompanyName = $sidebarCompanyName ?? 'Minha empresa';
$profileLogoUrl = $sidebarLogoUrl ?? null;
?>
<section class="company-profile" aria-label="Perfil da empresa">
  <div class="company-profile-avatar">
    <?php if ($profileLogoUrl): ?>
      <img src="<?= htmlspecialchars($profileLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo da empresa">
    <?php else: ?>
      <i data-lucide="building-2"></i>
    <?php endif; ?>
  </div>
  <h3><?= htmlspecialchars($profileCompanyName, ENT_QUOTES, 'UTF-8') ?></h3>
  <p><i data-lucide="badge-check"></i> Conta B2B verificada</p>
</section>
