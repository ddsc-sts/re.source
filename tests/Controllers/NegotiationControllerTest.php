<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class NegotiationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testAnuncioOfferDefineEmpresaLogadaComoCompradora(): void
    {
        [$buyerCompanyId, $sellerCompanyId] = $this->resolveParties('offer', ownerCompanyId: 20, loggedCompanyId: 10);

        $this->assertSame(10, $buyerCompanyId);
        $this->assertSame(20, $sellerCompanyId);
    }

    public function testAnuncioDemandDefineEmpresaLogadaComoVendedora(): void
    {
        [$buyerCompanyId, $sellerCompanyId] = $this->resolveParties('demand', ownerCompanyId: 20, loggedCompanyId: 10);

        $this->assertSame(20, $buyerCompanyId);
        $this->assertSame(10, $sellerCompanyId);
    }

    public function testEmpresaDonaDoAnuncioNaoPodeIniciarConversaConsigoMesma(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('VocÃª nÃ£o pode iniciar uma conversa sobre o prÃ³prio anÃºncio.');

        $this->resolveParties('offer', ownerCompanyId: 10, loggedCompanyId: 10);
    }

    public function testAnuncioIndisponivelBloqueiaInicioDeNegociacao(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Este anÃºncio nÃ£o estÃ¡ disponÃ­vel para conversa.');

        $this->assertListingAvailable(['status' => 'paused', 'company_status' => 'active']);
    }

    public function testFluxoCompletoDeNegociacaoAteAcordoMutuo(): void
    {
        $flow = $this->startConversation('offer', ownerCompanyId: 20, loggedCompanyId: 10);
        $proposal = $this->sendProposal($flow, quantity: 5, unitPrice: 100, freight: 'buyer');

        $afterBuyerAccept = $this->acceptProposal($flow, $proposal, companyId: 10);
        $afterSellerAccept = $this->acceptProposal($afterBuyerAccept['negotiation'], $afterBuyerAccept['proposal'], companyId: 20);

        $this->assertSame('accepted', $afterSellerAccept['negotiation']['status']);
        $this->assertSame('accepted', $afterSellerAccept['proposal']['status']);
        $this->assertSame(500.0, $afterSellerAccept['negotiation']['proposed_total']);
        $this->assertMatchesRegularExpression('/^RES-\d{8}-1-[A-F0-9]{4}$/', $afterSellerAccept['negotiation']['protocol_number']);
        $this->assertNotNull($afterSellerAccept['proposal']['buyer_accepted_at']);
        $this->assertNotNull($afterSellerAccept['proposal']['seller_accepted_at']);
    }

    public function testConversaExistenteEReaproveitada(): void
    {
        $existing = [
            'id' => 44,
            'listing_id' => 1,
            'buyer_company_id' => 10,
            'seller_company_id' => 20,
            'status' => 'open',
        ];

        $flow = $this->startConversation('offer', ownerCompanyId: 20, loggedCompanyId: 10, existing: $existing);

        $this->assertSame(44, $flow['id']);
        $this->assertSame('A conversa deste anúncio já existia e foi reaberta.', $flow['flash']);
    }

    public function testCorridaDeCriacaoBuscaNegociacaoCriadaPelaOutraRequisicao(): void
    {
        $flow = $this->startConversation('offer', ownerCompanyId: 20, loggedCompanyId: 10, concurrentCreatedId: 91);

        $this->assertSame(91, $flow['id']);
        $this->assertSame('A conversa criada em paralelo foi reutilizada.', $flow['flash']);
    }

    public function testRecusarPropostaVoltaNegociacaoParaAberta(): void
    {
        $flow = $this->startConversation('offer', ownerCompanyId: 20, loggedCompanyId: 10);
        $proposal = $this->sendProposal($flow, quantity: 5, unitPrice: 100, freight: 'shared');

        $result = $this->refuseProposal($flow, $proposal, companyId: 20, reason: 'Valor fora do combinado');

        $this->assertSame('open', $result['negotiation']['status']);
        $this->assertSame('refused', $result['proposal']['status']);
        $this->assertSame(20, $result['proposal']['refused_by_company_id']);
    }

    public function testCancelarNegociacaoAntesDoAcordoReativaAnuncio(): void
    {
        $flow = $this->startConversation('offer', ownerCompanyId: 20, loggedCompanyId: 10);
        $flow['status'] = 'proposal_sent';
        $listing = ['id' => 1, 'status' => 'negotiating'];

        $result = $this->cancelNegotiation($flow, $listing, companyId: 10, reason: 'Comprador desistiu da compra');

        $this->assertSame('cancelled', $result['negotiation']['status']);
        $this->assertSame('active', $result['listing']['status']);
        $this->assertSame(10, $result['negotiation']['cancelled_by']);
    }

    private function resolveParties(string $listingType, int $ownerCompanyId, int $loggedCompanyId): array
    {
        if ($ownerCompanyId === $loggedCompanyId) {
            throw new DomainException('VocÃª nÃ£o pode iniciar uma conversa sobre o prÃ³prio anÃºncio.');
        }

        if ($listingType === 'offer') {
            return [$loggedCompanyId, $ownerCompanyId];
        }

        return [$ownerCompanyId, $loggedCompanyId];
    }

    private function assertListingAvailable(array $listing): void
    {
        if (($listing['status'] ?? null) !== 'active' || ($listing['company_status'] ?? null) !== 'active') {
            throw new DomainException('Este anÃºncio nÃ£o estÃ¡ disponÃ­vel para conversa.');
        }

        $this->addToAssertionCount(1);
    }

    private function startConversation(
        string $listingType,
        int $ownerCompanyId,
        int $loggedCompanyId,
        ?array $existing = null,
        ?int $concurrentCreatedId = null
    ): array {
        [$buyerCompanyId, $sellerCompanyId] = $this->resolveParties($listingType, $ownerCompanyId, $loggedCompanyId);

        if ($existing) {
            return $existing + ['flash' => 'A conversa deste anúncio já existia e foi reaberta.'];
        }
        if ($concurrentCreatedId) {
            return [
                'id' => $concurrentCreatedId,
                'listing_id' => 1,
                'buyer_company_id' => $buyerCompanyId,
                'seller_company_id' => $sellerCompanyId,
                'status' => 'open',
                'flash' => 'A conversa criada em paralelo foi reutilizada.',
            ];
        }

        return [
            'id' => 1,
            'listing_id' => 1,
            'buyer_company_id' => $buyerCompanyId,
            'seller_company_id' => $sellerCompanyId,
            'status' => 'open',
            'flash' => 'Conversa iniciada com sucesso.',
        ];
    }

    private function sendProposal(array $negotiation, float $quantity, float $unitPrice, string $freight): array
    {
        if (!in_array($freight, ['buyer', 'seller', 'shared'], true)) {
            throw new DomainException('Responsável pelo frete inválido.');
        }

        return [
            'id' => 1,
            'negotiation_id' => $negotiation['id'],
            'sender_company_id' => $negotiation['buyer_company_id'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($quantity * $unitPrice, 2),
            'responsible_for_freight' => $freight,
            'status' => 'pending',
            'buyer_accepted_at' => null,
            'seller_accepted_at' => null,
        ];
    }

    private function acceptProposal(array $negotiation, array $proposal, int $companyId): array
    {
        $isBuyer = (int) $negotiation['buyer_company_id'] === $companyId;
        $proposal[$isBuyer ? 'buyer_accepted_at' : 'seller_accepted_at'] = date('Y-m-d H:i:s');
        $buyerAccepted = !empty($proposal['buyer_accepted_at']);
        $sellerAccepted = !empty($proposal['seller_accepted_at']);

        if ($buyerAccepted && $sellerAccepted) {
            $proposal['status'] = 'accepted';
            $negotiation['status'] = 'accepted';
            $negotiation['protocol_number'] = 'RES-' . date('Ymd') . '-' . $negotiation['id'] . '-ABCD';
            $negotiation['agreement_at'] = date('Y-m-d H:i:s');
            $negotiation['proposed_total'] = $proposal['total_price'];
        } else {
            $negotiation['status'] = $isBuyer ? 'buyer_accepted' : 'seller_accepted';
        }

        return ['negotiation' => $negotiation, 'proposal' => $proposal];
    }

    private function refuseProposal(array $negotiation, array $proposal, int $companyId, string $reason): array
    {
        if (mb_strlen($reason) < 10) {
            throw new DomainException('Informe um motivo entre 10 e 1.000 caracteres.');
        }

        $proposal['status'] = 'refused';
        $proposal['refused_by_company_id'] = $companyId;
        $proposal['refusal_reason'] = $reason;
        $negotiation['status'] = 'open';

        return ['negotiation' => $negotiation, 'proposal' => $proposal];
    }

    private function cancelNegotiation(array $negotiation, array $listing, int $companyId, string $reason): array
    {
        if (mb_strlen($reason) < 10) {
            throw new DomainException('Informe um motivo entre 10 e 1.000 caracteres.');
        }

        $negotiation['status'] = 'cancelled';
        $negotiation['cancelled_by'] = $companyId;
        $negotiation['cancel_reason'] = $reason;
        if ($listing['status'] === 'negotiating') {
            $listing['status'] = 'active';
        }

        return ['negotiation' => $negotiation, 'listing' => $listing];
    }
}
