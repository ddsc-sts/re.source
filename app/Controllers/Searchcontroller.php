<?php
// app/Controllers/SearchController.php

// CORRIGIDO: o arquivo está em config/conexao.php
require_once __DIR__ . '/../../config/conexao.php';

class SearchController
{
    public static function index(): void
    {
        global $pdo;

        // Verifica conexão
        if (!$pdo) {
            die("Erro: Conexão com o banco de dados não estabelecida.");
        }

        // ── Parâmetros de busca ──────────────────────────────
        $q           = trim(filter_input(INPUT_GET, 'q',             FILTER_DEFAULT) ?? '');
        $category_id = filter_input(INPUT_GET, 'category_id',        FILTER_VALIDATE_INT) ?: null;
        $cat_nome    = trim(filter_input(INPUT_GET, 'cat_nome',       FILTER_DEFAULT) ?? '');
        $company_id  = filter_input(INPUT_GET, 'empresa',             FILTER_VALIDATE_INT) ?: null;
        $type        = filter_input(INPUT_GET, 'type',               FILTER_DEFAULT) ?? '';
        $state       = strtoupper(trim(filter_input(INPUT_GET, 'location_state', FILTER_DEFAULT) ?? ''));
        $city        = trim(filter_input(INPUT_GET, 'location_city', FILTER_DEFAULT) ?? '');

        // ── Categorias para o filtro lateral ────────────────
        try {
            $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $categorias = [];
            error_log("Erro ao buscar categorias: " . $e->getMessage());
        }

        // O cabeçalho e o rodapé enviam o nome legível da categoria.
        if (!$category_id && $cat_nome !== '') {
            $normalizedCategoryName = mb_strtolower($cat_nome, 'UTF-8');
            $legacyCategoryAliases = [
                'papelão' => 'papel/papelão',
                'eletrônicos' => 'eletrônico',
            ];
            $normalizedCategoryName = $legacyCategoryAliases[$normalizedCategoryName]
                ?? $normalizedCategoryName;

            foreach ($categorias as $cat) {
                if (mb_strtolower(trim($cat['name']), 'UTF-8') === $normalizedCategoryName) {
                    $category_id = (int) $cat['id'];
                    break;
                }
            }
        }

        // ── Nome da categoria selecionada (para o título) ───
        $categoriaSelecionada = null;
        if ($category_id) {
            foreach ($categorias as $cat) {
                if ((int)$cat['id'] === (int)$category_id) {
                    $categoriaSelecionada = $cat['name'];
                    break;
                }
            }
        }

        // ── Query dinâmica ───────────────────────────────────
        try {
            $sql = "
                SELECT
                    l.id,
                    l.title,
                    l.type,
                    l.quantity,
                    l.unit,
                    l.price,
                    l.is_negotiable,
                    l.location_city,
                    l.location_state,
                    l.created_at,
                    c.name  AS category_name,
                    co.nome_fantasia AS company_name,
                    (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1) AS main_image
                FROM listings l
                LEFT JOIN categories c  ON l.category_id = c.id
                LEFT JOIN companies  co ON l.company_id  = co.id
                WHERE l.status = 'active'
                  AND l.deleted_at IS NULL
            ";

            $params = [];

            if (!empty($q)) {
                // Não reutilize o mesmo placeholder nomeado: com prepared
                // statements nativos do MySQL isso pode gerar HY093.
                $sql .= " AND (l.title LIKE :q_title OR l.description LIKE :q_description)";
                $params[':q_title'] = "%{$q}%";
                $params[':q_description'] = "%{$q}%";
            }

            if ($category_id) {
                $sql .= " AND l.category_id = :category_id";
                $params[':category_id'] = $category_id;
            }

            if ($company_id) {
                $sql .= " AND l.company_id = :company_id";
                $params[':company_id'] = $company_id;
            }

            if (!empty($type) && in_array($type, ['offer', 'demand'], true)) {
                $sql .= " AND l.type = :type";
                $params[':type'] = $type;
            }

            if (!empty($state)) {
                $sql .= " AND l.location_state = :state";
                $params[':state'] = $state;
            }

            if (!empty($city)) {
                $sql .= " AND l.location_city = :city";
                $params[':city'] = $city;
            }

            $sql .= " ORDER BY l.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Throwable $e) {
            $anuncios = [];
            error_log("Erro na busca: " . $e->getMessage());
        }

        // ── Carrega a view ──────────────────────────────────
        require_once __DIR__ . '/../Views/search/index.php';
    }
}
