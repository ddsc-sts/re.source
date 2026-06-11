<?php
// configuracoes.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php"; 

$company_id = $_SESSION['company_id'] ?? 1;

// Simulando preferências vindas do banco (no futuro, associadas à tabela de configurações da empresa)
$prefs = [
    'theme' => 'system', // light, dark, system
    'language' => 'pt-BR',
    'notify_proposals' => true,
    'notify_chat' => true,
    'notify_marketing' => false
];

$titulo_pagina = 'Configurações do Sistema — Re.Source';
include 'header.php';
?>

<style>
/* Layout Base Unificado */
.dashboard-layout {
    max-width: 1280px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
    align-items: start;
}

.dashboard-sidebar {
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    padding: 1.5rem 1rem;
    position: sticky;
    top: 100px;
    height: calc(100vh - 120px);
    overflow-y: auto;
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

.config-panel {
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    padding: 2rem;
    margin-bottom: 1.5rem;
}

.panel-header {
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.panel-header i { color: var(--green); }
.panel-header h2 { font-family: var(--font-main); font-size: 1.25rem; color: var(--dark); }

/* Custom Switch/Toggle (Estilo iOS) */
.setting-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f3f4f6;
}
.setting-row:last-child { border-bottom: none; }

.setting-info { display: flex; flex-direction: column; gap: 0.25rem; }
.setting-info label { font-weight: 600; color: var(--dark); font-size: 0.95rem; cursor: pointer; }
.setting-info p { font-size: 0.85rem; color: var(--muted); }

.switch {
    position: relative;
    display: inline-block;
    width: 46px;
    height: 24px;
}
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc; transition: .3s; border-radius: 24px;
}
.slider:before {
    position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
    background-color: white; transition: .3s; border-radius: 50%;
}
input:checked + .slider { background-color: var(--green); }
input:checked + .slider:before { transform: translateX(22px); }

/* Selectores Customizados */
.config-select {
    padding: 0.6rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    color: var(--dark);
    background-color: white;
    min-width: 180px;
}

/* Dispositivos Conectados */
.device-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg);
    border-radius: 0.5rem;
    margin-bottom: 0.75rem;
}
.device-icon {
    width: 40px;
    height: 40px;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
}
.device-details h4 { font-size: 0.9rem; color: var(--dark); }
.device-details p { font-size: 0.8rem; color: var(--muted); }
.device-badge {
    margin-left: auto;
    background: rgba(21, 115, 71, 0.1);
    color: var(--green);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

/* Zona de Perigo */
.danger-zone { border-color: #fca5a5; background: #fff5f5; }
.danger-zone .panel-header i { color: #ef4444; }
.btn-danger {
    background: #ef4444; color: white; border: none; padding: 0.6rem 1.5rem;
    border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: background 0.2s;
}
.btn-danger:hover { background: #dc2626; }

.btn-logout {
    background: transparent; border: 1px solid #d1d5db; color: #4b5563;
    padding: 0.6rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;
}
.btn-logout:hover { background: #f3f4f6; color: var(--dark); }

/* Sidebar user info components */
.sidebar-user { text-align: center; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); margin-bottom: 1.5rem; }
.sidebar-avatar { width: 64px; height: 64px; background: var(--bg); border-radius: 50%; margin: 0 auto 0.75rem; display: flex; align-items: center; justify-content: center; color: var(--green); }
.sidebar-nav { display: flex; flex-direction: column; gap: 0.5rem; }
.sidebar-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; color: var(--muted); transition: all 0.2s; }
.sidebar-link:hover { background: var(--bg); color: var(--dark); }
.sidebar-link.active { background: rgba(21, 115, 71, 0.1); color: var(--green); font-weight: 600; }

/* Modal Crítico */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.6); z-index: 1000;
    display: none; align-items: center; justify-content: center;
}
.modal-overlay.active { display: flex; }
.crit-modal {
    background: white; padding: 2.5rem; border-radius: var(--radius);
    width: 100%; max-width: 450px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
.crit-modal i { color: #ef4444; margin-bottom: 1rem; }
.crit-modal h3 { font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--dark); }
.crit-modal p { font-size: 0.9rem; color: var(--muted); margin-bottom: 2rem; line-height: 1.5; }
.modal-actions { display: flex; gap: 1rem; justify-content: center; }

@media (max-width: 768px) {
    .dashboard-layout { grid-template-columns: 1fr; }
    .setting-row { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .config-select { width: 100%; }
}
</style>

<main class="dashboard-layout">
    
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <i data-lucide="building-2"></i>
            </div>
            <h3>Configurações</h3>
            <p>Painel de Controle</p>
        </div>

        <nav class="sidebar-nav">
            <a href="estatisticas.php" class="sidebar-link"><i data-lucide="bar-chart-2"></i> Painel e Estatísticas</a>
            <a href="meusAnuncios.php" class="sidebar-link"><i data-lucide="package"></i> Meus Anúncios</a>
            <a href="conta.php" class="sidebar-link"><i data-lucide="user"></i> Detalhes da Conta</a>
            <a href="configuracoes.php" class="sidebar-link active"><i data-lucide="settings"></i> Configurações</a>
        </nav>
    </aside>

    <div class="dashboard-content">
        
        <form action="salvar_preferencias.php" method="POST">
            
            <div class="config-panel">
                <div class="panel-header">
                    <i data-lucide="sliders"></i>
                    <h2>Preferências do Sistema</h2>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <label>Tema da Interface</label>
                        <p>Personalize a aparência do seu painel corporativo.</p>
                    </div>
                    <select name="theme" class="config-select">
                        <option value="light" <?= $prefs['theme'] === 'light' ? 'selected' : '' ?>>Modo Claro</option>
                        <option value="dark" <?= $prefs['theme'] === 'dark' ? 'selected' : '' ?>>Modo Escuro</option>
                        <option value="system" <?= $prefs['theme'] === 'system' ? 'selected' : '' ?>>Seguir o Sistema</option>
                    </select>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <label>Idioma do Painel</label>
                        <p>Selecione a linguagem padrão para a navegação interna.</p>
                    </div>
                    <select name="language" class="config-select">
                        <option value="pt-BR" <?= $prefs['language'] === 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option>
                        <option value="en" <?= $prefs['language'] === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="es" <?= $prefs['language'] === 'es' ? 'selected' : '' ?>>Español</option>
                    </select>
                </div>
            </div>

            <div class="config-panel">
                <div class="panel-header">
                    <i data-lucide="bell"></i>
                    <h2>Central de Alertas (Notificações)</h2>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <label for="notify_proposals">Propostas de Compra/Venda</label>
                        <p>Receber alertas imediatos no e-mail quando outra empresa fizer uma oferta.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="notify_proposals" id="notify_proposals" value="1" <?= $prefs['notify_proposals'] ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <label for="notify_chat">Alertas de Chat Interno</label>
                        <p>Notificar e-mail institucional se houver mensagens não lidas pendentes na plataforma.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="notify_chat" id="notify_chat" value="1" <?= $prefs['notify_chat'] ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <label for="notify_marketing">Boletins de Mercado e ESG</label>
                        <p>Receber relatórios de preços de resíduos e newsletters de novidades do setor de sustentabilidade.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="notify_marketing" id="notify_marketing" value="1" <?= $prefs['notify_marketing'] ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;">
                <button type="submit" class="btn-save" style="background: var(--green); color: white; padding: 0.75rem 2rem; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">Salvar Preferências</button>
            </div>
        </form>

        <div class="config-panel">
            <div class="panel-header">
                <i data-lucide="shield-alert"></i>
                <h2>Dispositivos Conectados</h2>
            </div>
            <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Estes são os dispositivos que acessaram a conta da empresa recentemente. Caso não reconheça algum, encerre a sessão.</p>

            <div class="device-item">
                <div class="device-icon"><i data-lucide="monitor"></i></div>
                <div class="device-details">
                    <h4>Windows 11 — Chrome Browser</h4>
                    <p>Joinville, Brasil • IP: 177.85.22.104</p>
                </div>
                <span class="device-badge">Sessão Atual</span>
            </div>

            <div class="device-item">
                <div class="device-icon"><i data-lucide="smartphone"></i></div>
                <div class="device-details">
                    <h4>iPhone 15 — Safari Mobile</h4>
                    <p>São Paulo, Brasil • Há 2 horas</p>
                </div>
                <button type="button" style="margin-left: auto; background: none; border: none; color: #ef4444; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Derrubar</button>
            </div>
        </div>

        <div class="config-panel danger-zone">
            <div class="panel-header">
                <i data-lucide="octagon-alert"></i>
                <h2>Zona de Perigo Fiscal e Contratual</h2>
            </div>
            
            <div class="setting-row" style="border: none;">
                <div class="setting-info">
                    <label style="color: #b91c1c;">Desconectar desta Máquina</label>
                    <p>Encerra o token de login atual e limpa os cookies de sessão de forma segura.</p>
                </div>
                <button type="button" class="btn-logout" onclick="window.location.href='logout.php'">
                    <i data-lucide="log-out"></i> Sair da Conta
                </button>
            </div>

            <div class="setting-row" style="border-top: 1px solid #fee2e2; padding-top: 1.5rem; margin-top: 0.5rem;">
                <div class="setting-info">
                    <label style="color: #b91c1c;">Excluir Conta Corporativa</label>
                    <p>Ação irreversível. Apaga permanentemente seus anúncios ativos, histórico de propostas e relatórios fiscais.</p>
                </div>
                <button type="button" class="btn-danger" onclick="toggleDeleteModal(true)">Excluir Conta</button>
            </div>
        </div>

    </div>
</main>

<div class="modal-overlay" id="deleteModal">
    <div class="crit-modal">
        <i data-lucide="alert-triangle" style="width: 48px; height: 48px;"></i>
        <h3>Tem certeza absoluta?</h3>
        <p>Esta ação **não pode ser desfeita**. Você perderá o acesso instantâneo a todas as transações, dados de CO₂ evitado e fundos não retirados no sistema RE.SOURCE.</p>
        
        <div class="modal-actions">
            <button type="button" class="btn-logout" onclick="toggleDeleteModal(false)">Cancelar</button>
            <form action="excluir_conta.php" method="POST" style="display: inline;">
                <button type="submit" class="btn-danger">Sim, Excluir Tudo</button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleDeleteModal(show) {
        const modal = document.getElementById('deleteModal');
        if (show) {
            modal.classList.add('active');
        } else {
            modal.classList.remove('active');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php include 'footer.php'; ?>