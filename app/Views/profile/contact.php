<?php require __DIR__.'/_start.php'; ?>
<header class="profile-heading"><span>Comunicação empresarial</span><h2>Contato e responsável</h2><p>Dados institucionais usados em avisos e tratativas comerciais.</p></header>
<form class="profile-card" action="<?=app_url('/perfil/contato/salvar')?>" method="post"><?=csrf_field()?>
 <div class="profile-card__title"><i data-lucide="contact-round"></i><div><h3>Canal principal</h3><p>Mantenha os dados acessíveis e atualizados.</p></div></div>
 <div class="profile-grid"><label>E-mail comercial<input type="email" name="email" required maxlength="150" value="<?=htmlspecialchars($company['email']??'')?>"></label><label>Telefone / WhatsApp<input type="tel" name="phone" maxlength="20" value="<?=htmlspecialchars($company['phone']??'')?>"></label><label class="wide">Responsável pela empresa<input name="responsible_name" required maxlength="150" value="<?=htmlspecialchars($company['responsible_name']??'')?>"></label></div>
 <div class="profile-actions"><button type="submit">Salvar contato <i data-lucide="check"></i></button></div>
</form><?php require __DIR__.'/_end.php'; ?>
