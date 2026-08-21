<?php require __DIR__.'/_start.php'; ?>
<header class="profile-heading"><span>Acesso e privacidade</span><h2>Segurança da conta</h2><p>Ações sensíveis são processadas no servidor, sem confirmações simuladas em JavaScript.</p></header>
<article class="profile-card security-summary"><div class="profile-card__title"><i data-lucide="shield-check"></i><div><h3>Acesso principal</h3><p>Último login: <?=!empty($user['last_login_at'])?date('d/m/Y H:i',strtotime($user['last_login_at'])):'Não registrado'?></p></div></div><dl><div><dt>Administrador</dt><dd><?=htmlspecialchars($user['name']??'')?></dd></div><div><dt>E-mail de acesso</dt><dd><?=htmlspecialchars($user['email']??'')?></dd></div></dl><a class="profile-secondary" href="<?=app_url('/login')?>">Solicitar redefinição de senha</a></article>
<form class="profile-card danger-card" action="<?=app_url('/perfil/seguranca/desativar')?>" method="post"><?=csrf_field()?>
 <div class="profile-card__title"><i data-lucide="triangle-alert"></i><div><h3>Desativar conta</h3><p>Bloqueia os usuários e pausa os anúncios ativos.</p></div></div>
 <label>Para confirmar, digite <strong>EXCLUIR MINHA CONTA</strong><input name="confirmation" required autocomplete="off" pattern="EXCLUIR MINHA CONTA"></label>
 <div class="profile-actions"><button class="danger" type="submit">Desativar conta</button></div>
</form><?php require __DIR__.'/_end.php'; ?>
