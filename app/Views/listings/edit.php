<?php
$titulo_pagina = $titulo_pagina ?? "Editar Anúncio — Re.Source";
$erros = $erros ?? [];
require_once __DIR__ . '/../components/header.php';
?>

<style>
.form-container { max-width: 850px; margin: 3rem auto; padding: 3rem; background: var(--white); border-radius: 1rem; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.form-title-group { margin-bottom: 2.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; }
.form-title-group h2 { font-family: var(--font-main); color: var(--dark); font-size: 1.875rem; font-weight: 800; letter-spacing: -0.025em; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
.full-width { grid-column: span 2; }
.input-box { display: flex; flex-direction: column; gap: 0.4rem; }
.input-box label { font-family: var(--font-main); font-size: 0.875rem; font-weight: 600; color: var(--dark); }
.input-box input[type="text"],
.input-box input[type="number"],
.input-box select,
.input-box textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background-color: var(--bg); color: var(--dark); font-size: 0.95rem; font-family: var(--font-body); transition: all 0.2s; }
.input-box input:focus, .input-box select:focus, .input-box textarea:focus { border-color: var(--green); background-color: var(--white); box-shadow: 0 0 0 4px rgba(21,115,71,0.15); outline: none; }
.input-box input[type="file"] { background-color: var(--white); border: 1px dashed var(--border-color); padding: 1.5rem; cursor: pointer; border-radius: 0.5rem; }
.input-box input[type="file"]:hover { border-color: var(--green); background-color: var(--bg); }
.radio-group { display: flex; gap: 1.5rem; margin-top: 0.25rem; }
.radio-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; color: var(--muted); font-weight: 500; }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; margin-top: 0.5rem; color: var(--muted); font-weight: 500; }
.alert { padding: 1rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.95rem; font-weight: 500; }
.alert-danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.submit-wrap { margin-top: 3rem; display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
.btn-submit { padding: 0.875rem 2.5rem; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; color: #fff; background: var(--green); cursor: pointer; border: none; transition: background 0.2s, transform 0.1s; }
.btn-submit:hover { background: var(--green-d); transform: translateY(-1px); }
.btn-back { color: var(--muted); text-decoration: none; font-size: 0.95rem; font-weight: 600; }
.btn-back:hover { color: var(--dark); }
.images-gallery { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem; }
.img-wrapper { position: relative; width: 100px; height: 100px; border-radius: 0.5rem; overflow: hidden; border: 2px solid var(--border-color); transition: opacity .2s, border-color .2s; }
.img-wrapper img { width: 100%; height: 100%; object-fit: cover; display: block; }
.img-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.55); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .2s; }
.img-wrapper:hover .img-overlay, .img-wrapper.marked-for-deletion .img-overlay { opacity: 1; }
.img-wrapper.marked-for-deletion { border-color: #ef4444; opacity: .65; }
.btn-del-img { color: #fff; background: #ef4444; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 0; cursor: pointer; transition: transform 0.2s, background 0.2s; }
.btn-del-img:hover { transform: scale(1.15); background: #dc2626; color: #fff; }
.delete-hint { margin-top: .5rem; color: var(--muted); font-size: .8rem; }
</style>

<main class="listings-section">
    <div class="form-container">

        <div class="form-title-group">
            <h2>Editar Anúncio</h2>
            <p style="color:#6b7280;font-size:0.95rem;margin-top:6px;">Atualize as informações, fotos ou localização do seu lote de material.</p>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">As alterações foram salvas com sucesso!</div>
        <?php endif; ?>

        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <ul style="margin-left:1rem;">
                    <?php foreach ($erros as $erro): ?>
                        <li><?= htmlspecialchars($erro) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="/re.source/anuncios/editar/processar" method="POST" enctype="multipart/form-data" id="editListingForm">
            <?= csrf_field() ?>
            <input type="hidden" name="listing_id" value="<?= (int) $id ?>">
            <div id="deletedImagesInputs"></div>
            <div class="form-grid">

                <div class="input-box full-width">
                    <label>Finalidade do Anúncio</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="type" value="offer" <?= ($anuncio['type'] === 'offer') ? 'checked' : '' ?> onclick="togglePriceField(true)">
                            Oferta (Possuo o resíduo para venda/doação)
                        </label>
                    </div>
                    <div class="radio-group" style="margin-top:0.5rem;">
                        <label class="radio-label">
                            <input type="radio" name="type" value="demand" <?= ($anuncio['type'] === 'demand') ? 'checked' : '' ?> onclick="togglePriceField(false)">
                            Demanda (Necessito deste material)
                        </label>
                    </div>
                </div>

                <div class="input-box full-width">
                    <label for="title">Título do Anúncio</label>
                    <input type="text" id="title" name="title" minlength="5" value="<?= htmlspecialchars($anuncio['title']) ?>" required>
                </div>

                <div class="input-box">
                    <label for="category_id">Categoria do Material</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($anuncio['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="unit">Unidade de Medida</label>
                    <select id="unit" name="unit" required>
                        <?php foreach (['kg'=>'Quilograma (kg)','ton'=>'Tonelada (ton)','m3'=>'Metro Cúbico (m³)','m2'=>'Metro Quadrado (m²)','litro'=>'Litro (l)','unidade'=>'Unidade','outro'=>'Outro'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($anuncio['unit'] == $val) ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="quantity">Quantidade</label>
                    <input type="number" id="quantity" name="quantity" step="0.001" min="0.001" value="<?= floatval($anuncio['quantity']) ?>" required>
                </div>

                <div class="input-box" id="price-wrapper">
                    <label for="price">Preço Unitário (R$)</label>
                    <input type="text" id="price" name="price" value="<?= ($anuncio['price'] > 0) ? number_format($anuncio['price'], 2, ',', '') : '' ?>" placeholder="Ex: 2,50">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_negotiable" value="1" <?= ($anuncio['is_negotiable'] == 1) ? 'checked' : '' ?>>
                        Valor negociável
                    </label>
                </div>

                <div class="input-box">
                    <label for="location_state">Estado (UF)</label>
                    <select id="location_state" name="location_state" required data-selected="<?= htmlspecialchars($anuncio['location_state'] ?? '') ?>">
                        <option value="">Carregando Estados...</option>
                    </select>
                </div>

                <div class="input-box">
                    <label for="location_city">Cidade</label>
                    <select id="location_city" name="location_city" required disabled data-selected="<?= htmlspecialchars($anuncio['location_city'] ?? '') ?>">
                        <option value="">Selecione um Estado primeiro</option>
                    </select>
                </div>

                <div class="input-box full-width">
                    <label for="description">Descrição Técnica e Condições</label>
                    <textarea id="description" name="description" rows="4" style="resize:vertical;"><?= htmlspecialchars($anuncio['description'] ?? '') ?></textarea>
                </div>

                <?php if (!empty($imagens_atuais)): ?>
                    <div class="input-box full-width">
                        <label>Imagens Atuais do Anúncio</label>
                        <p class="delete-hint">Clique na lixeira para marcar uma foto. Ela só será excluída ao salvar as alterações.</p>
                        <div class="images-gallery">
                            <?php foreach ($imagens_atuais as $img): ?>
                                <div class="img-wrapper" data-image-id="<?= (int) $img['id'] ?>">
                                    <img src="<?= htmlspecialchars($img['url']) ?>" alt="Foto do Resíduo">
                                    <div class="img-overlay">
                                        <button type="button" class="btn-del-img" title="Marcar foto para exclusão" aria-pressed="false">
                                            <i data-lucide="trash-2" style="width:18px;height:18px;"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="input-box full-width" style="margin-top:1rem;">
                    <label for="images">Adicionar Mais Fotos <span style="font-weight:normal;color:#9ca3af;">(JPG, PNG, WebP)</span></label>
                    <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
                </div>

            </div>

            <div class="submit-wrap">
                <a href="/re.source/meus-anuncios" class="btn-back">← Voltar para Meus Anúncios</a>
                <button type="submit" class="btn-submit">Salvar Alterações</button>
            </div>
        </form>

    </div>
</main>

<script>
function togglePriceField(isOffer) {
    const priceInput   = document.getElementById('price');
    const priceWrapper = document.getElementById('price-wrapper');
    priceWrapper.style.opacity = isOffer ? '1' : '0.4';
    priceInput.disabled = !isOffer;
    if (!isOffer) priceInput.value = '';
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const selectedType = document.querySelector('input[name="type"]:checked').value;
    togglePriceField(selectedType === 'offer');

    const ufSelect     = document.getElementById('location_state');
    const citySelect   = document.getElementById('location_city');
    const selectedUf   = ufSelect.getAttribute('data-selected');
    const selectedCity = citySelect.getAttribute('data-selected');
    const deletedImagesInputs = document.getElementById('deletedImagesInputs');
    const editForm = document.getElementById('editListingForm');
    const imageInput = document.getElementById('images');

    editForm.addEventListener('submit', function(event) {
        const remainingImages = document.querySelectorAll(
            '.img-wrapper[data-image-id]:not(.marked-for-deletion)'
        ).length;
        if (remainingImages === 0 && imageInput.files.length === 0) {
            event.preventDefault();
            alert('O anúncio deve permanecer com pelo menos uma imagem.');
        }
    });

    document.querySelectorAll('.img-wrapper[data-image-id]').forEach(wrapper => {
        const button = wrapper.querySelector('.btn-del-img');
        button.addEventListener('click', function() {
            const marked = wrapper.classList.toggle('marked-for-deletion');
            button.setAttribute('aria-pressed', marked ? 'true' : 'false');
            button.title = marked ? 'Cancelar exclusão desta foto' : 'Marcar foto para exclusão';

            const imageId = wrapper.dataset.imageId;
            const existingInput = deletedImagesInputs.querySelector(`input[value="${imageId}"]`);
            if (marked && !existingInput) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_image_ids[]';
                input.value = imageId;
                deletedImagesInputs.appendChild(input);
            } else if (!marked && existingInput) {
                existingInput.remove();
            }
        });
    });

    fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome')
        .then(r => r.json())
        .then(states => {
            ufSelect.innerHTML = '<option value="">Selecione o Estado</option>';
            states.forEach(s => {
                const o = document.createElement('option');
                o.value = s.sigla;
                o.textContent = s.nome;
                if (s.sigla === selectedUf) o.selected = true;
                ufSelect.appendChild(o);
            });
            if (selectedUf) loadCities(selectedUf, selectedCity);
        });

    ufSelect.addEventListener('change', function() {
        if (this.value) {
            loadCities(this.value, '');
        } else {
            citySelect.innerHTML = '<option value="">Selecione um Estado primeiro</option>';
            citySelect.disabled = true;
        }
    });

    function loadCities(uf, cityToSelect) {
        citySelect.innerHTML = '<option value="">Carregando Cidades...</option>';
        citySelect.disabled = true;
        fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${uf}/municipios`)
            .then(r => r.json())
            .then(cities => {
                citySelect.innerHTML = '<option value="">Selecione a Cidade</option>';
                cities.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.nome;
                    o.textContent = c.nome;
                    if (c.nome === cityToSelect) o.selected = true;
                    citySelect.appendChild(o);
                });
                citySelect.disabled = false;
            });
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
