<?php

class NegotiationController
{
    public static function start(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        if (!csrf_validate()) {
            flash('error', 'A sessão do formulário expirou. Tente novamente.');
            redirect_to('/busca');
        }

        $listingId = filter_input(INPUT_POST, 'listing_id', FILTER_VALIDATE_INT);
        $companyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
        if (!$listingId || !$companyId) {
            flash('error', 'Não foi possível identificar o anúncio.');
            redirect_to('/busca');
        }

        global $pdo;

        try {
            $stmt = $pdo->prepare(
                "SELECT l.id, l.company_id, l.type, l.status, c.status AS company_status
                 FROM listings l
                 INNER JOIN companies c ON c.id = l.company_id
                 WHERE l.id = ? AND l.deleted_at IS NULL
                 LIMIT 1"
            );
            $stmt->execute([$listingId]);
            $listing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$listing || $listing['status'] !== 'active' || $listing['company_status'] !== 'active') {
                throw new DomainException('Este anúncio não está disponível para conversa.');
            }

            $ownerCompanyId = (int) $listing['company_id'];
            if ($ownerCompanyId === $companyId) {
                throw new DomainException('Você não pode iniciar uma conversa sobre o próprio anúncio.');
            }

            if ($listing['type'] === 'offer') {
                $buyerCompanyId = $companyId;
                $sellerCompanyId = $ownerCompanyId;
            } else {
                $buyerCompanyId = $ownerCompanyId;
                $sellerCompanyId = $companyId;
            }

            $stmtExisting = $pdo->prepare(
                'SELECT id FROM negotiations
                 WHERE listing_id = ? AND buyer_company_id = ? AND seller_company_id = ?
                 LIMIT 1'
            );
            $stmtExisting->execute([$listingId, $buyerCompanyId, $sellerCompanyId]);
            $negotiationId = $stmtExisting->fetchColumn();

            if (!$negotiationId) {
                try {
                    $pdo->beginTransaction();
                    $stmtInsert = $pdo->prepare(
                        "INSERT INTO negotiations
                            (listing_id, buyer_company_id, seller_company_id, status)
                         VALUES (?, ?, ?, 'open')"
                    );
                    $stmtInsert->execute([$listingId, $buyerCompanyId, $sellerCompanyId]);
                    $negotiationId = (int) $pdo->lastInsertId();
                    $pdo->commit();
                    flash('success', 'Conversa iniciada com sucesso.');
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }

                    $stmtExisting->execute([$listingId, $buyerCompanyId, $sellerCompanyId]);
                    $negotiationId = $stmtExisting->fetchColumn();
                    if (!$negotiationId) {
                        throw $e;
                    }
                }
            } else {
                flash('info', 'A conversa deste anúncio já existia e foi reaberta.');
            }

            redirect_to('/conversas/abrir?id=' . (int) $negotiationId);
        } catch (DomainException $e) {
            flash('warning', $e->getMessage());
            redirect_to('/anuncio?id=' . (int) $listingId);
        } catch (Throwable $e) {
            flash('error', 'Não foi possível iniciar a conversa agora.');
            redirect_to('/anuncio?id=' . (int) $listingId);
        }
    }
}
