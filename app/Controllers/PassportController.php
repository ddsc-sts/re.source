<?php

final class PassportController
{
    public static function create(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_validate()) { http_response_code(419); exit('Sessão expirada.'); }
        global $pdo; $companyId=(int)($_SESSION['user']['company_id']??0); $id=(int)($_POST['negotiation_id']??0);
        $stmt=$pdo->prepare("SELECT n.*,l.title,l.quantity,l.unit,s.nome_fantasia seller,b.nome_fantasia buyer FROM negotiations n JOIN listings l ON l.id=n.listing_id JOIN companies s ON s.id=n.seller_company_id JOIN companies b ON b.id=n.buyer_company_id WHERE n.id=? AND n.status='concluded' AND (n.buyer_company_id=? OR n.seller_company_id=?)");
        $stmt->execute([$id,$companyId,$companyId]); $n=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$n){ http_response_code(404); exit('Negociação concluída não encontrada.'); }
        $quantity=(float)($n['proposed_quantity']?:$n['quantity']); $kg=$n['unit']==='ton'?$quantity*1000:($n['unit']==='kg'?$quantity:0);
        $code='RS-'.date('Y').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT); $token=hash('sha256',$code.'|'.random_bytes(32));
        $pdo->prepare("INSERT INTO material_passports(negotiation_id,passport_code,public_token,material_name,quantity_kg,origin_company,destination_company,reused_at) VALUES(?,?,?,?,?,?,?,COALESCE(?,NOW())) ON DUPLICATE KEY UPDATE negotiation_id=VALUES(negotiation_id)")->execute([$id,$code,$token,$n['title'],$kg,$n['seller'],$n['buyer'],$n['concluded_at']]);
        $stmt=$pdo->prepare('SELECT public_token FROM material_passports WHERE negotiation_id=?');$stmt->execute([$id]);redirect_to('/passaporte?token='.urlencode((string)$stmt->fetchColumn()));
    }
    public static function show(): void
    {
        global $pdo; $token=(string)($_GET['token']??'');
        $stmt=$pdo->prepare('SELECT * FROM material_passports WHERE public_token=?');$stmt->execute([$token]);$passport=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$passport){http_response_code(404);view('passport/show',['passport'=>null]);return;}
        view('passport/show',['titulo_pagina'=>'Passaporte '.$passport['passport_code'],'passport'=>$passport]);
    }
}
