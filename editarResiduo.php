<?php
// editarResiduo.php
// CORREÇÃO: Removidos mocks, substituído por auth_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php";
require_once __DIR__ . "/BackEnd/config/auth_check.php"; // define $company_id e $user_id

$erros   = [];
$sucesso = false;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: meusAnuncios.php");
    exit;
}

// ==========================================
// EXCLUSÃO DE IMAGEM INDIVIDUAL
// ==========================================
if (isset($_GET['delete_image'])) {
    $img_id = filter_input(INPUT_GET, 'delete_image', FILTER_VALIDATE_INT);
    if ($img_id) {
        try {
            // Garante que a imagem pertence a um anúncio da empresa logada
            $stmtImg = $pdo->prepare("
                SELECT li.url FROM listing_images li
                JOIN listings l ON l.id = li.listing_id
                WHERE li.id = :img_id AND li.listing_id = :listing_id AND l.company_id = :company_id
            ");
            $stmtImg->execute([':img_id' => $img_id, ':listing_id' => $id, ':company_id' => $company_id]);
            $imgData = $stmtImg->fetch();

            if ($imgData) {
                $nome_arquivo   = basename($imgData['url']);
                $caminho_fisico = __DIR__ . '/uploads/listings/' . $nome_arquivo;
                if (file_exists($caminho_fisico)) unlink($caminho_fisico);

                $pdo->prepare("DELETE FROM listing_images WHERE id = :img_id")->execute([':img_id' => $img_id]);

                header("Location: editarResiduo.php?id=$id&msg=img_deleted");
                exit;
            }
        } catch (Exception $e) {
            $erros[] = "Erro ao excluir imagem: " . $e->getMessage();
        }
    }
}

// ==========================================
// PROCESSAMENTO DO FORMULÁRIO (POST)
// ==========================================
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

    if (!in_array($type, ['offer', 'demand'])) $erros[] = "Selecione o tipo do anúncio.";
    if (empty($title) || strlen($title) < 5)   $erros[] = "O título deve conter pelo menos 5 caracteres.";
    if (!$category_id)                          $erros[] = "Selecione uma categoria válida.";
    if ($quantity <= 0)                         $erros[] = "A quantidade deve ser maior que zero.";
    if (empty($location_state))                 $erros[] = "Selecione o Estado (UF).";
    if (empty($location_city))                  $erros[] = "Selecione a cidade.";

    $price = null;
    if ($type === 'offer') {
        if ($price_raw === '' || $price_raw === null) {
            $price = 0.00;
        } else {
            $price = str_replace(['.', ','], ['', '.'], $price_raw);
            $price = filter_var($price, FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0) $erros[] = "Informe um preço válido.";
        }
    }

    if (empty($erros)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE listings SET 
                    type = :type, title = :title, description = :description, 
                    category_id = :category_id, quantity = :quantity, unit = :unit, 
                    price = :price, is_negotiable = :is_negotiable, 
                    location_state = :state, location_city = :city
                WHERE id = :id AND company_id = :company_id
            ");
            $stmt->execute([
                ':type'          => $type,
                ':title'         => $title,
                ':description'   => $description ?: null,
                ':category_id'   => $category_id,
                ':quantity'      => $quantity,
                ':unit'          => $unit,
                ':price'         => $price,
                ':is_negotiable' => $is_negotiable,
                ':state'         => strtoupper($location_state),
                ':city'          => $location_city,
                ':id'            => $id,
                ':company_id'    => $company_id, // ← empresa real
            ]);

            // Upload de novas imagens
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $upload_dir = __DIR__ . '/uploads/listings/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $permitidos = ['image/jpeg', 'image/png', 'image/webp'];

                foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
                    if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                        $mime = $_FILES['images']['type'][$index];
                        if (in_array($mime, $permitidos)) {
                            $ext          = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
                            $nome_arquivo = 'listing_' . $id . '_' . uniqid() . '.' . $ext;
                            move_uploaded_file($tmp_name, $upload_dir . $nome_arquivo);
                            $url_publica  = '/RE.SOURCE/uploads/listings/' . $nome_arquivo;

                            $stmtImg = $pdo->prepare("INSERT INTO listing_images (listing_id, url, `order`) VALUES (:listing_id, :url, :order)");
                            $stmtImg->execute([':listing_id' => $id, ':url' => $url_publica, ':order' => $index + 10]);
                        }
                    }
                }
            }

            $pdo->commit();
            header("Location: editarResiduo.php?id=$id&success=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}

// ==========================================
// BUSCA DADOS DO ANÚNCIO
// ==========================================
try {
    $stmtAnuncio = $pdo->prepare("SELECT * FROM listings WHERE id = :id AND company_id = :company_id");
    $stmtAnuncio->execute([':id' => $id, ':company_id' => $company_id]); // ← empresa real
    $anuncio = $stmtAnuncio->fetch();

    if (!$anuncio) {
        // Anúncio não existe ou não pertence a esta empresa
        header("Location: meusAnuncios.php");
        exit;
    }

    $stmtCat = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
    $categorias = $stmtCat->fetchAll();

    $stmtImgs = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = :id ORDER BY `order` ASC");
    $stmtImgs->execute([':id' => $id]);
    $imagens_atuais = $stmtImgs->fetchAll();

} catch (PDOException $e) {
    die("Erro crítico: " . $e->getMessage());
}

$titulo_pagina = "Editar Anúncio — Re.Source";
include 'header.php';
?>

<style>
.form-container { max-width: 850px; margin: 3rem auto; padding: 3rem; background: var(--white); border-radius: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.form-title-group { margin-bottom: 2.5rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 1.5rem; }
.form-title-group h2 { font-family: var(--font-main); color: #111827; font-size: 1.875rem; font-weight: 800; letter-spacing: -0.025em; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
.full-width { grid-column: span 2; }
.input-box { display: flex; flex-direction: column; gap: 0.4rem; }
.input-box label { font-family: var(--font-main); font-size: 0.875rem; font-weight: 600; color: #374151; }
.input-box input[type="text"],
.input-box input[type="number"],
.input-box select,
.input-box textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background-color: #f9fafb; color: #1f2937; font-size: 0.95rem; font-family: var(--font-body); transition: all 0.2s; }
.input-box input:focus, .input-box select:focus, .input-box textarea:focus { border-color: var(--green); background-color: #fff; box-shadow: 0 0 0 4px rgba(21,115,71,0.15); outline: none; }
.input-box input[type="file"] { background-color: #fff; border: 1px dashed #d1d5db; padding: 1.5rem; cursor: pointer; border-radius: 0.5rem; }
.input-box input[type="file"]:hover { border-color: var(--green); background-color: #f0fdf4; }
.radio-group { display: flex; gap: 1.5rem; margin-top: 0.25rem; }
.radio-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; color: #4b5563; font-weight: 500; }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; margin-top: 0.5rem; color: #4b5563; font-weight: 500; }
.alert { padding: 1rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.95rem; font-weight: 500; }
.alert-danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.submit-wrap { margin-top: 3rem; display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid #f3f4f6; }
.btn-submit { padding: 0.875rem 2.5rem; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; color: var(--white); background: var(--green); cursor: pointer; border: none; transition: background 0.2s, transform 0.1s; }
.btn-submit:hover { background: var(--green-d); transform: translateY(-1px); }
.btn-back { color: #6b7280; text-decoration: none; font-size: 0.95rem; font-weight: 600; }
.btn-back:hover { color: #111827; }
.images-gallery { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem; }
.img-wrapper { position: relative; width: 100px; height: 100px; border-radius: 0.5rem; overflow: hidden; border: 1px solid #e5e7eb; }
.img-wrapper img { width: 100%; height: 100%; object-fit: cover; display: block; }
.img-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; }
.img-wrapper:hover .img-overlay { opacity: 1; }
.btn-del-img { color: #fff; background: #ef4444; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s, background 0.2s; }
.btn-del-img:hover { transform: scale(1.15); background: #dc2626; color: #fff; }
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

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'img_deleted'): ?>
            <div class="alert alert-success">Foto removida permanentemente do anúncio.</div>
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

        <form action="editarResiduo.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
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
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($anuncio['title']) ?>" required>
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
                        <div class="images-gallery">
                            <?php foreach ($imagens_atuais as $img): ?>
                                <div class="img-wrapper">
                                    <img src="<?= htmlspecialchars($img['url']) ?>" alt="Foto do Resíduo">
                                    <div class="img-overlay">
                                        <a href="editarResiduo.php?id=<?= $id ?>&delete_image=<?= $img['id'] ?>"
                                           class="btn-del-img"
                                           title="Excluir Imagem"
                                           onclick="return confirm('Deseja excluir esta foto? A ação é imediata.');">
                                            <i data-lucide="trash-2" style="width:18px;height:18px;"></i>
                                        </a>
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
                <a href="meusAnuncios.php" class="btn-back">← Voltar para Meus Anúncios</a>
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

<?php include 'footer.php'; ?>