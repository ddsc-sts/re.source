<?php
// app/Controllers/EstatisticasController.php

class EstatisticasController
{
    /**
     * GET /estatisticas
     * Antes: estatisticas.php (página "gorda")
     *
     * Correção aplicada:
     * - path do banco: usava require __DIR__ . "/BackEnd/config/conexao.php" (path morto).
     *   Sessão já estava no formato correto ($_SESSION['user']['company_id']).
     */
    public static function index()
    {
        global $pdo;

        $company_id = $_SESSION['user']['company_id'] ?? null;

        if (!$company_id) {
            header("Location: /re.source/login");
            exit();
        }

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS withdrawals (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    company_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    pix_key VARCHAR(255) NOT NULL,
                    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

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

            $nome_empresa = !empty($empresa['nome_fantasia']) ? $empresa['nome_fantasia'] : $razao_social_final;
            $logo_url = $empresa['logo_url'] ?? null;

            $stmtTotalVendas = $pdo->prepare("SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations WHERE seller_company_id = ? AND status = 'concluded'");
            $stmtTotalVendas->execute([$company_id]);
            $total_ganho = (float) $stmtTotalVendas->fetchColumn();

            $stmtTotalSaques = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE company_id = ? AND status IN ('pending', 'completed')");
            $stmtTotalSaques->execute([$company_id]);
            $total_sacado = (float) $stmtTotalSaques->fetchColumn();

            $saldo_disponivel = max(0, $total_ganho - $total_sacado);

            $stmtAnuncios = $pdo->prepare("SELECT COUNT(id) FROM listings WHERE company_id = ? AND status = 'active' AND deleted_at IS NULL");
            $stmtAnuncios->execute([$company_id]);
            $total_anuncios = $stmtAnuncios->fetchColumn();

            $stmtViews = $pdo->prepare("SELECT COALESCE(SUM(views_count), 0) FROM listings WHERE company_id = ? AND deleted_at IS NULL");
            $stmtViews->execute([$company_id]);
            $total_views = (int) $stmtViews->fetchColumn();

            $stmtViewsHistory = $pdo->prepare("
                SELECT DATE(vh.created_at) as data_view, COUNT(vh.id) as views_dia
                FROM views_history vh
                JOIN listings l ON vh.listing_id = l.id
                WHERE l.company_id = ? AND vh.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                GROUP BY DATE(vh.created_at)
            ");
            $stmtViewsHistory->execute([$company_id]);
            $views_db = $stmtViewsHistory->fetchAll(PDO::FETCH_KEY_PAIR);

            $labelsViews = [];
            $dataViews = [];
            for ($i = 29; $i >= 0; $i--) {
                $dataLabel = date('Y-m-d', strtotime("-$i days"));
                $labelsViews[] = date('d/m', strtotime("-$i days"));
                $dataViews[] = $views_db[$dataLabel] ?? 0;
            }

            $stmtCats = $pdo->prepare("
                SELECT c.name, COUNT(l.id) as total_anuncios
                FROM listings l
                JOIN categories c ON l.category_id = c.id
                WHERE l.company_id = ? AND l.deleted_at IS NULL
                GROUP BY c.id
            ");
            $stmtCats->execute([$company_id]);
            $categorias_db = $stmtCats->fetchAll();

            $catLabels = [];
            $catData = [];
            if (empty($categorias_db)) {
                $catLabels = ['Sem anúncios'];
                $catData = [1];
            } else {
                foreach ($categorias_db as $cat) {
                    $catLabels[] = $cat['name'];
                    $catData[] = $cat['total_anuncios'];
                }
            }

            $stmtRecentes = $pdo->prepare("
                SELECT
                    n.updated_at as data,
                    IF(n.buyer_company_id = ?, 'Compra', 'Venda') as tipo,
                    l.title as material,
                    n.proposed_total as valor,
                    n.status
                FROM negotiations n
                JOIN listings l ON n.listing_id = l.id
                WHERE (n.buyer_company_id = ? OR n.seller_company_id = ?)
                ORDER BY n.updated_at DESC
                LIMIT 5
            ");
            $stmtRecentes->execute([$company_id, $company_id, $company_id]);
            $negociacoes_recentes = $stmtRecentes->fetchAll();

            $stmtHistoricoSaques = $pdo->prepare("
                SELECT amount, pix_key, status, created_at
                FROM withdrawals
                WHERE company_id = ?
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmtHistoricoSaques->execute([$company_id]);
            $historico_saques = $stmtHistoricoSaques->fetchAll();

        } catch (PDOException $e) {
            die("Erro ao carregar o painel: " . $e->getMessage());
        }

        view('estatisticas/index', [
            'titulo_pagina'        => 'Estatísticas do Painel — Re.Source',
            'empresa'              => $empresa,
            'nome_empresa'         => $nome_empresa,
            'logo_url'             => $logo_url,
            'saldo_disponivel'     => $saldo_disponivel,
            'total_anuncios'       => $total_anuncios,
            'total_views'          => $total_views,
            'labelsViews'          => $labelsViews,
            'dataViews'            => $dataViews,
            'catLabels'            => $catLabels,
            'catData'              => $catData,
            'negociacoes_recentes' => $negociacoes_recentes,
            'historico_saques'     => $historico_saques,
        ]);
    }

    /**
     * POST /estatisticas/processar-saque
     * Antes: processar_saque.php
     *
     * Correções aplicadas:
     * - path do banco: usava require __DIR__ . "/BackEnd/config/conexao.php" (path morto).
     * - sessão: usava $_SESSION['company_id'] (formato antigo) — isso fazia o saque
     *   ser processado sempre com company_id = null, e como a query não tinha
     *   fallback, o saque nem era vinculado à empresa certa. Corrigido para
     *   $_SESSION['user']['company_id'], igual ao estatisticas.php que chama esta rota.
     */
    public static function processarSaque()
    {
        global $pdo;

        $company_id = $_SESSION['user']['company_id'] ?? null;

        if (!$company_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /re.source/estatisticas");
            exit;
        }

        $valor_saque = (float) ($_POST['valor_saque'] ?? 0);
        $chave_pix = trim($_POST['chave_pix'] ?? '');

        try {
            $stmtTotalVendas = $pdo->prepare("SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations WHERE seller_company_id = ? AND status = 'concluded'");
            $stmtTotalVendas->execute([$company_id]);
            $total_ganho = (float) $stmtTotalVendas->fetchColumn();

            $stmtTotalSaques = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE company_id = ? AND status IN ('pending', 'completed')");
            $stmtTotalSaques->execute([$company_id]);
            $total_sacado = (float) $stmtTotalSaques->fetchColumn();

            $saldo_disponivel = $total_ganho - $total_sacado;

            if ($valor_saque <= 0) {
                $_SESSION['saque_msg'] = "O valor do saque deve ser maior que zero.";
                $_SESSION['saque_tipo'] = "error";
            } elseif ($valor_saque > $saldo_disponivel) {
                $_SESSION['saque_msg'] = "Tentativa de saque bloqueada: Saldo insuficiente.";
                $_SESSION['saque_tipo'] = "error";
            } elseif (empty($chave_pix)) {
                $_SESSION['saque_msg'] = "A chave PIX é obrigatória.";
                $_SESSION['saque_tipo'] = "error";
            } else {
                $stmtInsert = $pdo->prepare("INSERT INTO withdrawals (company_id, amount, pix_key, status) VALUES (?, ?, ?, 'pending')");
                $stmtInsert->execute([$company_id, $valor_saque, $chave_pix]);

                $_SESSION['saque_msg'] = "Saque de R$ " . number_format($valor_saque, 2, ',', '.') . " solicitado com sucesso! O valor será transferido em breve.";
                $_SESSION['saque_tipo'] = "success";
            }

        } catch (PDOException $e) {
            $_SESSION['saque_msg'] = "Erro de sistema ao processar saque: " . $e->getMessage();
            $_SESSION['saque_tipo'] = "error";
        }

        header("Location: /estatisticas");
        exit;
    }
}