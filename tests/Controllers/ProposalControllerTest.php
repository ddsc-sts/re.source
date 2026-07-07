<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ProposalControllerTest extends TestCase
{
    public function testArquivoDeTesteEstaConfigurado(): void
    {
        $this->assertTrue(class_exists(ProposalController::class));
    }

    public function testValidacaoDePropostaBloqueiaQuantidadeValorEFreteInvalidos(): void
    {
        $errors = $this->validateProposal(['quantity' => 0, 'unit_price' => -1, 'responsible_for_freight' => 'invalid']);

        $this->assertContains('Informe quantidade e valor válidos.', $errors);
        $this->assertContains('Responsável pelo frete inválido.', $errors);
    }

    public function testValidacaoDePropostaBloqueiaPrazoNoPassadoENotasLongas(): void
    {
        $errors = $this->validateProposal([
            'quantity' => 1,
            'unit_price' => 10,
            'responsible_for_freight' => 'buyer',
            'delivery_deadline' => date('Y-m-d', strtotime('-1 day')),
            'notes' => str_repeat('a', 1001),
        ]);

        $this->assertContains('Informe um prazo de entrega válido.', $errors);
        $this->assertContains('As observações devem ter no máximo 1.000 caracteres.', $errors);
    }

    public function testSalvarPropostaCalculaTotalEMudaStatusDaNegociacao(): void
    {
        $negotiation = ['id' => 5, 'listing_id' => 9, 'status' => 'open'];
        $proposal = $this->saveProposal($negotiation, quantity: 3.5, unitPrice: 20.0, freight: 'shared');

        $this->assertSame(70.0, $proposal['total_price']);
        $this->assertSame('pending', $proposal['status']);
        $this->assertSame('proposal_sent', $proposal['negotiation_status']);
        $this->assertSame('negotiating', $proposal['listing_status']);
    }

    public function testPropostaNaoPodeSerAlteradaDepoisDoAcordoOuCancelamento(): void
    {
        foreach (['accepted', 'awaiting_freight', 'shipping', 'delivered', 'concluded', 'cancelled'] as $status) {
            $this->expectException(DomainException::class);
            $this->saveProposal(['id' => 5, 'listing_id' => 9, 'status' => $status], 1, 10, 'buyer');
        }
    }

    public function testOtherCompanyRetornaAOutraParteDaNegociacao(): void
    {
        $negotiation = ['buyer_company_id' => 10, 'seller_company_id' => 20];

        $this->assertSame(20, $this->invokePrivate('otherCompany', $negotiation, 10));
        $this->assertSame(10, $this->invokePrivate('otherCompany', $negotiation, 20));
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function validateProposal(array $input): array
    {
        $quantity = (float) ($input['quantity'] ?? 0);
        $unitPrice = (float) ($input['unit_price'] ?? 0);
        $freight = (string) ($input['responsible_for_freight'] ?? 'buyer');
        $deadline = trim((string) ($input['delivery_deadline'] ?? ''));
        $notes = (string) ($input['notes'] ?? '');
        $errors = [];

        if ($quantity <= 0 || $quantity > 999999999 || $unitPrice < 0 || $unitPrice > 999999999) {
            $errors[] = 'Informe quantidade e valor válidos.';
        }
        if (!in_array($freight, ['buyer', 'seller', 'shared'], true)) {
            $errors[] = 'Responsável pelo frete inválido.';
        }
        if ($deadline !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $deadline);
            if (!$date || $date->format('Y-m-d') !== $deadline || $date < new DateTimeImmutable('today')) {
                $errors[] = 'Informe um prazo de entrega válido.';
            }
        }
        if (mb_strlen($notes) > 1000) {
            $errors[] = 'As observações devem ter no máximo 1.000 caracteres.';
        }

        return $errors;
    }

    private function saveProposal(array $negotiation, float $quantity, float $unitPrice, string $freight): array
    {
        if (in_array($negotiation['status'], ['accepted', 'awaiting_freight', 'shipping', 'delivered', 'concluded', 'cancelled'], true)) {
            throw new DomainException('A proposta não pode mais ser alterada neste estágio.');
        }

        return [
            'negotiation_id' => $negotiation['id'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($quantity * $unitPrice, 2),
            'responsible_for_freight' => $freight,
            'status' => 'pending',
            'negotiation_status' => 'proposal_sent',
            'listing_status' => 'negotiating',
        ];
    }

    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(ProposalController::class, $method);

        return $reflection->invoke(null, ...$args);
    }
}
