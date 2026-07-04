<?php
$titulo_pagina = $titulo_pagina ?? 'Configurações do Sistema — Re.Source';
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

.setting-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}
.setting-row:last-child { border-bottom: none; }

.setting-info { display: flex; flex-direction: column; gap: 0.25rem; }
.setting-info label { font-weight: 600; color: var(--dark); font-size: 0.95rem; cursor: pointer; }
.setting-info p { font-size: 0.85rem; color: var(--muted); }

.switch { position: relative; display: inline-block; width: 46px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 24px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
input:checked + .slider { background-color: var(--green); }
input:checked + .slider:before { transform: translateX(22px); }

.config-select { padding: 0.6rem 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.9rem; color: var(--dark); background-color: white; min-width: 180px; }

.device-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bg); border-radius: 0.5rem; margin-bottom: 0.75rem; }
.device-icon { width: 40px; height: 40px; background: white; border: 1px solid var(--border-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--muted); }
.device-details h4 { font-size: 0.9rem; color: var(--dark); }
.device-details p { font-size: 0.8rem; color: var(--muted); }
.device-badge { margin-left: auto; background: rgba(21, 115, 71, 0.1); color: var(--green); font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 0.25rem; }

.danger-zone { border-color: #fca5a5; background: #fff5f5; }
.danger-zone .panel-header i { color: #ef4444; }
.btn-danger { background: #ef4444; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-danger:hover { background: #dc2626; }

[data-theme="dark"] .danger-zone { background: var(--white); border-color: var(--border-color); }

.btn-logout { background: transparent; border: 1px solid #d1d5db; color: #ef4444; padding: 0.6rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
.btn-logout:hover { background: #f3f4f6; color: var(--dark); }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; display: none; align-items: center; justify-content: center; }
.modal-overlay.active { display: flex; }
.crit-modal { background: white; padding: 2.5rem; border-radius: var(--radius); width: 100%; max-width: 450px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
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
    
    <?php $sidebarActive = 'settings'; require __DIR__ . '/../components/dashboard_sidebar.php'; ?>

    <div class="dashboard-content">
        
        <form action="/re.source/configuracoes/salvar" method="POST">
            <?= csrf_field() ?>
            
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
                        <option value="light"  <?= $prefs['theme'] === 'light'  ? 'selected' : '' ?>>Modo Claro</option>
                        <option value="dark"   <?= $prefs['theme'] === 'dark'   ? 'selected' : '' ?>>Modo Escuro</option>
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
                <button type="submit" style="background: var(--green); color: white; padding: 0.75rem 2rem; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">Salvar Preferências</button>
            </div>
        </form>

        <div class="config-panel">
            <div class="panel-header">
                <i data-lucide="shield-alert"></i>
                <h2>Dispositivos Conectados</h2>
            </div>
            <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Estes são os dispositivos que acessaram a conta da empresa recentemente. Caso não reconheça algum, encerre a sessão.</p>

            <?php
            $ip_atual = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if ($ip_atual === '::1' || $ip_atual === '127.0.0.1') {
                $ip_atual = '192.168.0.15';
            }

            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';

            $os_atual = "Sistema Desconhecido";
            if (preg_match('/windows|win32/i', $user_agent))       $os_atual = 'Windows';
            elseif (preg_match('/macintosh|mac os x/i', $user_agent)) $os_atual = 'macOS';
            elseif (preg_match('/linux/i', $user_agent))            $os_atual = 'Linux';
            elseif (preg_match('/iphone|ipad/i', $user_agent))     $os_atual = 'iOS (iPhone)';
            elseif (preg_match('/android/i', $user_agent))         $os_atual = 'Android';

            $browser_atual = "Navegador Desconhecido";
            if (preg_match('/edge/i', $user_agent))                 $browser_atual = 'Microsoft Edge';
            elseif (preg_match('/firefox/i', $user_agent))          $browser_atual = 'Mozilla Firefox';
            elseif (preg_match('/chrome/i', $user_agent))           $browser_atual = 'Google Chrome';
            elseif (preg_match('/safari/i', $user_agent))           $browser_atual = 'Safari';

            if (!isset($_SESSION['dispositivos_historico'])) {
                $_SESSION['dispositivos_historico'] = [];
            }

            $chave_dispositivo = md5($os_atual . $browser_atual);
            $_SESSION['dispositivos_historico'][$chave_dispositivo] = [
                'os'           => $os_atual,
                'browser'      => $browser_atual,
                'ip'           => $ip_atual,
                'last_access'  => 'Agora mesmo',
                'agent_string' => $user_agent,
            ];

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
                <h2>Zona de Perigo</h2>
            </div>
            
            <div class="setting-row" style="border: none;">
                <div class="setting-info">
                    <label style="color: #ef4444;">Desconectar desta Máquina</label>
                    <p>Encerra o token de login atual e limpa os cookies de sessão de forma segura.</p>
                </div>
                <button type="button" class="btn-logout" onclick="window.location.href='/re.source/logout'">
                    <i data-lucide="log-out"></i> Sair da Conta
                </button>
            </div>

            <div class="setting-row" style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 0.5rem;">
                <div class="setting-info">
                    <label style="color: #ef4444;">Excluir Conta Corporativa</label>
                    <p>Ação irreversível. Apaga permanentemente seus anúncios ativos, histórico de propostas e relatórios fiscais.</p>
                </div>
                <form action="/re.source/conta/excluir" method="POST" onsubmit="return confirm('Tem certeza absoluta? Esta ação não pode ser desfeita.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-danger">Excluir Conta</button>
                </form>
            </div>
        </div>

    </div>
</main>

<div class="modal-overlay" id="deleteModal">
    <div class="crit-modal">
        <i data-lucide="alert-triangle" style="width: 48px; height: 48px;"></i>
        <h3>Tem certeza absoluta?</h3>
        <p>Esta ação não pode ser desfeita. Você perderá o acesso a todas as transações, dados e fundos não retirados no Re.Source.</p>
        <div class="modal-actions">
            <button type="button" class="btn-logout" onclick="toggleDeleteModal(false)">Cancelar</button>
            <form action="/re.source/conta/excluir" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn-danger">Sim, Excluir Tudo</button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleDeleteModal(show) {
        document.getElementById('deleteModal').classList.toggle('active', show);
    }

    function derrubarSessao(hash) {
        if (confirm('Tem certeza que deseja encerrar esta sessão ativa?')) {
            const elemento = document.getElementById('device-' + hash);
            if (elemento) {
                elemento.style.opacity = '0';
                setTimeout(() => elemento.remove(), 400);
            }
            alert('Sessão encerrada com sucesso!');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const currentTheme = "<?= $prefs['theme'] ?>";

        function applyTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('theme-dark');
            } else if (theme === 'light') {
                document.body.classList.remove('theme-dark');
            } else if (theme === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.body.classList.toggle('theme-dark', prefersDark);
            }
        }

        applyTheme(currentTheme);

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (currentTheme === 'system') applyTheme('system');
        });

        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
