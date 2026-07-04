<?php

class EstatisticasController
{
    private static function companyId(): int { return (int) ($_SESSION['user']['company_id'] ?? 0); }
    private static function userId(): int { return (int) ($_SESSION['user']['id'] ?? 0); }

    public static function index(): void
    {
        global $pdo;
        $companyId = self::companyId();
        $stmt = $pdo->prepare('SELECT razao_social, nome_fantasia, logo_url FROM companies WHERE id = ?');
        $stmt->execute([$companyId]);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $nome_empresa = $empresa['nome_fantasia'] ?: ($empresa['razao_social'] ?? 'Empresa');
        $logo_url = $empresa['logo_url'] ?? null;
        $balances = FinanceService::balances($pdo, $companyId);

        $stmt = $pdo->prepare("SELECT COUNT(*) total, COALESCE(SUM(views_count),0) views FROM listings
            WHERE company_id = ? AND status = 'active' AND deleted_at IS NULL");
        $stmt->execute([$companyId]);
        $listingStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $total_anuncios = (int) ($listingStats['total'] ?? 0);
        $total_views = (int) ($listingStats['views'] ?? 0);

        $stmt = $pdo->prepare("SELECT DATE(vh.created_at) data_view, COUNT(*) views_dia FROM views_history vh
            INNER JOIN listings l ON l.id = vh.listing_id WHERE l.company_id = ?
            AND vh.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY DATE(vh.created_at)");
        $stmt->execute([$companyId]);
        $views = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $labelsViews = $dataViews = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labelsViews[] = date('d/m', strtotime($date));
            $dataViews[] = (int) ($views[$date] ?? 0);
        }

        $stmt = $pdo->prepare("SELECT c.name, COUNT(l.id) total FROM listings l INNER JOIN categories c ON c.id = l.category_id
            WHERE l.company_id = ? AND l.deleted_at IS NULL GROUP BY c.id, c.name");
        $stmt->execute([$companyId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $catLabels = $categories ? array_column($categories, 'name') : ['Sem anúncios'];
        $catData = $categories ? array_map('intval', array_column($categories, 'total')) : [1];

        $stmt = $pdo->prepare("SELECT n.updated_at data, IF(n.buyer_company_id = ?, 'Compra', 'Venda') tipo,
            l.title material, n.proposed_total valor, n.status FROM negotiations n INNER JOIN listings l ON l.id = n.listing_id
            WHERE n.buyer_company_id = ? OR n.seller_company_id = ? ORDER BY n.updated_at DESC LIMIT 5");
        $stmt->execute([$companyId, $companyId, $companyId]);
        $negociacoes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT amount, method, COALESCE(pix_key, CONCAT(bank_name, ' / ', agency, ' / ', account_number)) destination,
            status, created_at, rejection_reason FROM withdrawals WHERE company_id = ? ORDER BY id DESC LIMIT 8");
        $stmt->execute([$companyId]);
        $historico_saques = $stmt->fetchAll(PDO::FETCH_ASSOC);

        view('estatisticas/index', compact('empresa','nome_empresa','logo_url','total_anuncios','total_views',
            'labelsViews','dataViews','catLabels','catData','negociacoes_recentes','historico_saques') + [
                'saldo_disponivel' => $balances['available'], 'saldo_futuro' => $balances['future'],
                'saldo_reservado' => $balances['reserved'], 'saldo_sacado' => $balances['withdrawn'],
            ]);
    }

    public static function saque(): void
    {
        global $pdo;
        $companyId = self::companyId();
        $stmt = $pdo->prepare('SELECT nome_fantasia, razao_social, cnpj FROM companies WHERE id = ?');
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $balances = FinanceService::balances($pdo, $companyId);
        $stmt = $pdo->prepare('SELECT amount, method, status, created_at FROM withdrawals WHERE company_id = ? ORDER BY id DESC LIMIT 5');
        $stmt->execute([$companyId]);
        $recentWithdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION['withdrawal_request_token'] = bin2hex(random_bytes(32));
        view('estatisticas/saque', [
            'titulo_pagina' => 'Solicitar saque — Re.Source', 'company' => $company,
            'availableBalance' => $balances['available'], 'balances' => $balances,
            'recentWithdrawals' => $recentWithdrawals, 'csrfToken' => csrf_token(),
            'requestToken' => $_SESSION['withdrawal_request_token'],
        ]);
    }

    public static function processarSaque(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_validate()) {
            flash('error', 'Sessão expirada. Recarregue o formulário.'); redirect_to('/estatisticas/saque');
        }
        $method = (string) ($_POST['method'] ?? 'pix');
        $amount = round((float) str_replace(',', '.', (string) ($_POST['valor_saque'] ?? 0)), 2);
        $holderName = trim((string) ($_POST['titular_nome'] ?? ''));
        $holderDocument = preg_replace('/\D/', '', (string) ($_POST['titular_documento'] ?? ''));
        $token = (string) ($_POST['request_token'] ?? '');
        $errors = [];
        if ($amount < 10) $errors[] = 'O valor mínimo é R$ 10,00.';
        if (!in_array($method, ['pix','ted'], true)) $errors[] = 'Selecione PIX ou TED.';
        if (mb_strlen($holderName) < 3 || !in_array(strlen($holderDocument), [11,14], true)) $errors[] = 'Revise os dados do titular.';
        if (!isset($_POST['aceite_termos'])) $errors[] = 'Aceite os termos da solicitação.';
        if ($token === '' || !hash_equals($_SESSION['withdrawal_request_token'] ?? '', $token)) $errors[] = 'Solicitação duplicada ou expirada.';

        $pixKeyType = $method === 'pix' ? trim((string) ($_POST['pix_key_type'] ?? '')) : null;
        $pixKey = $method === 'pix' ? trim((string) ($_POST['chave_pix'] ?? '')) : null;
        if ($method === 'pix') {
            if (!in_array($pixKeyType, ['cnpj','cpf','email','phone','random'], true) || $pixKey === '') $errors[] = 'Informe uma chave PIX válida.';
            if ($pixKeyType === 'email' && !filter_var($pixKey, FILTER_VALIDATE_EMAIL)) $errors[] = 'Informe um e-mail válido.';
            if (in_array($pixKeyType, ['cnpj','cpf','phone'], true)) $pixKey = preg_replace('/\D/', '', $pixKey);
        }
        $bankCode = $method === 'ted' ? preg_replace('/\D/', '', (string) ($_POST['bank_code'] ?? '')) : null;
        $bankName = $method === 'ted' ? trim((string) ($_POST['bank_name'] ?? '')) : null;
        $agency = $method === 'ted' ? trim((string) ($_POST['agency'] ?? '')) : null;
        $account = $method === 'ted' ? trim((string) ($_POST['account_number'] ?? '')) : null;
        $digit = $method === 'ted' ? trim((string) ($_POST['account_digit'] ?? '')) : null;
        $accountType = $method === 'ted' ? (string) ($_POST['account_type'] ?? '') : null;
        if ($method === 'ted' && (!$bankCode || !$bankName || !$agency || !$account || !$digit || !in_array($accountType, ['checking','savings'], true))) $errors[] = 'Preencha todos os dados bancários da TED.';

        if ($errors) {
            $_SESSION['saque_form_error'] = implode(' ', $errors); $_SESSION['saque_old'] = $_POST;
            redirect_to('/estatisticas/saque');
        }
        global $pdo;
        try {
            FinanceService::requestWithdrawal($pdo, self::companyId(), self::userId(), [
                'amount'=>$amount,'method'=>$method,'pix_key'=>$pixKey,'pix_key_type'=>$pixKeyType,
                'bank_code'=>$bankCode,'bank_name'=>$bankName,'agency'=>$agency,'account_number'=>$account,
                'account_digit'=>$digit,'account_type'=>$accountType,'holder_name'=>$holderName,
                'holder_document'=>$holderDocument,'request_note'=>trim((string) ($_POST['observacao'] ?? '')) ?: null,
                'request_token'=>$token,
            ]);
            unset($_SESSION['withdrawal_request_token'], $_SESSION['saque_old']);
            flash('success', 'Saque reservado e enviado para análise manual.');
            redirect_to('/estatisticas');
        } catch (Throwable $error) {
            $_SESSION['saque_form_error'] = $error instanceof DomainException ? $error->getMessage() : 'Não foi possível solicitar o saque.';
            $_SESSION['saque_old'] = $_POST; redirect_to('/estatisticas/saque');
        }
    }
}
