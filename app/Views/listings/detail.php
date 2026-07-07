<?php
$titulo_pagina = $anuncio['title'] . " — Re.Source";
$unitLabel = ['kg'=>'Kg','ton'=>'Ton','m2'=>'m²','m3'=>'m³','unidade'=>'un.','litro'=>'L','outro'=>''];

require_once __DIR__ . '/../components/header.php';
?>

<style>
/* =========================================
   ESTILOS GERAIS
   ========================================= */
body {
    background-color: var(--bg);
}

.anuncio-main {
    max-width: 1280px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.anuncio-topo-titulo {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 1.5rem;
    font-family: 'Sora', sans-serif;
}

.anuncio-header-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    align-items: start;
    margin-bottom: 2rem;
}

/* =========================================
   MÁGICA DO MOSAICO OLX (CORRIGIDO ESPAÇOS BRANCOS)
   ========================================= */
.olx-grid {
    display: grid;
    gap: 8px;
    background-color: var(--white);
    padding: 8px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    width: 100%;
    height: 480px; 
}

.olx-item {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    cursor: pointer;
    background: var(--bg);
    border-radius: 4px;
}

.olx-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease, filter 0.2s;
    display: block;
}

.olx-item:hover img {
    transform: scale(1.02);
    filter: brightness(0.9);
}

/* 1 IMAGEM */
.olx-grid.count-1 { grid-template-columns: 1fr; grid-template-rows: 1fr; }

/* 2 IMAGENS */
.olx-grid.count-2 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr; }

/* 3 IMAGENS */
.olx-grid.count-3 { grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; }
.olx-grid.count-3 .olx-item:nth-child(1) { grid-row: 1 / 3; } /* Imagem principal puxa 2 linhas */

/* 4 IMAGENS */
.olx-grid.count-4 { grid-template-columns: 2fr 1fr 1fr; grid-template-rows: 1fr 1fr; }
.olx-grid.count-4 .olx-item:nth-child(1) { grid-row: 1 / 3; } 
.olx-grid.count-4 .olx-item:nth-child(2) { grid-column: 2 / 4; } /* A 2ª foto fica mais larga para preencher o vazio */

/* 5 OU MAIS IMAGENS (Padrão OLX perfeito) */
.olx-grid.count-default {
    grid-template-columns: 2fr 1fr 1fr;
    grid-template-rows: 1fr 1fr;
}
.olx-grid.count-default .olx-item:nth-child(1) {
    grid-row: 1 / 3; /* Imagem principal na esquerda */
}

/* MÁSCARA PARA MAIS FOTOS */
.olx-overlay-more {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    font-family: 'Sora', sans-serif;
    pointer-events: none;
}

/* =========================================
   LIGHTBOX (ZOOM) CORRIGIDO
   ========================================= */
.custom-lightbox {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.95);
    z-index: 999999; /* Z-index altíssimo para ficar sobre headers e modais */
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.custom-lightbox.active {
    display: flex;
    opacity: 1;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.lightbox-img {
    max-width: 100%;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    transform: scale(0.95);
    transition: transform 0.3s ease;
}

.custom-lightbox.active .lightbox-img {
    transform: scale(1);
}

.lightbox-close {
    position: absolute;
    top: -40px; right: -40px;
    background: transparent;
    border: none;
    color: white;
    font-size: 3rem;
    cursor: pointer;
    line-height: 1;
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.1);
    border: none;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    border-radius: 50%;
    width: 60px; height: 60px;
}
.lightbox-nav:hover { background: rgba(255,255,255,0.25); }
.lightbox-prev { left: -80px; }
.lightbox-next { right: -80px; }

/* =========================================
   CARD DE COMPRA & BLOCOS DE INFORMAÇÃO
   ========================================= */
.card-compra {
    background: var(--white);
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
}

.preco-enorme { font-size: 2.5rem; font-weight: 700; color: var(--dark); margin-bottom: 0.25rem; }
.txt-parcelas { font-size: 0.9rem; color: var(--muted); margin-bottom: 2rem; display: block; }
.btn-acao { display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 1rem; border-radius: 2rem; font-size: 1rem; font-weight: 600; cursor: pointer; border: none; }
.btn-chat { background-color: #157347; color: white; transition: filter 0.2s; }
.btn-chat:hover { filter: brightness(0.9); }
.disclaimer-contato { font-size: 0.75rem; color: var(--muted); text-align: center; line-height: 1.4; margin-top: 1rem; }

.bloco-info { background: var(--white); padding: 2rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border-color); margin-bottom: 1.5rem; }
.bloco-titulo { font-size: 1.25rem; font-weight: 700; color: var(--dark); margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
.grid-detalhes { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
.detalhe-item { display: flex; align-items: flex-start; gap: 1rem; }
.detalhe-icone { color: var(--muted); padding-top: 0.2rem; }
.detalhe-textos { display: flex; flex-direction: column; }
.detalhe-label { font-size: 0.85rem; color: var(--muted); margin-bottom: 0.2rem; }
.detalhe-valor { font-size: 1rem; color: var(--dark); font-weight: 600; }
.texto-descricao { font-size: 1rem; line-height: 1.7; color: var(--dark); white-space: pre-line; }

.localizacao-box { display: flex; align-items: center; gap: 1rem; }
.icone-cidade { width: 48px; height: 48px; background: var(--white); color: #157347; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); overflow: hidden; }
.icone-cidade img { width: 100%; height: 100%; object-fit: cover; }

/* Carrossel de mais anúncios */
.carrosel-topo { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; margin-top: 3rem; }
.carrosel-titulo { font-size: 1.25rem; font-weight: 700; color: var(--dark); }
.link-ver-todos { color: #157347; font-size: 0.9rem; font-weight: 600; text-decoration: none; }
.grid-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; }
.mini-card { background: var(--white); border: 1px solid var(--border-color); border-radius: 0.5rem; overflow: hidden; text-decoration: none; color: inherit; transition: box-shadow 0.2s; position: relative; }
.mini-card:hover { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
.mini-card img { width: 100%; height: 160px; object-fit: cover; }
.mini-card-info { padding: 1rem; }
.mini-card-titulo { font-size: 0.95rem; font-weight: 600; color: var(--dark); margin-bottom: 0.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-card-preco { font-size: 1.1rem; font-weight: 700; color: #157347; }
.mini-card-local { font-size: 0.75rem; color: var(--muted); margin-top: 0.5rem; }
.badge-card { position: absolute; color: white; font-size: 0.7rem; font-weight: bold; padding: 0.2rem 0.5rem; border-radius: 0.2rem; margin: 0.5rem; text-transform: uppercase; }

body {
    background-color: #fbf9f8;
    background-image:
        linear-gradient(to right, rgba(0,0,0,.035) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(0,0,0,.035) 1px, transparent 1px);
    background-size: 32px 32px;
}
.anuncio-main {
    max-width: 1320px;
    margin: 3rem auto 6rem;
}
.anuncio-topo-titulo {
    font-family: 'Bebas Neue', var(--font-main, sans-serif);
    font-size: clamp(2.5rem, 6vw, 4.2rem);
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: .02em;
    border-left: 4px solid #005131;
    padding-left: 1rem;
}
.anuncio-header-grid {
    grid-template-columns: minmax(0, 2fr) minmax(320px, .9fr);
}
.olx-grid,
.card-compra,
.bloco-info,
.mini-card {
    border-radius: 0;
    border: 1px solid #1b1c1c;
    box-shadow: none;
}
.olx-grid {
    padding: 0;
    gap: 0;
    background: #fff;
}
.olx-item {
    border-radius: 0;
    border-right: 1px solid #bec9bf;
    border-bottom: 1px solid #bec9bf;
}
.olx-item img {
    filter: grayscale(.45) contrast(1.08);
}
.olx-item:hover img {
    filter: grayscale(0) contrast(1);
}
.card-compra {
    position: sticky;
    top: 8.5rem;
    background: #fff;
    color: #1b1c1c;
    border-color: #b7c4bc;
    border-top: 4px solid #005131;
    box-shadow: 8px 8px 0 #dbe3de;
}
.preco-enorme {
    font-family: 'Bebas Neue', var(--font-main, sans-serif);
    font-size: clamp(2.6rem, 5vw, 4rem);
    font-weight: 400;
    color: #005131;
}
.txt-parcelas,
.disclaimer-contato {
    color: #65736b;
}
.btn-acao {
    border-radius: 0;
    min-height: 56px;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-size: .82rem;
}
.btn-chat {
    background: #005131;
}
.btn-chat:hover {
    filter: none;
    background: #0d6b44;
    color: #fff;
}
.bloco-info {
    background: rgba(255,255,255,.86);
    padding: 2rem;
}
.bloco-titulo,
.carrosel-titulo {
    font-family: 'Bebas Neue', var(--font-main, sans-serif);
    font-size: 2rem;
    font-weight: 400;
    text-transform: uppercase;
    border-bottom: 0;
    margin-bottom: 1rem;
}
.bloco-titulo {
    border-left: 4px solid #005131;
    padding: 0 0 0 .75rem;
}
.grid-detalhes {
    border: 1px solid #bec9bf;
    gap: 0;
}
.detalhe-item {
    padding: 1rem;
    border-right: 1px solid #bec9bf;
    border-bottom: 1px solid #bec9bf;
}
.detalhe-label {
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
}
.texto-descricao {
    line-height: 1.8;
}
.icone-cidade {
    border-radius: 0;
    color: #005131;
}
.carrosel-topo {
    border-bottom: 2px solid #1b1c1c;
    padding-bottom: 1rem;
}
.link-ver-todos {
    color: #005131;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.mini-card:hover {
    box-shadow: 5px 5px 0 #005131;
}
.mini-card img {
    filter: grayscale(.45) contrast(1.08);
    border-bottom: 1px solid #bec9bf;
}
.mini-card:hover img {
    filter: grayscale(0);
}
.badge-card {
    border-radius: 0;
    background: #005131 !important;
}

@media (max-width: 900px) {
    .anuncio-header-grid { grid-template-columns: 1fr; }
    .olx-grid { height: 350px; }
    .lightbox-close { top: -45px; right: 0; }
    .lightbox-nav { width: 40px; height: 40px; font-size: 1.2rem; }
    .lightbox-prev { left: 10px; }
    .lightbox-next { right: 10px; }
}
</style>

<main class="anuncio-main">

    <h1 class="anuncio-topo-titulo"><?= htmlspecialchars($anuncio['title']) ?></h1>

    <div class="anuncio-header-grid">
        
        <?php 
            $numImages = count($imagens);
            $maxVisible = 5; // OLX exibe 5 imagens estourando
            $gridClass = 'count-default';
            if ($numImages == 1) $gridClass = 'count-1';
            elseif ($numImages == 2) $gridClass = 'count-2';
            elseif ($numImages == 3) $gridClass = 'count-3';
            elseif ($numImages == 4) $gridClass = 'count-4';
        ?>
        <div class="olx-grid <?= $gridClass ?>">
            <?php 
            foreach($imagens as $index => $img): 
                if ($index >= $maxVisible) break; 
            ?>
                <div class="olx-item" onclick="abrirLightbox(<?= $index ?>)">
                    <img src="<?= htmlspecialchars($img) ?>" alt="Imagem do anúncio">
                    
                    <?php if ($index === ($maxVisible - 1) && $numImages > $maxVisible): ?>
                        <div class="olx-overlay-more">+<?= $numImages - $maxVisible + 1 ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card-compra">
            <?php if ($anuncio['type'] === 'offer' && $anuncio['price'] !== null): ?>
                <div class="preco-enorme">
                    <?= ($anuncio['price'] > 0) ? 'R$ ' . number_format($anuncio['price'], 2, ',', '.') : 'Doação'; ?>
                </div>
                <span class="txt-parcelas">
                    Preço por <?= $unitLabel[$anuncio['unit']] ?? $anuncio['unit'] ?>
                    <?= $anuncio['is_negotiable'] ? ' • Valor Negociável' : '' ?>
                </span>
            <?php else: ?>
                <div class="preco-enorme" style="color: #d97706;">Busca</div>
                <span class="txt-parcelas">A empresa está procurando este material.</span>
            <?php endif; ?>

            <?php
            $viewerCompanyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
            $isOwnListing = $viewerCompanyId > 0 && $viewerCompanyId === (int) $anuncio['company_id'];
            $listingPaused = ($anuncio['status'] ?? '') === 'paused';
            ?>
            <?php if ($listingPaused): ?>
                <p role="status" style="margin:10px 0 0;color:#b45309;font-size:.84rem;line-height:1.5;">
                    Este anúncio foi pausado pela administração. Para mais informações, entre em contato com o suporte da plataforma.
                </p>
            <?php elseif ($isOwnListing): ?>
                <button class="btn-acao btn-chat" type="button" disabled title="Este anúncio pertence à sua empresa">
                    <i data-lucide="message-circle"></i> Anúncio da sua empresa
                </button>
                <p role="status" style="margin:10px 0 0;color:#9a6700;font-size:.82rem;line-height:1.45;">
                    Você não pode iniciar uma conversa sobre o próprio anúncio. Use “Meus Anúncios” para editar ou acompanhar esta publicação.
                </p>
            <?php elseif ($viewerCompanyId > 0): ?>
                <form method="POST" action="<?= htmlspecialchars(app_url('/conversas/iniciar'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="listing_id" value="<?= (int) $anuncio['id'] ?>">
                    <button class="btn-acao btn-chat" type="submit">
                        <i data-lucide="message-circle"></i> Entrar em Contato
                    </button>
                </form>
            <?php else: ?>
                <a class="btn-acao btn-chat" href="<?= htmlspecialchars(app_url('/login'), ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none;">
                    <i data-lucide="log-in"></i> Entre para conversar
                </a>
            <?php endif; ?>
            
            <p class="disclaimer-contato">Ao clicar em entrar em contato, conectaremos você diretamente com <strong><?= htmlspecialchars($anuncio['company_name']) ?></strong>.</p>
        </div>

    </div>

    <div class="bloco-info">
        <h2 class="bloco-titulo">Detalhes</h2>
        <div class="grid-detalhes">
            
            <div class="detalhe-item">
                <div class="detalhe-icone"><i data-lucide="tag"></i></div>
                <div class="detalhe-textos">
                    <span class="detalhe-label">Categoria</span>
                    <span class="detalhe-valor"><?= htmlspecialchars($anuncio['category_name']) ?></span>
                </div>
            </div>

            <div class="detalhe-item">
                <div class="detalhe-icone"><i data-lucide="box"></i></div>
                <div class="detalhe-textos">
                    <span class="detalhe-label">Quantidade Disponível</span>
                    <span class="detalhe-valor">
                        <?php
                            $qty = $anuncio['quantity'] == floor($anuncio['quantity'])
                                 ? number_format($anuncio['quantity'], 0, ',', '.')
                                 : number_format($anuncio['quantity'], 3, ',', '.');
                            echo $qty . ' ' . ($unitLabel[$anuncio['unit']] ?? $anuncio['unit']);
                        ?>
                    </span>
                </div>
            </div>

            <div class="detalhe-item">
                <div class="detalhe-icone"><i data-lucide="arrow-right-left"></i></div>
                <div class="detalhe-textos">
                    <span class="detalhe-label">Tipo de Anúncio</span>
                    <span class="detalhe-valor"><?= $anuncio['type'] === 'offer' ? 'Venda de Material' : 'Procura de Material' ?></span>
                </div>
            </div>

            <div class="detalhe-item">
                <div class="detalhe-icone"><i data-lucide="calendar"></i></div>
                <div class="detalhe-textos">
                    <span class="detalhe-label">Publicado em</span>
                    <span class="detalhe-valor"><?= date('d/m/Y', strtotime($anuncio['created_at'])) ?></span>
                </div>
            </div>

        </div>
    </div>

    <div class="bloco-info">
        <h2 class="bloco-titulo">Descrição do Resíduo</h2>
        <div class="texto-descricao"><?= nl2br(htmlspecialchars($anuncio['description'])) ?></div>
    </div>

<div class="bloco-info">
    <h2 class="bloco-titulo">Localização</h2>
    <div class="localizacao-box">
        <div class="icone-cidade">
            <i data-lucide="building-2"></i>
        </div>
        <div>
            <div style="font-weight: 600; color: var(--dark); font-size: 1.1rem;"><?= htmlspecialchars($anuncio['company_name'] ?? 'Empresa Sem Nome') ?></div>
            
            <div style="color: var(--muted); font-size: 0.9rem;">
                <?= htmlspecialchars($anuncio['location_city'] ?? 'Cidade não informada') ?>, 
                <?= htmlspecialchars($anuncio['location_state'] ?? 'SC') ?>
            </div>
        </div>
    </div>
</div>

    <?php if (!empty($sellerAds)): ?>
    <div class="carrosel-topo">
        <h2 class="carrosel-titulo">Mais anúncios desse vendedor</h2>
        <a href="/re.source/busca?empresa=<?= $anuncio['company_id'] ?>" class="link-ver-todos">Ver todos os anúncios <i data-lucide="chevron-right" style="width: 16px; height: 16px; vertical-align: middle;"></i></a>
    </div>
    <div class="grid-cards">
        <?php foreach($sellerAds as $ad): ?>
            <a href="/re.source/anuncio?id=<?= $ad['id'] ?>" class="mini-card">
                <span class="badge-card" style="background:<?= $ad['type'] === 'offer' ? '#16a34a' : '#f97316' ?>;">
                    <?= $ad['type'] === 'offer' ? 'Venda' : 'Procura' ?>
                </span>
                <img src="<?= htmlspecialchars($ad['thumb']) ?>" alt="Capa">
                <div class="mini-card-info">
                    <div class="mini-card-titulo"><?= htmlspecialchars($ad['title']) ?></div>
                    <div class="mini-card-preco">
                        <?= ($ad['price'] > 0) ? 'R$ ' . number_format($ad['price'], 2, ',', '.') : 'Consulte' ?>
                    </div>
                    <div class="mini-card-local">Hoje • <?= htmlspecialchars($ad['location_city'] ?? 'Local não informado') ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<div class="custom-lightbox" id="lightbox">
    <div class="lightbox-content">
        <button type="button" class="lightbox-close" id="lightboxClose" onclick="fecharLightbox()">&times;</button>
        <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev" onclick="fotoAnterior()">&#10094;</button>
        <img src="" alt="Imagem Ampliada" class="lightbox-img" id="lightboxImg">
        <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext" onclick="fotoProxima()">&#10095;</button>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    const loadedImagesArray = <?php echo json_encode($imagens, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    let indiceAtual = 0;

    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    const btnPrev = document.getElementById('lightboxPrev');
    const btnNext = document.getElementById('lightboxNext');

    if (loadedImagesArray.length <= 1) {
        btnPrev.style.display = 'none';
        btnNext.style.display = 'none';
    }

    window.abrirLightbox = function(index) {
        indiceAtual = index;
        lightboxImg.src = loadedImagesArray[indiceAtual];
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden'; 
    };

    window.fecharLightbox = function() {
        lightbox.classList.remove('active');
        document.body.style.overflow = ''; 
    };

    window.fotoProxima = function() {
        indiceAtual = (indiceAtual + 1) % loadedImagesArray.length;
        lightboxImg.src = loadedImagesArray[indiceAtual];
    };

    window.fotoAnterior = function() {
        indiceAtual = (indiceAtual - 1 + loadedImagesArray.length) % loadedImagesArray.length;
        lightboxImg.src = loadedImagesArray[indiceAtual];
    };

    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox || e.target.classList.contains('lightbox-content')) {
            fecharLightbox();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') fecharLightbox();
        if (loadedImagesArray.length > 1) {
            if (e.key === 'ArrowRight') fotoProxima();
            if (e.key === 'ArrowLeft') fotoAnterior();
        }
    });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
