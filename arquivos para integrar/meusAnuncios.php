<?php
// meusAnuncios.php
// CORREÇÃO: Removido mock $_SESSION['company_id'] = 1 e trocado por auth_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php";
require_once __DIR__ . "/BackEnd/config/auth_check.php"; // define $company_id e $user_id, redireciona se não logado

$mensagem = "";

if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $mensagem = "<div class='alert alert-success'>Anúncio excluído permanentemente!</div>";
}

// LÓGICA DE EXCLUSÃO
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_para_excluir = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($id_para_excluir) {
        try {
            $pdo->beginTransaction();

            $stmtImg = $pdo->prepare("DELETE FROM listing_images WHERE listing_id = :id");
            $stmtImg->execute([':id' => $id_para_excluir]);

            // Garante que só deleta anúncios da empresa logada (segurança)
            $stmtDel = $pdo->prepare("DELETE FROM listings WHERE id = :id AND company_id = :company_id");
            $stmtDel->execute([':id' => $id_para_excluir, ':company_id' => $company_id]);
            
            $pdo->commit();
            
            header("Location: meusAnuncios.php?deleted=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "<div class='alert alert-danger'>Erro ao remover anúncio: " . $e->getMessage() . "</div>";
        }
    }
}

// BUSCA OS ANÚNCIOS DA EMPRESA LOGADA
try {
    $sql = "
        SELECT l.*, c.name as category_name,
        (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1) as main_image
        FROM listings l
        LEFT JOIN categories c ON l.category_id = c.id
        WHERE l.company_id = :company_id
        ORDER BY l.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':company_id' => $company_id]);
    $anuncios = $stmt->fetchAll();
} catch (PDOException $e) {
    $anuncios = [];
    $mensagem = "<div class='alert alert-danger'>Erro ao carregar anúncios: " . $e->getMessage() . "</div>";
}

// CORREÇÃO SIDEBAR: Busca e padroniza dados da empresa igual ao arquivo de referência (estatisticas.php)
try {
    $stmtEmpresa = $pdo->prepare("SELECT razao_social, nome_fantasia, logo_url FROM companies WHERE id = ?");
    $stmtEmpresa->execute([$company_id]);

    $dados_banco = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

    if ($dados_banco) {
        $empresa = array_change_key_case($dados_banco, CASE_LOWER);
        $razao_social_final = !empty($empresa['razao_social']) ? $empresa['razao_social'] : 'Razão Social não preenchida';
    } else {
        $razao_social_final = 'Empresa Não Encontrada';
        $empresa = ['nome_fantasia' => '', 'logo_url' => null];
    }

    $nome_empresa  = !empty($empresa['nome_fantasia']) ? $empresa['nome_fantasia'] : $razao_social_final;
    $nome_exibicao = $nome_empresa; // Usado na sidebar
    $logo_url      = $empresa['logo_url'] ?? null; // Usado na sidebar
} catch (PDOException $e) {
    $razao_social_final = 'Erro ao carregar';
    $nome_exibicao = 'Erro ao carregar';
    $logo_url = null;
}

$titulo_pagina = "Meus Anúncios — Re.Source";
include 'header.php';
?>

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
.dashboard-sidebar {
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    padding: 1.5rem 1rem;
    position: sticky;
    top: 100px;
    height: calc(100vh - 120px);
    overflow-y: auto;
}

.sidebar-user { text-align: center; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); margin-bottom: 1.5rem; }
.sidebar-avatar {
    width: 64px; height: 64px;
    background: var(--bg);
    border-radius: 50%;
    margin: 0 auto 0.75rem;
    display: flex; align-items: center; justify-content: center;
    color: var(--green);
    overflow: hidden;
    border: 2px solid var(--border-color);
}
.sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
.sidebar-nav { display: flex; flex-direction: column; gap: 0.5rem; }
.sidebar-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; color: var(--muted); transition: all 0.2s; }
.sidebar-link:hover { background: var(--bg); color: var(--dark); }
.sidebar-link.active { background: rgba(21, 115, 71, 0.1); color: var(--green); font-weight: 600; }

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
    
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?php if (!empty($logo_url)): ?>
                    <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo da empresa">
                <?php else: ?>
                    <i data-lucide="building-2" style="width:32px;height:32px;"></i>
                <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars($nome_exibicao) ?></h3>
            <p>Conta B2B Verificada</p>
        </div>

        <nav class="sidebar-nav">
            <a href="estatisticas.php" class="sidebar-link"><i data-lucide="bar-chart-2"></i> Painel e Estatísticas</a>
            <a href="meusAnuncios.php" class="sidebar-link active"><i data-lucide="package"></i> Meus Anúncios</a>
            <a href="conta.php" class="sidebar-link "><i data-lucide="user"></i> Detalhes da Conta</a>
            <a href="configuracoes.php" class="sidebar-link"><i data-lucide="settings"></i> Configurações</a>
            <a href="logout.php" class="sidebar-link"><i data-lucide="log-out"></i> Sair</a>
        </nav>
    </aside>

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
                    <p>Você ainda não possui nenhum anúncio cadastrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($anuncios as $ad): ?>
                    <div class="listing-card" onclick="window.location.href='anuncio.php?id=<?= $ad['id']; ?>';" style="cursor: pointer;" title="Ver detalhes do anúncio">
                        
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
                            <a href="editarResiduo.php?id=<?= $ad['id']; ?>" class="btn-action" title="Editar" onclick="event.stopPropagation();">
                                <i data-lucide="pencil" style="width: 18px; height: 18px;"></i>
                            </a>
                            <a href="meusAnuncios.php?action=delete&id=<?= $ad['id']; ?>" 
                               class="btn-action btn-delete" 
                               title="Excluir" 
                               onclick="event.stopPropagation(); return confirm('⚠️ Deseja realmente apagar este anúncio?\nEsta ação não pode ser desfeita.');">
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
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>

<?php include 'footer.php'; ?>