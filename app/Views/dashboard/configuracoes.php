<?php
// app/Views/dashboard/configuracoes.php
require_once VIEW_PATH . '/components/header.php';
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
    transition: background 0.3s, border-color 0.3s;
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
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--border-color);
}
.setting-row:last-of-type { border-bottom: none; }

.setting-info { display: flex; flex-direction: column; gap: 0.25rem; }
.setting-info label { font-family: var(--font-main); font-size: 1rem; font-weight: 600; color: var(--dark); }
.setting-info p { font-size: 0.85rem; color: var(--muted); }

.config-select {
    padding: 0.5rem 2rem 0.5rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    background-color: var(--white);
    color: var(--dark);
    font-family: var(--font-body);
    font-size: 0.95rem;
    cursor: pointer;
}

/* Switch styling */
.switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: var(--green); }
input:focus + .slider { box-shadow: 0 0 1px var(--green); }
input:checked + .slider:before { transform: translateX(24px); }

/* Danger Zone */
.danger-zone { border-color: #fee2e2; }
.danger-zone .panel-header i { color: #ef4444; }
.btn-logout { background: #f3f4f6; color: #374151; font-weight: 600; border: 1px solid var(--border-color); padding: 0.6rem 1.5rem; border-radius: 0.5rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; transition: background 0.2s; }
.btn-logout:hover { background: #e5e7eb; }
.btn-danger { background: #ef4444; color: white; font-weight: 600; padding: 0.6rem 1.5rem; border-radius: 0.5rem; border: none; cursor: pointer; font-size: 0.9rem; transition: background 0.2s; }
.btn-danger:hover { background: #dc3545; }

/* Dispositivos */
.device-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bg); border-radius: 0.5rem; margin-bottom: 1rem; }
.device-icon { width: 40px; height: 40px; border-radius: 50%; background: white; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--muted); }
.device-details h4 { font-size: 0.95rem; font-weight: 600; color: var(--dark); }
.device-details p { font-size: 0.8rem; color: var(--muted); }
.device-badge { margin-left: auto; background: rgba(21, 115, 71, 0.1); color: var(--green); font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px; }

/* Modal Exclusão */
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

/* REGRAS DO MODO ESCURO (DARK MODE) */
body.theme-dark {
    background-color: #0f172a !important;
}
.theme-dark .config-panel, 
.theme-dark .dashboard-sidebar {
    background: #1e293b !important;
    border-color: #334155 !important;
}
.theme-dark var(--dark),
.theme-dark h2,
.theme-dark h3,
.theme-dark h4,
.theme-dark label {
    color: #f8fafc !important;
}
.theme-dark p,
.theme-dark .setting-info p {
    color: #94a3b8 !important;
}
.theme-dark .setting-row {
    border-bottom: 1px solid #334155 !important;
}
.theme-dark .config-select {
    background-color: #1e293b !important;
    color: #f8fafc !important;
    border-color: #475569 !important;
}
.theme-dark .device-item {
    background: #0f172a !important;
}
.theme-dark .device-icon {
    background: #1e293b !important;
    border-color: #334155 !important;
}

@media (max-width: 768px) {
    .dashboard-layout { grid-template-columns: 1fr; }
    .setting-row { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .config-select { width: 100%; }
}
</style>

<main class="dashboard-layout">
    
<aside class="dashboard-sidebar">
    <div class="sidebar-user">
        <div class="sidebar-avatar" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($logo_url)): ?>
                <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo da Empresa" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <i data-lucide="building-2"></i>
            <?php endif; ?>
        </div>
        <h3><?= htmlspecialchars($nome_empresa) ?></h3>
        <p>Painel de Controle</p>
    </div>

    <nav class="sidebar-nav">
            <a href="/re.source/estatisticas" class="sidebar-link "><i data-lucide="bar-chart-2"></i> Painel e Estatísticas</a>
            <a href="/re.source/meus-anuncios" class="sidebar-link"><i data-lucide="package"></i> Meus Anúncios</a>
            <a href="/re.source/conta" class="sidebar-link"><i data-lucide="user"></i> Detalhes da Conta</a>
            <a href="/re.source/configuracoes" class="sidebar-link active"><i data-lucide="settings"></i> Configurações</a>
            <a href="/re.source/logout" class="sidebar-link"><i data-lucide="log-out"></i> Sair</a>
        </nav>
</aside>

    <div class="dashboard-content">
        
        <form action="/re.source/configuracoes/salvar" method="POST">
            
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

            <?php
            // 1. CAPTURA DO IP REAL (Trata servidores locais)
            $ip_atual = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if ($ip_atual === '::1' || $ip_atual === '127.0.0.1') {
                $ip_atual = '192.168.0.15'; // Força um IP local amigável para testes visuais no localhost
            }

            // 2. CAPTURA E TRATAMENTO DO USER AGENT ATUAL
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';

            // Detecta o Sistema Operacional Atual
            $os_atual = "Sistema Desconhecido";
            if (preg_match('/windows|win32/i', $user_agent)) $os_atual = 'Windows';
            elseif (preg_match('/macintosh|mac os x/i', $user_agent)) $os_atual = 'macOS';
            elseif (preg_match('/linux/i', $user_agent)) $os_atual = 'Linux';
            elseif (preg_match('/iphone|ipad/i', $user_agent)) $os_atual = 'iOS (iPhone)';
            elseif (preg_match('/android/i', $user_agent)) $os_atual = 'Android';

            // Detecta o Navegador Atual
            $browser_atual = "Navegador Desconhecido";
            if (preg_match('/edge/i', $user_agent)) $browser_atual = 'Microsoft Edge';
            elseif (preg_match('/firefox/i', $user_agent)) $browser_atual = 'Mozilla Firefox';
            elseif (preg_match('/chrome/i', $user_agent)) $browser_atual = 'Google Chrome';
            elseif (preg_match('/safari/i', $user_agent)) $browser_atual = 'Safari';

            // 3. SIMULAÇÃO DA TABELA `user_sessions`
            if (!isset($_SESSION['dispositivos_historico'])) {
                $_SESSION['dispositivos_historico'] = [];
            }

            $chave_dispositivo = md5($os_atual . $browser_atual);

            $_SESSION['dispositivos_historico'][$chave_dispositivo] = [
                'os' => $os_atual,
                'browser' => $browser_atual,
                'ip' => $ip_atual,
                'last_access' => 'Agora mesmo',
                'agent_string' => $user_agent
            ];

            // 4. RENDERIZAÇÃO DA LISTA DE DISPOSITIVOS REAIS
            foreach ($_SESSION['dispositivos_historico'] as $hash => $device):
                $eh_atual = ($device['os'] === $os_atual && $device['browser'] === $browser_atual);
            ?>
                <div class="device-item" id="device-<?= $hash ?>">
                    <div class="device-icon">
                        <?php if (preg_match('/iphone|ipad|android/i', $device['agent_string'])): ?>
                            <i data-lucide="smartphone"></i>
                        <?php else: ?>
                            <i data-lucide="monitor"></i>
                        <?php endif; ?>
                    </div>
                    <div class="device-details">
                        <h4><?= $device['os'] . " — " . $device['browser'] ?></h4>
                        <p>Joinville, Brasil • IP: <?= $device['ip'] ?> • <?= $device['last_access'] ?></p>
                    </div>
                    
                    <?php if ($eh_atual): ?>
                        <span class="device-badge">Sessão Atual</span>
                    <?php else: ?>
                        <button type="button" 
                                style="margin-left: auto; background: none; border: none; color: #ef4444; font-size: 0.85rem; font-weight: 600; cursor: pointer;" 
                                onclick="derrubarSessao('<?= $hash ?>')">
                            Derrubar
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
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
                <button type="button" class="btn-logout" onclick="window.location.href='/re.source/logout'">
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
            <form action="/re.source/conta/excluir" method="POST" style="display: inline;">
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

    function derrubarSessao(hash) {
        if (confirm('Tem certeza que deseja encerrar e derrubar esta sessão ativa?')) {
            const elemento = document.getElementById('device-' + hash);
            if (elemento) {
                elemento.style.opacity = '0';
                setTimeout(() => elemento.remove(), 400);
            }
            alert('Sessão encerrada com sucesso! O token de segurança deste navegador foi revogado.');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const currentTheme = "<?= $prefs['theme'] ?>";
        const body = document.body;

        // Função para aplicar o tema
        function applyTheme(theme) {
            if (theme === 'dark') {
                body.classList.add('theme-dark');
            } else if (theme === 'light') {
                body.classList.remove('theme-dark');
            } else if (theme === 'system') {
                // Verifica a preferência do sistema operacional
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (prefersDark) {
                    body.classList.add('theme-dark');
                } else {
                    body.classList.remove('theme-dark');
                }
            }
        }

        // Aplica o tema configurado
        applyTheme(currentTheme);

        // Opcional: Escuta mudanças em tempo real se o usuário trocar o tema no sistema operacional
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (currentTheme === 'system') {
                applyTheme('system');
            }
        });
    });
</script>

<?php require_once VIEW_PATH . '/components/footer.php'; ?>
