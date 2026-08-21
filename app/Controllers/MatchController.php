<?php

final class MatchController
{
    public static function index(): void
    {
        global $pdo;
        $companyId = (int) ($_SESSION['user']['company_id'] ?? 0);
        $result = MatchService::recommendations($pdo, $companyId, (int)($_GET['listing_id'] ?? 0));
        view('match/index', ['titulo_pagina'=>'Re.Source Match', 'listing'=>$result['listing'], 'matches'=>$result['matches']]);
    }
}
