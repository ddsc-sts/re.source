<?php

class MelhorEnvioService
{
    private string $baseUrl;
    private ?string $accessToken;

    public function __construct()
    {
        $this->baseUrl = env('MELHOR_ENVIO_SANDBOX', true)
            ? 'https://sandbox.melhorenvio.com.br'
            : 'https://www.melhorenvio.com.br';

        $this->accessToken = env('MELHOR_ENVIO_ACCESS_TOKEN');
    }

    /**
     * Envia uma requisição para a API do Melhor Envio.
     */
    private function request(
        string $method,
        string $endpoint,
        array $payload = [],
        array $headers = []
    ): array {

        $url = $this->baseUrl . $endpoint;

        $curl = curl_init($url);

        $defaultHeaders = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        if (!empty($this->accessToken)) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => array_merge($defaultHeaders, $headers),
            CURLOPT_TIMEOUT        => 30,
        ]);

        if (!empty($payload)) {
            curl_setopt(
                $curl,
                CURLOPT_POSTFIELDS,
                json_encode($payload, JSON_UNESCAPED_UNICODE)
            );
        }

        $response = curl_exec($curl);

        if ($response === false) {
            throw new RuntimeException(curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new RuntimeException(
                $data['message'] ?? 'Erro ao comunicar com o Melhor Envio.'
            );
        }

        return $data;
    }

    /**
     * Consulta cotações de frete.
     */
    public function quote(array $payload): array
    {
        return $this->request(
            'POST',
            '/api/v2/me/shipment/calculate',
            $payload
        );
    }

    /**
     * Contrata um frete.
     */
    public function contract(array $payload): array
    {
        return $this->request(
            'POST',
            '/api/v2/me/shipment/checkout',
            $payload
        );
    }

    /**
     * Consulta rastreamento.
     */
    public function tracking(string $trackingCode): array
    {
        return $this->request(
            'GET',
            '/api/v2/me/shipment/tracking/' . urlencode($trackingCode)
        );
    }

    /**
     * Cancela um envio.
     */
    public function cancel(int $shipmentId): array
    {
        return $this->request(
            'POST',
            '/api/v2/me/shipment/cancel',
            [
                'shipment' => $shipmentId
            ]
        );
    }

    /**
     * Download da etiqueta.
     */
    public function label(int $shipmentId): array
    {
        return $this->request(
            'GET',
            '/api/v2/me/shipment/labels/' . $shipmentId
        );
    }
}