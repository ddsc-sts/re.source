<?php
// 1. GERENCIAMENTO DE SESSÃO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. CONEXÃO COM O BANCO DE DADOS
require_once __DIR__ . "/BackEnd/config/conexao.php"; 

// Mock temporário (remover quando o login estiver pronto)
if (!isset($_SESSION['company_id'])) $_SESSION['company_id'] = 1; 

$mensagem = "";

// Captura aviso de sucesso vindo do redirecionamento
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $mensagem = "<div class='alert alert-success'>Anúncio excluído permanentemente!</div>";
}

// 3. LÓGICA DE EXCLUSÃO REAL (HARD DELETE)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_para_excluir = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($id_para_excluir) {
        try {
            $pdo->beginTransaction();

            // Passo 1: Deleta primeiro as imagens associadas na tabela 'listing_images' (evita erro de chave estrangeira)
            $stmtImg = $pdo->prepare("DELETE FROM listing_images WHERE listing_id = :id");
            $stmtImg->execute([':id' => $id_para_excluir]);

            // Passo 2: Deleta o anúncio definitivo da tabela 'listings'
            $stmtDel = $pdo->prepare("DELETE FROM listings WHERE id = :id AND company_id = :company_id");
            $stmtDel->execute([
                ':id' => $id_para_excluir,
                ':company_id' => $_SESSION['company_id']
            ]);
            
            $pdo->commit();
            
            // Passo 3: Redireciona para limpar a URL e atualizar a lista na hora
            header("Location: meusAnuncios.php?deleted=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "<div class='alert alert-danger'>Erro crítico ao remover do banco: " . $e->getMessage() . "</div>";
        }
    }
}

// 4. BUSCA OS ANÚNCIOS ATIVOS DA EMPRESA
try {
    $sql = "SELECT l.*, c.name as category_name,
            (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1) as main_image
            FROM listings l
            LEFT JOIN categories c ON l.category_id = c.id
            WHERE l.company_id = :company_id
            ORDER BY l.created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':company_id' => $_SESSION['company_id']]);
    $anuncios = $stmt->fetchAll();
} catch (PDOException $e) {
    $anuncios = [];
    $mensagem = "<div class='alert alert-danger'>Erro ao carregar anúncios: " . $e->getMessage() . "</div>";
}

// 5. AUTO-DETECÇÃO DO HEADER
$titulo_pagina = "Meus Anúncios — Re.Source";
$caminhos_header = [
    __DIR__ . '/header.php',
    __DIR__ . '/FrontEnd/header.php',
    __DIR__ . '/FrontEnd/includes/header.php',
    __DIR__ . '/includes/header.php'
];

$header_carregado = false;
foreach ($caminhos_header as $caminho) {
    if (file_exists($caminho)) {
        include $caminho;
        $header_carregado = true;
        break;
    }
}

if (!$header_carregado) {
    echo "<div style='background:red; color:white; padding:10px;'>Aviso crítico: Arquivo header.php não encontrado.</div>";
}
?>

<style>
.dashboard-container {
    max-width: 1000px;
    margin: 3rem auto;
    padding: 0 1.5rem;
}

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

.btn-new:hover {
    background: var(--green-d);
    color: var(--white);
}

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

.listing-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-card);
}

.listing-img {
    width: 100px;
    height: 100px;
    border-radius: 0.5rem;
    object-fit: cover;
    background: var(--bg);
    border: 1px solid var(--border-color);
}

.listing-img-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 0.5rem;
    background: var(--bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
    border: 1px solid var(--border-color);
}

.listing-info {
    flex: 1;
}

.listing-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.25rem;
}

.listing-meta {
    font-size: 0.85rem;
    color: var(--muted);
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.badge {
    padding: 0.25rem 0.6rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-offer { background: #e0f2fe; color: #0284c7; }
.badge-demand { background: #fef3c7; color: #d97706; }

.listing-price {
    font-weight: 700;
    color: var(--green);
    font-size: 1.1rem;
}

.listing-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    background: var(--white);
    color: var(--dark);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-action:hover {
    background: var(--bg);
}

.btn-delete:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fecaca;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--white);
    border: 1px dashed var(--border-color);
    border-radius: var(--radius);
    color: var(--muted);
}

.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}
.alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
.alert-danger { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

@media (max-width: 768px) {
    .listing-card { flex-direction: column; align-items: flex-start; }
    .listing-img, .listing-img-placeholder { width: 100%; height: 180px; }
    .listing-actions { width: 100%; justify-content: flex-end; margin-top: 1rem; }
}
</style>

<main>
    <div class="dashboard-container">
        
        <div class="dashboard-header">
            <h2>Meus Anúncios</h2>
            <a href="anunciarResiduos.php" class="btn-new">
                <i data-lucide="plus"></i> Novo Anúncio
            </a>
        </div>

        <?php echo $mensagem; ?>

        <div class="listings-list">
            <?php if (empty($anuncios)): ?>
                <div class="empty-state">
                    <i data-lucide="inbox" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>Nenhum anúncio encontrado</h3>
                    <p>Você ainda não possui nenhum anúncio cadastrado no banco.</p>
                </div>
            <?php else: ?>
                <?php foreach ($anuncios as $ad): ?>
                    <div class="listing-card">
                        
                        <?php if (!empty($ad['main_image'])): ?>
                            <img src="<?php echo htmlspecialchars($ad['main_image']); ?>" alt="Imagem" class="listing-img">
                        <?php else: ?>
                            <div class="listing-img-placeholder">
                                <i data-lucide="image" style="width: 32px; height: 32px;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="listing-info">
                            <div class="listing-title"><?php echo htmlspecialchars($ad['title']); ?></div>
                            <div class="listing-meta">
                                <span><i data-lucide="tag" style="width:14px; height:14px; vertical-align:middle;"></i> <?php echo htmlspecialchars($ad['category_name']); ?></span>
                                <span><i data-lucide="box" style="width:14px; height:14px; vertical-align:middle;"></i> <?php echo floatval($ad['quantity']) . ' ' . $ad['unit']; ?></span>
                                <span><i data-lucide="map-pin" style="width:14px; height:14px; vertical-align:middle;"></i> <?php echo htmlspecialchars($ad['location_city'] . '/' . $ad['location_state']); ?></span>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                                <?php if ($ad['type'] === 'offer'): ?>
                                    <span class="badge badge-offer">Oferta</span>
                                <?php else: ?>
                                    <span class="badge badge-demand">Demanda</span>
                                <?php endif; ?>

                                <?php if ($ad['type'] === 'offer'): ?>
                                    <span class="listing-price">
                                        <?php echo ($ad['price'] > 0) ? 'R$ ' . number_format($ad['price'], 2, ',', '.') : 'Doação'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="listing-actions">
                            <a href="editarResiduo.php?id=<?php echo $ad['id']; ?>" class="btn-action" title="Editar">
                                <i data-lucide="pencil" style="width: 18px; height: 18px;"></i>
                            </a>
                            
                            <a href="meusAnuncios.php?action=delete&id=<?php echo $ad['id']; ?>" 
                               class="btn-action btn-delete" 
                               title="Excluir" 
                               onclick="return confirm('⚠️ ATENÇÃO:\n\nDeseja realmente apagar este anúncio?\nEle será deletado permanentemente e esta ação não poderá ser desfeita!');">
                                <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<?php 
// 6. AUTO-DETECÇÃO DO FOOTER
$caminhos_footer = [
    __DIR__ . '/footer.php',
    __DIR__ . '/FrontEnd/footer.php',
    __DIR__ . '/FrontEnd/includes/footer.php',
    __DIR__ . '/includes/footer.php'
];

$footer_carregado = false;
foreach ($caminhos_footer as $caminho) {
    if (file_exists($caminho)) {
        include $caminho;
        $footer_carregado = true;
        break;
    }
}

if (!$footer_carregado) {
    echo "<div style='background:red; color:white; padding:10px;'>Aviso crítico: Arquivo footer.php não encontrado.</div>";
}
?>