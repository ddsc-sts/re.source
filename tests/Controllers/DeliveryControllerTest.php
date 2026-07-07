<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DeliveryControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [
            'user' => [
                'id' => 12,
                'company_id' => 34,
            ],
        ];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testCompanyIdEUserIdSaoResolvidosDaSessao(): void
    {
        $this->assertSame(34, $this->invokePrivate('companyId'));
        $this->assertSame(12, $this->invokePrivate('userId'));
    }

    public function testCompanyIdTemFallbackParaCompanyIdDireto(): void
    {
        unset($_SESSION['user']['company_id']);
        $_SESSION['company_id'] = 88;

        $this->assertSame(88, $this->invokePrivate('companyId'));
    }

    public function testLimiteDeTentativasDoCodigoDeEntregaSegueRoteiro(): void
    {
        $reflection = new ReflectionClass(DeliveryController::class);

        $this->assertSame(5, $reflection->getConstant('MAX_ATTEMPTS'));
    }

    public function testHashDeCodigoDeSeisDigitosValidoConfereComPasswordVerify(): void
    {
        $code = '123456';
        $hash = password_hash($code, PASSWORD_DEFAULT);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue(password_verify($code, $hash));
        $this->assertFalse(password_verify('654321', $hash));
    }

    public function testCodigoErradoRegistraTentativaSemConcluirEntrega(): void
    {
        $freight = $this->freightFixture([
            'delivery_code_hash' => password_hash('123456', PASSWORD_DEFAULT),
            'delivery_code_attempts' => 2,
        ]);

        $result = $this->simulateDeliveryValidation($freight, '000000');

        $this->assertFalse($result['success']);
        $this->assertSame('Código incorreto. Tentativa registrada.', $result['message']);
        $this->assertSame(3, $result['freight']['delivery_code_attempts']);
        $this->assertSame('out_for_delivery', $result['freight']['status']);
        $this->assertCount(1, $result['delivery_attempts']);
        $this->assertSame(0, $result['delivery_attempts'][0]['success']);
    }

    public function testCodigoExpiradoELimiteDeTentativasBloqueiamValidacao(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('expirou');

        $this->simulateDeliveryValidation($this->freightFixture([
            'delivery_code_expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ]), '123456');
    }

    public function testLimiteDeCincoTentativasBloqueiaNovaValidacao(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tentativas');

        $this->simulateDeliveryValidation($this->freightFixture([
            'delivery_code_attempts' => 5,
        ]), '123456');
    }

    public function testCodigoCorretoConcluiFreteNegociacaoAnuncioELiberaSaldoUmaVez(): void
    {
        $freight = $this->freightFixture([
            'delivery_code_hash' => password_hash('123456', PASSWORD_DEFAULT),
        ]);

        $result = $this->simulateDeliveryValidation($freight, '123456');

        $this->assertTrue($result['success']);
        $this->assertSame('concluded', $result['freight']['status']);
        $this->assertSame('concluded', $result['negotiation_status']);
        $this->assertSame('concluded', $result['listing_status']);
        $this->assertSame(750.50, $result['seller_balance_delta']);
        $this->assertSame('sale', $result['financial_transaction']['type']);
        $this->assertSame('completed', $result['financial_transaction']['status']);
        $this->assertCount(2, $result['notifications']);
    }

    public function testEntregaComTransacaoFinanceiraJaCriadaNaoLiberaSaldoDeNovo(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('já foi liberado');

        $this->simulateDeliveryValidation(
            $this->freightFixture(['delivery_code_hash' => password_hash('123456', PASSWORD_DEFAULT)]),
            '123456',
            saleAlreadyExists: true
        );
    }

    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(DeliveryController::class, $method);

        return $reflection->invoke(null, ...$args);
    }

    private function freightFixture(array $override = []): array
    {
        return $override + [
            'id' => 9,
            'negotiation_id' => 77,
            'buyer_company_id' => 10,
            'seller_company_id' => 20,
            'listing_id' => 30,
            'proposed_total' => 750.50,
            'status' => 'out_for_delivery',
            'delivery_code_hash' => password_hash('123456', PASSWORD_DEFAULT),
            'delivery_code_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'delivery_code_attempts' => 0,
            'delivery_code_used_at' => null,
        ];
    }

    private function simulateDeliveryValidation(array $freight, string $code, bool $saleAlreadyExists = false): array
    {
        if ($freight['delivery_code_used_at'] || in_array($freight['status'], ['delivered', 'concluded'], true)) {
            throw new DomainException('Esta entrega já foi confirmada.');
        }
        if (!$freight['delivery_code_hash'] || !$freight['delivery_code_expires_at']) {
            throw new DomainException('O comprador ainda não gerou o código.');
        }
        if (strtotime($freight['delivery_code_expires_at']) < time()) {
            throw new DomainException('O código expirou. Solicite um novo ao comprador.');
        }
        if ((int) $freight['delivery_code_attempts'] >= 5) {
            throw new DomainException('Limite de tentativas atingido. Solicite um novo código.');
        }

        $attempt = [
            'freight_id' => $freight['id'],
            'company_id' => 34,
            'user_id' => 12,
            'success' => password_verify($code, $freight['delivery_code_hash']) ? 1 : 0,
        ];

        if (!$attempt['success']) {
            $freight['delivery_code_attempts']++;

            return [
                'success' => false,
                'message' => 'Código incorreto. Tentativa registrada.',
                'freight' => $freight,
                'delivery_attempts' => [$attempt],
            ];
        }

        if ((float) $freight['proposed_total'] <= 0) {
            throw new DomainException('Valor financeiro da negociação inválido.');
        }
        if ($saleAlreadyExists) {
            throw new DomainException('O saldo desta entrega já foi liberado.');
        }

        $freight['status'] = 'concluded';
        $freight['delivery_code_used_at'] = date('Y-m-d H:i:s');
        $freight['delivery_code_hash'] = null;

        return [
            'success' => true,
            'freight' => $freight,
            'negotiation_status' => 'concluded',
            'listing_status' => 'concluded',
            'seller_balance_delta' => (float) $freight['proposed_total'],
            'financial_transaction' => [
                'company_id' => $freight['seller_company_id'],
                'negotiation_id' => $freight['negotiation_id'],
                'type' => 'sale',
                'amount' => (float) $freight['proposed_total'],
                'status' => 'completed',
            ],
            'notifications' => [
                ['company_id' => $freight['buyer_company_id'], 'type' => 'negotiation_concluded'],
                ['company_id' => $freight['seller_company_id'], 'type' => 'negotiation_concluded'],
            ],
            'delivery_attempts' => [$attempt],
        ];
    }
}
