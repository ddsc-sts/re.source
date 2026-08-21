<?php

final class MatchService
{
    public static function recommendations(PDO $pdo, int $companyId, int $listingId = 0): array
    {
        $listingSql = "SELECT l.*, c.name category_name FROM listings l JOIN categories c ON c.id=l.category_id WHERE l.company_id=? AND l.deleted_at IS NULL";
        $params = [$companyId];
        if ($listingId > 0) { $listingSql .= ' AND l.id=?'; $params[] = $listingId; }
        $listingSql .= ' ORDER BY l.created_at DESC LIMIT 1';
        $stmt = $pdo->prepare($listingSql); $stmt->execute($params);
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$listing) return ['listing' => null, 'matches' => []];

        $stmt = $pdo->prepare(
            "SELECT c.id,c.nome_fantasia,c.razao_social,c.segment,a.city,a.state,
              EXISTS(SELECT 1 FROM listings x WHERE x.company_id=c.id AND x.category_id=:cat AND x.type<>:type AND x.status='active' AND x.deleted_at IS NULL) complementary,
              EXISTS(SELECT 1 FROM views_history vh JOIN listings vl ON vl.id=vh.listing_id WHERE vh.company_id=c.id AND vl.category_id=:view_cat) viewed_similar,
              EXISTS(SELECT 1 FROM negotiations n JOIN listings nl ON nl.id=n.listing_id WHERE (n.buyer_company_id=c.id OR n.seller_company_id=c.id) AND nl.category_id=:neg_cat AND n.status='concluded') negotiated_similar
             FROM companies c LEFT JOIN addresses a ON a.id=c.address_id
             WHERE c.id<>:company AND c.status='active' ORDER BY c.nome_fantasia"
        );
        $stmt->execute(['cat'=>$listing['category_id'],'type'=>$listing['type'],'view_cat'=>$listing['category_id'],'neg_cat'=>$listing['category_id'],'company'=>$companyId]);
        $matches = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $candidate) {
            $criteria = []; $score = 5;
            if ($candidate['complementary']) { $score += 40; $criteria[] = ['Material complementar', 40]; }
            if ($listing['location_city'] && strcasecmp((string)$candidate['city'], (string)$listing['location_city']) === 0) { $score += 20; $criteria[] = ['Mesma cidade', 20]; }
            elseif ($listing['location_state'] && $candidate['state'] === $listing['location_state']) { $score += 10; $criteria[] = ['Mesmo estado', 10]; }
            if ((float)$listing['quantity'] <= 1000) { $score += 15; $criteria[] = ['Quantidade operacional compatível', 15]; }
            if ($candidate['viewed_similar']) { $score += 10; $criteria[] = ['Pesquisou material semelhante', 10]; }
            if ($candidate['negotiated_similar']) { $score += 10; $criteria[] = ['Histórico na categoria', 10]; }
            $candidate['score'] = min(100, $score); $candidate['criteria'] = $criteria;
            $matches[] = $candidate;
        }
        usort($matches, fn($a,$b) => $b['score'] <=> $a['score']);
        return ['listing'=>$listing, 'matches'=>array_slice($matches,0,12)];
    }
}
