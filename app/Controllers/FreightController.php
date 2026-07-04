<?php

class FreightController
{
    /**
     * Exibe tela de cotação de frete
     */
    public static function quote()
    {
        global $pdo;

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            flash('error', 'Você precisa estar logado.');
            redirect_to('/login');
        }

        // Exemplo: origem fixa (empresa logada)
        $stmt = $pdo->prepare("
            SELECT c.* 
            FROM companies c
            INNER JOIN users u ON u.company_id = c.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $company = $stmt->fetch();

        if (!$company) {
            flash('error', 'Empresa não encontrada.');
            redirect_to('/base');
        }

        // Dados de destino (ex: vindo do POST ou GET)
        $destination = [
            'zipcode' => $_GET['zipcode'] ?? null
        ];

        if (!$destination['zipcode']) {
            flash('error', 'CEP de destino não informado.');
            redirect_to('/base');
        }

        // Dados básicos do pacote (MVP)
        $package = [
            'height' => 10,
            'width'  => 20,
            'length' => 20,
            'weight' => 1
        ];

        try {

            $service = new MelhorEnvioService();

            $quotes = $service->quote([
                "from" => [
                    "postal_code" => preg_replace('/\D/', '', $company['zipcode'])
                ],
                "to" => [
                    "postal_code" => preg_replace('/\D/', '', $destination['zipcode'])
                ],
                "products" => [
                    [
                        "weight" => $package['weight'],
                        "height" => $package['height'],
                        "width"  => $package['width'],
                        "length" => $package['length']
                    ]
                ],
                "options" => [
                    "receipt" => false,
                    "own_hand" => false
                ]
            ]);

        } catch (Exception $e) {

            flash('error', 'Erro ao calcular frete: ' . $e->getMessage());
            redirect_to('/logistica');
        }

        require __DIR__ . '/../Views/freight/quote.php';
    }

    /**
     * Contrata frete escolhido
     */
    public static function contract()
    {
        global $pdo;

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            flash('error', 'Não autorizado.');
            redirect_to('/login');
        }

        $shipmentId = $_POST['shipment_id'] ?? null;

        if (!$shipmentId) {
            flash('error', 'Frete inválido.');
            redirect_to('/logistica');
        }

        try {

            $service = new MelhorEnvioService();

            $result = $service->contract([
                "id" => $shipmentId
            ]);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO freights (
                    shipment_id,
                    tracking_code,
                    status,
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");

            $stmt->execute([
                $result['id'] ?? null,
                $result['tracking'] ?? null,
                'shipping'
            ]);

            $pdo->commit();

            flash('success', 'Frete contratado com sucesso!');
            redirect_to('/logistica');

        } catch (Exception $e) {

            $pdo->rollBack();

            flash('error', 'Erro ao contratar frete: ' . $e->getMessage());
            redirect_to('/logistica');
        }
    }

    /**
     * Rastreamento
     */
    public static function track()
    {
        $trackingCode = $_GET['tracking'] ?? null;

        if (!$trackingCode) {
            flash('error', 'Código inválido.');
            redirect_to('/logistica');
        }

        try {

            $service = new MelhorEnvioService();

            $tracking = $service->tracking($trackingCode);

        } catch (Exception $e) {

            flash('error', 'Erro no rastreio.');
            redirect_to('/logistica');
        }

        require __DIR__ . '/../Views/freight/track.php';
    }

    /**
     * Cancelamento de frete
     */
    public static function cancel()
    {
        global $pdo;

        $shipmentId = $_POST['shipment_id'] ?? null;

        if (!$shipmentId) {
            flash('error', 'Frete inválido.');
            redirect_to('/logistica');
        }

        try {

            $service = new MelhorEnvioService();

            $service->cancel($shipmentId);

            $stmt = $pdo->prepare("
                UPDATE freights
                SET status = 'cancelled'
                WHERE shipment_id = ?
            ");

            $stmt->execute([$shipmentId]);

            flash('success', 'Frete cancelado.');
            redirect_to('/logistica');

        } catch (Exception $e) {

            flash('error', 'Erro ao cancelar frete.');
            redirect_to('/logistica');
        }
    }

    /**
     * OAuth callback (já usado antes)
     */
    public static function oauthCallback()
    {
        if (!isset($_GET['code'])) {
            die('Código OAuth não recebido.');
        }

        $service = new MelhorEnvioService();

        try {

            $token = $service->exchangeCodeForToken($_GET['code']);

            $_SESSION['melhor_envio_access_token']  = $token['access_token'];
            $_SESSION['melhor_envio_refresh_token'] = $token['refresh_token'];

            flash('success', 'Melhor Envio conectado com sucesso!');
            redirect_to('/logistica');

        } catch (Exception $e) {

            flash('error', 'Erro OAuth: ' . $e->getMessage());
            redirect_to('/logistica');
        }
    }
}