<?php
$css_especifico = asset_url('/css/experience.css');
require VIEW_PATH . '/components/header.php';
$publicUrl = $passport
    ? app_url('/passaporte?token=' . urlencode($passport['public_token']))
    : '';
?>
<main class="help-page">
  <header class="help-hero">
    <span>Rastreabilidade circular</span>
    <h1>Passaporte Re.Source</h1>
    <p>Registro público e verificável do material reaproveitado.</p>
  </header>

  <?php if (!$passport): ?>
    <section class="help-flow">
      <h2>Passaporte não encontrado</h2>
      <p>Confira o endereço ou solicite um novo código à empresa responsável.</p>
    </section>
  <?php else: ?>
    <section class="help-flow">
      <h2><?= htmlspecialchars($passport['passport_code'], ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="help-grid">
        <article class="help-card">
          <span>Material</span>
          <h3><?= htmlspecialchars($passport['material_name'], ENT_QUOTES, 'UTF-8') ?></h3>
          <p><?= number_format((float) $passport['quantity_kg'], 3, ',', '.') ?> kg reaproveitados</p>
        </article>
        <article class="help-card">
          <span>Origem</span>
          <h3><?= htmlspecialchars($passport['origin_company'], ENT_QUOTES, 'UTF-8') ?></h3>
          <span>Destino</span>
          <h3><?= htmlspecialchars($passport['destination_company'], ENT_QUOTES, 'UTF-8') ?></h3>
        </article>
        <article class="help-card">
          <div id="passportQr" aria-label="QR Code do passaporte"></div>
          <p>Escaneie para consultar este registro.</p>
        </article>
      </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" crossorigin="anonymous"></script>
    <script>
      (() => {
        const container = document.getElementById('passportQr');
        const publicUrl = <?= json_encode($publicUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        if (typeof window.QRCode === 'function') {
          new window.QRCode(container, { text: publicUrl, width: 180, height: 180 });
          return;
        }

        const fallback = document.createElement('a');
        fallback.href = publicUrl;
        fallback.className = 'ui-btn ui-btn--secondary';
        fallback.textContent = 'Abrir passaporte';
        container.appendChild(fallback);
      })();
    </script>
  <?php endif; ?>
</main>
<?php require VIEW_PATH . '/components/footer.php'; ?>
