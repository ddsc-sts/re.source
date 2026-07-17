<?php

require_once __DIR__ . '/../../config/conexao.php';

class BaseController
{
    public static function index(): void
    {
        global $pdo;

        $recentListings = self::getAnunciosRecentes($pdo);
        $companyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
        $profileNotifications = self::getProfileNotifications($pdo, $companyId);
        $baseCategories = self::getBaseCategories($pdo);
        $marketplaceStats = self::getMarketplaceStats($pdo);
        $dashboardOverview = self::getDashboardOverview($pdo, $companyId);

        view('dashboard/index', [
            'titulo_pagina'  => 'Re.Source — Economia Circular em Joinville',
            'recentListings' => $recentListings,
            'profileNotifications' => $profileNotifications,
            'baseCategories' => $baseCategories,
            'marketplaceStats' => $marketplaceStats,
            'dashboardOverview' => $dashboardOverview,
            'unitLabel'      => [
                'kg' => 'Kg', 'ton' => 'Ton', 'm2' => 'm²', 'm3' => 'm³',
                'unidade' => 'un.', 'litro' => 'L', 'outro' => ''
            ],
        ]);
    }

    private static function getDashboardOverview(PDO $pdo, int $companyId): array
    {
        $empty = [
            'active_listings' => 0,
            'active_negotiations' => 0,
            'completed_negotiations' => 0,
            'unread_messages' => 0,
            'unseen_notifications' => 0,
            'released_revenue' => 0.0,
            'reused_kg' => 0.0,
            'avoided_co2_kg' => 0.0,
            'monthly' => array_values(DashboardMetrics::emptyMonthlyEvolution()),
        ];

        if ($companyId <= 0) {
            return $empty;
        }

        try {
            $stmt = $pdo->prepare(
                "SELECT
                    (SELECT COUNT(*) FROM listings
                     WHERE company_id = :company_listings AND status = 'active' AND deleted_at IS NULL) AS active_listings,
                    (SELECT COUNT(*) FROM negotiations
                     WHERE (buyer_company_id = :company_active_buyer OR seller_company_id = :company_active_seller)
                       AND status NOT IN ('concluded', 'cancelled')) AS active_negotiations,
                    (SELECT COUNT(*) FROM negotiations
                     WHERE (buyer_company_id = :company_completed_buyer OR seller_company_id = :company_completed_seller)
                       AND status = 'concluded') AS completed_negotiations,
                    (SELECT COUNT(*) FROM notifications
                     WHERE company_id = :company_notifications AND is_seen = 0) AS unseen_notifications,
                    (SELECT COUNT(*)
                     FROM messages m
                     INNER JOIN negotiations n ON n.id = m.negotiation_id
                     INNER JOIN users sender ON sender.id = m.sender_user_id
                     WHERE m.read_at IS NULL
                       AND sender.company_id <> :company_sender
                       AND (n.buyer_company_id = :company_messages_buyer OR n.seller_company_id = :company_messages_seller)) AS unread_messages,
                    (SELECT COALESCE(SUM(amount), 0) FROM financial_transactions
                     WHERE company_id = :company_revenue AND type = 'sale' AND status = 'completed') AS released_revenue"
            );
            $stmt->execute([
                'company_listings' => $companyId,
                'company_active_buyer' => $companyId,
                'company_active_seller' => $companyId,
                'company_completed_buyer' => $companyId,
                'company_completed_seller' => $companyId,
                'company_notifications' => $companyId,
                'company_sender' => $companyId,
                'company_messages_buyer' => $companyId,
                'company_messages_seller' => $companyId,
                'company_revenue' => $companyId,
            ]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $impactStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(
                    CASE l.unit
                        WHEN 'ton' THEN n.proposed_quantity * 1000
                        WHEN 'kg' THEN n.proposed_quantity
                        ELSE 0
                    END
                ), 0)
                FROM negotiations n
                INNER JOIN listings l ON l.id = n.listing_id
                WHERE n.status = 'concluded'
                  AND (n.buyer_company_id = ? OR n.seller_company_id = ?)"
            );
            $impactStmt->execute([$companyId, $companyId]);
            $reusedKg = (float) $impactStmt->fetchColumn();

            return [
                'active_listings' => (int) ($summary['active_listings'] ?? 0),
                'active_negotiations' => (int) ($summary['active_negotiations'] ?? 0),
                'completed_negotiations' => (int) ($summary['completed_negotiations'] ?? 0),
                'unread_messages' => (int) ($summary['unread_messages'] ?? 0),
                'unseen_notifications' => (int) ($summary['unseen_notifications'] ?? 0),
                'released_revenue' => (float) ($summary['released_revenue'] ?? 0),
                'reused_kg' => $reusedKg,
                'avoided_co2_kg' => DashboardMetrics::avoidedCo2($reusedKg),
                'monthly' => self::getMonthlyEvolution($pdo, $companyId),
            ];
        } catch (Throwable $e) {
            error_log('Falha ao carregar resumo do dashboard: ' . $e->getMessage());
            return $empty;
        }
    }

    private static function getMonthlyEvolution(PDO $pdo, int $companyId): array
    {
        $months = DashboardMetrics::emptyMonthlyEvolution();
        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(concluded_at, '%Y-%m') AS month_key,
                    COUNT(*) AS negotiations,
                    COALESCE(SUM(CASE WHEN seller_company_id = :seller THEN proposed_total ELSE 0 END), 0) AS revenue
             FROM negotiations
             WHERE status = 'concluded'
               AND concluded_at >= DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL 5 MONTH), '%Y-%m-01')
               AND (buyer_company_id = :buyer OR seller_company_id = :company_seller)
             GROUP BY DATE_FORMAT(concluded_at, '%Y-%m')"
        );
        $stmt->execute(['seller' => $companyId, 'buyer' => $companyId, 'company_seller' => $companyId]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (string) ($row['month_key'] ?? '');
            if (isset($months[$key])) {
                $months[$key]['negotiations'] = (int) $row['negotiations'];
                $months[$key]['revenue'] = (float) $row['revenue'];
            }
        }

        return array_values($months);
    }

    private static function getBaseCategories(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query(
                "SELECT c.id, c.name, c.slug, COUNT(l.id) AS listing_count
                 FROM categories c
                 LEFT JOIN listings l ON l.category_id = c.id
                    AND l.status = 'active' AND l.deleted_at IS NULL
                 WHERE c.is_active = 1
                 GROUP BY c.id, c.name, c.slug
                 ORDER BY listing_count DESC, c.name ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Falha ao carregar categorias da Base: ' . $e->getMessage());
            return [];
        }
    }

    private static function getMarketplaceStats(PDO $pdo): array
    {
        try {
            return [
                'companies' => (int) $pdo->query("SELECT COUNT(*) FROM companies WHERE status = 'active'")->fetchColumn(),
                'listings' => (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn(),
                'negotiations' => (int) $pdo->query("SELECT COUNT(*) FROM negotiations")->fetchColumn(),
                'deliveries' => (int) $pdo->query("SELECT COUNT(*) FROM freights WHERE status = 'delivered'")->fetchColumn(),
            ];
        } catch (Throwable $e) {
            error_log('Falha ao carregar indicadores da Base: ' . $e->getMessage());
            return ['companies' => 0, 'listings' => 0, 'negotiations' => 0, 'deliveries' => 0];
        }
    }

    public static function notifications(): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');

        $companyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
        if ($companyId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sessão inválida.']);
            return;
        }

        $items = self::getProfileNotifications($pdo, $companyId, 12);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE company_id = ? AND is_seen = 0');
        $stmt->execute([$companyId]);

        echo json_encode([
            'success' => true,
            'unseen_count' => (int) $stmt->fetchColumn(),
            'notifications' => $items,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function markNotificationsRead(): void
    {
        global $pdo;
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            return;
        }
        if (!csrf_validate()) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $companyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
        $stmt = $pdo->prepare(
            'UPDATE notifications SET is_seen = 1, read_at = COALESCE(read_at, CURRENT_TIMESTAMP)
             WHERE company_id = ? AND is_seen = 0'
        );
        $stmt->execute([$companyId]);
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    private static function getProfileNotifications(PDO $pdo, int $companyId, int $limit = 5): array
    {
        if ($companyId <= 0) {
            return [];
        }

        try {
            $limit = max(1, min(20, $limit));
            $stmt = $pdo->prepare(
                "SELECT id, type, title, body, data_json, is_seen, created_at
                 FROM notifications
                 WHERE company_id = ?
                 ORDER BY created_at DESC, id DESC
                 LIMIT {$limit}"
            );
            $stmt->execute([$companyId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Falha ao carregar notificações: ' . $e->getMessage());
            return [];
        }
    }

    public static function sobre(): void
    {
        $titulo_pagina = 'Sobre Nós — Re.Source';
        view('home/sobre', [
            'titulo_pagina' => $titulo_pagina
        ]);
    }

    public static function contato(): void
    {
        $titulo_pagina = 'Contato — Re.Source';
        view('home/contato', [
            'titulo_pagina' => $titulo_pagina
        ]);
    }

    public static function termos(): void
    {
        $titulo_pagina = 'Termos de Uso — Re.Source';
        view('home/termos', [
            'titulo_pagina' => $titulo_pagina
        ]);
    }

    public static function privacidade(): void
    {
        $titulo_pagina = 'Política de Privacidade — Re.Source';
        view('home/privacidade', [
            'titulo_pagina' => $titulo_pagina
        ]);
    }

    /**
     * Proxy robusto para busca de CNPJ com cache de 24h e múltiplos fallbacks (ReceitaWS, cnpj.ws, BrasilAPI)
     */
    public static function cnpj(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');

        if (strlen($cnpj) !== 14) {
            http_response_code(400);
            echo json_encode(['error' => 'CNPJ inválido.']);
            return;
        }

        $cacheDir = ROOT_PATH . '/storage/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/' . $cnpj . '.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            echo file_get_contents($cacheFile);
            return;
        }

        $apis = [
            "https://receitaws.com.br/v1/cnpj/{$cnpj}",
            "https://publica.cnpj.ws/cnpj/{$cnpj}",
            "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}",
        ];

        $response = null;
        $httpCode = 0;

        foreach ($apis as $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Re.Source/1.0');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) break;
        }

        if ($httpCode === 200 && !empty($response)) {
            $data = json_decode($response, true);
            
            // Normaliza ReceitaWS para o mesmo padrão da BrasilAPI
            if (isset($data['nome']) && !isset($data['razao_social'])) {
                $data['razao_social'] = $data['nome'];
                $data['municipio']    = $data['municipio'] ?? '';
                $response = json_encode($data);
            }

            // Normaliza cnpj.ws
            if (isset($data['razao_social']) && isset($data['estabelecimento'])) {
                $data['municipio'] = $data['estabelecimento']['cidade']['nome'] ?? '';
                $data['uf']        = $data['estabelecimento']['estado']['sigla'] ?? '';
                $response = json_encode($data);
            }

            file_put_contents($cacheFile, $response);
        }

        http_response_code($httpCode === 0 ? 502 : $httpCode);
        echo $response ?: json_encode(['error' => 'Não foi possível consultar o CNPJ.']);
    }

    public static function conta(): void
    {
        global $pdo;
        $company_id = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $pdo->prepare("
                SELECT c.*, a.zip_code, a.street, a.number, a.complement, a.district, a.city, a.state 
                FROM companies c
                LEFT JOIN addresses a ON c.address_id = a.id
                WHERE c.id = ?
            ");
            $stmt->execute([$company_id]);
            $empresa = $stmt->fetch(\PDO::FETCH_ASSOC);

            $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE company_id = ? AND role = 'admin_company' LIMIT 1");
            $stmtUser->execute([$company_id]);
            $admin = $stmtUser->fetch(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            die("Erro ao carregar dados: " . $e->getMessage());
        }

        $titulo_pagina = 'Configurações da Conta — Re.Source';
        view('dashboard/conta', [
            'titulo_pagina' => $titulo_pagina,
            'empresa'       => $empresa,
            'admin'         => $admin
        ]);
    }

    public static function atualizarConta(): void
    {
        global $pdo;
        $company_id = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 1;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /re.source/conta");
            exit();
        }

        if (!csrf_validate()) {
            $_SESSION['error'] = 'Sua sessão expirou. Recarregue a página e tente novamente.';
            header('Location: /re.source/conta');
            exit();
        }

        $nome_fantasia    = filter_input(INPUT_POST, 'nome_fantasia', FILTER_SANITIZE_SPECIAL_CHARS);
        $razao_social     = filter_input(INPUT_POST, 'razao_social', FILTER_SANITIZE_SPECIAL_CHARS);
        $segment          = filter_input(INPUT_POST, 'segment', FILTER_SANITIZE_SPECIAL_CHARS);
        $phone            = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
        $email_comercial  = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $responsible_name = filter_input(INPUT_POST, 'responsible_name', FILTER_SANITIZE_SPECIAL_CHARS);

        $zip_code   = filter_input(INPUT_POST, 'zip_code', FILTER_SANITIZE_SPECIAL_CHARS);
        $street     = filter_input(INPUT_POST, 'street', FILTER_SANITIZE_SPECIAL_CHARS);
        $number     = filter_input(INPUT_POST, 'number', FILTER_SANITIZE_SPECIAL_CHARS);
        $complement = filter_input(INPUT_POST, 'complement', FILTER_SANITIZE_SPECIAL_CHARS);
        $district   = filter_input(INPUT_POST, 'district', FILTER_SANITIZE_SPECIAL_CHARS);
        $city       = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_SPECIAL_CHARS);
        $state      = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$email_comercial) {
            $_SESSION['error'] = "E-mail comercial inválido.";
            header("Location: /re.source/conta");
            exit();
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT address_id, logo_url FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            $empresa_atual = $stmt->fetch(\PDO::FETCH_ASSOC);
            $address_id = $empresa_atual['address_id'] ?? null;
            $logo_url = $empresa_atual['logo_url'] ?? null;

            if (isset($_FILES['logo_empresa']) && $_FILES['logo_empresa']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['logo_empresa']['tmp_name'];
                $fileName = $_FILES['logo_empresa']['name'];
                $fileSize = $_FILES['logo_empresa']['size'];
                
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png'];

                if (in_array($fileExtension, $allowedExtensions)) {
                    if ($fileSize <= 2 * 1024 * 1024) {
                        $uploadFileDir = ROOT_PATH . '/uploads/logos/';
                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0755, true);
                        }

                        $newFileName = md5(time() . $company_id) . '.' . $fileExtension;
                        $dest_path = 'uploads/logos/' . $newFileName;

                        if (move_uploaded_file($fileTmpPath, ROOT_PATH . '/' . $dest_path)) {
                            if ($logo_url && file_exists(ROOT_PATH . '/' . $logo_url)) {
                                @unlink(ROOT_PATH . '/' . $logo_url);
                            }
                            $logo_url = '/re.source/' . $dest_path;
                        }
                    } else {
                        throw new \Exception("O logotipo excede o tamanho máximo de 2MB.");
                    }
                } else {
                    throw new \Exception("Formato de imagem não suportado. Use apenas JPG, JPEG ou PNG.");
                }
            }

            if ($address_id) {
                $sqlAddress = "UPDATE addresses SET zip_code = ?, street = ?, number = ?, complement = ?, district = ?, city = ?, state = ? WHERE id = ?";
                $stmtAddress = $pdo->prepare($sqlAddress);
                $stmtAddress->execute([$zip_code, $street, $number, $complement, $district, $city, $state, $address_id]);
            } else {
                $sqlAddress = "INSERT INTO addresses (zip_code, street, number, complement, district, city, state) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtAddress = $pdo->prepare($sqlAddress);
                $stmtAddress->execute([$zip_code, $street, $number, $complement, $district, $city, $state]);
                $address_id = $pdo->lastInsertId();
            }

            $sqlCompany = "UPDATE companies SET 
                            nome_fantasia = ?, 
                            razao_social = ?, 
                            segment = ?, 
                            phone = ?, 
                            email = ?, 
                            responsible_name = ?, 
                            logo_url = ?,
                            address_id = ?
                           WHERE id = ?";
            
            $stmtCompany = $pdo->prepare($sqlCompany);
            $stmtCompany->execute([
                $nome_fantasia, 
                $razao_social, 
                $segment, 
                $phone, 
                $email_comercial, 
                $responsible_name, 
                $logo_url,
                $address_id,
                $company_id
            ]);

            $pdo->commit();
            $_SESSION['success'] = "Dados cadastrais atualizados com sucesso!";
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "Erro ao atualizar: " . $e->getMessage();
        }

        header("Location: /re.source/conta");
        exit();
    }

    public static function configuracoes(): void
    {
        global $pdo;
        $company_id = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $pdo->prepare("SELECT theme, notify_proposals, notify_chat, razao_social, nome_fantasia, logo_url FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            $company_data = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $prefs = [
                'theme' => $company_data['theme'] ?? 'light',
                'notify_proposals' => isset($company_data['notify_proposals']) ? (bool)$company_data['notify_proposals'] : true,
                'notify_chat' => isset($company_data['notify_chat']) ? (bool)$company_data['notify_chat'] : true
            ];

            $nome_empresa = !empty($company_data['nome_fantasia']) ? $company_data['nome_fantasia'] : ($company_data['razao_social'] ?? 'Minha Empresa');
            $logo_url = $company_data['logo_url'] ?? null;

        } catch (\PDOException $e) {
            $prefs = [ 'theme' => 'light', 'notify_proposals' => true, 'notify_chat' => true ];
            $nome_empresa = 'Minha Empresa';
            $logo_url = null;
        }

        $_SESSION['user_theme'] = $prefs['theme'];
        $titulo_pagina = 'Configurações do Sistema — Re.Source';
        view('dashboard/configuracoes', [
            'titulo_pagina' => $titulo_pagina,
            'prefs'         => $prefs,
            'nome_empresa'  => $nome_empresa,
            'logo_url'      => $logo_url
        ]);
    }

    public static function salvarPreferencias(): void
    {
        global $pdo;
        $company_id = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 1;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /re.source/configuracoes");
            exit();
        }

        if (!csrf_validate()) {
            $_SESSION['error'] = 'Sua sessão expirou. Recarregue a página e tente novamente.';
            header('Location: /re.source/configuracoes');
            exit();
        }

        $theme = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'system';
        $notify_proposals = isset($_POST['notify_proposals']) ? 1 : 0;
        $notify_chat      = isset($_POST['notify_chat']) ? 1 : 0;

        try {
            $sql = "UPDATE companies SET theme = ?, notify_proposals = ?, notify_chat = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$theme, $notify_proposals, $notify_chat, $company_id]);

            $_SESSION['success'] = "Preferências do sistema atualizadas!";
            $_SESSION['user_theme'] = $theme;
        } catch (\PDOException $e) {
            $_SESSION['error'] = "Erro ao salvar preferências: " . $e->getMessage();
        }

        header("Location: /re.source/configuracoes");
        exit();
    }

    public static function excluirConta(): void
    {
        global $pdo;
        $company_id = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? null;

        if (!$company_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /re.source/configuracoes");
            exit();
        }

        if (!csrf_validate()) {
            $_SESSION['error'] = 'Sua sessão expirou. Recarregue a página e tente novamente.';
            header('Location: /re.source/configuracoes');
            exit();
        }

        try {
            $pdo->beginTransaction();

            $stmtCompany = $pdo->prepare("UPDATE companies SET status = 'inactive', deactivated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtCompany->execute([$company_id]);

            $stmtUsers = $pdo->prepare("UPDATE users SET is_active = 0, deleted_at = CURRENT_TIMESTAMP WHERE company_id = ?");
            $stmtUsers->execute([$company_id]);

            $stmtListings = $pdo->prepare("UPDATE listings SET status = 'paused', deleted_at = CURRENT_TIMESTAMP WHERE company_id = ?");
            $stmtListings->execute([$company_id]);

            try {
                $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (company_id, action, severity, ip_address, user_agent) VALUES (?, 'ACCOUNT_DEACTIVATED_BY_USER', 'critical', ?, ?)");
                $stmtAudit->execute([
                    $company_id, 
                    $_SERVER['REMOTE_ADDR'] ?? null, 
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
            } catch (\PDOException $e_audit) {
                // ignora
            }

            $pdo->commit();

            $_SESSION = array();

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), 
                    '', 
                    time() - 42000,
                    $params["path"], 
                    $params["domain"],
                    $params["secure"], 
                    $params["httponly"]
                );
            }

            session_destroy();

            header("Location: /re.source/login?account=deleted");
            exit();
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            die("Erro crítico ao processar a exclusão da conta: " . $e->getMessage());
        }
    }

    public static function negociacoes(): void
    {
        echo "Página de Negociações do Usuário (Em desenvolvimento)";
    }

    public static function logistica(): void
    {
        echo "Página de Logística do Usuário (Em desenvolvimento)";
    }

    public static function impacto(): void
    {
        echo "Página de Impacto do Usuário (Em desenvolvimento)";
    }

    public static function suporte(): void
    {
        echo "Página de Suporte do Usuário (Em desenvolvimento)";
    }

    private static function getAnunciosRecentes($pdo): array
    {
        try {
            $stmt = $pdo->query("
                SELECT
                    l.id,
                    l.title,
                    l.type,
                    l.quantity,
                    l.unit,
                    l.price,
                    l.is_negotiable,
                    l.location_city,
                    l.location_state,
                    c.nome_fantasia AS company_name,
                    cat.name AS category_name,
                    COALESCE(
                        (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1),
                        'https://images.unsplash.com/photo-1718473476174-21e7a853f5f6?w=600'
                    ) AS thumb
                FROM listings l
                INNER JOIN companies c ON c.id = l.company_id
                INNER JOIN categories cat ON cat.id = l.category_id
                WHERE l.status = 'active'
                  AND l.deleted_at IS NULL
                ORDER BY l.created_at DESC
                LIMIT 8
            ");
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
