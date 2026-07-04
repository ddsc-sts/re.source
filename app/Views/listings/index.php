<?php
$titulo_pagina = $titulo_pagina ?? "Meus Anúncios — Re.Source";
require_once __DIR__ . '/../components/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/dashboard-sidebar.css'), ENT_QUOTES, 'UTF-8') ?>">

<style>
.dashboard-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    max-width: 1200px;
    margin: 3rem auto;
    padding: 0 1.5rem;
    align-items: start;
}

.dashboard-layout {
    max-width: 1280px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
    align-items: start;
}
.dashboard-container { width: 100%; }

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}

.dashboard-header h2 {
    font-family: var(--font-main);
    color: var(--dark);
    font-size: 1.75rem;
    font-weight: 700;
}

.btn-new {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--white);
    background: var(--green);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s;
}
.btn-new:hover { background: var(--green-d); color: var(--white); }

.listing-card {
    display: flex;
    align-items: center;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    gap: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}
.listing-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-card); }

.listing-img { width: 100px; height: 100px; border-radius: 0.5rem; object-fit: cover; background: var(--bg); border: 1px solid var(--border-color); }
.listing-img-placeholder { width: 100px; height: 100px; border-radius: 0.5rem; background: var(--bg); display: flex; align-items: center; justify-content: center; color: var(--muted); border: 1px solid var(--border-color); }

.listing-info { flex: 1; }
.listing-title { font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 0.25rem; }
.listing-meta { font-size: 0.85rem; color: var(--muted); display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.5rem; }

.badge { padding: 0.25rem 0.6rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
.badge-offer { background: #e0f2fe; color: #0284c7; }
.badge-demand { background: #fef3c7; color: #d97706; }

.listing-price { font-weight: 700; color: var(--green); font-size: 1.1rem; }
.listing-actions { display: flex; gap: 0.5rem; }
.btn-action { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--white); color: var(--dark); cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-action:hover { background: var(--bg); }
.btn-delete:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }

.empty-state { text-align: center; padding: 4rem 2rem; background: var(--white); border: 1px dashed var(--border-color); border-radius: var(--radius); color: var(--muted); }
.alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.9rem; }
.alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
.alert-danger { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

@media (max-width: 992px) {
    .dashboard-layout { grid-template-columns: 1fr; }
    .dashboard-sidebar { position: relative; top: 0; height: auto; }
}
@media (max-width: 768px) {
    .listing-card { flex-direction: column; align-items: flex-start; }
    .listing-img, .listing-img-placeholder { width: 100%; height: 180px; }
    .listing-actions { width: 100%; justify-content: flex-end; margin-top: 1rem; }
}
</style>

<main class="dashboard-layout">
    
    <?php $sidebarActive = 'listings'; require __DIR__ . '/../components/dashboard_sidebar.php'; ?>

    <div class="dashboard-container">
        
        <div class="dashboard-header">
            <h2>Meus Anúncios</h2>
            <a href="/re.source/anuncios/novo" class="btn-new">
                <i data-lucide="plus"></i> Novo Anúncio
            </a>
        </div>

        <?php echo $mensagem; ?>

        <div class="listings-list">
            <?php if (empty($anuncios)): ?>
                <div class="empty-state">
                    <i data-lucide="inbox" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>Nenhum anúncio encontrado</h3>
                    <p>Você ainda não possui nenhum anúncio cadastrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($anuncios as $ad): ?>
                    <div class="listing-card" onclick="window.location.href='/re.source/anuncio?id=<?= $ad['id']; ?>';" style="cursor: pointer;" title="Ver detalhes do anúncio">
                        
                        <?php if (!empty($ad['main_image'])): ?>
                            <img src="<?= htmlspecialchars($ad['main_image']); ?>" alt="Imagem" class="listing-img">
                        <?php else: ?>
                            <div class="listing-img-placeholder">
                                <i data-lucide="image" style="width: 32px; height: 32px;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="listing-info">
                            <div class="listing-title"><?= htmlspecialchars($ad['title']); ?></div>
                            <div class="listing-meta">
                                <span><i data-lucide="tag" style="width:14px; height:14px; vertical-align:middle;"></i> <?= htmlspecialchars($ad['category_name']); ?></span>
                                <span><i data-lucide="box" style="width:14px; height:14px; vertical-align:middle;"></i> <?= floatval($ad['quantity']) . ' ' . $ad['unit']; ?></span>
                                <span><i data-lucide="map-pin" style="width:14px; height:14px; vertical-align:middle;"></i> <?= htmlspecialchars($ad['location_city'] . '/' . $ad['location_state']); ?></span>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                                <?php if ($ad['type'] === 'offer'): ?>
                                    <span class="badge badge-offer">Oferta</span>
                                    <span class="listing-price">
                                        <?= ($ad['price'] > 0) ? 'R$ ' . number_format($ad['price'], 2, ',', '.') : 'Doação'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-demand">Demanda</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="listing-actions">
                            <a href="/re.source/anuncios/editar?id=<?= $ad['id']; ?>" class="btn-action" title="Editar" onclick="event.stopPropagation();">
                                <i data-lucide="pencil" style="width: 18px; height: 18px;"></i>
                            </a>
                            <form action="/re.source/anuncios/excluir" method="POST" style="display:inline;" onclick="event.stopPropagation();" onsubmit="return confirm('⚠️ Deseja realmente apagar este anúncio?\nEsta ação não pode ser desfeita.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" title="Excluir" style="cursor:pointer;">
                                    <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
