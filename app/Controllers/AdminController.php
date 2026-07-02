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

            $gmv = (float)$pdo->query("
                SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations
                WHERE status = 'concluded'
                  AND MONTH(concluded_at) = MONTH(NOW()) AND YEAR(concluded_at) = YEAR(NOW())
            ")->fetchColumn();

            $co2kg = (float)$pdo->query("
                SELECT COALESCE(SUM(proposed_quantity), 0) * 2.5 FROM negotiations
                WHERE status = 'concluded'
                  AND MONTH(concluded_at) = MONTH(NOW()) AND YEAR(concluded_at) = YEAR(NOW())
            ")->fetchColumn();

            // Mês anterior — para calcular deltas
            $emp_prev  = (int)$pdo->query("SELECT COUNT(*) FROM companies WHERE status = 'active' AND MONTH(created_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(NOW() - INTERVAL 1 MONTH)")->fetchColumn();
            $anun_prev = (int)$pdo->query("SELECT COUNT(*) FROM listings  WHERE status = 'active' AND deleted_at IS NULL AND MONTH(created_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(NOW() - INTERVAL 1 MONTH)")->fetchColumn();
            $neg_prev  = (int)$pdo->query("SELECT COUNT(*) FROM negotiations WHERE status = 'concluded' AND MONTH(concluded_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(concluded_at) = YEAR(NOW() - INTERVAL 1 MONTH)")->fetchColumn();
            $gmv_prev  = (float)$pdo->query("SELECT COALESCE(SUM(proposed_total),0) FROM negotiations WHERE status='concluded' AND MONTH(concluded_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(concluded_at)=YEAR(NOW()-INTERVAL 1 MONTH)")->fetchColumn();
            $co2_prev  = (float)$pdo->query("SELECT COALESCE(SUM(proposed_quantity),0)*2.5 FROM negotiations WHERE status='concluded' AND MONTH(concluded_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(concluded_at)=YEAR(NOW()-INTERVAL 1 MONTH)")->fetchColumn();
            $cham_prev = (int)$pdo->query("SELECT COUNT(*) FROM negotiations WHERE status='open' AND MONTH(created_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(created_at)=YEAR(NOW()-INTERVAL 1 MONTH)")->fetchColumn();

            return [
                'empresas_ativas'      => number_format($empresas, 0, ',', '.'),
                'anuncios_publicados'  => number_format($anuncios, 0, ',', '.'),
                'negociacoes_fechadas' => number_format($negs,     0, ',', '.'),
                'gmv_mes'              => 'R$ ' . self::formatMillions($gmv),
                'co2_evitado'          => number_format($co2kg / 1000, 1, ',', '.') . ' t',
                'chamados_abertos'     => $chamados,
                'anuncios_pendentes'   => $pendentes,
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

        $stmt = $pdo->query("
            SELECT
                n.id,
                n.protocol_number,
                n.proposed_quantity,
                n.proposed_price,
                n.proposed_total,
                n.status,
                n.created_at,

                comprador.razao_social AS comprador,
                vendedor.razao_social AS vendedor,

                l.title AS anuncio

            FROM negotiations n

            LEFT JOIN companies comprador
                ON n.buyer_company_id = comprador.id

            LEFT JOIN companies vendedor
                ON n.seller_company_id = vendedor.id

            LEFT JOIN listings l
                ON n.listing_id = l.id

            ORDER BY n.created_at DESC
        ");

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
        $user = AdminAuth::user();

        $metrics = self::getMetrics($GLOBALS['pdo']);

        require_once __DIR__ . '/../Views/dashboard/admin/suporte.php';
    }

    public static function configuracoes_admin(): void
    {
        $user = AdminAuth::user();

        $metrics = self::getMetrics($GLOBALS['pdo']);

        require_once __DIR__ . '/../Views/dashboard/admin/configuracoes_admin.php';
    }

    public static function empresas(): void
    {
        global $pdo;

        $user = AdminAuth::user();
        $metrics = self::getMetrics($pdo);

        $stmt = $pdo->query("
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
                c.created_at,

                a.city,
                a.state

            FROM companies c

            LEFT JOIN addresses a
                ON c.address_id = a.id

            ORDER BY c.created_at DESC
        ");

        $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../Views/dashboard/admin/empresas.php';
    }

    public static function aprovarEmpresa(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        if (!AdminAuth::can('company_approve')) {
            http_response_code(403);
            exit('Você não possui permissão para aprovar empresas.');
        }

        if (!csrf_validate()) {
            $_SESSION['admin_error'] = 'A sessão do formulário expirou. Tente novamente.';
            header('Location: /re.source/admin/empresas');
            exit;
        }

        $companyId = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
        if (!$companyId) {
            $_SESSION['admin_error'] = 'Empresa inválida.';
            header('Location: /re.source/admin/empresas');
            exit;
        }

        global $pdo;
        $adminUser = AdminAuth::user();

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT status FROM companies WHERE id = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$companyId]);
            $oldStatus = $stmt->fetchColumn();

            if ($oldStatus === false) {
                throw new DomainException('Empresa não encontrada.');
            }

            if ($oldStatus !== 'pending') {
                throw new DomainException('A empresa não está aguardando aprovação.');
            }

            $stmt = $pdo->prepare(
                "UPDATE companies
                 SET status = 'active', approved_at = NOW(), approved_by_user_id = ?
                 WHERE id = ? AND status = 'pending'"
            );
            $stmt->execute([(int) $adminUser['id'], $companyId]);

            $stmt = $pdo->prepare(
                "INSERT INTO audit_logs
                    (user_id, company_id, action, severity, entity_type, entity_id,
                     old_values_json, new_values_json, ip_address, user_agent)
                 VALUES (?, ?, 'COMPANY_APPROVED', 'info', 'company', ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                (int) $adminUser['id'],
                $companyId,
                $companyId,
                json_encode(['status' => $oldStatus], JSON_UNESCAPED_UNICODE),
                json_encode(['status' => 'active'], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            $pdo->commit();
            $_SESSION['admin_success'] = 'Empresa aprovada e acesso completo liberado.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['admin_error'] = $e instanceof DomainException
                ? $e->getMessage()
                : 'Não foi possível aprovar a empresa.';
        }

        header('Location: /re.source/admin/empresas');
        exit;
    }

}
