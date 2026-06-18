<?php
// atualizar_conta.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php"; 

// Pega o ID correto da sessão e bloqueia se não estiver logado
$company_id = $_SESSION['user']['company_id'] ?? null; 

if (!$company_id) {
    header("Location: login.php");
    exit();
}

// 1. HIGIENIZAÇÃO E CAPTURA DOS DADOS DO FORMULÁRIO
$nome_fantasia    = filter_input(INPUT_POST, 'nome_fantasia', FILTER_SANITIZE_SPECIAL_CHARS);
$razao_social     = filter_input(INPUT_POST, 'razao_social', FILTER_SANITIZE_SPECIAL_CHARS);
$segment          = filter_input(INPUT_POST, 'segment', FILTER_SANITIZE_SPECIAL_CHARS);
$phone            = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
$email_comercial  = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$responsible_name = filter_input(INPUT_POST, 'responsible_name', FILTER_SANITIZE_SPECIAL_CHARS);

// Dados de Endereço
$zip_code   = filter_input(INPUT_POST, 'zip_code', FILTER_SANITIZE_SPECIAL_CHARS);
$street     = filter_input(INPUT_POST, 'street', FILTER_SANITIZE_SPECIAL_CHARS);
$number     = filter_input(INPUT_POST, 'number', FILTER_SANITIZE_SPECIAL_CHARS);
$complement = filter_input(INPUT_POST, 'complement', FILTER_SANITIZE_SPECIAL_CHARS);
$city       = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_SPECIAL_CHARS);
$state      = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$email_comercial) {
    $_SESSION['error'] = "E-mail comercial inválido.";
    header("Location: conta.php");
    exit();
}

try {
    // Inicia uma transação para garantir que endereço e empresa atualizem juntos
    $pdo->beginTransaction();

    // 2. BUSCAR O ADDRESS_ID DA EMPRESA
    $stmt = $pdo->prepare("SELECT address_id, logo_url FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $empresa_atual = $stmt->fetch();
    $address_id = $empresa_atual['address_id'] ?? null;
    $logo_url = $empresa_atual['logo_url'] ?? null;

    // 3. GERENCIAMENTO E UPLOAD DO LOGOTIPO
    if (isset($_FILES['logo_empresa']) && $_FILES['logo_empresa']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['logo_empresa']['tmp_name'];
        $fileName = $_FILES['logo_empresa']['name'];
        $fileSize = $_FILES['logo_empresa']['size'];
        $fileType = $_FILES['logo_empresa']['type'];
        
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        // Validações do arquivo
        if (in_array($fileExtension, $allowedExtensions)) {
            if ($fileSize <= 2 * 1024 * 1024) { // Limite de 2MB
                // Cria o diretório de uploads se não existir
                $uploadFileDir = 'uploads/logos/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                // Gera um nome único e seguro para o arquivo
                $newFileName = md5(time() . $company_id) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Se o upload deu certo, apaga a logo antiga do servidor (se houver e for local)
                    if ($logo_url && file_exists($logo_url)) {
                        unlink($logo_url);
                    }
                    $logo_url = $dest_path; // Atualiza o caminho para salvar no banco
                }
            } else {
                throw new Exception("O logotipo excede o tamanho máximo de 2MB.");
            }
        } else {
            throw new Exception("Formato de imagem não suportado. Use apenas JPG, JPEG ou PNG.");
        }
    }

    // 4. ATUALIZAR OU INSERIR ENDEREÇO
    if ($address_id) {
        // Atualiza endereço existente
        $sqlAddress = "UPDATE addresses SET zip_code = ?, street = ?, number = ?, complement = ?, city = ?, state = ? WHERE id = ?";
        $stmtAddress = $pdo->prepare($sqlAddress);
        $stmtAddress->execute([$zip_code, $street, $number, $complement, $city, $state, $address_id]);
    } else {
        // Se por algum motivo não tinha endereço vinculado, cria um novo
        $sqlAddress = "INSERT INTO addresses (zip_code, street, number, complement, city, state) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtAddress = $pdo->prepare($sqlAddress);
        $stmtAddress->execute([$zip_code, $street, $number, $complement, $city, $state]);
        $address_id = $pdo->lastInsertId();
    }

    // 5. ATUALIZAR DADOS DA EMPRESA
    $sqlCompany = "UPDATE companies SET 
                    nome_fantasia = ?, 
                    razao_social = ?, 
                    segment = ?, 
                    phone = ?, 
                    email = ?, 
                    responsible_name = ?, 
                    logo_url = ?,
                    address_id = ?
                   WHERE id = ?";
    
    $stmtCompany = $pdo->prepare($sqlCompany);
    $stmtCompany->execute([
        $nome_fantasia, 
        $razao_social, 
        $segment, 
        $phone, 
        $email_comercial, 
        $responsible_name, 
        $logo_url,
        $address_id,
        $company_id
    ]);

    // Comita as alterações com segurança
    $pdo->commit();
    
    $_SESSION['success'] = "Dados cadastrais atualizados com sucesso!";
    header("Location: conta.php");
    exit();

} catch (Exception $e) {
    // Se algo der errado, desfaz tudo no banco
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Tratamento de erro amigável
    $_SESSION['error'] = "Erro ao atualizar: " . $e->getMessage();
    header("Location: conta.php");
    exit();
}