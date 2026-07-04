<?php
// Variáveis seguras passadas pelo controller

// Variáveis seguras — nunca null — para usar no HTML sem warnings
$logo_url        = $empresa['logo_url']        ?? '';
$nome_fantasia   = $empresa['nome_fantasia']   ?? '';
$razao_social    = $empresa['razao_social']    ?? '';
$nome_exibicao   = !empty($nome_fantasia) ? $nome_fantasia : $razao_social;
$cnpj            = $empresa['cnpj']            ?? '';
$segment         = $empresa['segment']         ?? '';
$phone           = $empresa['phone']           ?? '';
$email           = $empresa['email']           ?? '';
$responsible     = $empresa['responsible_name'] ?? '';
$zip_code        = $empresa['zip_code']        ?? '';
$street          = $empresa['street']          ?? '';
$number          = $empresa['number']          ?? '';
$complement      = $empresa['complement']      ?? '';
$city            = $empresa['city']            ?? '';
$state           = $empresa['state']           ?? '';
$admin_email     = $admin['email']             ?? 'seu e-mail';

$titulo_pagina = $titulo_pagina ?? 'Configurações da Conta — Re.Source';
require_once __DIR__ . '/../components/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/dashboard-sidebar.css'), ENT_QUOTES, 'UTF-8') ?>">

<style>
.dashboard-layout {
    max-width: 1280px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
    align-items: start;
}
.dashboard-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    position: sticky;
    top: 100px;
    height: calc(100vh - 120px);
    overflow-y: auto;
    padding-right: 10px;
}
.account-panel {
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    padding: 2rem;
    margin-bottom: 1.5rem;
}
.panel-header {
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.panel-header i { color: var(--green); }
.panel-header h2 { font-family: var(--font-main); font-size: 1.25rem; color: var(--dark); }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group.full { grid-column: span 2; }
.form-group label { font-size: 0.85rem; font-weight: 600; color: var(--muted); }
.form-group input, .form-group select, .form-group textarea {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-family: var(--font-body);
    font-size: 0.95rem;
    transition: border-color 0.2s;
}
.form-group input:focus {
    border-color: var(--green);
    outline: none;
    box-shadow: 0 0 0 3px rgba(21, 115, 71, 0.1);
}
.logo-upload-container {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg);
    border-radius: 0.5rem;
}
.current-logo {
    width: 100px;
    height: 100px;
    border-radius: 0.5rem;
    object-fit: cover;
    background: white;
    border: 1px solid var(--border-color);
    flex-shrink: 0;
}
.btn-save {
    background: var(--green);
    color: white;
    padding: 0.8rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 1rem;
    transition: background 0.2s;
    border: none;
    cursor: pointer;
}
.btn-save:hover { background: #0f5132; }
.security-row { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; margin-bottom: 1rem; }
.security-info h3 { font-size: 1rem; color: var(--dark); margin-bottom: 0.25rem; }
.security-info p { font-size: 0.85rem; color: var(--muted); }
.btn-outline { background: transparent; border: 1px solid var(--green); color: var(--green); padding: 0.5rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-outline:hover { background: var(--green); color: white; }
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; }
.modal-overlay.active { display: flex; }
.security-modal { background: white; padding: 2rem; border-radius: 0.5rem; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.modal-header h3 { font-size: 1.2rem; }
.btn-close { background: none; border: none; cursor: pointer; color: var(--muted); }
.code-inputs { display: flex; gap: 0.5rem; justify-content: space-between; margin-bottom: 1.5rem; }
.code-inputs input { width: 45px; height: 50px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 1px solid #d1d5db; border-radius: 0.5rem; }
.code-inputs input:focus { border-color: var(--green); outline: none; }
#dynamic-modal-content { display: none; margin-top: 1.5rem; }
@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .dashboard-layout { grid-template-columns: 1fr; }
    .security-row { flex-direction: column; align-items: flex-start; gap: 1rem; }
}
</style>

<main class="dashboard-layout">

    <?php $sidebarActive = 'account'; require __DIR__ . '/../components/dashboard_sidebar.php'; ?>

    <div class="dashboard-content">

        <form action="/re.source/conta/atualizar" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="account-panel">
                <div class="panel-header">
                    <i data-lucide="camera"></i>
                    <h2>Identidade Visual</h2>
                </div>

                <div class="logo-upload-container">
                    <?php if (!empty($logo_url)): ?>
                        <img src="<?= htmlspecialchars($logo_url) ?>" class="current-logo" id="logoPreview" alt="Logo atual">
                    <?php else: ?>
                        <img src="/re.source/public/img/logos/logo.png" class="current-logo" id="logoPreview" alt="Logo padrão">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Logotipo da Empresa</label>
                        <input type="file" name="logo_empresa" accept="image/*" onchange="previewImage(event)">
                        <p style="font-size: 0.75rem; color: var(--muted);">Recomendado: PNG ou JPG, 500x500px.</p>
                    </div>
                </div>

                <div class="form-group full">
                    <label>Biografia / Descrição da Empresa</label>
                    <textarea name="segment" rows="3" placeholder="Conte um pouco sobre o que sua empresa faz..."><?= htmlspecialchars($segment) ?></textarea>
                </div>
            </div>

            <div class="account-panel">
                <div class="panel-header">
                    <i data-lucide="briefcase"></i>
                    <h2>Informações Jurídicas</h2>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome Fantasia</label>
                        <input type="text" name="nome_fantasia" value="<?= htmlspecialchars($nome_fantasia) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Razão Social</label>
                        <input type="text" name="razao_social" value="<?= htmlspecialchars($razao_social) ?>" required>
                    </div>
                    <div class="form-group full">
                        <label>CNPJ</label>
                        <input type="text" name="cnpj" value="<?= htmlspecialchars($cnpj) ?>" readonly style="background:#f3f4f6;cursor:not-allowed;width:50%;">
                    </div>
                </div>
            </div>

            <div class="account-panel">
                <div class="panel-header">
                    <i data-lucide="map-pin"></i>
                    <h2>Endereço Comercial</h2>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>CEP</label>
                        <input type="text" name="zip_code" id="cep" value="<?= htmlspecialchars($zip_code) ?>" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label>Logradouro (Rua/Av)</label>
                        <input type="text" name="street" value="<?= htmlspecialchars($street) ?>">
                    </div>
                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="number" value="<?= htmlspecialchars($number) ?>">
                    </div>
                    <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" name="complement" value="<?= htmlspecialchars($complement) ?>">
                    </div>
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($city) ?>">
                    </div>
                    <div class="form-group">
                        <label>Estado (UF)</label>
                        <input type="text" name="state" maxlength="2" value="<?= htmlspecialchars($state) ?>">
                    </div>
                </div>
            </div>

            <div class="account-panel">
                <div class="panel-header">
                    <i data-lucide="phone"></i>
                    <h2>Contato e Negociações</h2>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>WhatsApp para Negócios</label>
                        <input type="text" name="phone" placeholder="(47) 99999-9999" value="<?= htmlspecialchars($phone) ?>">
                    </div>
                    <div class="form-group">
                        <label>E-mail Financeiro/Admin</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nome do Responsável</label>
                        <input type="text" name="responsible_name" value="<?= htmlspecialchars($responsible) ?>" required>
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-bottom:2rem;">
                <button type="submit" class="btn-save">Salvar Dados Cadastrais</button>
            </div>
        </form>

        <div class="account-panel" style="border-color:#fca5a5;">
            <div class="panel-header">
                <i data-lucide="shield" style="color:#ef4444;"></i>
                <h2>Central de Segurança</h2>
            </div>

            <div class="security-row">
                <div class="security-info">
                    <h3>Senha de Acesso</h3>
                    <p>Será necessário verificar seu e-mail para alterar a senha.</p>
                </div>
                <button type="button" class="btn-outline" onclick="openSecurityModal('password')">Alterar Senha</button>
            </div>

            <div class="security-row">
                <div class="security-info">
                    <h3>Autenticação em Duas Etapas (2FA)</h3>
                    <p>Proteja sua conta exigindo um código do e-mail ao fazer login.</p>
                </div>
                <button type="button" class="btn-outline" onclick="openSecurityModal('2fa')">Ativar 2FA</button>
            </div>
        </div>

    </div>
</main>

<div class="modal-overlay" id="securityModal">
    <div class="security-modal">
        <div class="modal-header">
            <h3 id="modalTitle">Verificação de Segurança</h3>
            <button class="btn-close" onclick="closeModal()"><i data-lucide="x"></i></button>
        </div>

        <p style="font-size:0.9rem;color:var(--muted);margin-bottom:1.5rem;">
            Enviamos um código de 6 dígitos para: <strong style="color:var(--dark);"><?= htmlspecialchars($admin_email) ?></strong>
        </p>

        <form id="securityForm">
            <div class="code-inputs" id="otp-inputs">
                <input type="text" maxlength="1" onkeyup="moveToNext(this, event)">
                <input type="text" maxlength="1" onkeyup="moveToNext(this, event)">
                <input type="text" maxlength="1" onkeyup="moveToNext(this, event)">
                <input type="text" maxlength="1" onkeyup="moveToNext(this, event)">
                <input type="text" maxlength="1" onkeyup="moveToNext(this, event)">
                <input type="text" maxlength="1" onkeyup="moveToNext(this, event)">
            </div>

            <div id="dynamic-modal-content">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Nova Senha</label>
                    <input type="password" name="new_password">
                </div>
                <div class="form-group">
                    <label>Confirmar Nova Senha</label>
                    <input type="password" name="confirm_password">
                </div>
            </div>

            <button type="button" class="btn-save" style="width:100%;margin-top:1.5rem;" onclick="processSecurityAction()">Confirmar e Salvar</button>
        </form>
    </div>
</div>

<script>
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function() {
            document.getElementById('logoPreview').src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    let currentSecurityAction = '';

    function openSecurityModal(action) {
        currentSecurityAction = action;
        const dynamicContent = document.getElementById('dynamic-modal-content');
        document.getElementById('modalTitle').innerText = action === 'password' ? 'Alterar Senha' : 'Ativar 2FA';
        dynamicContent.style.display = action === 'password' ? 'block' : 'none';
        document.getElementById('securityModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('securityModal').classList.remove('active');
        document.getElementById('securityForm').reset();
    }

    function moveToNext(current, event) {
        if (current.value.length === 1 && current.nextElementSibling) current.nextElementSibling.focus();
        if (event.key === "Backspace" && current.previousElementSibling) current.previousElementSibling.focus();
    }

    function processSecurityAction() {
        alert('Validação via AJAX — ação: ' + currentSecurityAction);
        closeModal();
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
