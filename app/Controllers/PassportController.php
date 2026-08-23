<?php

final class PassportController
{
    public static function create(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_validate()) {
            http_response_code(419);
            exit('Sessão expirada.');
        }

        global $pdo;
        $companyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
        $negotiationId = (int) ($_POST['negotiation_id'] ?? 0);

        $stmt = $pdo->prepare(
            "SELECT n.*, l.title, l.quantity, l.unit,
                    seller.nome_fantasia AS seller,
                    buyer.nome_fantasia AS buyer
             FROM negotiations n
             JOIN listings l ON l.id = n.listing_id
             JOIN companies seller ON seller.id = n.seller_company_id
             JOIN companies buyer ON buyer.id = n.buyer_company_id
             WHERE n.id = ?
               AND n.status = 'concluded'
               AND (n.buyer_company_id = ? OR n.seller_company_id = ?)"
        );
        $stmt->execute([$negotiationId, $companyId, $companyId]);
        $negotiation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$negotiation) {
            flash('error', 'Negociação concluída não encontrada.');
            redirect_to('/conversas');
        }

        $quantity = (float) ($negotiation['proposed_quantity'] ?: $negotiation['quantity']);
        $quantityKg = match ($negotiation['unit']) {
            'ton' => $quantity * 1000,
            'kg' => $quantity,
            default => 0,
        };

        $passportCode = 'RS-' . date('Y') . '-' . str_pad((string) $negotiationId, 5, '0', STR_PAD_LEFT);
        $publicToken = hash('sha256', $passportCode . '|' . random_bytes(32));

        try {
            $pdo->prepare(
                'INSERT INTO material_passports
                    (negotiation_id, passport_code, public_token, material_name, quantity_kg,
                     origin_company, destination_company, reused_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()))
                 ON DUPLICATE KEY UPDATE negotiation_id = VALUES(negotiation_id)'
            )->execute([
                $negotiationId,
                $passportCode,
                $publicToken,
                $negotiation['title'],
                $quantityKg,
                $negotiation['seller'],
                $negotiation['buyer'],
                $negotiation['concluded_at'],
            ]);

            $stmt = $pdo->prepare('SELECT public_token FROM material_passports WHERE negotiation_id = ?');
            $stmt->execute([$negotiationId]);
            $storedToken = (string) $stmt->fetchColumn();

            if ($storedToken === '') {
                throw new RuntimeException('O passaporte foi salvo sem token público.');
            }

            redirect_to('/passaporte?token=' . urlencode($storedToken));
        } catch (Throwable $error) {
            error_log('Falha ao criar passaporte de material: ' . $error->getMessage());
            flash('error', 'Não foi possível gerar o passaporte agora. Tente novamente.');
            redirect_to('/conversas/abrir?id=' . $negotiationId);
        }
    }

    public static function show(): void
    {
        global $pdo;
        $token = trim((string) ($_GET['token'] ?? ''));
        $passport = null;

        if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
            try {
                $stmt = $pdo->prepare('SELECT * FROM material_passports WHERE public_token = ?');
                $stmt->execute([$token]);
                $passport = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Throwable $error) {
                error_log('Falha ao consultar passaporte de material: ' . $error->getMessage());
            }
        }

        if (!$passport) {
            http_response_code(404);
            view('passport/show', [
                'titulo_pagina' => 'Passaporte não encontrado — Re.Source',
                'passport' => null,
            ]);
            return;
        }

        view('passport/show', [
            'titulo_pagina' => 'Passaporte ' . $passport['passport_code'],
            'passport' => $passport,
        ]);
    }
}
