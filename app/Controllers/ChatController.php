<?php

class ChatController
{
    private const MAX_MESSAGE_LENGTH = 2000;

    private static function companyId(): int
    {
        return (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
    }

    private static function userId(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    private static function negotiation(PDO $pdo, int $negotiationId, int $companyId): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT n.*, l.title AS listing_title, l.type AS listing_type, l.unit,
                    buyer.nome_fantasia AS buyer_name,
                    seller.nome_fantasia AS seller_name
             FROM negotiations n
             INNER JOIN listings l ON l.id = n.listing_id
             INNER JOIN companies buyer ON buyer.id = n.buyer_company_id
             INNER JOIN companies seller ON seller.id = n.seller_company_id
             WHERE n.id = ?
               AND (n.buyer_company_id = ? OR n.seller_company_id = ?)
             LIMIT 1"
        );
        $stmt->execute([$negotiationId, $companyId, $companyId]);
        $negotiation = $stmt->fetch(PDO::FETCH_ASSOC);
        return $negotiation ?: null;
    }

    private static function markReceivedAsRead(PDO $pdo, int $negotiationId, int $companyId): void
    {
        $stmt = $pdo->prepare(
            'UPDATE messages m
             INNER JOIN users sender ON sender.id = m.sender_user_id
             SET m.read_at = CURRENT_TIMESTAMP
             WHERE m.negotiation_id = ?
               AND m.read_at IS NULL
               AND sender.company_id <> ?'
        );
        $stmt->execute([$negotiationId, $companyId]);
    }

    private static function unreadTotal(PDO $pdo, int $companyId): int
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM messages m
             INNER JOIN negotiations n ON n.id = m.negotiation_id
             INNER JOIN users sender ON sender.id = m.sender_user_id
             WHERE m.read_at IS NULL
               AND sender.company_id <> ?
               AND (n.buyer_company_id = ? OR n.seller_company_id = ?)'
        );
        $stmt->execute([$companyId, $companyId, $companyId]);
        return (int) $stmt->fetchColumn();
    }

    private static function conversations(PDO $pdo, int $companyId): array
    {
        $stmt = $pdo->prepare(
            "SELECT n.id, n.status, n.created_at, n.updated_at,
                    l.id AS listing_id, l.title AS listing_title,
                    CASE WHEN n.buyer_company_id = ?
                         THEN seller.nome_fantasia ELSE buyer.nome_fantasia END AS other_company_name,
                    (SELECT m.content FROM messages m
                     WHERE m.negotiation_id = n.id ORDER BY m.id DESC LIMIT 1) AS last_message,
                    (SELECT m.created_at FROM messages m
                     WHERE m.negotiation_id = n.id ORDER BY m.id DESC LIMIT 1) AS last_message_at,
                    (SELECT COUNT(*) FROM messages unread
                     INNER JOIN users unread_sender ON unread_sender.id = unread.sender_user_id
                     WHERE unread.negotiation_id = n.id
                       AND unread.read_at IS NULL
                       AND unread_sender.company_id <> ?) AS unread_count
             FROM negotiations n
             INNER JOIN listings l ON l.id = n.listing_id
             INNER JOIN companies buyer ON buyer.id = n.buyer_company_id
             INNER JOIN companies seller ON seller.id = n.seller_company_id
             WHERE n.buyer_company_id = ? OR n.seller_company_id = ?
             ORDER BY COALESCE(last_message_at, n.updated_at) DESC"
        );
        $stmt->execute([$companyId, $companyId, $companyId, $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function latestUnread(PDO $pdo, int $companyId): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT m.id, m.negotiation_id, m.content, m.created_at,
                    sender.name AS sender_name,
                    sender_company.nome_fantasia AS sender_company_name,
                    l.title AS listing_title
             FROM messages m
             INNER JOIN users sender ON sender.id = m.sender_user_id
             INNER JOIN companies sender_company ON sender_company.id = sender.company_id
             INNER JOIN negotiations n ON n.id = m.negotiation_id
             INNER JOIN listings l ON l.id = n.listing_id
             WHERE m.read_at IS NULL
               AND sender.company_id <> ?
               AND (n.buyer_company_id = ? OR n.seller_company_id = ?)
             ORDER BY m.id DESC
             LIMIT 1'
        );
        $stmt->execute([$companyId, $companyId, $companyId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        return $message ?: null;
    }

    private static function latestProposal(PDO $pdo, int $negotiationId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM proposals WHERE negotiation_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$negotiationId]);
        $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
        return $proposal ?: null;
    }

    private static function sendNewMessageEmail(PDO $pdo, int $companyId, string $preview, int $negotiationId): void
    {
        try {
            $stmt = $pdo->prepare(
                "SELECT u.email, u.name FROM users u
                 INNER JOIN companies c ON c.id = u.company_id
                 WHERE u.company_id = ? AND u.is_active = 1 AND u.deleted_at IS NULL
                   AND c.notify_chat = 1
                 ORDER BY (u.role = 'admin_company') DESC, u.id ASC LIMIT 1"
            );
            $stmt->execute([$companyId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) return;
            require_once CONFIG_PATH . '/mailer.php';
            $baseUrl = rtrim((string) env('APP_URL', 'http://localhost/re.source'), '/');
            enviarEmailFluxo(
                (string) $target['email'], (string) $target['name'],
                'Nova mensagem — Re.Source', 'Você recebeu uma nova mensagem',
                mb_substr($preview, 0, 300),
                $baseUrl . '/conversas/abrir?id=' . $negotiationId
            );
        } catch (Throwable $error) {
            error_log('Falha no e-mail de nova mensagem: ' . $error->getMessage());
        }
    }

    public static function index(): void
    {
        global $pdo;
        $companyId = self::companyId();

        view('chat/index', [
            'titulo_pagina' => 'Conversas — Re.Source',
            'conversations' => self::conversations($pdo, $companyId),
        ]);
    }

    public static function show(): void
    {
        $negotiationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$negotiationId) {
            flash('error', 'Conversa inválida.');
            redirect_to('/conversas');
        }

        global $pdo;
        $companyId = self::companyId();
        $negotiation = self::negotiation($pdo, (int) $negotiationId, $companyId);
        if (!$negotiation) {
            http_response_code(403);
            flash('error', 'Você não possui acesso a esta conversa.');
            redirect_to('/conversas');
        }

        self::markReceivedAsRead($pdo, (int) $negotiationId, $companyId);

        $stmt = $pdo->prepare(
            "SELECT recent.id, recent.sender_user_id, recent.content, recent.created_at,
                    u.name AS sender_name, u.company_id AS sender_company_id
             FROM (
                 SELECT id, sender_user_id, content, created_at
                 FROM messages
                 WHERE negotiation_id = ?
                 ORDER BY id DESC
                 LIMIT 100
             ) recent
             INNER JOIN users u ON u.id = recent.sender_user_id
             ORDER BY recent.id ASC"
        );
        $stmt->execute([$negotiationId]);

        $otherCompanyName = (int) $negotiation['buyer_company_id'] === $companyId
            ? $negotiation['seller_name']
            : $negotiation['buyer_name'];

        $proposal = self::latestProposal($pdo, (int) $negotiationId);

        view('chat/show', [
            'titulo_pagina' => 'Conversa — Re.Source',
            'negotiation' => $negotiation,
            'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'companyId' => $companyId,
            'otherCompanyName' => $otherCompanyName,
            'proposal' => $proposal,
            'isBuyer' => (int) $negotiation['buyer_company_id'] === $companyId,
        ]);
    }

    public static function send(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            self::jsonError('Método não permitido.', 405);
        }
        if (!csrf_validate()) {
            self::jsonError('A sessão expirou. Recarregue a página.', 403);
        }

        $negotiationId = filter_input(INPUT_POST, 'negotiation_id', FILTER_VALIDATE_INT);
        $content = trim((string) ($_POST['content'] ?? ''));
        if (!$negotiationId) {
            self::jsonError('Conversa inválida.', 422);
        }
        if ($content === '') {
            self::jsonError('Digite uma mensagem.', 422);
        }
        if (mb_strlen($content) > self::MAX_MESSAGE_LENGTH) {
            self::jsonError('A mensagem deve ter no máximo 2.000 caracteres.', 422);
        }

        global $pdo;
        $companyId = self::companyId();
        $userId = self::userId();
        $negotiation = $userId ? self::negotiation($pdo, (int) $negotiationId, $companyId) : null;
        if (!$userId || !$negotiation) {
            self::jsonError('Você não possui acesso a esta conversa.', 403);
        }

        $stmtUser = $pdo->prepare(
            'SELECT id, name FROM users
             WHERE id = ? AND company_id = ? AND is_active = 1 AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmtUser->execute([$userId, $companyId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            self::jsonError('Usuário inválido.', 403);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO messages (negotiation_id, sender_user_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$negotiationId, $userId, $content]);
        $messageId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('SELECT created_at FROM messages WHERE id = ?');
        $stmt->execute([$messageId]);
        $createdAt = $stmt->fetchColumn();

        $otherCompanyId = (int) $negotiation['buyer_company_id'] === $companyId
            ? (int) $negotiation['seller_company_id']
            : (int) $negotiation['buyer_company_id'];
        $stmtNotification = $pdo->prepare(
            "INSERT INTO notifications (company_id, type, title, body, data_json)
             VALUES (?, 'new_message', 'Nova mensagem', ?, ?)"
        );
        $stmtNotification->execute([
            $otherCompanyId,
            mb_substr($content, 0, 300),
            json_encode(['negotiation_id' => (int) $negotiationId], JSON_UNESCAPED_UNICODE),
        ]);

        self::jsonThen([
            'success' => true,
            'message' => [
                'id' => $messageId,
                'sender_user_id' => $userId,
                'sender_company_id' => $companyId,
                'sender_name' => $user['name'],
                'content' => $content,
                'created_at' => $createdAt,
            ],
        ], static function () use ($pdo, $otherCompanyId, $content, $negotiationId): void {
            self::sendNewMessageEmail($pdo, $otherCompanyId, $content, (int) $negotiationId);
        });
    }

    public static function messages(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            self::jsonError('Método não permitido.', 405);
        }

        $negotiationId = filter_input(INPUT_GET, 'negotiation_id', FILTER_VALIDATE_INT);
        $afterId = filter_input(INPUT_GET, 'after_id', FILTER_VALIDATE_INT);
        $afterId = $afterId ?: 0;
        if (!$negotiationId) {
            self::jsonError('Conversa inválida.', 422);
        }

        global $pdo;
        if (!self::negotiation($pdo, (int) $negotiationId, self::companyId())) {
            self::jsonError('Você não possui acesso a esta conversa.', 403);
        }

        $companyId = self::companyId();
        self::markReceivedAsRead($pdo, (int) $negotiationId, $companyId);

        $stmt = $pdo->prepare(
            "SELECT m.id, m.sender_user_id, m.content, m.created_at,
                    u.name AS sender_name, u.company_id AS sender_company_id
             FROM messages m
             INNER JOIN users u ON u.id = m.sender_user_id
             WHERE m.negotiation_id = ? AND m.id > ?
             ORDER BY m.id ASC
             LIMIT 100"
        );
        $stmt->execute([$negotiationId, $afterId]);
        self::json(['success' => true, 'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public static function unreadCount(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            self::jsonError('Método não permitido.', 405);
        }

        global $pdo;
        $companyId = self::companyId();
        if (!$companyId) {
            self::jsonError('Empresa inválida.', 403);
        }

        self::json([
            'success' => true,
            'unread_count' => self::unreadTotal($pdo, $companyId),
            'latest_message' => self::latestUnread($pdo, $companyId),
        ]);
    }

    public static function conversationList(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            self::jsonError('Método não permitido.', 405);
        }

        global $pdo;
        $companyId = self::companyId();
        if (!$companyId) {
            self::jsonError('Empresa inválida.', 403);
        }

        self::json([
            'success' => true,
            'conversations' => self::conversations($pdo, $companyId),
        ]);
    }

    private static function jsonError(string $message, int $status): never
    {
        http_response_code($status);
        self::json(['success' => false, 'message' => $message]);
    }

    private static function json(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function jsonThen(array $data, callable $after): never
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Length: ' . strlen($payload));
        header('Connection: close');
        echo $payload;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        try {
            $after();
        } catch (Throwable $error) {
            error_log('Falha em tarefa apos resposta JSON: ' . $error->getMessage());
        }
        exit;
    }
}
