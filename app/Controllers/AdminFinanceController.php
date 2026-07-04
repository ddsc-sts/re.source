<?php

class AdminFinanceController
{
    private static function authorize(): void
    {
        if (!AdminAuth::can('view_financial')) { http_response_code(403); exit('Acesso financeiro negado.'); }
    }

    public static function index(): void
    {
        self::authorize();
        global $pdo;
        $status = (string) ($_GET['status'] ?? '');
        $method = (string) ($_GET['method'] ?? '');
        $q = trim((string) ($_GET['q'] ?? ''));
        $where = ['1=1']; $params = [];
        if (in_array($status, ['pending','completed','rejected'], true)) { $where[] = 'w.status = ?'; $params[] = $status; }
        if (in_array($method, ['pix','ted'], true)) { $where[] = 'w.method = ?'; $params[] = $method; }
        if ($q !== '') { $where[] = '(c.razao_social LIKE ? OR c.nome_fantasia LIKE ? OR c.cnpj LIKE ?)'; $like = "%{$q}%"; array_push($params,$like,$like,$like); }
        $stmt = $pdo->prepare("SELECT w.*, c.razao_social, c.nome_fantasia, c.cnpj,
            reviewer.name reviewer_name, ft.status transaction_status
            FROM withdrawals w INNER JOIN companies c ON c.id = w.company_id
            LEFT JOIN users reviewer ON reviewer.id = w.reviewed_by_user_id
            LEFT JOIN financial_transactions ft ON ft.withdrawal_id = w.id
            WHERE " . implode(' AND ', $where) . ' ORDER BY (w.status = \'pending\') DESC, w.id DESC');
        $stmt->execute($params);
        $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $metrics = [
            'saques_pendentes' => (int) $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn(),
            'valor_pendente' => (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='pending'")->fetchColumn(),
            'valor_aprovado' => (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='completed'")->fetchColumn(),
            'valor_rejeitado' => (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='rejected'")->fetchColumn(),
            'anuncios_pendentes' => 0, 'chamados_abertos' => 0,
        ];
        $stmt = $pdo->query("SELECT al.*, u.name user_name, c.nome_fantasia company_name FROM audit_logs al
            LEFT JOIN users u ON u.id=al.user_id LEFT JOIN companies c ON c.id=al.company_id
            WHERE al.entity_type='withdrawal' ORDER BY al.id DESC LIMIT 20");
        $auditHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $user = AdminAuth::user();
        require VIEW_PATH . '/dashboard/admin/saques.php';
    }

    public static function approve(): void { self::review(true); }
    public static function reject(): void { self::review(false); }

    private static function review(bool $approve): void
    {
        self::authorize();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_validate()) {
            flash('error', 'Sessão expirada.'); redirect_to('/admin/saques');
        }
        global $pdo;
        $id = filter_input(INPUT_POST, 'withdrawal_id', FILTER_VALIDATE_INT);
        $reason = trim((string) ($_POST['reason'] ?? ''));
        try {
            $withdrawal = FinanceService::review($pdo, (int) $id, (int) ($_SESSION['user']['id'] ?? 0), $approve, $reason ?: null);
            self::sendEmail($pdo, $withdrawal, $approve, $reason);
            flash('success', $approve ? 'Saque aprovado manualmente.' : 'Saque recusado e saldo devolvido.');
        } catch (Throwable $error) {
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Não foi possível analisar o saque.');
        }
        redirect_to('/admin/saques');
    }

    public static function exportCsv(): void
    {
        self::authorize();
        global $pdo;
        $rows = $pdo->query("SELECT w.id, c.razao_social, c.cnpj, w.method, w.amount, w.status,
            w.created_at, w.reviewed_at, w.rejection_reason FROM withdrawals w
            INNER JOIN companies c ON c.id=w.company_id ORDER BY w.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="saques-resource-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Empresa','CNPJ','Método','Valor','Status','Solicitado em','Analisado em','Motivo'], ';');
        foreach ($rows as $row) fputcsv($out, $row, ';');
        fclose($out); exit;
    }

    private static function sendEmail(PDO $pdo, array $withdrawal, bool $approved, string $reason): void
    {
        try {
            $stmt = $pdo->prepare("SELECT email,name FROM users WHERE company_id=? AND is_active=1 AND deleted_at IS NULL ORDER BY id LIMIT 1");
            $stmt->execute([$withdrawal['company_id']]); $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) return;
            require_once CONFIG_PATH . '/mailer.php';
            enviarEmailFluxo($target['email'],$target['name'],$approved?'Saque aprovado — Re.Source':'Saque recusado — Re.Source',
                $approved?'Transferência aprovada':'Solicitação recusada',
                $approved?'A análise foi concluída e o saque foi aprovado.':'O saldo reservado foi devolvido. Motivo: '.$reason,
                rtrim((string)env('APP_URL','http://localhost/re.source'),'/').'/estatisticas');
        } catch (Throwable $error) { error_log('Falha no e-mail de saque: '.$error->getMessage()); }
    }
}
