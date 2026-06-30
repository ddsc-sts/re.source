<?php
// anunciarResiduos.php
// CORREÇÃO: Removido mock $_SESSION['company_id'] = 1, substituído por auth_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php";
require_once __DIR__ . "/BackEnd/config/auth_check.php"; // define $company_id e $user_id, redireciona se não logado

$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type           = filter_input(INPUT_POST, 'type',           FILTER_DEFAULT);
    $title          = trim(filter_input(INPUT_POST, 'title',     FILTER_DEFAULT));
    $category_id    = filter_input(INPUT_POST, 'category_id',   FILTER_VALIDATE_INT);
    $quantity       = filter_input(INPUT_POST, 'quantity',       FILTER_VALIDATE_FLOAT);
    $unit           = filter_input(INPUT_POST, 'unit',           FILTER_DEFAULT);
    $price_raw      = filter_input(INPUT_POST, 'price',          FILTER_DEFAULT);
    $is_negotiable  = isset($_POST['is_negotiable']) ? 1 : 0;
    $location_state = trim(filter_input(INPUT_POST, 'location_state', FILTER_DEFAULT));
    $location_city  = trim(filter_input(INPUT_POST, 'location_city',  FILTER_DEFAULT));
    $description    = trim(filter_input(INPUT_POST, 'description',    FILTER_DEFAULT));

    if (!in_array($type, ['offer', 'demand'])) $erros[] = "Selecione se o anúncio é uma Oferta ou Demanda.";
    if (empty($title) || strlen($title) < 5)   $erros[] = "O título deve ter no mínimo 5 caracteres.";
    if (!$category_id)                          $erros[] = "Selecione uma categoria válida.";
    if ($quantity <= 0)                         $erros[] = "A quantidade deve ser maior que zero.";
    if (empty($location_state))                 $erros[] = "Selecione o Estado.";
    if (empty($location_city))                  $erros[] = "Selecione a Cidade.";

    $price = null;
    if ($type === 'offer') {
        if ($price_raw === '' || $price_raw === null) {
            $price = 0.00;
        } else {
            $price = str_replace(['.', ','], ['', '.'], $price_raw);
            $price = filter_var($price, FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0) $erros[] = "Insira um preço válido ou deixe vazio para doação.";
        }
    }

    if (empty($erros)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO listings 
                    (company_id, created_by_user_id, category_id, type, title, description, quantity, unit, price, is_negotiable, location_state, location_city, status) 
                VALUES 
                    (:company_id, :user_id, :category_id, :type, :title, :description, :quantity, :unit, :price, :is_negotiable, :state, :city, 'active')
            ");
            $stmt->execute([
                ':company_id'    => $company_id,   // ← empresa real do usuário logado
                ':user_id'       => $user_id,       // ← usuário real logado
                ':category_id'   => $category_id,
                ':type'          => $type,
                ':title'         => $title,
                ':description'   => $description ?: null,
                ':quantity'      => $quantity,
                ':unit'          => $unit,
                ':price'         => $price,
                ':is_negotiable' => $is_negotiable,
                ':state'         => strtoupper($location_state),
                ':city'          => $location_city,
            ]);

            $listing_id = $pdo->lastInsertId();

            // Upload de imagens
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $upload_dir = __DIR__ . '/uploads/listings/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $permitidos = ['image/jpeg', 'image/png', 'image/webp'];

                foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
                    if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                        $mime = $_FILES['images']['type'][$index];
                        if (in_array($mime, $permitidos)) {
                            $ext          = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
                            $nome_arquivo = 'listing_' . $listing_id . '_' . uniqid() . '.' . $ext;

                            if (move_uploaded_file($tmp_name, $upload_dir . $nome_arquivo)) {
                                $url_publica = '/RE.SOURCE/uploads/listings/' . $nome_arquivo;
                                $stmtImg = $pdo->prepare("INSERT INTO listing_images (listing_id, url, `order`) VALUES (:listing_id, :url, :order)");
                                $stmtImg->execute([':listing_id' => $listing_id, ':url' => $url_publica, ':order' => $index]);
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            $sucesso = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

// Categorias para o select
try {
    $stmtCat   = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
    $categorias = $stmtCat->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
}

$titulo_pagina = "Anunciar Resíduo — Re.Source";
include 'header.php';
?>

<style>
body { font-family: var(--font-body); background: var(--bg); color: var(--dark); min-height: 100vh; }

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
.input-box textarea {
    width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 0.5rem;
    background-color: var(--bg); color: var(--dark); font-size: 0.95rem; font-family: var(--font-body);
    transition: all 0.2s ease;
}
.input-box input:focus, .input-box select:focus, .input-box textarea:focus {
    border-color: var(--green); background-color: var(--white); box-shadow: 0 0 0 4px rgba(21,115,71,0.15); outline: none;
}
.input-box input[type="file"] { background-color: var(--white); border: 1px dashed var(--border-color); padding: 1.5rem; cursor: pointer; border-radius: 0.5rem; }
.input-box input[type="file"]:hover { border-color: var(--green); background-color: var(--bg); }

.radio-group { display: flex; gap: 1.5rem; margin-top: 0.25rem; }
.radio-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; color: var(--muted); font-weight: 500; }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; margin-top: 0.5rem; color: var(--muted); font-weight: 500; }

.alert { padding: 1rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.95rem; font-weight: 500; }
.alert-danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

.submit-wrap { margin-top: 3rem; display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
.btn-submit { padding: 0.875rem 2.5rem; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; color: #fff; background: var(--green); cursor: pointer; border: none; transition: background 0.2s, transform 0.1s; box-shadow: 0 4px 6px -1px rgba(21,115,71,0.3); }
.btn-submit:hover { background: var(--green-d); transform: translateY(-1px); }
.btn-back { color: var(--muted); text-decoration: none; font-size: 0.95rem; font-weight: 600; transition: color 0.2s; }
.btn-back:hover { color: var(--dark); }

/* Grid estilo OLX */
.olx-preview-container { margin-top: 1rem; width: 100%; display: none; }
.olx-grid { display: grid; gap: 8px; background-color: var(--bg); padding: 8px; border-radius: 8px; overflow: hidden; width: 100%; height: 400px; }
.olx-grid.count-1 { grid-template-columns: 1fr; grid-template-rows: 1fr; }
.olx-grid.count-2 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr; }
.olx-grid.count-3, .olx-grid.count-4, .olx-grid.count-default { grid-template-columns: 1.5fr 1fr; grid-template-rows: repeat(2, 1fr); }
.olx-item { position: relative; width: 100%; height: 100%; overflow: hidden; cursor: pointer; background: var(--bg); border-radius: 4px; }
.olx-grid.count-3 .olx-item:nth-child(1),
.olx-grid.count-4 .olx-item:nth-child(1),
.olx-grid.count-default .olx-item:nth-child(1) { grid-row: span 2; }
.olx-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s, filter 0.2s; display: block; }
.olx-item:hover img { transform: scale(1.02); filter: brightness(0.9); }
.olx-overlay-more { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.55); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; }

/* Botão de excluir imagem no preview */
.btn-remove-preview {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #ef4444;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    z-index: 10;
    transition: transform 0.2s, background 0.2s;
    font-size: 16px;
    line-height: 1;
}
.btn-remove-preview:hover {
    background: #dc2626;
    transform: scale(1.15);
}

/* Lightbox */
.custom-lightbox { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 99999; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; }
.custom-lightbox.active { display: flex; opacity: 1; }
.lightbox-content { position: relative; max-width: 90%; max-height: 85%; }
.lightbox-img { max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 4px; transform: scale(0.95); transition: transform 0.3s; }
.custom-lightbox.active .lightbox-img { transform: scale(1); }
.lightbox-close { position: absolute; top: -45px; right: 0; background: transparent; border: none; color: white; font-size: 2rem; cursor: pointer; }
.lightbox-close:hover { color: #ef4444; }
.lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.1); border: none; color: white; font-size: 1.5rem; padding: 1rem; cursor: pointer; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.lightbox-nav:hover { background: rgba(255,255,255,0.25); }
.lightbox-prev { left: -70px; }
.lightbox-next { right: -70px; }

@media (max-width: 768px) {
    .olx-grid { height: 280px; }
    .lightbox-prev { left: 10px; }
    .lightbox-next { right: 10px; }
}
</style>

<main class="listings-section">
    <div class="form-container">

        <div class="form-title-group">
            <h2>Anunciar Resíduo ou Demanda</h2>
            <p style="color:#6b7280;font-size:0.95rem;margin-top:6px;">Publique materiais disponíveis para destinação ou registre o que sua empresa está procurando.</p>
        </div>

        <?php if ($sucesso): ?>
            <div class="alert alert-success">Anúncio publicado com sucesso! Ele já está visível no marketplace.</div>
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

        <form action="anunciarResiduos.php" method="POST" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="input-box full-width">
                    <label>Finalidade do Anúncio</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="type" value="offer" checked onclick="togglePriceField(true)">
                            Oferta (Possuo o resíduo para destinação/venda)
                        </label>
                    </div>
                    <div class="radio-group" style="margin-top:0.5rem;">
                        <label class="radio-label">
                            <input type="radio" name="type" value="demand" onclick="togglePriceField(false)">
                            Demanda (Estou buscando/precisando deste material)
                        </label>
                    </div>
                </div>

                <div class="input-box full-width">
                    <label for="title">Título do Anúncio</label>
                    <input type="text" id="title" name="title" placeholder="Ex: Lote de Paletes de Madeira Secos - 50 un" required>
                </div>

                <div class="input-box">
                    <label for="category_id">Categoria do Material</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="unit">Unidade de Medida</label>
                    <select id="unit" name="unit" required>
                        <option value="kg">Quilograma (kg)</option>
                        <option value="ton" selected>Tonelada (ton)</option>
                        <option value="m3">Metro Cúbico (m³)</option>
                        <option value="m2">Metro Quadrado (m²)</option>
                        <option value="litro">Litro (l)</option>
                        <option value="unidade">Unidade</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="input-box">
                    <label for="quantity">Quantidade Disponível</label>
                    <input type="number" id="quantity" name="quantity" step="0.001" min="0.001" placeholder="Ex: 5.500" required>
                </div>

                <div class="input-box" id="price-wrapper">
                    <label for="price">Preço Unitário (R$)</label>
                    <input type="text" id="price" name="price" placeholder="Deixe em branco se for Doação">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_negotiable" value="1"> Valor negociável
                    </label>
                </div>

                <div class="input-box">
                    <label for="location_state">Estado (UF)</label>
                    <select id="location_state" name="location_state" required>
                        <option value="">Carregando Estados...</option>
                    </select>
                </div>

                <div class="input-box">
                    <label for="location_city">Cidade</label>
                    <select id="location_city" name="location_city" required disabled>
                        <option value="">Selecione um Estado primeiro</option>
                    </select>
                </div>

                <div class="input-box full-width">
                    <label for="description">Descrição Técnica e Condições do Material</label>
                    <textarea id="description" name="description" rows="4" placeholder="Descreva o estado de conservação, presença de impurezas, embalagem, necessidade de frete, etc." style="resize:vertical;"></textarea>
                </div>

                <div class="input-box full-width">
                    <label for="images">Fotos do Resíduo <span style="font-weight:normal;color:#9ca3af;">(Selecione uma ou mais imagens)</span></label>
                    <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp">

                    <div class="olx-preview-container" id="previewContainer">
                        <label style="margin-bottom:0.5rem;display:block;">Pré-visualização do Álbum:</label>
                        <div class="olx-grid" id="olxGrid"></div>
                    </div>
                </div>

            </div>

            <div class="submit-wrap">
                <a href="meusAnuncios.php" class="btn-back">Visualizar Meus Anúncios</a>
                <button type="submit" class="btn-submit">Publicar Anúncio</button>
            </div>
        </form>

    </div>
</main>

<div class="custom-lightbox" id="lightbox">
    <div class="lightbox-content">
        <button type="button" class="lightbox-close" id="lightboxClose">&times;</button>
        <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev">&#10094;</button>
        <img src="" alt="Imagem Ampliada" class="lightbox-img" id="lightboxImg">
        <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext">&#10095;</button>
    </div>
</div>

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

    // Carrega estados via IBGE
    const ufSelect   = document.getElementById('location_state');
    const citySelect = document.getElementById('location_city');

    fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome')
        .then(r => r.json())
        .then(states => {
            ufSelect.innerHTML = '<option value="">Selecione o Estado</option>';
            states.forEach(s => {
                const o = document.createElement('option');
                o.value = s.sigla; o.textContent = s.nome;
                ufSelect.appendChild(o);
            });
        });

    ufSelect.addEventListener('change', function() {
        const uf = this.value;
        if (!uf) { citySelect.innerHTML = '<option value="">Selecione um Estado primeiro</option>'; citySelect.disabled = true; return; }
        citySelect.innerHTML = '<option value="">Carregando Cidades...</option>';
        citySelect.disabled = true;
        fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${uf}/municipios`)
            .then(r => r.json())
            .then(cities => {
                citySelect.innerHTML = '<option value="">Selecione a Cidade</option>';
                cities.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.nome; o.textContent = c.nome;
                    citySelect.appendChild(o);
                });
                citySelect.disabled = false;
            });
    });

    // Grid de preview estilo OLX
    const imageInput       = document.getElementById('images');
    const previewContainer = document.getElementById('previewContainer');
    const olxGrid          = document.getElementById('olxGrid');
    let loadedImagesArray  = [];
    let currentLightboxIndex = 0;
    let selectedFiles = []; // to keep track of files

    imageInput.addEventListener('change', function() {
        selectedFiles = Array.from(this.files);
        renderPreview();
    });

    function renderPreview() {
        olxGrid.innerHTML = '';
        loadedImagesArray = [];
        if (selectedFiles.length === 0) { 
            previewContainer.style.display = 'none'; 
            const dt = new DataTransfer();
            imageInput.files = dt.files;
            return; 
        }

        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        imageInput.files = dt.files;

        previewContainer.style.display = 'block';
        olxGrid.className = 'olx-grid';
        if      (selectedFiles.length === 1) olxGrid.classList.add('count-1');
        else if (selectedFiles.length === 2) olxGrid.classList.add('count-2');
        else if (selectedFiles.length === 3) olxGrid.classList.add('count-3');
        else if (selectedFiles.length === 4) olxGrid.classList.add('count-4');
        else                         olxGrid.classList.add('count-default');

        const maxVisivel = 4;

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64Url = e.target.result;
                loadedImagesArray[index] = base64Url;

                if (index < maxVisivel) {
                    const item = document.createElement('div');
                    item.className = 'olx-item';
                    item.setAttribute('data-index', index);

                    const img = document.createElement('img');
                    img.src = base64Url;
                    item.appendChild(img);

                    const btnRemove = document.createElement('button');
                    btnRemove.type = 'button';
                    btnRemove.className = 'btn-remove-preview';
                    btnRemove.innerHTML = '&times;';
                    btnRemove.title = 'Remover Imagem';
                    btnRemove.addEventListener('click', function(evt) {
                        evt.stopPropagation();
                        selectedFiles.splice(index, 1);
                        renderPreview();
                    });
                    item.appendChild(btnRemove);

                    if (index === maxVisivel - 1 && selectedFiles.length > maxVisivel) {
                        const overlay = document.createElement('div');
                        overlay.className = 'olx-overlay-more';
                        overlay.innerText = `+${selectedFiles.length - maxVisivel + 1}`;
                        item.appendChild(overlay);
                    }

                    item.addEventListener('click', function() {
                        openLightbox(index);
                    });

                    olxGrid.appendChild(item);
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // Lightbox
    const lightbox      = document.getElementById('lightbox');
    const lightboxImg   = document.getElementById('lightboxImg');
    const lightboxClose = document.getElementById('lightboxClose');
    const lightboxPrev  = document.getElementById('lightboxPrev');
    const lightboxNext  = document.getElementById('lightboxNext');

    function openLightbox(index) {
        currentLightboxIndex = index;
        lightboxImg.src = loadedImagesArray[currentLightboxIndex];
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
    function showNext() {
        currentLightboxIndex = (currentLightboxIndex + 1) % loadedImagesArray.length;
        lightboxImg.src = loadedImagesArray[currentLightboxIndex];
    }
    function showPrev() {
        currentLightboxIndex = (currentLightboxIndex - 1 + loadedImagesArray.length) % loadedImagesArray.length;
        lightboxImg.src = loadedImagesArray[currentLightboxIndex];
    }

    lightboxClose.addEventListener('click', closeLightbox);
    lightboxNext.addEventListener('click', showNext);
    lightboxPrev.addEventListener('click', showPrev);
    lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
    document.addEventListener('keydown', e => {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape')      closeLightbox();
        if (e.key === 'ArrowRight')  showNext();
        if (e.key === 'ArrowLeft')   showPrev();
    });
});
</script>

<?php include 'footer.php'; ?>