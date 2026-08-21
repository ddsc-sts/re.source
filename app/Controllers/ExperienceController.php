<?php

final class ExperienceController
{
    public static function help(): void
    {
        view('help/index', ['titulo_pagina' => 'Central de Ajuda — Re.Source']);
    }

    public static function completeOnboarding(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_validate()) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $companyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
        if ($companyId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Faça login para continuar.']);
            return;
        }

        global $pdo;
        $pdo->prepare('UPDATE companies SET onboarding_completed = 1 WHERE id = ?')->execute([$companyId]);
        $_SESSION['onboarding_completed'] = true;
        echo json_encode(['success' => true]);
    }
}
