<?php
$css_especifico=asset_url('/css/profile-pages.css');
require VIEW_PATH.'/components/header.php';
$profileTabs=[
 ['company','/perfil/empresa','building-2','Empresa'],['address','/perfil/endereco','map-pin','Endereço'],['contact','/perfil/contato','contact','Contato'],['preferences','/perfil/preferencias','sliders-horizontal','Preferências'],['security','/perfil/seguranca','shield-check','Segurança']
];
?>
<main class="profile-layout">
 <section class="profile-identity">
  <div class="profile-identity__avatar"><?php if(!empty($company['logo_url'])):?><img src="<?=htmlspecialchars($company['logo_url'],ENT_QUOTES,'UTF-8')?>" alt="Logo da empresa"><?php else:?><i data-lucide="building-2"></i><?php endif;?></div>
  <div><span>Workspace empresarial</span><h1><?=htmlspecialchars($company['nome_fantasia']?:($company['razao_social']??'Minha empresa'),ENT_QUOTES,'UTF-8')?></h1><p>Central de dados, preferências e segurança da organização.</p></div>
  <a href="<?=app_url('/base')?>"><i data-lucide="arrow-up-right"></i><span>Ir para o painel</span></a>
 </section>
 <nav class="profile-sections" aria-label="Seções do perfil"><?php foreach($profileTabs as [$key,$url,$icon,$label]):?><a href="<?=app_url($url)?>" <?=$profilePage===$key?'aria-current="page"':''?>><i data-lucide="<?=$icon?>"></i><span><?=$label?></span><?php if($profilePage===$key):?><small>Atual</small><?php endif;?></a><?php endforeach;?></nav>
 <section class="profile-content">
