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

    public static function index(): void
    {
        global $pdo;
        $companyId = self::companyId();

        $stmt = $pdo->prepare(
            "SELECT n.id, n.status, n.created_at, n.updated_at,
                    l.id AS listing_id, l.title AS listing_title,
                    CASE WHEN n.buyer_company_id = ?
                         THEN seller.nome_fantasia ELSE buyer.nome_fantasia END AS other_company_name,
                    (SELECT m.content FROM messages m
                     WHERE m.negotiation_id = n.id ORDER BY m.id DESC LIMIT 1) AS last_message,
                    (SELECT m.created_at FROM messages m
                     WHERE m.negotiation_id = n.id ORDER BY m.id DESC LIMIT 1) AS last_message_at
             FROM negotiations n
             INNER JOIN listings l ON l.id = n.listing_id
             INNER JOIN companies buyer ON buyer.id = n.buyer_company_id
             INNER JOIN companies seller ON seller.id = n.seller_company_id
             WHERE n.buyer_company_id = ? OR n.seller_company_id = ?
             ORDER BY COALESCE(last_message_at, n.updated_at) DESC"
        );
        $stmt->execute([$companyId, $companyId, $companyId]);

        view('chat/index', [
            'titulo_pagina' => 'Conversas — Re.Source',
            'conversations' => $stmt->fetchAll(PDO::FETCH_ASSOC),
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

        view('chat/show', [
            'titulo_pagina' => 'Conversa — Re.Source',
            'negotiation' => $negotiation,
            'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'companyId' => $companyId,
            'otherCompanyName' => $otherCompanyName,
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
        if (!$userId || !self::negotiation($pdo, (int) $negotiationId, $companyId)) {
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

        self::json([
            'success' => true,
            'message' => [
                'id' => $messageId,
                'sender_user_id' => $userId,
                'sender_company_id' => $companyId,
                'sender_name' => $user['name'],
                'content' => $content,
                'created_at' => $stmt->fetchColumn(),
            ],
        ]);
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
}
