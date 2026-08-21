<?php

final class ProfileController
{
    private static function companyId(): int
    {
        return (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
    }

    private static function loadCompany(PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT c.*,a.zip_code,a.street,a.number,a.complement,a.district,a.city,a.state FROM companies c LEFT JOIN addresses a ON a.id=c.address_id WHERE c.id=?");
        $stmt->execute([self::companyId()]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private static function render(string $page, string $title): void
    {
        global $pdo;
        $company = self::loadCompany($pdo);
        $stmt = $pdo->prepare("SELECT name,email,last_login_at FROM users WHERE company_id=? AND role='admin_company' AND deleted_at IS NULL ORDER BY id LIMIT 1");
        $stmt->execute([self::companyId()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        view('profile/'.$page, ['titulo_pagina'=>$title.' — Re.Source','company'=>$company,'user'=>$user,'profilePage'=>$page]);
    }

    private static function requirePost(string $fallback): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_validate()) {
            flash('error', 'Sua sessão expirou. Recarregue a página e tente novamente.');
            redirect_to($fallback);
        }
    }

    public static function company(): void { self::render('company', 'Perfil da empresa'); }
    public static function address(): void { self::render('address', 'Endereço comercial'); }
    public static function contact(): void { self::render('contact', 'Contato e responsável'); }
    public static function preferences(): void { self::render('preferences', 'Preferências'); }
    public static function security(): void { self::render('security', 'Segurança da conta'); }

    public static function saveCompany(): void
    {
        self::requirePost('/perfil/empresa'); global $pdo; $id=self::companyId();
        $fantasy=trim((string)($_POST['nome_fantasia']??'')); $legal=trim((string)($_POST['razao_social']??'')); $segment=trim((string)($_POST['segment']??''));
        if (mb_strlen($fantasy)<2 || mb_strlen($legal)<2) { flash('error','Informe nome fantasia e razão social.'); redirect_to('/perfil/empresa'); }
        $stmt=$pdo->prepare('SELECT logo_url FROM companies WHERE id=?');$stmt->execute([$id]);$logo=$stmt->fetchColumn()?:null;
        if (!empty($_FILES['logo_empresa']['tmp_name']) && ($_FILES['logo_empresa']['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_OK) {
            if ((int)$_FILES['logo_empresa']['size'] > 2*1024*1024) { flash('error','O logotipo deve ter no máximo 2 MB.'); redirect_to('/perfil/empresa'); }
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo_empresa']['tmp_name']); $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($extensions[$mime])) { flash('error','Envie uma imagem JPG, PNG ou WebP válida.'); redirect_to('/perfil/empresa'); }
            $dir=ROOT_PATH.'/uploads/logos'; if(!is_dir($dir)) mkdir($dir,0755,true); $name=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
            if(!move_uploaded_file($_FILES['logo_empresa']['tmp_name'],$dir.'/'.$name)){flash('error','Não foi possível salvar o logotipo.');redirect_to('/perfil/empresa');}
            $logo=app_url('/uploads/logos/'.$name);
        }
        $pdo->prepare('UPDATE companies SET nome_fantasia=?,razao_social=?,segment=?,logo_url=? WHERE id=?')->execute([$fantasy,$legal,$segment?:null,$logo,$id]);
        flash('success','Perfil da empresa atualizado.'); redirect_to('/perfil/empresa');
    }

    public static function saveAddress(): void
    {
        self::requirePost('/perfil/endereco'); global $pdo; $id=self::companyId(); $state=strtoupper(trim((string)($_POST['state']??'')));
        if ($state!=='' && !preg_match('/^[A-Z]{2}$/',$state)) { flash('error','Informe uma UF válida com duas letras.'); redirect_to('/perfil/endereco'); }
        $values=array_map(fn($key)=>trim((string)($_POST[$key]??'')),['zip_code','street','number','complement','district','city']);
        $pdo->beginTransaction(); try { $stmt=$pdo->prepare('SELECT address_id FROM companies WHERE id=? FOR UPDATE');$stmt->execute([$id]);$addressId=$stmt->fetchColumn();
            if($addressId){$pdo->prepare('UPDATE addresses SET zip_code=?,street=?,number=?,complement=?,district=?,city=?,state=? WHERE id=?')->execute([...$values,$state,$addressId]);}
            else{$pdo->prepare('INSERT INTO addresses(zip_code,street,number,complement,district,city,state) VALUES(?,?,?,?,?,?,?)')->execute([...$values,$state]);$pdo->prepare('UPDATE companies SET address_id=? WHERE id=?')->execute([$pdo->lastInsertId(),$id]);}
            $pdo->commit(); flash('success','Endereço comercial atualizado.');
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error','Não foi possível atualizar o endereço.');}
        redirect_to('/perfil/endereco');
    }

    public static function saveContact(): void
    {
        self::requirePost('/perfil/contato'); global $pdo; $email=filter_var(trim((string)($_POST['email']??'')),FILTER_VALIDATE_EMAIL);$phone=trim((string)($_POST['phone']??''));$responsible=trim((string)($_POST['responsible_name']??''));
        if(!$email || mb_strlen($responsible)<3){flash('error','Revise o e-mail e o nome do responsável.');redirect_to('/perfil/contato');}
        $pdo->prepare('UPDATE companies SET email=?,phone=?,responsible_name=? WHERE id=?')->execute([$email,$phone,$responsible,self::companyId()]);flash('success','Dados de contato atualizados.');redirect_to('/perfil/contato');
    }

    public static function savePreferences(): void
    {
        self::requirePost('/perfil/preferencias'); global $pdo;$theme=(string)($_POST['theme']??'system');if(!in_array($theme,['light','dark','system'],true))$theme='system';
        $pdo->prepare('UPDATE companies SET theme=?,notify_proposals=?,notify_chat=? WHERE id=?')->execute([$theme,isset($_POST['notify_proposals'])?1:0,isset($_POST['notify_chat'])?1:0,self::companyId()]);$_SESSION['user_theme']=$theme;flash('success','Preferências atualizadas.');redirect_to('/perfil/preferencias');
    }

    public static function deactivate(): void
    {
        self::requirePost('/perfil/seguranca'); if(trim((string)($_POST['confirmation']??''))!=='EXCLUIR MINHA CONTA'){flash('error','Digite a frase de confirmação exatamente como exibida.');redirect_to('/perfil/seguranca');}
        global $pdo;$id=self::companyId();$pdo->beginTransaction();try{$pdo->prepare("UPDATE companies SET status='inactive',deactivated_at=NOW() WHERE id=?")->execute([$id]);$pdo->prepare('UPDATE users SET is_active=0,deleted_at=NOW() WHERE company_id=?')->execute([$id]);$pdo->prepare("UPDATE listings SET status='paused',deleted_at=NOW() WHERE company_id=? AND deleted_at IS NULL")->execute([$id]);$pdo->commit();$_SESSION=[];session_destroy();redirect_to('/login?account=deleted');}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error','Não foi possível desativar a conta.');redirect_to('/perfil/seguranca');}
    }
}
