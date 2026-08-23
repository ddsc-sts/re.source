<?php
$titulo_pagina = "Buscar Resíduos — Re.Source";
$hideSearchBar = true;
require_once __DIR__ . '/../components/header.php';
?>

<style>
/* =========================================
   ESTILOS DA PÁGINA DE BUSCA
   ========================================= */
.search-page {
    max-width: 1400px; /* Aumentei de 1200px para 1400px para empurrar o menu pra esquerda */
    margin: 2rem auto;
    padding: 0 2rem;
    display: flex;
    gap: 2.5rem; /* Dei um espacinho um pouco maior entre os filtros e os cards */
    align-items: flex-start;
}

/* --- BARRA LATERAL (FILTROS) --- */
.filters-sidebar {
    width: 300px;
    flex-shrink: 0;
    background: var(--white, #ffffff);
    padding: 1.5rem;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    position: sticky;
    top: 2rem;
}

.filters-sidebar h3 {
    font-family: var(--font-main, sans-serif);
    font-size: 1.25rem;
    color: #111827;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group {
    margin-bottom: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.filter-group input[type="text"],
.filter-group select {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background-color: #f9fafb;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.2s;
}

.filter-group input[type="text"]:focus,
.filter-group select:focus {
    border-color: var(--green, #157347);
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(21, 115, 71, 0.1);
}

.radio-group-vertical {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 0.25rem;
}

.radio-group-vertical label {
    font-weight: 500;
    font-size: 0.9rem;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.btn-filter {
    width: 100%;
    padding: 0.75rem;
    margin-top: 1rem;
    background: var(--green, #157347);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-filter:hover { background: var(--green-d, #0f5132); }
.btn-clear {
    width: 100%;
    padding: 0.75rem;
    margin-top: 0.5rem;
    background: transparent;
    color: #6b7280;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}
.btn-clear:hover { background: #f3f4f6; color: #111827; }

/* --- RESULTADOS --- */
.results-area {
    flex: 1;
}

.results-header {
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.results-header h2 {
    font-size: 1.5rem;
    color: #111827;
}

.results-count {
    color: #6b7280;
    font-size: 0.95rem;
}

.cards-grid {
    display: grid;
    /* Reduzi um pouquinho o tamanho mínimo para garantir que caibam 3 cards com folga */
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
    gap: 1.5rem;
}

.ad-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}

.ad-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.ad-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    background: #f3f4f6;
}

.ad-image-placeholder {
    width: 100%;
    height: 180px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.ad-content {
    padding: 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.ad-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ad-meta {
    font-size: 0.85rem;
    color: #6b7280;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    margin-bottom: 1rem;
}

.badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    width: fit-content;
    margin-bottom: 0.5rem;
}
.badge-offer { background: #e0f2fe; color: #0284c7; }
.badge-demand { background: #fef3c7; color: #d97706; }

.ad-footer {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ad-price {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--green, #157347);
}

.btn-view {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    color: #111827;
    text-decoration: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    transition: background 0.2s;
}

.btn-view:hover {
    background: #e5e7eb;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border: 1px dashed #d1d5db;
    border-radius: 1rem;
    color: #6b7280;
}

body {
    background-color: #fbf9f8;
    background-image:
        linear-gradient(to right, rgba(0,0,0,.035) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(0,0,0,.035) 1px, transparent 1px);
    background-size: 32px 32px;
}
.search-page {
    max-width: 1320px;
    margin: 3rem auto 5rem;
    gap: 1.5rem;
}
.filters-sidebar,
.ad-card,
.empty-state {
    border-radius: 0;
    border: 1px solid #bec9bf;
    box-shadow: none;
}
.filters-sidebar {
    top: 8.5rem;
    padding: 1.75rem;
}
.filters-sidebar h3,
.results-header h2 {
    font-family: 'Bebas Neue', var(--font-main, sans-serif);
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: .02em;
}
.filters-sidebar h3 {
    font-size: 2rem;
    color: #1b1c1c;
}
.filter-group label {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #5f5e5e;
}
.filter-group input[type="text"],
.filter-group select {
    border-radius: 0;
    min-height: 48px;
    background: #fff;
    border-color: #bec9bf;
}
.filter-group input[type="text"]:focus,
.filter-group select:focus {
    border-color: #005131;
    box-shadow: none;
    outline: 2px solid rgba(0,81,49,.12);
}
.radio-group-vertical input { accent-color: #005131; }
.btn-filter,
.btn-clear,
.btn-view {
    border-radius: 0;
    min-height: 48px;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-size: .74rem;
}
.btn-filter { background: #005131; }
.btn-filter:hover { background: #106b44; }
.btn-clear {
    color: #1b1c1c;
    background: #fff;
    border-color: #bec9bf;
}
.results-header {
    align-items: flex-end;
    padding-bottom: 1.25rem;
    border-bottom: 2px solid #1b1c1c;
}
.results-header h2 {
    font-size: clamp(2rem, 4vw, 3rem);
}
.results-count {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
}
.cards-grid {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}
.ad-card:hover {
    transform: none;
    box-shadow: 6px 6px 0 #005131;
    border-color: #1b1c1c;
}
.ad-image,
.ad-image-placeholder {
    height: 260px;
    border-bottom: 1px solid #bec9bf;
}
.ad-image {
    filter: grayscale(.35) contrast(1.05);
    transition: transform .45s ease, filter .45s ease;
}
.ad-card:hover .ad-image {
    transform: scale(1.025);
    filter: grayscale(0);
}
.badge {
    border-radius: 0;
    padding: .35rem .75rem;
    letter-spacing: .08em;
}
.badge-offer { background: #005131; color: #fff; }
.badge-demand { background: #1b1c1c; color: #fff; }
.ad-title {
    font-family: 'Work Sans', var(--font-main, sans-serif);
    font-size: 1.18rem;
    text-transform: uppercase;
}
.ad-price {
    color: #005131;
    font-weight: 800;
}
.btn-view {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #1b1c1c;
    color: #1b1c1c;
}
.btn-view:hover {
    background: #005131;
    color: #fff;
}

@media (max-width: 768px) {
    .search-page { flex-direction: column; }
    .filters-sidebar { width: 100%; position: static; }
}
</style>

<main class="search-page">
    
    <aside class="filters-sidebar">
        <h3><i data-lucide="search"></i> Encontre o material certo</h3>
        
        <form action="/re.source/busca" method="GET">
            <?php if (!empty($company_id)): ?>
                <input type="hidden" name="empresa" value="<?= (int) $company_id ?>">
            <?php endif; ?>
            <div class="filter-group">
                <label for="q">O que você procura?</label>
                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ex: Paletes, Plástico...">
            </div>

            <div class="filter-group">
                <label for="type">Tipo de anúncio</label>
                <select id="type" name="type">
                    <option value="" <?php echo empty($type) ? 'selected' : ''; ?>>Todos</option>
                    <option value="offer" <?php echo ($type === 'offer') ? 'selected' : ''; ?>>Materiais disponíveis</option>
                    <option value="demand" <?php echo ($type === 'demand') ? 'selected' : ''; ?>>Empresas procurando</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="category_id">Categoria</label>
                <select id="category_id" name="category_id">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="location_state">Estado</label>
                <select id="location_state" name="location_state" data-selected="<?php echo htmlspecialchars($state); ?>">
                    <option value="">Todos os Estados</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="location_city">Cidade</label>
                <select id="location_city" name="location_city" disabled data-selected="<?php echo htmlspecialchars($city); ?>">
                    <option value="">Selecione o Estado primeiro</option>
                </select>
            </div>

            <button type="submit" class="btn-filter"><i data-lucide="search"></i> Buscar</button>
            <a href="/re.source/busca" class="btn-clear">Limpar</a>
        </form>
    </aside>

    <section class="results-area">
        <div class="results-header">
            <div><span class="ui-eyebrow">Marketplace circular</span><h1>Materiais disponíveis</h1></div>
            <span class="results-count"><?php echo count($anuncios); ?> oportunidade(s)</span>
        </div>

        <?php if (empty($anuncios)): ?>
            <div class="empty-state">
                <i data-lucide="search-x" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;"></i>
                <h3>Nenhum resultado encontrado</h3>
                <p>Tente ajustar os filtros ou buscar por palavras diferentes.</p>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($anuncios as $ad): ?>
                    <div class="ad-card">
                        <?php if (!empty($ad['main_image'])): ?>
                            <img src="<?php echo htmlspecialchars($ad['main_image']); ?>" alt="Imagem" class="ad-image">
                        <?php else: ?>
                            <div class="ad-image-placeholder">
                                <i data-lucide="image" style="width: 32px; height: 32px;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="ad-content">
                            <?php if ($ad['type'] === 'offer'): ?>
                                <span class="badge badge-offer">Oferta</span>
                            <?php else: ?>
                                <span class="badge badge-demand">Demanda</span>
                            <?php endif; ?>
                            
                            <h3 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h3>
                            
                            <div class="ad-meta">
                                <span><i data-lucide="tag" style="width:14px; height:14px; vertical-align:middle;"></i> <?php echo htmlspecialchars($ad['category_name']); ?></span>
                                <span><i data-lucide="box" style="width:14px; height:14px; vertical-align:middle;"></i> <?php echo floatval($ad['quantity']) . ' ' . $ad['unit']; ?></span>
                                <span><i data-lucide="map-pin" style="width:14px; height:14px; vertical-align:middle;"></i> <?php echo htmlspecialchars($ad['location_city'] . ' - ' . $ad['location_state']); ?></span>
                            </div>

                            <div class="ad-footer">
                                <?php if ($ad['type'] === 'offer'): ?>
                                    <span class="ad-price">
                                        <?php echo ($ad['price'] > 0) ? 'R$ ' . number_format($ad['price'], 2, ',', '.') : 'Doação'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="ad-price" style="color: #d97706;">Busca</span>
                                <?php endif; ?>

                                <a href="/re.source/anuncio?id=<?php echo $ad['id']; ?>" class="btn-view">Ver Detalhes</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<script>
// ==========================================
// INTEGRAÇÃO COM API DO IBGE
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const ufSelect = document.getElementById('location_state');
    const citySelect = document.getElementById('location_city');
    const selectedUf = ufSelect.getAttribute('data-selected');
    const selectedCity = citySelect.getAttribute('data-selected');

    // 1. Carregar Estados
    fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome')
        .then(response => response.json())
        .then(states => {
            ufSelect.innerHTML = '<option value="">Todos os Estados</option>';
            states.forEach(state => {
                const option = document.createElement('option');
                option.value = state.sigla;
                option.textContent = state.nome;
                if(state.sigla === selectedUf) option.selected = true;
                ufSelect.appendChild(option);
            });
            
            // Se já tem um estado preenchido via GET, carrega as cidades
            if(selectedUf) loadCities(selectedUf, selectedCity);
        });

    // 2. Evento ao trocar o estado manualmente
    ufSelect.addEventListener('change', function() {
        if(this.value) {
            loadCities(this.value, '');
        } else {
            citySelect.innerHTML = '<option value="">Selecione o Estado primeiro</option>';
            citySelect.disabled = true;
        }
    });

    // 3. Função para carregar cidades
    function loadCities(uf, cityToSelect) {
        citySelect.innerHTML = '<option value="">Carregando Cidades...</option>';
        citySelect.disabled = true;

        fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${uf}/municipios`)
            .then(response => response.json())
            .then(cities => {
                citySelect.innerHTML = '<option value="">Todas as Cidades</option>';
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.nome;
                    option.textContent = city.nome;
                    if(city.nome === cityToSelect) option.selected = true;
                    citySelect.appendChild(option);
                });
                citySelect.disabled = false;
            });
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
