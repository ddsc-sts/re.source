<?php

require_once __DIR__ . '/../Middleware/AdminAuth.php';

class AdminController
{
    public static function dashboard(): void
    {
        global $pdo;

        $metrics         = self::getMetrics($pdo);
        $recentCompanies = self::getRecentCompanies($pdo);
        $recentActivity  = self::getRecentActivity($pdo);
        $esgIndicators   = self::getEsgIndicators($pdo);
        $volumeChart     = self::getVolumeChart($pdo);
        $chartStats      = self::getChartStats($pdo, $volumeChart);
        $heroStats       = self::getHeroStats($pdo);

        $user = AdminAuth::user();

        require_once __DIR__ . '/../Views/dashboard/admin/dashboard.php';
    }

    // ── Métricas dos cards ──────────────────────────────────────────────

    private static function getMetrics($pdo): array
    {
        try {
            $empresas  = (int)$pdo->query("SELECT COUNT(*) FROM companies WHERE status = 'active'")->fetchColumn();
            $anuncios  = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn();
            $pendentes = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'draft' AND deleted_at IS NULL")->fetchColumn();
            $negs      = (int)$pdo->query("SELECT COUNT(*) FROM negotiations WHERE status = 'concluded'")->fetchColumn();
            $chamados  = (int)$pdo->query("SELECT COUNT(*) FROM negotiations WHERE status = 'open'")->fetchColumn();
            $saquesPendentes = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
            $valorPendente = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
            $entregasAtivas = (int)$pdo->query("SELECT COUNT(*) FROM freights WHERE status IN ('contracted','preparing','in_transit','out_for_delivery')")->fetchColumn();

            $gmv = (float)$pdo->query("
                SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations
                WHERE status = 'concluded'
                  AND MONTH(concluded_at) = MONTH(NOW()) AND YEAR(concluded_at) = YEAR(NOW())
            ")->fetchColumn();

            $co2kg = (float)$pdo->query("
                SELECT COALESCE(SUM(CASE WHEN l.unit='ton' THEN n.proposed_quantity*1000 ELSE n.proposed_quantity END), 0) * 2.5
                FROM negotiations n INNER JOIN listings l ON l.id=n.listing_id
                WHERE n.status = 'concluded'
                  AND MONTH(n.concluded_at) = MONTH(NOW()) AND YEAR(n.concluded_at) = YEAR(NOW())
            ")->fetchColumn();

            // Mês anterior — para calcular deltas
            $emp_prev  = (int)$pdo->query("SELECT COUNT(*) FROM companies WHERE status = 'active' AND MONTH(created_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(NOW() - INTERVAL 1 MONTH)")->fetchColumn();
            $anun_prev = (int)$pdo->query("SELECT COUNT(*) FROM listings  WHERE status = 'active' AND deleted_at IS NULL AND MONTH(created_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(NOW() - INTERVAL 1 MONTH)")->fetchColumn();
            $neg_prev  = (int)$pdo->query("SELECT COUNT(*) FROM negotiations WHERE status = 'concluded' AND MONTH(concluded_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(concluded_at) = YEAR(NOW() - INTERVAL 1 MONTH)")->fetchColumn();
            $gmv_prev  = (float)$pdo->query("SELECT COALESCE(SUM(proposed_total),0) FROM negotiations WHERE status='concluded' AND MONTH(concluded_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(concluded_at)=YEAR(NOW()-INTERVAL 1 MONTH)")->fetchColumn();
            $co2_prev  = (float)$pdo->query("SELECT COALESCE(SUM(CASE WHEN l.unit='ton' THEN n.proposed_quantity*1000 ELSE n.proposed_quantity END),0)*2.5 FROM negotiations n INNER JOIN listings l ON l.id=n.listing_id WHERE n.status='concluded' AND MONTH(n.concluded_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(n.concluded_at)=YEAR(NOW()-INTERVAL 1 MONTH)")->fetchColumn();
            $cham_prev = (int)$pdo->query("SELECT COUNT(*) FROM negotiations WHERE status='open' AND MONTH(created_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(created_at)=YEAR(NOW()-INTERVAL 1 MONTH)")->fetchColumn();

            return [
                'empresas_ativas'      => number_format($empresas, 0, ',', '.'),
                'anuncios_publicados'  => number_format($anuncios, 0, ',', '.'),
                'negociacoes_fechadas' => number_format($negs,     0, ',', '.'),
                'gmv_mes'              => 'R$ ' . self::formatMillions($gmv),
                'co2_evitado'          => number_format($co2kg / 1000, 1, ',', '.') . ' t',
                'chamados_abertos'     => $chamados,
                'anuncios_pendentes'   => $pendentes,
                'saques_pendentes'     => $saquesPendentes,
                'saques_valor_pendente'=> $valorPendente,
                'entregas_ativas'      => $entregasAtivas,
                // Deltas calculados
                'delta_empresas'  => self::delta($empresas,  $emp_prev),
                'delta_anuncios'  => self::delta($anuncios,  $anun_prev),
                'delta_negs'      => self::delta($negs,      $neg_prev),
                'delta_gmv'       => self::delta($gmv,       $gmv_prev),
                'delta_co2'       => self::delta($co2kg,     $co2_prev),
                'delta_chamados'  => self::delta($chamados,  $cham_prev),
            ];

        } catch (\Throwable $e) {
            return [
                'empresas_ativas'      => '—', 'anuncios_publicados'  => '—',
                'negociacoes_fechadas' => '—', 'gmv_mes'              => '—',
                'co2_evitado'          => '—', 'chamados_abertos'     => 0,
                'anuncios_pendentes'   => 0,
                'saques_pendentes' => 0, 'saques_valor_pendente' => 0, 'entregas_ativas' => 0,
                'delta_empresas'  => null, 'delta_anuncios' => null,
                'delta_negs'      => null, 'delta_gmv'      => null,
                'delta_co2'       => null, 'delta_chamados' => null,
            ];
        }
    }

    // ── Empresas recentes ──────────────────────────────────────────────

    private static function getRecentCompanies($pdo): array
    {
        try {
            $stmt = $pdo->query("
                SELECT
                    c.razao_social   AS name,
                    a.city           AS city,
                    a.state          AS state,
                    c.segment        AS segment,
                    c.status,
                    c.created_at,
                    COUNT(l.id)      AS total_listings
                FROM companies c
                LEFT JOIN addresses  a ON a.id = c.address_id
                LEFT JOIN listings   l ON l.company_id = c.id AND l.deleted_at IS NULL
                GROUP BY c.id, c.razao_social, a.city, a.state, c.segment, c.status, c.created_at
                ORDER BY c.created_at DESC
                LIMIT 5
            ");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $n = (int)$row['total_listings'];
                $row['volume'] = $n . ' anúncio' . ($n !== 1 ? 's' : '');
            }
            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Atividade recente ─────────────────────────────────────────────

    private static function getRecentActivity($pdo): array
    {
        try {
            $stmt = $pdo->query("
                SELECT al.action, al.entity_type, al.severity, al.created_at,
                       u.name AS user_name, c.razao_social AS company_name
                FROM audit_logs al
                LEFT JOIN users     u ON u.id = al.user_id
                LEFT JOIN companies c ON c.id = al.company_id
                ORDER BY al.created_at DESC
                LIMIT 5
            ");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($rows)) return [];

            $iconMap = [
                'company_created'       => ['icon' => 'building-2',   'color' => 'green'],
                'company_approved'      => ['icon' => 'shield-check',  'color' => 'green'],
                'company_suspended'     => ['icon' => 'ban',           'color' => 'orange'],
                'listing_created'       => ['icon' => 'tag',           'color' => 'blue'],
                'listing_approved'      => ['icon' => 'check-circle',  'color' => 'green'],
                'negotiation_opened'    => ['icon' => 'handshake',     'color' => 'blue'],
                'negotiation_concluded' => ['icon' => 'handshake',     'color' => 'green'],
                'user_login'            => ['icon' => 'log-in',        'color' => 'gray'],
                'user_registered'       => ['icon' => 'user-plus',     'color' => 'blue'],
            ];

            return array_map(function ($row) use ($iconMap) {
                $meta  = $iconMap[$row['action']] ?? ['icon' => 'activity', 'color' => 'gray'];
                $actor = $row['company_name'] ?? $row['user_name'] ?? 'Sistema';
                return [
                    'icon'  => $meta['icon'],
                    'color' => $meta['color'],
                    'title' => ucfirst(str_replace('_', ' ', $row['action'])),
                    'desc'  => $actor . ($row['entity_type'] ? ' · ' . $row['entity_type'] : ''),
                    'time'  => self::timeAgo($row['created_at']),
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Indicadores ESG ───────────────────────────────────────────────

    private static function getEsgIndicators($pdo): array
    {
        try {
            $verified = (int)$pdo->query("
                SELECT ROUND(COUNT(CASE WHEN email_verified_at IS NOT NULL THEN 1 END)*100.0/NULLIF(COUNT(*),0))
                FROM companies
            ")->fetchColumn();

            $matchRate = (int)$pdo->query("
                SELECT ROUND(COUNT(DISTINCT n.listing_id)*100.0/NULLIF(COUNT(DISTINCT l.id),0))
                FROM listings l LEFT JOIN negotiations n ON n.listing_id = l.id
                WHERE l.deleted_at IS NULL
            ")->fetchColumn();

            $reuso = (int)$pdo->query("
                SELECT ROUND(COUNT(CASE WHEN status='concluded' THEN 1 END)*100.0/NULLIF(COUNT(*),0))
                FROM negotiations
            ")->fetchColumn();

            $cancel = (int)$pdo->query("
                SELECT ROUND(COUNT(CASE WHEN status='cancelled' THEN 1 END)*100.0/NULLIF(COUNT(*),0))
                FROM negotiations
            ")->fetchColumn();

            return [
                ['label' => 'Empresas verificadas',          'value' => $verified,  'color' => 'green'],
                ['label' => 'Taxa de match de resíduos',     'value' => $matchRate, 'color' => 'green'],
                ['label' => 'Reaproveitamento (vs. aterro)', 'value' => $reuso,     'color' => 'green'],
                ['label' => 'Cancelamentos de negociação',   'value' => $cancel,    'color' => 'red'],
            ];
        } catch (\Throwable $e) {
            return [
                ['label' => 'Empresas verificadas',          'value' => 0, 'color' => 'green'],
                ['label' => 'Taxa de match de resíduos',     'value' => 0, 'color' => 'green'],
                ['label' => 'Reaproveitamento (vs. aterro)', 'value' => 0, 'color' => 'green'],
                ['label' => 'Cancelamentos de negociação',   'value' => 0, 'color' => 'red'],
            ];
        }
    }

    // ── Volume chart ──────────────────────────────────────────────────

    private static function getVolumeChart($pdo): array
    {
        try {
            $stmt = $pdo->query("
                SELECT MONTH(concluded_at) AS mes, COALESCE(SUM(proposed_total), 0) AS total
                FROM negotiations
                WHERE status = 'concluded' AND YEAR(concluded_at) = YEAR(NOW())
                GROUP BY MONTH(concluded_at)
                ORDER BY mes
            ");
            $rows  = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $chart = array_fill(0, 12, 0);
            foreach ($rows as $r) {
                $chart[(int)$r['mes'] - 1] = (float)$r['total'];
            }
            return array_sum($chart) > 0
                ? $chart
                : array_fill(0, 12, 0);
        } catch (\Throwable $e) {
            return array_fill(0, 12, 0);
        }
    }

    // ── Estatísticas do chart (total + delta vs ano anterior) ─────────

    private static function getChartStats($pdo, array $volumeChart): array
    {
        $totalAno = array_sum($volumeChart);

        try {
            $totalAnoAnterior = (float)$pdo->query("
                SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations
                WHERE status = 'concluded' AND YEAR(concluded_at) = YEAR(NOW()) - 1
            ")->fetchColumn();

            $delta = self::delta($totalAno, $totalAnoAnterior);

            return [
                'total_fmt'  => self::formatMillions($totalAno),
                'delta'      => $delta,
            ];
        } catch (\Throwable $e) {
            return [
                'total_fmt' => self::formatMillions($totalAno),
                'delta'     => null,
            ];
        }
    }

    // ── Hero stats (novos cadastros hoje + crescimento semanal) ───────

    private static function getHeroStats($pdo): array
    {
        try {
            $novos_hoje = (int)$pdo->query("
                SELECT COUNT(*) FROM companies WHERE DATE(created_at) = CURDATE()
            ")->fetchColumn();

            // Volume negociado esta semana vs semana anterior
            $semana_atual = (float)$pdo->query("
                SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations
                WHERE status = 'concluded'
                  AND concluded_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
            ")->fetchColumn();

            $semana_anterior = (float)$pdo->query("
                SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations
                WHERE status = 'concluded'
                  AND concluded_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) + 7 DAY)
                  AND concluded_at <  DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
            ")->fetchColumn();

            $delta_semana = self::delta($semana_atual, $semana_anterior);

            return [
                'novos_hoje'   => $novos_hoje,
                'delta_semana' => $delta_semana,
            ];
        } catch (\Throwable $e) {
            return ['novos_hoje' => 0, 'delta_semana' => null];
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Retorna array ['valor' => '14%', 'direcao' => 'up'|'down'|'flat']
     * ou null se não houver dados suficientes.
     */
    private static function delta(float $atual, float $anterior): ?array
    {
        if ($anterior <= 0) return null;
        $pct = round((($atual - $anterior) / $anterior) * 100, 1);
        return [
            'valor'    => abs($pct) . '%',
            'direcao'  => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'),
        ];
    }

    private static function formatMillions(float $val): string
    {
        if ($val >= 1_000_000) return number_format($val / 1_000_000, 2, ',', '.') . ' mi';
        if ($val >= 1_000)     return number_format($val / 1_000,     1, ',', '.') . ' mil';
        return number_format($val, 2, ',', '.');
    }

    private static function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)    return 'agora';
        if ($diff < 3600)  return (int)($diff / 60) . ' min';
        if ($diff < 86400) return (int)($diff / 3600) . ' h';
        return (int)($diff / 86400) . ' d';
    }

    private static function mockActivity(): array
    {
        return [
            ['icon' => 'building-2',    'color' => 'green',  'title' => 'Nova empresa verificada', 'desc' => 'MetalForma S.A. concluiu o KYC',       'time' => 'agora'],
            ['icon' => 'handshake',     'color' => 'blue',   'title' => 'Negociação iniciada',      'desc' => 'Lote de aparas de PEAD',               'time' => '18 min'],
            ['icon' => 'check-circle',  'color' => 'green',  'title' => 'Anúncio publicado',        'desc' => 'Sucata de alumínio 6061 (1,8 t)',      'time' => '1 h'],
            ['icon' => 'truck',         'color' => 'orange', 'title' => 'Frete contratado',         'desc' => 'Rota Campinas → Sorocaba',             'time' => '2 h'],
            ['icon' => 'message-circle','color' => 'gray',   'title' => 'Mensagem enviada',         'desc' => 'Negociação #4821 — disputa resolvida', 'time' => '4 h'],
        ];
    }

    public static function anuncios(): void
    {
        global $pdo;

        $user = AdminAuth::user();

        $metrics = self::getMetrics($pdo);

        $stmt = $pdo->query("
            SELECT
                l.id,
                l.title,
                l.type,
                l.price,
                l.status,
                l.created_at,
                u.name AS usuario

            FROM listings l

            LEFT JOIN users u
                ON l.created_by_user_id = u.id

            WHERE l.deleted_at IS NULL

            ORDER BY l.created_at DESC
        ");

        $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/dashboard/admin/anuncios.php';
    }

    public static function negociacoes(): void
    {
        global $pdo;

        $user = AdminAuth::user();
        $metrics = self::getMetrics($pdo);

        $allowedStatuses = [
            'open', 'proposal_sent', 'buyer_accepted', 'seller_accepted', 'accepted',
            'awaiting_freight', 'shipping', 'delivered', 'concluded', 'cancelled',
        ];
        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        if (!in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = '';
        }
        $search = trim((string) ($_GET['q'] ?? ''));
        $where = [];
        $params = [];
        if ($statusFilter !== '') {
            $where[] = 'n.status = ?';
            $params[] = $statusFilter;
        }
        if ($search !== '') {
            $where[] = '(comprador.razao_social LIKE ? OR vendedor.razao_social LIKE ? OR l.title LIKE ? OR n.protocol_number LIKE ?)';
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term, $term);
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $negotiationMetrics = $pdo->query(
            "SELECT COUNT(*) AS total,
                    SUM(status IN ('open','proposal_sent','buyer_accepted','seller_accepted')) AS em_andamento,
                    SUM(status IN ('accepted','awaiting_freight','shipping','delivered','concluded')) AS acordos,
                    SUM(status = 'cancelled') AS canceladas,
                    COALESCE(SUM(CASE WHEN status IN ('accepted','awaiting_freight','shipping','delivered','concluded') THEN proposed_total ELSE 0 END), 0) AS volume
             FROM negotiations"
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = $pdo->prepare("
            SELECT
                n.id,
                n.protocol_number,
                n.proposed_quantity,
                n.proposed_price,
                n.proposed_total,
                n.status,
                n.created_at,
                n.updated_at,
                n.agreement_at,
                n.cancel_reason,

                comprador.razao_social AS comprador,
                comprador.nome_fantasia AS comprador_fantasia,
                vendedor.razao_social AS vendedor,
                vendedor.nome_fantasia AS vendedor_fantasia,

                l.title AS anuncio,
                l.unit,
                p.id AS proposal_id,
                p.quantity,
                p.unit_price,
                p.total_price,
                p.delivery_deadline,
                p.responsible_for_freight,
                p.notes,
                p.status AS proposal_status,
                p.buyer_accepted_at,
                p.seller_accepted_at,
                p.refusal_reason,
                p.cancel_reason AS proposal_cancel_reason

            FROM negotiations n

            LEFT JOIN companies comprador
                ON n.buyer_company_id = comprador.id

            LEFT JOIN companies vendedor
                ON n.seller_company_id = vendedor.id

            LEFT JOIN listings l
                ON n.listing_id = l.id

            LEFT JOIN proposals p
                ON p.id = (
                    SELECT p2.id FROM proposals p2
                    WHERE p2.negotiation_id = n.id
                    ORDER BY p2.id DESC LIMIT 1
                )

            {$whereSql}
            ORDER BY COALESCE(n.agreement_at, n.updated_at, n.created_at) DESC
        ");

        $stmt->execute($params);

        $negociacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/dashboard/admin/negociacoes.php';
    }

    public static function logistica(): void
    {
        $user = AdminAuth::user();

        $metrics = self::getMetrics($GLOBALS['pdo']);

        require_once __DIR__ . '/../Views/dashboard/admin/logistica.php';
    }

    public static function impacto(): void
    {
        global $pdo;

        $user = AdminAuth::user();

        $metrics = self::getMetrics($pdo);

        // KG reaproveitados
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(l.quantity),0) total
            FROM negotiations n
            INNER JOIN listings l
                ON l.id = n.listing_id
            WHERE n.status = 'concluded'
        ");
        $totalKg = $stmt->fetchColumn();

        // Valor movimentado
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(proposed_total),0)
            FROM negotiations
            WHERE status = 'concluded'
        ");
        $valorMovimentado = $stmt->fetchColumn();

        // Negociações concluídas
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM negotiations
            WHERE status = 'concluded'
        ");
        $negociacoesConcluidas = $stmt->fetchColumn();

        // Empresas ativas
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM companies
            WHERE status = 'active'
        ");
        $empresasAtivas = $stmt->fetchColumn();

        // Categorias ESG
        $stmt = $pdo->query("
            SELECT
                c.name,
                SUM(l.quantity) AS total
            FROM negotiations n
            INNER JOIN listings l
                ON l.id = n.listing_id
            INNER JOIN categories c
                ON c.id = l.category_id
            WHERE n.status = 'concluded'
            GROUP BY c.id
            ORDER BY total DESC
        ");

        $categoriasESG = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Evolução mensal
        $stmt = $pdo->query("
            SELECT
                DATE_FORMAT(n.concluded_at,'%Y-%m') mes,
                SUM(l.quantity) kg
            FROM negotiations n
            INNER JOIN listings l
                ON l.id = n.listing_id
            WHERE n.status = 'concluded'
            GROUP BY mes
            ORDER BY mes
        ");

        $evolucaoMensal = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/dashboard/admin/impacto.php';
    }

    public static function suporte(): void
    {
        global $pdo;
        $user = AdminAuth::user();

        $metrics = self::getMetrics($pdo);
        $supportSummary = [
            'pending_companies' => (int) $pdo->query("SELECT COUNT(*) FROM companies WHERE status IN ('pending','changes_requested')")->fetchColumn(),
            'open_negotiations' => (int) $pdo->query("SELECT COUNT(*) FROM negotiations WHERE status NOT IN ('concluded','cancelled')")->fetchColumn(),
            'active_deliveries' => (int) $pdo->query("SELECT COUNT(*) FROM freights WHERE status IN ('contracted','preparing','in_transit','out_for_delivery')")->fetchColumn(),
            'pending_withdrawals' => (int) $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn(),
        ];
        $supportQueue = $pdo->query(
            "SELECT n.id, n.status, n.created_at, l.title,
                    buyer.nome_fantasia AS buyer_name, seller.nome_fantasia AS seller_name
             FROM negotiations n
             INNER JOIN listings l ON l.id = n.listing_id
             INNER JOIN companies buyer ON buyer.id = n.buyer_company_id
             INNER JOIN companies seller ON seller.id = n.seller_company_id
             WHERE n.status NOT IN ('concluded','cancelled')
             ORDER BY n.updated_at DESC LIMIT 12"
        )->fetchAll(PDO::FETCH_ASSOC);
        $supportActivity = $pdo->query(
            "SELECT action, severity, entity_type, entity_id, created_at
             FROM audit_logs WHERE severity IN ('warning','critical')
             ORDER BY created_at DESC LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/dashboard/admin/suporte.php';
    }

    public static function configuracoes_admin(): void
    {
        global $pdo;
        if (!AdminAuth::can('view_settings')) {
            http_response_code(403);
            exit('Acesso negado.');
        }
        $user = AdminAuth::user();

        $metrics = self::getMetrics($pdo);
        $adminSettings = [];
        try {
            foreach ($pdo->query('SELECT setting_key, setting_value FROM system_settings') as $row) {
                $adminSettings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            error_log('Tabela system_settings indisponível: ' . $e->getMessage());
        }

        require_once __DIR__ . '/../Views/dashboard/admin/configuracoes_admin.php';
    }

    public static function salvarConfiguracoesAdmin(): void
    {
        global $pdo;
        if (!AdminAuth::can('view_settings')) {
            http_response_code(403);
            exit('Acesso negado.');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_validate()) {
            flash('error', 'A sessão do formulário expirou.');
            redirect_to('/admin/configuracoes');
        }

        $definitions = [
            'platform_name' => 100,
            'support_email' => 190,
            'support_whatsapp' => 30,
            'maintenance_message' => 500,
            'demo_mode' => 1,
        ];

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO system_settings (setting_key, setting_value, updated_by_user_id)
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
                 updated_by_user_id = VALUES(updated_by_user_id), updated_at = CURRENT_TIMESTAMP'
            );
            foreach ($definitions as $key => $maxLength) {
                $value = $key === 'demo_mode' ? (isset($_POST[$key]) ? '1' : '0') : trim((string) ($_POST[$key] ?? ''));
                $value = mb_substr($value, 0, $maxLength);
                if ($key === 'support_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new DomainException('Informe um e-mail de suporte válido.');
                }
                $stmt->execute([$key, $value, (int) (AdminAuth::user()['id'] ?? 0)]);
            }
            $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, severity, entity_type, new_values_json, ip_address, user_agent)
                 VALUES (?, 'ADMIN_SETTINGS_UPDATED', 'info', 'system_settings', ?, ?, ?)"
            )->execute([
                (int) (AdminAuth::user()['id'] ?? 0),
                json_encode(array_keys($definitions), JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
            $pdo->commit();
            flash('success', 'Configurações administrativas salvas.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $e instanceof DomainException ? $e->getMessage() : 'Não foi possível salvar as configurações. Execute o schema atualizado.');
        }
        redirect_to('/admin/configuracoes');
    }

    public static function empresas(): void
    {
        global $pdo;

        $user = AdminAuth::user();
        $metrics = self::getMetrics($pdo);

        $companyStatusCounts = array_fill_keys(
            ['pending', 'changes_requested', 'active', 'suspended', 'rejected', 'inactive'],
            0
        );
        foreach ($pdo->query('SELECT status, COUNT(*) AS total FROM companies GROUP BY status') as $row) {
            $companyStatusCounts[$row['status']] = (int) $row['total'];
        }

        $allowedStatuses = ['pending', 'changes_requested', 'active', 'suspended', 'rejected', 'inactive'];
        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        if (!in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = '';
        }
        $search = trim((string) ($_GET['q'] ?? ''));
        $where = [];
        $params = [];

        if ($statusFilter !== '') {
            $where[] = 'c.status = ?';
            $params[] = $statusFilter;
        }
        if ($search !== '') {
            $where[] = '(c.razao_social LIKE ? OR c.nome_fantasia LIKE ? OR c.cnpj LIKE ? OR c.email LIKE ?)';
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term, $term);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("
            SELECT
                c.id,
                c.nome_fantasia,
                c.razao_social,
                c.cnpj,
                c.slug,
                c.email,
                c.phone,
                c.responsible_name,
                c.segment,
                c.status,
                c.review_notes,
                c.reviewed_at,
                c.created_at,

                a.city,
                a.state

            FROM companies c

            LEFT JOIN addresses a
                ON c.address_id = a.id

            {$whereSql}
            ORDER BY c.created_at DESC
        ");

        $stmt->execute($params);

        $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/dashboard/admin/empresas.php';
    }

    public static function aprovarEmpresa(): void
    {
        self::processCompanyAction('approve');
    }

    public static function solicitarCorrecaoEmpresa(): void
    {
        self::processCompanyAction('request_changes');
    }

    public static function rejeitarEmpresa(): void
    {
        self::processCompanyAction('reject');
    }

    public static function suspenderEmpresa(): void
    {
        self::processCompanyAction('suspend');
    }

    public static function reativarEmpresa(): void
    {
        self::processCompanyAction('reactivate');
    }

    private static function processCompanyAction(string $action): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        $definitions = [
            'approve' => [
                'permission' => 'company_approve',
                'allowed' => ['pending'],
                'target' => 'active',
                'audit' => 'COMPANY_APPROVED',
                'severity' => 'info',
                'notification' => 'account_approved',
                'title' => 'Empresa aprovada',
                'message' => 'Empresa aprovada e acesso completo liberado.',
                'requires_reason' => false,
            ],
            'request_changes' => [
                'permission' => 'company_approve',
                'allowed' => ['pending'],
                'target' => 'changes_requested',
                'audit' => 'COMPANY_CHANGES_REQUESTED',
                'severity' => 'warning',
                'notification' => 'account_changes_requested',
                'title' => 'Correções solicitadas no cadastro',
                'message' => 'A solicitação de correção foi enviada para a empresa.',
                'requires_reason' => true,
            ],
            'reject' => [
                'permission' => 'company_approve',
                'allowed' => ['pending', 'changes_requested'],
                'target' => 'rejected',
                'audit' => 'COMPANY_REJECTED',
                'severity' => 'warning',
                'notification' => 'account_rejected',
                'title' => 'Cadastro rejeitado',
                'message' => 'Cadastro rejeitado e acesso bloqueado.',
                'requires_reason' => true,
            ],
            'suspend' => [
                'permission' => 'company_suspend',
                'allowed' => ['active'],
                'target' => 'suspended',
                'audit' => 'COMPANY_SUSPENDED',
                'severity' => 'warning',
                'notification' => 'account_suspended',
                'title' => 'Empresa suspensa',
                'message' => 'Empresa suspensa e acesso operacional bloqueado.',
                'requires_reason' => true,
            ],
            'reactivate' => [
                'permission' => 'company_suspend',
                'allowed' => ['suspended'],
                'target' => 'active',
                'audit' => 'COMPANY_REACTIVATED',
                'severity' => 'info',
                'notification' => 'account_reactivated',
                'title' => 'Empresa reativada',
                'message' => 'Empresa reativada e acesso restaurado.',
                'requires_reason' => false,
            ],
        ];

        $definition = $definitions[$action] ?? null;
        if (!$definition) {
            http_response_code(400);
            exit('Ação administrativa inválida.');
        }
        if (!AdminAuth::can($definition['permission'])) {
            http_response_code(403);
            exit('Você não possui permissão para executar esta ação.');
        }
        if (!csrf_validate()) {
            flash('error', 'A sessão do formulário expirou. Tente novamente.');
            redirect_to('/admin/empresas');
        }

        $companyId = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if (!$companyId) {
            flash('error', 'Empresa inválida.');
            redirect_to('/admin/empresas');
        }
        if ($definition['requires_reason'] && mb_strlen($reason) < 10) {
            flash('error', 'Informe um motivo com pelo menos 10 caracteres.');
            redirect_to('/admin/empresas');
        }
        if (mb_strlen($reason) > 1000) {
            flash('error', 'O motivo deve ter no máximo 1.000 caracteres.');
            redirect_to('/admin/empresas');
        }

        global $pdo;
        $adminUser = AdminAuth::user();

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT status, review_notes FROM companies WHERE id = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$companyId]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$company) {
                throw new DomainException('Empresa não encontrada.');
            }
            if (!in_array($company['status'], $definition['allowed'], true)) {
                throw new DomainException('O status atual da empresa não permite esta ação.');
            }

            $target = $definition['target'];
            $adminId = (int) $adminUser['id'];
            $reviewNote = $reason !== '' ? $reason : null;

            if ($action === 'approve') {
                $sql = "UPDATE companies SET status = 'active', approved_at = NOW(),
                        approved_by_user_id = ?, review_notes = NULL, reviewed_at = NOW(),
                        reviewed_by_user_id = ?, suspended_at = NULL WHERE id = ?";
                $values = [$adminId, $adminId, $companyId];
            } elseif ($action === 'reactivate') {
                $sql = "UPDATE companies SET status = 'active', suspended_at = NULL,
                        review_notes = NULL, reviewed_at = NOW(), reviewed_by_user_id = ? WHERE id = ?";
                $values = [$adminId, $companyId];
            } elseif ($action === 'suspend') {
                $sql = "UPDATE companies SET status = 'suspended', suspended_at = NOW(),
                        review_notes = ?, reviewed_at = NOW(), reviewed_by_user_id = ? WHERE id = ?";
                $values = [$reviewNote, $adminId, $companyId];
            } else {
                $sql = 'UPDATE companies SET status = ?, review_notes = ?, reviewed_at = NOW(), reviewed_by_user_id = ? WHERE id = ?';
                $values = [$target, $reviewNote, $adminId, $companyId];
            }
            $pdo->prepare($sql)->execute($values);

            $oldValues = ['status' => $company['status'], 'review_notes' => $company['review_notes']];
            $newValues = ['status' => $target, 'review_notes' => $reviewNote];
            $stmt = $pdo->prepare(
                'INSERT INTO audit_logs
                    (user_id, company_id, action, severity, entity_type, entity_id,
                     old_values_json, new_values_json, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, \'company\', ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $adminId,
                $companyId,
                $definition['audit'],
                $definition['severity'],
                $companyId,
                json_encode($oldValues, JSON_UNESCAPED_UNICODE),
                json_encode($newValues, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            $notificationBody = $reason !== ''
                ? $definition['title'] . ': ' . $reason
                : $definition['message'];
            $stmt = $pdo->prepare(
                'INSERT INTO notifications (company_id, type, title, body, data_json)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $companyId,
                $definition['notification'],
                $definition['title'],
                $notificationBody,
                json_encode(['status' => $target], JSON_UNESCAPED_UNICODE),
            ]);

            $pdo->commit();
            flash('success', $definition['message']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', $e instanceof DomainException ? $e->getMessage() : 'Não foi possível atualizar a empresa.');
        }

        redirect_to('/admin/empresas');
    }

}
