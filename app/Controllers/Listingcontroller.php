<?php

require_once __DIR__ . '/../../config/conexao.php';

class ListingController
{
    // ══════════════════════════════════════════
    // VIEWS
    // ══════════════════════════════════════════

    public static function showDetail(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        global $pdo;

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: /re.source/busca');
            exit;
        }

        try {
            // ── 0. Lógica de visualizações únicas por sessão/dia ──────────
            $viewer_company_id = $_SESSION['company_id'] ?? null;
            $viewer_session_id = session_id();

            $stmtCheckView = $pdo->prepare("
                SELECT id FROM views_history
                WHERE listing_id = ?
                AND DATE(created_at) = CURDATE()
                AND (session_id = ? OR (company_id IS NOT NULL AND company_id = ?))
            ");
            $stmtCheckView->execute([$id, $viewer_session_id, $viewer_company_id]);

            if (!$stmtCheckView->fetch()) {
                $stmtInsertView = $pdo->prepare("INSERT INTO views_history (listing_id, company_id, session_id) VALUES (?, ?, ?)");
                $stmtInsertView->execute([$id, $viewer_company_id, $viewer_session_id]);

                $stmtUpdateTotal = $pdo->prepare("UPDATE listings SET views_count = views_count + 1 WHERE id = ?");
                $stmtUpdateTotal->execute([$id]);
            }

            // ── 1. Dados do anúncio principal ─────────────────────────────
            $stmt = $pdo->prepare("
                SELECT
                    l.*,
                    c.nome_fantasia AS company_name,
                    cat.name AS category_name
                FROM listings l
                INNER JOIN companies c ON c.id = l.company_id
                INNER JOIN categories cat ON cat.id = l.category_id
                WHERE l.id = ? AND l.deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            $anuncio = $stmt->fetch();

            if (!$anuncio) {
                http_response_code(404);
                die("<h1>Anúncio não encontrado ou indisponível.</h1>");
            }

            // ── 2. Imagens do anúncio ─────────────────────────────────────
            $stmtImg = $pdo->prepare("SELECT url FROM listing_images WHERE listing_id = ? ORDER BY `order` ASC");
            $stmtImg->execute([$id]);
            $imagens = $stmtImg->fetchAll(\PDO::FETCH_COLUMN);
            if (empty($imagens)) {
                $imagens[] = '/re.source/public/img/no-image.png';
            }

            // ── 3. Mais anúncios desse vendedor (máx 4) ──────────────────
            $stmtSeller = $pdo->prepare("
                SELECT l.id, l.title, l.price, l.type, l.location_city, l.location_state,
                       (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1) as thumb
                FROM listings l
                WHERE l.company_id = ? AND l.id != ? AND l.status = 'active' AND l.deleted_at IS NULL
                ORDER BY l.created_at DESC LIMIT 4
            ");
            $stmtSeller->execute([$anuncio['company_id'], $id]);
            $sellerAds = $stmtSeller->fetchAll();

            // ── 4. Anúncios relevantes da mesma categoria (máx 4) ─────────
            $stmtRelevant = $pdo->prepare("
                SELECT l.id, l.title, l.price, l.type, l.location_city, l.location_state,
                       (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1) as thumb
                FROM listings l
                WHERE l.category_id = ? AND l.company_id != ? AND l.id != ? AND l.status = 'active' AND l.deleted_at IS NULL
                ORDER BY l.created_at DESC LIMIT 4
            ");
            $stmtRelevant->execute([$anuncio['category_id'], $anuncio['company_id'], $id]);
            $relevantAds = $stmtRelevant->fetchAll();

        } catch (\PDOException $e) {
            die("Erro no banco de dados: " . $e->getMessage());
        }

        $unitLabel = [
            'kg'      => 'Kg',
            'ton'     => 'Ton',
            'm2'      => 'm²',
            'm3'      => 'm³',
            'unidade' => 'un.',
            'litro'   => 'L',
            'outro'   => '',
        ];

        view('listings/detail', [
            'anuncio'     => $anuncio,
            'imagens'     => $imagens,
            'sellerAds'   => $sellerAds,
            'relevantAds' => $relevantAds,
            'unitLabel'   => $unitLabel,
        ]);
    }

    public static function showCreate(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['company_id'])) {
            header('Location: /re.source/login');
            exit;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
            $categorias = $stmt->fetchAll();
        } catch (PDOException $e) {
            $categorias = [];
        }
        require_once __DIR__ . '/../Views/listings/create.php';
    }

    public static function showMeus(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['company_id'])) {
            header('Location: /re.source/login');
            exit;
        }
        global $pdo;
        $mensagem = '';
        if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
            $mensagem = "<div class='alert alert-success'>Anúncio excluído permanentemente!</div>";
        }
        try {
            $sql = "SELECT l.*, c.name as category_name,
                    (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1) as main_image
                    FROM listings l
                    LEFT JOIN categories c ON l.category_id = c.id
                    WHERE l.company_id = :company_id
                    ORDER BY l.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':company_id' => $_SESSION['company_id']]);
            $anuncios = $stmt->fetchAll();
        } catch (PDOException $e) {
            $anuncios = [];
            $mensagem = "<div class='alert alert-danger'>Erro ao carregar anúncios: " . $e->getMessage() . "</div>";
        }
        require_once __DIR__ . '/../Views/listings/index.php';
    }

    public static function showEdit(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['company_id'])) {
            header('Location: /re.source/login');
            exit;
        }
        global $pdo;
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: /re.source/meus-anuncios');
            exit;
        }

        // Exclusão de imagem individual
        if (isset($_GET['delete_image'])) {
            $img_id = filter_input(INPUT_GET, 'delete_image', FILTER_VALIDATE_INT);
            if ($img_id) {
                try {
                    $stmtImg = $pdo->prepare("SELECT url FROM listing_images WHERE id = :img_id AND listing_id = :listing_id");
                    $stmtImg->execute([':img_id' => $img_id, ':listing_id' => $id]);
                    $imgData = $stmtImg->fetch();
                    if ($imgData) {
                        $nome_arquivo = basename($imgData['url']);
                        $caminho_fisico = __DIR__ . '/../../uploads/listings/' . $nome_arquivo;
                        if (file_exists($caminho_fisico)) unlink($caminho_fisico);
                        $stmtDelImg = $pdo->prepare("DELETE FROM listing_images WHERE id = :img_id");
                        $stmtDelImg->execute([':img_id' => $img_id]);
                        header("Location: /re.source/anuncios/editar?id=$id&msg=img_deleted");
                        exit;
                    }
                } catch (Exception $e) {
                    // continua para a view com o erro
                }
            }
        }

        try {
            $stmtAnuncio = $pdo->prepare("SELECT * FROM listings WHERE id = :id AND company_id = :company_id");
            $stmtAnuncio->execute([':id' => $id, ':company_id' => $_SESSION['company_id']]);
            $anuncio = $stmtAnuncio->fetch();
            if (!$anuncio) {
                header('Location: /re.source/meus-anuncios');
                exit;
            }
            $stmtCat = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
            $categorias = $stmtCat->fetchAll();
            $stmtImgs = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = :id ORDER BY `order` ASC");
            $stmtImgs->execute([':id' => $id]);
            $imagens_atuais = $stmtImgs->fetchAll();
        } catch (PDOException $e) {
            die("Erro crítico: " . $e->getMessage());
        }
        require_once __DIR__ . '/../Views/listings/edit.php';
    }

    // ══════════════════════════════════════════
    // PROCESS CREATE
    // ══════════════════════════════════════════

    public static function processCreate(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['company_id'])) {
            echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
            exit;
        }

        global $pdo;

        $type           = filter_input(INPUT_POST, 'type', FILTER_DEFAULT);
        $title          = trim(filter_input(INPUT_POST, 'title', FILTER_DEFAULT));
        $category_id    = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $quantity       = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
        $unit           = filter_input(INPUT_POST, 'unit', FILTER_DEFAULT);
        $price_raw      = filter_input(INPUT_POST, 'price', FILTER_DEFAULT);
        $is_negotiable  = isset($_POST['is_negotiable']) ? 1 : 0;
        $location_state = trim(filter_input(INPUT_POST, 'location_state', FILTER_DEFAULT));
        $location_city  = trim(filter_input(INPUT_POST, 'location_city', FILTER_DEFAULT));
        $description    = trim(filter_input(INPUT_POST, 'description', FILTER_DEFAULT));

        $erros = [];
        if (!in_array($type, ['offer', 'demand'])) $erros[] = "Selecione se o anúncio é uma Oferta ou Demanda.";
        if (empty($title) || strlen($title) < 5)   $erros[] = "O título deve ter no mínimo 5 caracteres.";
        if (!$category_id)                          $erros[] = "Selecione uma categoria válida.";
        if ($quantity <= 0)                         $erros[] = "A quantidade deve ser maior que zero.";
        if (empty($location_state))                 $erros[] = "Selecione o Estado.";
        if (empty($location_city))                  $erros[] = "Selecione a Cidade.";

        $price = null;
        if ($type === 'offer') {
            if ($price_raw === '' || $price_raw === null) {
                $price = 0.00;
            } else {
                $price = str_replace(['.', ','], ['', '.'], $price_raw);
                $price = filter_var($price, FILTER_VALIDATE_FLOAT);
                if ($price === false || $price < 0) $erros[] = "Insira um preço válido ou deixe vazio para doação.";
            }
        }

        if (!empty($erros)) {
            echo json_encode(['success' => false, 'errors' => $erros]);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO listings (company_id, category_id, type, title, description, quantity, unit, price, is_negotiable, location_state, location_city, status)
                    VALUES (:company_id, :category_id, :type, :title, :description, :quantity, :unit, :price, :is_negotiable, :state, :city, 'active')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':company_id'    => $_SESSION['company_id'],
                ':category_id'   => $category_id,
                ':type'          => $type,
                ':title'         => $title,
                ':description'   => $description ?: null,
                ':quantity'      => $quantity,
                ':unit'          => $unit,
                ':price'         => $price,
                ':is_negotiable' => $is_negotiable,
                ':state'         => strtoupper($location_state),
                ':city'          => $location_city,
            ]);
            $listing_id = $pdo->lastInsertId();

            // Upload de imagens
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $upload_dir = __DIR__ . '/../../uploads/listings/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
                foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
                    if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                        $mime = $_FILES['images']['type'][$index];
                        if (in_array($mime, $permitidos)) {
                            $ext = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
                            $nome_arquivo = 'listing_' . $listing_id . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($tmp_name, $upload_dir . $nome_arquivo)) {
                                $url_publica = '/re.source/uploads/listings/' . $nome_arquivo;
                                $stmtImg = $pdo->prepare("INSERT INTO listing_images (listing_id, url, `order`) VALUES (:listing_id, :url, :order)");
                                $stmtImg->execute([':listing_id' => $listing_id, ':url' => $url_publica, ':order' => $index]);
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'redirect' => '/re.source/meus-anuncios']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
        }
        exit;
    }

    // ══════════════════════════════════════════
    // PROCESS EDIT
    // ══════════════════════════════════════════

    public static function processEdit(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['company_id'])) {
            header('Location: /re.source/login');
            exit;
        }

        global $pdo;
        $id = filter_input(INPUT_POST, 'listing_id', FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: /re.source/meus-anuncios');
            exit;
        }

        $type           = filter_input(INPUT_POST, 'type', FILTER_DEFAULT);
        $title          = trim(filter_input(INPUT_POST, 'title', FILTER_DEFAULT));
        $category_id    = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $quantity       = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
        $unit           = filter_input(INPUT_POST, 'unit', FILTER_DEFAULT);
        $price_raw      = filter_input(INPUT_POST, 'price', FILTER_DEFAULT);
        $is_negotiable  = isset($_POST['is_negotiable']) ? 1 : 0;
        $location_state = trim(filter_input(INPUT_POST, 'location_state', FILTER_DEFAULT));
        $location_city  = trim(filter_input(INPUT_POST, 'location_city', FILTER_DEFAULT));
        $description    = trim(filter_input(INPUT_POST, 'description', FILTER_DEFAULT));

        $erros = [];
        if (!in_array($type, ['offer', 'demand'])) $erros[] = "Selecione o tipo do anúncio.";
        if (empty($title) || strlen($title) < 5)   $erros[] = "O título deve ter pelo menos 5 caracteres.";
        if (!$category_id)                          $erros[] = "Selecione uma categoria válida.";
        if ($quantity <= 0)                         $erros[] = "A quantidade deve ser maior que zero.";
        if (empty($location_state))                 $erros[] = "Selecione o Estado (UF).";
        if (empty($location_city))                  $erros[] = "Selecione a cidade.";

        $price = null;
        if ($type === 'offer') {
            if ($price_raw === '' || $price_raw === null) {
                $price = 0.00;
            } else {
                $price = str_replace(['.', ','], ['', '.'], $price_raw);
                $price = filter_var($price, FILTER_VALIDATE_FLOAT);
                if ($price === false || $price < 0) $erros[] = "Informe um preço válido.";
            }
        }

        if (!empty($erros)) {
            header("Location: /re.source/anuncios/editar?id=$id&erros=" . urlencode(implode('|', $erros)));
            exit;
        }

        try {
            $pdo->beginTransaction();
            $sql = "UPDATE listings SET
                        type = :type, title = :title, description = :description,
                        category_id = :category_id, quantity = :quantity, unit = :unit,
                        price = :price, is_negotiable = :is_negotiable,
                        location_state = :state, location_city = :city
                    WHERE id = :id AND company_id = :company_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':type'          => $type,
                ':title'         => $title,
                ':description'   => $description ?: null,
                ':category_id'   => $category_id,
                ':quantity'      => $quantity,
                ':unit'          => $unit,
                ':price'         => $price,
                ':is_negotiable' => $is_negotiable,
                ':state'         => strtoupper($location_state),
                ':city'          => $location_city,
                ':id'            => $id,
                ':company_id'    => $_SESSION['company_id'],
            ]);

            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $upload_dir = __DIR__ . '/../../uploads/listings/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
                foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
                    if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                        $mime = $_FILES['images']['type'][$index];
                        if (in_array($mime, $permitidos)) {
                            $ext = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
                            $nome_arquivo = 'listing_' . $id . '_' . uniqid() . '.' . $ext;
                            move_uploaded_file($tmp_name, $upload_dir . $nome_arquivo);
                            $url_publica = '/re.source/uploads/listings/' . $nome_arquivo;
                            $stmtImg = $pdo->prepare("INSERT INTO listing_images (listing_id, url, `order`) VALUES (:listing_id, :url, :order)");
                            $stmtImg->execute([':listing_id' => $id, ':url' => $url_publica, ':order' => $index + 10]);
                        }
                    }
                }
            }

            $pdo->commit();
            header("Location: /re.source/anuncios/editar?id=$id&success=1");
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: /re.source/anuncios/editar?id=$id&erros=" . urlencode('Erro ao atualizar: ' . $e->getMessage()));
        }
        exit;
    }

    // ══════════════════════════════════════════
    // PROCESS DELETE
    // ══════════════════════════════════════════

    public static function processDelete(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['company_id'])) {
            header('Location: /re.source/login');
            exit;
        }

        global $pdo;
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if ($id) {
            try {
                $pdo->beginTransaction();
                $stmtImg = $pdo->prepare("DELETE FROM listing_images WHERE listing_id = :id");
                $stmtImg->execute([':id' => $id]);
                $stmtDel = $pdo->prepare("DELETE FROM listings WHERE id = :id AND company_id = :company_id");
                $stmtDel->execute([':id' => $id, ':company_id' => $_SESSION['company_id']]);
                $pdo->commit();
                header("Location: /re.source/meus-anuncios?deleted=1");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
            }
        }

        header("Location: /re.source/meus-anuncios");
        exit;
    }

    public static function showDetail(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        global $pdo;

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            header("Location: /re.source/base");
            exit;
        }

        try {
            // Lógica de Visualizações (Única por usuário/sessão por dia)
            $viewer_company_id = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? null;
            $viewer_session_id = session_id();

            $stmtCheckView = $pdo->prepare("
                SELECT id FROM views_history 
                WHERE listing_id = ? 
                AND DATE(created_at) = CURDATE()
                AND (session_id = ? OR (company_id IS NOT NULL AND company_id = ?))
            ");
            $stmtCheckView->execute([$id, $viewer_session_id, $viewer_company_id]);
            
            if (!$stmtCheckView->fetch()) {
                $stmtInsertView = $pdo->prepare("INSERT INTO views_history (listing_id, company_id, session_id) VALUES (?, ?, ?)");
                $stmtInsertView->execute([$id, $viewer_company_id, $viewer_session_id]);
                
                $stmtUpdateTotal = $pdo->prepare("UPDATE listings SET views_count = views_count + 1 WHERE id = ?");
                $stmtUpdateTotal->execute([$id]);
            }

            // Dados do Anúncio Principal
            $stmt = $pdo->prepare("
                SELECT 
                    l.*, 
                    c.nome_fantasia AS company_name, 
                    cat.name AS category_name
                FROM listings l
                INNER JOIN companies c ON c.id = l.company_id
                INNER JOIN categories cat ON cat.id = l.category_id
                WHERE l.id = ? AND l.deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            $anuncio = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$anuncio) {
                die("<h1>Anúncio não encontrado ou indisponível.</h1>");
            }

            // Imagens do Anúncio
            $stmtImg = $pdo->prepare("SELECT url FROM listing_images WHERE listing_id = ? ORDER BY `order` ASC");
            $stmtImg->execute([$id]);
            $imagens = $stmtImg->fetchAll(\PDO::FETCH_COLUMN);
            if (empty($imagens)) {
                $imagens[] = '/re.source/FrontEnd/img/no-image.png'; 
            }

            // Mais Anúncios desse Vendedor (Máx 4)
            $stmtSeller = $pdo->prepare("
                SELECT l.id, l.title, l.price, l.type, l.location_city, l.location_state,
                       (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1) as thumb
                FROM listings l
                WHERE l.company_id = ? AND l.id != ? AND l.status = 'active' AND l.deleted_at IS NULL
                ORDER BY l.created_at DESC LIMIT 4
            ");
            $stmtSeller->execute([$anuncio['company_id'], $id]);
            $sellerAds = $stmtSeller->fetchAll(\PDO::FETCH_ASSOC);

            // Anúncios Relevantes da mesma Categoria (Máx 4)
            $stmtRelevant = $pdo->prepare("
                SELECT l.id, l.title, l.price, l.type, l.location_city, l.location_state,
                       (SELECT url FROM listing_images li WHERE li.listing_id = l.id ORDER BY `order` ASC LIMIT 1) as thumb
                FROM listings l
                WHERE l.category_id = ? AND l.company_id != ? AND l.id != ? AND l.status = 'active' AND l.deleted_at IS NULL
                ORDER BY l.created_at DESC LIMIT 4
            ");
            $stmtRelevant->execute([$anuncio['category_id'], $anuncio['company_id'], $id]);
            $relevantAds = $stmtRelevant->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            die("Erro no banco de dados: " . $e->getMessage());
        }

        $titulo_pagina = $anuncio['title'] . " — Re.Source";
        $unitLabel = ['kg'=>'Kg','ton'=>'Ton','m2'=>'m²','m3'=>'m³','unidade'=>'un.','litro'=>'L','outro'=>''];

        view('listings/detail', [
            'titulo_pagina' => $titulo_pagina,
            'anuncio'       => $anuncio,
            'imagens'       => $imagens,
            'sellerAds'     => $sellerAds,
            'relevantAds'   => $relevantAds,
            'unitLabel'     => $unitLabel
        ]);
    }
}