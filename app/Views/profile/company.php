<?php require __DIR__.'/_start.php'; ?>
<header class="profile-heading"><span>Identidade e apresentação</span><h2>Perfil da empresa</h2><p>Informações que representam sua organização no marketplace.</p></header>
<form class="profile-card" action="<?=app_url('/perfil/empresa/salvar')?>" method="post" enctype="multipart/form-data"><?=csrf_field()?>
 <div class="profile-card__title"><i data-lucide="factory"></i><div><h3>Identidade empresarial</h3><p>CNPJ: <?=htmlspecialchars($company['cnpj']??'')?></p></div></div>
 <div class="profile-logo"><div><?php if(!empty($company['logo_url'])):?><img src="<?=htmlspecialchars($company['logo_url'])?>" alt="Logo atual"><?php else:?><i data-lucide="building-2"></i><?php endif;?></div><label>Logotipo<input type="file" name="logo_empresa" accept="image/png,image/jpeg,image/webp"><small>JPG, PNG ou WebP, até 2 MB.</small></label></div>
 <div class="profile-grid"><label>Nome fantasia<input name="nome_fantasia" required maxlength="200" value="<?=htmlspecialchars($company['nome_fantasia']??'')?>"></label><label>Razão social<input name="razao_social" required maxlength="200" value="<?=htmlspecialchars($company['razao_social']??'')?>"></label><label class="wide">Descrição da empresa<textarea name="segment" rows="6" maxlength="1000"><?=htmlspecialchars($company['segment']??'')?></textarea></label></div>
 <div class="profile-actions"><button type="submit">Salvar perfil <i data-lucide="check"></i></button></div>
</form><?php require __DIR__.'/_end.php'; ?>
