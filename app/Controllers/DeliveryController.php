<?php

class DeliveryController
{
    /**
     * Portal do entregador / validação de entrega
     */
    public static function portal()
    {
        global $pdo;

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            flash('error', 'Acesso negado.');
            redirect_to('/login');
        }

        $stmt = $pdo->prepare("
            SELECT f.*, n.buyer_id, n.seller_id
            FROM freights f
            INNER JOIN negotiations n ON n.id = f.negotiation_id
            WHERE f.status IN ('shipping', 'out_for_delivery')
            ORDER BY f.id DESC
        ");
        $stmt->execute();
        $freights = $stmt->fetchAll();

        require __DIR__ . '/../Views/delivery/portal.php';
    }

    /**
     * Gera código de entrega (6 dígitos)
     */
    public static function generateCode()
    {
        global $pdo;

        $freightId = $_POST['freight_id'] ?? null;

        if (!$freightId) {
            flash('error', 'Frete inválido.');
            redirect_to('/delivery');
        }

        try {

            $code = random_int(100000, 999999);
            $hash = hash('sha256', $code);

            $stmt = $pdo->prepare("
                UPDATE freights
                SET delivery_code_hash = ?,
                    delivery_code_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                WHERE id = ?
            ");

            $stmt->execute([$hash, $freightId]);

            flash('success', "Código gerado: {$code} (exibido apenas uma vez)");
            redirect_to('/delivery');

        } catch (Exception $e) {

            flash('error', 'Erro ao gerar código.');
            redirect_to('/delivery');
        }
    }

    /**
     * Validação do código pelo entregador
     */
    public static function validate()
    {
        global $pdo;

        $freightId = $_POST['freight_id'] ?? null;
        $code      = $_POST['code'] ?? null;

        if (!$freightId || !$code) {
            flash('error', 'Dados inválidos.');
            redirect_to('/delivery');
        }

        try {

            $stmt = $pdo->prepare("
                SELECT * FROM freights
                WHERE id = ?
                FOR UPDATE
            ");

            $pdo->beginTransaction();

            $stmt->execute([$freightId]);
            $freight = $stmt->fetch();

            if (!$freight) {
                throw new Exception('Frete não encontrado.');
            }

            if ($freight['delivery_code_expires_at'] < date('Y-m-d H:i:s')) {
                throw new Exception('Código expirado.');
            }

            $hash = hash('sha256', $code);

            if ($hash !== $freight['delivery_code_hash']) {

                $stmt = $pdo->prepare("
                    INSERT INTO delivery_attempts (
                        freight_id,
                        success,
                        attempted_at
                    ) VALUES (?, 0, NOW())
                ");
                $stmt->execute([$freightId]);

                throw new Exception('Código inválido.');
            }

            // sucesso
            $stmt = $pdo->prepare("
                UPDATE freights
                SET status = 'delivered',
                    delivered_at = NOW(),
                    validated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$freightId]);

            $stmt = $pdo->prepare("
                INSERT INTO delivery_attempts (
                    freight_id,
                    success,
                    attempted_at
                ) VALUES (?, 1, NOW())
            ");
            $stmt->execute([$freightId]);

            $pdo->commit();

            flash('success', 'Entrega validada com sucesso!');
            redirect_to('/delivery');

        } catch (Exception $e) {

            $pdo->rollBack();

            flash('error', $e->getMessage());
            redirect_to('/delivery');
        }
    }

    /**
     * Finaliza fluxo e libera pagamento
     */
    public static function finish()
    {
        global $pdo;

        $freightId = $_POST['freight_id'] ?? null;

        if (!$freightId) {
            flash('error', 'Frete inválido.');
            redirect_to('/delivery');
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE freights
                SET status = 'concluded'
                WHERE id = ?
            ");
            $stmt->execute([$freightId]);

            $stmt = $pdo->prepare("
                INSERT INTO financial_transactions (
                    freight_id,
                    type,
                    amount,
                    status,
                    created_at
                )
                SELECT 
                    id,
                    'credit',
                    contracted_price,
                    'released',
                    NOW()
                FROM freights
                WHERE id = ?
            ");
            $stmt->execute([$freightId]);

            $pdo->commit();

            flash('success', 'Entrega concluída e pagamento liberado!');
            redirect_to('/delivery');

        } catch (Exception $e) {

            $pdo->rollBack();

            flash('error', 'Erro ao finalizar entrega.');
            redirect_to('/delivery');
        }
    }

    /**
     * Histórico de entregas
     */
    public static function history()
    {
        global $pdo;

        $userId = $_SESSION['user_id'] ?? null;

        $stmt = $pdo->prepare("
            SELECT f.*
            FROM freights f
            INNER JOIN negotiations n ON n.id = f.negotiation_id
            WHERE n.buyer_id = ? OR n.seller_id = ?
            ORDER BY f.created_at DESC
        ");

        $stmt->execute([$userId, $userId]);
        $history = $stmt->fetchAll();

        require __DIR__ . '/../Views/delivery/history.php';
    }
}