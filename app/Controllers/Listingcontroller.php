<?php

require_once __DIR__ . '/../../config/conexao.php';

class ListingController
{
    private const IMAGE_MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

    private static function companyId(): ?int
    {
        $companyId = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? null;
        return $companyId ? (int) $companyId : null;
    }

    private static function validateImageUploads(array $files, array &$errors): array
    {
        if (!isset($files['error']) || !is_array($files['error'])) {
            return [];
        }

        $uploads = [];
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($files['error'] as $index => $error) {
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = 'Não foi possível receber uma das imagens.';
                continue;
            }

            $tmpName = $files['tmp_name'][$index] ?? '';
            $size = (int) ($files['size'][$index] ?? 0);
            if ($size <= 0 || $size > self::MAX_IMAGE_SIZE) {
                $errors[] = 'Cada imagem deve ter no máximo 5 MB.';
                continue;
            }

            $mime = $finfo->file($tmpName) ?: '';
            if (!isset(self::IMAGE_MIME_EXTENSIONS[$mime])) {
                $errors[] = 'Envie somente imagens JPG, PNG ou WebP.';
                continue;
            }

            $uploads[] = [
                'tmp_name' => $tmpName,
                'extension' => self::IMAGE_MIME_EXTENSIONS[$mime],
            ];
        }

        return $uploads;
    }

    private static function storeImageUploads(
        PDO $pdo,
        array $uploads,
        int $listingId,
        int $startOrder,
        array &$storedFiles
    ): void {
        if (!$uploads) {
            return;
        }

        $uploadDir = __DIR__ . '/../../uploads/listings/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
        }

        $stmtImage = $pdo->prepare(
            'INSERT INTO listing_images (listing_id, url, `order`) VALUES (:listing_id, :url, :order)'
        );

        foreach ($uploads as $offset => $upload) {
            $filename = 'listing_' . $listingId . '_' . bin2hex(random_bytes(8)) . '.' . $upload['extension'];
            $destination = $uploadDir . $filename;
            if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                throw new RuntimeException('Não foi possível salvar uma das imagens.');
            }

            $storedFiles[] = $destination;
            $stmtImage->execute([
                ':listing_id' => $listingId,
                ':url' => '/re.source/uploads/listings/' . $filename,
                ':order' => $startOrder + $offset,
            ]);
        }
    }

    // ══════════════════════════════════════════
    // VIEWS
    // ══════════════════════════════════════════



    public static function showCreate(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!self::companyId()) {
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
        $sucesso = false;
        $erros = [];
        require_once __DIR__ . '/../Views/listings/create.php';
    }

    public static function showMeus(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = self::companyId();
        if (!$companyId) {
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
            $stmt->execute([':company_id' => $companyId]);
            $anuncios = $stmt->fetchAll();
        } catch (PDOException $e) {
            $anuncios = [];
            $mensagem = "<div class='alert alert-danger'>Erro ao carregar anúncios: " . $e->getMessage() . "</div>";
        }
        try {
            $stmtEmpresa = $pdo->prepare("SELECT razao_social, nome_fantasia, logo_url FROM companies WHERE id = ?");
            $stmtEmpresa->execute([$companyId]);
            $empresa = $stmtEmpresa->fetch();
            $nome_exibicao = !empty($empresa['nome_fantasia']) ? $empresa['nome_fantasia'] : ($empresa['razao_social'] ?? 'Minha Empresa');
            $logo_url = $empresa['logo_url'] ?? null;
        } catch (PDOException $e) {
            $nome_exibicao = 'Erro';
            $logo_url = null;
        }

        require_once __DIR__ . '/../Views/listings/index.php';
    }

    public static function showEdit(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = self::companyId();
        if (!$companyId) {
            header('Location: /re.source/login');
            exit;
        }
        global $pdo;
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: /re.source/meus-anuncios');
            exit;
        }

        try {
            $stmtAnuncio = $pdo->prepare("SELECT * FROM listings WHERE id = :id AND company_id = :company_id");
            $stmtAnuncio->execute([':id' => $id, ':company_id' => $companyId]);
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
            $erros = !empty($_GET['erros']) ? explode('|', (string) $_GET['erros']) : [];
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

        $companyId = self::companyId();
        if (!$companyId) {
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

        $imageUploads = self::validateImageUploads($_FILES['images'] ?? [], $erros);
        if (!$imageUploads) {
            $erros[] = "Adicione pelo menos uma imagem válida ao anúncio.";
        }

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

        $storedFiles = [];
        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO listings (company_id, category_id, type, title, description, quantity, unit, price, is_negotiable, location_state, location_city, status)
                    VALUES (:company_id, :category_id, :type, :title, :description, :quantity, :unit, :price, :is_negotiable, :state, :city, 'active')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':company_id'    => $companyId,
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

            self::storeImageUploads($pdo, $imageUploads, (int) $listing_id, 0, $storedFiles);

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Anúncio publicado com sucesso!',
                'redirect' => '/re.source/anuncio?id=' . (int) $listing_id,
            ]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($storedFiles as $storedFile) {
                if (is_file($storedFile)) {
                    unlink($storedFile);
                }
            }
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
        $companyId = self::companyId();
        if (!$companyId) {
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
        $deleteImageIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['delete_image_ids'] ?? [])),
            static fn (int $imageId): bool => $imageId > 0
        )));

        $erros = [];
        if (!in_array($type, ['offer', 'demand'])) $erros[] = "Selecione o tipo do anúncio.";
        if (empty($title) || strlen($title) < 5)   $erros[] = "O título deve ter pelo menos 5 caracteres.";
        if (!$category_id)                          $erros[] = "Selecione uma categoria válida.";
        if ($quantity <= 0)                         $erros[] = "A quantidade deve ser maior que zero.";
        if (empty($location_state))                 $erros[] = "Selecione o Estado (UF).";
        if (empty($location_city))                  $erros[] = "Selecione a cidade.";

        $imageUploads = self::validateImageUploads($_FILES['images'] ?? [], $erros);

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

        $storedFiles = [];
        try {
            $pdo->beginTransaction();
            $imageFilesToDelete = [];

            $stmtOwner = $pdo->prepare(
                "SELECT id FROM listings WHERE id = :id AND company_id = :company_id FOR UPDATE"
            );
            $stmtOwner->execute([':id' => $id, ':company_id' => $companyId]);
            if (!$stmtOwner->fetchColumn()) {
                throw new RuntimeException('Anúncio não encontrado ou sem permissão para edição.');
            }

            $stmtCurrentImages = $pdo->prepare(
                "SELECT id, url, `order` FROM listing_images WHERE listing_id = :id ORDER BY `order` ASC FOR UPDATE"
            );
            $stmtCurrentImages->execute([':id' => $id]);
            $currentImages = $stmtCurrentImages->fetchAll(PDO::FETCH_ASSOC);
            $currentImagesById = [];
            foreach ($currentImages as $currentImage) {
                $currentImagesById[(int) $currentImage['id']] = $currentImage;
            }

            $authorizedDeleteIds = array_values(array_intersect(
                $deleteImageIds,
                array_keys($currentImagesById)
            ));
            if ((count($currentImages) - count($authorizedDeleteIds) + count($imageUploads)) < 1) {
                throw new RuntimeException('O anúncio deve permanecer com pelo menos uma imagem.');
            }

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
                ':company_id'    => $companyId,
            ]);

            if ($authorizedDeleteIds) {
                $deletePlaceholders = implode(',', array_fill(0, count($authorizedDeleteIds), '?'));
                $stmtDeleteImages = $pdo->prepare(
                    "DELETE FROM listing_images WHERE listing_id = ? AND id IN ($deletePlaceholders)"
                );
                $stmtDeleteImages->execute(array_merge([$id], $authorizedDeleteIds));
                foreach ($authorizedDeleteIds as $imageId) {
                    $imageFilesToDelete[] = __DIR__ . '/../../uploads/listings/'
                        . basename($currentImagesById[$imageId]['url']);
                }
            }

            $remainingOrders = array_map(
                static fn (array $image): int => (int) $image['order'],
                array_filter(
                    $currentImages,
                    static fn (array $image): bool => !in_array((int) $image['id'], $authorizedDeleteIds, true)
                )
            );
            $startOrder = $remainingOrders ? max($remainingOrders) + 1 : 0;
            self::storeImageUploads($pdo, $imageUploads, (int) $id, $startOrder, $storedFiles);

            $pdo->commit();
            foreach ($imageFilesToDelete as $imageFile) {
                if (is_file($imageFile)) {
                    unlink($imageFile);
                }
            }
            header("Location: /re.source/anuncios/editar?id=$id&success=1");
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($storedFiles as $storedFile) {
                if (is_file($storedFile)) {
                    unlink($storedFile);
                }
            }
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
        $companyId = self::companyId();
        if (!$companyId) {
            header('Location: /re.source/login');
            exit;
        }

        global $pdo;
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if ($id) {
            try {
                $pdo->beginTransaction();
                $stmtOwner = $pdo->prepare("SELECT id FROM listings WHERE id = :id AND company_id = :company_id FOR UPDATE");
                $stmtOwner->execute([':id' => $id, ':company_id' => $companyId]);
                if (!$stmtOwner->fetchColumn()) {
                    $pdo->rollBack();
                    header("Location: /re.source/meus-anuncios");
                    exit;
                }
                $stmtImageFiles = $pdo->prepare("SELECT url FROM listing_images WHERE listing_id = :id");
                $stmtImageFiles->execute([':id' => $id]);
                $imageFiles = array_map(
                    static fn (string $url): string => __DIR__ . '/../../uploads/listings/' . basename($url),
                    $stmtImageFiles->fetchAll(PDO::FETCH_COLUMN)
                );
                $stmtImg = $pdo->prepare("DELETE FROM listing_images WHERE listing_id = :id");
                $stmtImg->execute([':id' => $id]);
                $stmtDel = $pdo->prepare("DELETE FROM listings WHERE id = :id AND company_id = :company_id");
                $stmtDel->execute([':id' => $id, ':company_id' => $companyId]);
                $pdo->commit();
                foreach ($imageFiles as $imageFile) {
                    if (is_file($imageFile)) {
                        unlink($imageFile);
                    }
                }
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
            // Confirma que o anúncio existe antes de registrar a visualização.
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
                http_response_code(404);
                echo '<h1>Anúncio não encontrado ou indisponível.</h1>';
                exit;
            }

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

            // Imagens do Anúncio
            $stmtImg = $pdo->prepare("SELECT url FROM listing_images WHERE listing_id = ? ORDER BY `order` ASC");
            $stmtImg->execute([$id]);
            $imagens = $stmtImg->fetchAll(\PDO::FETCH_COLUMN);

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
