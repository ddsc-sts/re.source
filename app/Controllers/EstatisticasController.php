<?php
// app/Controllers/EstatisticasController.php

class EstatisticasController
{
    private static function companyId(): ?int
    {
        $companyId = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? null;
        return $companyId ? (int) $companyId : null;
    }

    private static function ensureWithdrawalSchema(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS withdrawals (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                pix_key VARCHAR(255) NOT NULL,
                status ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_withdrawals_company_status (company_id, status),
                CONSTRAINT fk_withdrawals_company FOREIGN KEY (company_id)
                    REFERENCES companies (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $columns = [];
        foreach ($pdo->query('SHOW COLUMNS FROM withdrawals')->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[$column['Field']] = true;
        }
        $newColumns = [
            'pix_key_type' => "VARCHAR(20) NULL AFTER pix_key",
            'account_holder_name' => "VARCHAR(150) NULL AFTER pix_key_type",
            'account_holder_document' => "VARCHAR(20) NULL AFTER account_holder_name",
            'request_note' => "VARCHAR(500) NULL AFTER account_holder_document",
            'terms_accepted_at' => "TIMESTAMP NULL AFTER request_note",
            'reviewed_at' => "TIMESTAMP NULL AFTER terms_accepted_at",
            'rejection_reason' => "VARCHAR(500) NULL AFTER reviewed_at",
        ];
        foreach ($newColumns as $name => $definition) {
            if (!isset($columns[$name])) {
                $pdo->exec("ALTER TABLE withdrawals ADD COLUMN {$name} {$definition}");
            }
        }
    }

    private static function availableBalance(PDO $pdo, int $companyId): float
    {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations WHERE seller_company_id = ? AND status = 'concluded'");
        $stmt->execute([$companyId]);
        $earned = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE company_id = ? AND status IN ('pending', 'completed')");
        $stmt->execute([$companyId]);
        return max(0, $earned - (float) $stmt->fetchColumn());
    }

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

        $company_id = self::companyId();

        if (!$company_id) {
            header("Location: /re.source/login");
            exit();
        }

        try {
            self::ensureWithdrawalSchema($pdo);

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

            $saldo_disponivel = self::availableBalance($pdo, $company_id);

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

    public static function saque(): void
    {
        global $pdo;
        $companyId = self::companyId();
        if (!$companyId) {
            header('Location: /re.source/login');
            exit;
        }

        try {
            self::ensureWithdrawalSchema($pdo);
            $stmt = $pdo->prepare('SELECT nome_fantasia, razao_social, cnpj FROM companies WHERE id = ?');
            $stmt->execute([$companyId]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $availableBalance = self::availableBalance($pdo, $companyId);
            $stmt = $pdo->prepare("SELECT amount, status, created_at FROM withdrawals WHERE company_id = ? ORDER BY created_at DESC LIMIT 3");
            $stmt->execute([$companyId]);
            $recentWithdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['saque_msg'] = 'Não foi possível carregar a solicitação de saque.';
            $_SESSION['saque_tipo'] = 'error';
            header('Location: /re.source/estatisticas');
            exit;
        }

        $_SESSION['withdrawal_csrf'] = bin2hex(random_bytes(32));
        view('estatisticas/saque', [
            'titulo_pagina' => 'Solicitar saque — Re.Source',
            'company' => $company,
            'availableBalance' => $availableBalance,
            'recentWithdrawals' => $recentWithdrawals,
            'csrfToken' => $_SESSION['withdrawal_csrf'],
        ]);
    }

    public static function processarSaque(): void
    {
        global $pdo;
        $companyId = self::companyId();
        if (!$companyId || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /re.source/estatisticas/saque');
            exit;
        }

        $csrfToken = (string) ($_POST['csrf_token'] ?? '');
        if (!$csrfToken || !hash_equals($_SESSION['withdrawal_csrf'] ?? '', $csrfToken)) {
            $_SESSION['saque_form_error'] = 'Sua sessão expirou. Revise os dados e tente novamente.';
            header('Location: /re.source/estatisticas/saque');
            exit;
        }

        $amount = (float) str_replace(',', '.', (string) ($_POST['valor_saque'] ?? '0'));
        $pixKeyType = trim((string) ($_POST['pix_key_type'] ?? ''));
        $pixKey = trim((string) ($_POST['chave_pix'] ?? ''));
        $holderName = trim((string) ($_POST['titular_nome'] ?? ''));
        $holderDocument = preg_replace('/\D/', '', (string) ($_POST['titular_documento'] ?? ''));
        $requestNote = trim((string) ($_POST['observacao'] ?? ''));
        $acceptedTerms = isset($_POST['aceite_termos']);
        $errors = [];

        if ($amount < 10) $errors[] = 'O valor mínimo para saque é R$ 10,00.';
        if (!in_array($pixKeyType, ['cnpj', 'cpf', 'email', 'phone', 'random'], true)) $errors[] = 'Selecione o tipo da chave PIX.';
        if ($pixKey === '' || mb_strlen($pixKey) > 255) $errors[] = 'Informe uma chave PIX válida.';
        if (in_array($pixKeyType, ['cpf', 'cnpj', 'phone'], true)) {
            $numericPixKey = preg_replace('/\D/', '', $pixKey);
            $validLength = match ($pixKeyType) {
                'cpf' => strlen($numericPixKey) === 11,
                'cnpj' => strlen($numericPixKey) === 14,
                'phone' => in_array(strlen($numericPixKey), [10, 11], true),
                default => false,
            };
            if (!$validLength) {
                $errors[] = 'A chave PIX não possui a quantidade correta de números.';
            } else {
                $pixKey = $numericPixKey;
            }
        } elseif ($pixKeyType === 'email' && !filter_var($pixKey, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail válido para a chave PIX.';
        }
        if (mb_strlen($holderName) < 3 || mb_strlen($holderName) > 150) $errors[] = 'Informe o nome completo do titular.';
        if (!in_array(strlen($holderDocument), [11, 14], true)) $errors[] = 'Informe um CPF ou CNPJ válido para o titular.';
        if (mb_strlen($requestNote) > 500) $errors[] = 'A observação deve ter no máximo 500 caracteres.';
        if (!$acceptedTerms) $errors[] = 'É necessário aceitar os termos da solicitação.';

        if ($errors) {
            $_SESSION['saque_form_error'] = implode(' ', $errors);
            $_SESSION['saque_old'] = $_POST;
            header('Location: /re.source/estatisticas/saque');
            exit;
        }

        try {
            self::ensureWithdrawalSchema($pdo);
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT id FROM companies WHERE id = ? FOR UPDATE');
            $lock->execute([$companyId]);
            $availableBalance = self::availableBalance($pdo, $companyId);
            if ($amount > $availableBalance) {
                throw new DomainException('O valor solicitado excede o saldo disponível.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO withdrawals
                    (company_id, amount, pix_key, pix_key_type, account_holder_name,
                     account_holder_document, request_note, terms_accepted_at, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 'pending')
            ");
            $stmt->execute([$companyId, $amount, $pixKey, $pixKeyType, $holderName, $holderDocument, $requestNote ?: null]);
            $pdo->commit();

            unset($_SESSION['withdrawal_csrf'], $_SESSION['saque_old']);
            $_SESSION['saque_msg'] = 'Solicitação enviada para análise. Nossa equipe validará os dados antes de aprovar a transferência.';
            $_SESSION['saque_tipo'] = 'success';
            header('Location: /re.source/estatisticas');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['saque_form_error'] = $e instanceof DomainException
                ? $e->getMessage()
                : 'Não foi possível enviar a solicitação. Tente novamente.';
            $_SESSION['saque_old'] = $_POST;
            header('Location: /re.source/estatisticas/saque');
            exit;
        }
    }
}
