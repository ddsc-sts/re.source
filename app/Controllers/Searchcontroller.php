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
        $type        = filter_input(INPUT_GET, 'type',               FILTER_DEFAULT) ?? '';
        $state       = strtoupper(trim(filter_input(INPUT_GET, 'state', FILTER_DEFAULT) ?? ''));

        // ── Categorias para o filtro lateral ────────────────
        try {
            $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $categorias = [];
            error_log("Erro ao buscar categorias: " . $e->getMessage());
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
                $sql .= " AND (l.title LIKE :q OR l.description LIKE :q)";
                $params[':q'] = "%{$q}%";
            }

            if ($category_id) {
                $sql .= " AND l.category_id = :category_id";
                $params[':category_id'] = $category_id;
            }

            if (!empty($type) && in_array($type, ['offer', 'demand'], true)) {
                $sql .= " AND l.type = :type";
                $params[':type'] = $type;
            }

            if (!empty($state)) {
                $sql .= " AND l.location_state = :state";
                $params[':state'] = $state;
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