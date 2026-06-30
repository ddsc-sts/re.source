<?php
$old = $_SESSION['saque_old'] ?? [];
$formError = $_SESSION['saque_form_error'] ?? null;
unset($_SESSION['saque_old'], $_SESSION['saque_form_error']);
$companyName = $company['nome_fantasia'] ?: ($company['razao_social'] ?? 'Sua empresa');
$document = preg_replace('/\D/', '', (string) ($company['cnpj'] ?? ''));
require_once __DIR__ . '/../components/header.php';
?>

<style>
.withdraw-page { max-width: 1180px; margin: 2.5rem auto 4rem; padding: 0 1.5rem; }
.withdraw-back { display: inline-flex; align-items: center; gap: .4rem; margin-bottom: 1.25rem; color: var(--muted); text-decoration: none; font-weight: 600; }
.withdraw-back:hover { color: var(--green); }
.withdraw-heading { margin-bottom: 1.75rem; position: static; }
.withdraw-heading h1 { color: var(--dark); font-family: var(--font-main); font-size: clamp(1.8rem, 4vw, 2.5rem); }
.withdraw-heading p { max-width: 720px; margin-top: .5rem; color: var(--muted); line-height: 1.6; }
.withdraw-layout { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(280px, .75fr); gap: 1.5rem; align-items: start; }
.withdraw-card { padding: 1.75rem; background: var(--white); border: 1px solid var(--border-color); border-radius: var(--radius); box-shadow: 0 8px 24px rgba(0,0,0,.04); }
.balance-card { margin-bottom: 1.5rem; padding: 1.4rem; color: #fff; background: linear-gradient(135deg, var(--green), #0d4a2e); border-radius: .9rem; }
.balance-card span { display: block; margin-bottom: .35rem; font-size: .85rem; opacity: .85; }
.balance-card strong { font-family: var(--font-main); font-size: 2rem; }
.form-section-title { margin: 1.6rem 0 1rem; padding-bottom: .6rem; color: var(--dark); border-bottom: 1px solid var(--border-color); font-family: var(--font-main); font-size: 1rem; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: .4rem; }
.field.full { grid-column: 1 / -1; }
.field label { color: var(--dark); font-size: .86rem; font-weight: 700; }
.field input, .field select, .field textarea { width: 100%; padding: .82rem .9rem; color: var(--dark); background: var(--bg); border: 1px solid var(--border-color); border-radius: .55rem; font: inherit; }
.field input:focus, .field select:focus, .field textarea:focus { outline: 0; border-color: var(--green); box-shadow: 0 0 0 3px rgba(21,115,71,.13); }
.field small { color: var(--muted); line-height: 1.4; }
.terms-box { margin-top: 1.5rem; padding: 1rem; background: var(--bg); border: 1px solid var(--border-color); border-radius: .65rem; }
.terms-box label { display: flex; gap: .7rem; align-items: flex-start; color: var(--dark); font-size: .86rem; line-height: 1.55; cursor: pointer; }
.terms-box input { margin-top: .25rem; }
.withdraw-actions { display: flex; justify-content: flex-end; gap: .75rem; margin-top: 1.5rem; }
.btn-cancel, .btn-submit { padding: .8rem 1.2rem; border-radius: .55rem; font-weight: 700; text-decoration: none; cursor: pointer; }
.btn-cancel { color: var(--muted); background: transparent; border: 1px solid var(--border-color); }
.btn-submit { color: #fff; background: var(--green); border: 1px solid var(--green); }
.btn-submit:disabled { opacity: .55; cursor: not-allowed; }
.form-alert { margin-bottom: 1rem; padding: 1rem; color: #842029; background: #f8d7da; border: 1px solid #f5c2c7; border-radius: .55rem; }
.info-stack { display: grid; gap: 1rem; }
.info-card { padding: 1.3rem; background: var(--white); border: 1px solid var(--border-color); border-radius: var(--radius); }
.info-card h2 { display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem; color: var(--dark); font-size: 1rem; }
.info-card p, .info-card li { color: var(--muted); font-size: .86rem; line-height: 1.55; }
.info-card ol { display: grid; gap: .75rem; padding-left: 1.2rem; }
.history-row { display: flex; justify-content: space-between; gap: 1rem; padding: .7rem 0; border-bottom: 1px solid var(--border-color); font-size: .83rem; }
.history-row:last-child { border-bottom: 0; }
.history-row strong { color: var(--dark); }
.status-pending { color: #b45309; }
@media (max-width: 850px) { .withdraw-layout { grid-template-columns: 1fr; } }
@media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } .field.full { grid-column: auto; } .withdraw-card { padding: 1.2rem; } .withdraw-actions { flex-direction: column-reverse; } .btn-cancel, .btn-submit { text-align: center; } }
</style>

<main class="withdraw-page">
    <a href="/re.source/estatisticas" class="withdraw-back"><i data-lucide="arrow-left"></i> Voltar ao painel</a>
    <header class="withdraw-heading">
        <h1>Solicitar retirada</h1>
        <p>Informe os dados do titular e da chave PIX. A solicitação será enviada para análise antes de qualquer transferência.</p>
    </header>

    <div class="withdraw-layout">
        <section class="withdraw-card">
            <div class="balance-card">
                <span>Saldo disponível para solicitação</span>
                <strong>R$ <?= number_format($availableBalance, 2, ',', '.') ?></strong>
            </div>

            <?php if ($formError): ?>
                <div class="form-alert"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form action="/re.source/estatisticas/processar-saque" method="POST" id="withdrawForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <h2 class="form-section-title">Dados da retirada</h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="valor_saque">Valor solicitado</label>
                        <input type="number" id="valor_saque" name="valor_saque" min="10" max="<?= htmlspecialchars((string) $availableBalance) ?>" step="0.01" value="<?= htmlspecialchars((string) ($old['valor_saque'] ?? '')) ?>" required>
                        <small>Valor mínimo de R$ 10,00.</small>
                    </div>
                    <div class="field">
                        <label for="pix_key_type">Tipo da chave PIX</label>
                        <select id="pix_key_type" name="pix_key_type" required>
                            <option value="">Selecione</option>
                            <?php foreach (['cnpj' => 'CNPJ', 'cpf' => 'CPF', 'email' => 'E-mail', 'phone' => 'Celular', 'random' => 'Chave aleatória'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($old['pix_key_type'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="chave_pix">Chave PIX</label>
                        <input type="text" id="chave_pix" name="chave_pix" maxlength="255" value="<?= htmlspecialchars((string) ($old['chave_pix'] ?? '')) ?>" autocomplete="off" required>
                        <small id="pixKeyHint">Selecione o tipo da chave para aplicar a formatação correta.</small>
                    </div>
                </div>

                <h2 class="form-section-title">Titular da conta</h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="titular_nome">Nome ou razão social</label>
                        <input type="text" id="titular_nome" name="titular_nome" minlength="3" maxlength="150" value="<?= htmlspecialchars((string) ($old['titular_nome'] ?? $companyName)) ?>" required>
                    </div>
                    <div class="field">
                        <label for="titular_documento">CPF ou CNPJ</label>
                        <input type="text" id="titular_documento" name="titular_documento" maxlength="18" value="<?= htmlspecialchars((string) ($old['titular_documento'] ?? $document)) ?>" required>
                    </div>
                    <div class="field full">
                        <label for="observacao">Observação para análise <span style="font-weight:400">(opcional)</span></label>
                        <textarea id="observacao" name="observacao" maxlength="500" rows="3" placeholder="Inclua alguma informação relevante para a equipe financeira."><?= htmlspecialchars((string) ($old['observacao'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="terms-box">
                    <label>
                        <input type="checkbox" name="aceite_termos" value="1" required <?= isset($old['aceite_termos']) ? 'checked' : '' ?>>
                        <span>Confirmo que os dados informados pertencem à empresa ou a um titular autorizado. Estou ciente de que esta solicitação não representa pagamento imediato: ela será analisada, poderá exigir comprovação adicional e poderá ser aprovada ou rejeitada. Solicitações pendentes reservam o saldo correspondente.</span>
                    </label>
                </div>

                <div class="withdraw-actions">
                    <a href="/re.source/estatisticas" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-submit" <?= $availableBalance < 10 ? 'disabled' : '' ?>>Enviar para análise</button>
                </div>
            </form>
        </section>

        <aside class="info-stack">
            <section class="info-card">
                <h2><i data-lucide="shield-check"></i> Como funciona</h2>
                <ol>
                    <li>Você envia os dados e o valor desejado.</li>
                    <li>A equipe valida saldo, titularidade e chave PIX.</li>
                    <li>Após aprovação, a transferência é processada e o status atualizado.</li>
                </ol>
            </section>
            <section class="info-card">
                <h2><i data-lucide="clock-3"></i> Prazos e condições</h2>
                <p>A solicitação permanece pendente durante a análise. Dados divergentes podem causar rejeição. O saldo solicitado fica reservado para evitar retiradas duplicadas.</p>
            </section>
            <?php if ($recentWithdrawals): ?>
                <section class="info-card">
                    <h2><i data-lucide="history"></i> Solicitações recentes</h2>
                    <?php foreach ($recentWithdrawals as $withdrawal): ?>
                        <div class="history-row">
                            <span><?= date('d/m/Y', strtotime($withdrawal['created_at'])) ?><br><strong>R$ <?= number_format((float) $withdrawal['amount'], 2, ',', '.') ?></strong></span>
                            <span class="<?= $withdrawal['status'] === 'pending' ? 'status-pending' : '' ?>"><?= $withdrawal['status'] === 'pending' ? 'Em análise' : ($withdrawal['status'] === 'completed' ? 'Aprovado' : 'Rejeitado') ?></span>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('pix_key_type');
    const keyInput = document.getElementById('chave_pix');
    const hint = document.getElementById('pixKeyHint');

    const settings = {
        cpf: { maxLength: 14, inputMode: 'numeric', placeholder: '000.000.000-00', hint: 'Digite os 11 números do CPF.' },
        cnpj: { maxLength: 18, inputMode: 'numeric', placeholder: '00.000.000/0000-00', hint: 'Digite os 14 números do CNPJ.' },
        phone: { maxLength: 15, inputMode: 'numeric', placeholder: '(00) 00000-0000', hint: 'Informe o celular com DDD.' },
        email: { maxLength: 255, inputMode: 'email', placeholder: 'nome@empresa.com.br', hint: 'Informe o e-mail cadastrado como chave PIX.' },
        random: { maxLength: 255, inputMode: 'text', placeholder: 'Chave aleatória', hint: 'Cole a chave aleatória completa.' }
    };

    function onlyDigits(value) {
        return value.replace(/\D/g, '');
    }

    function maskCpf(value) {
        return onlyDigits(value).slice(0, 11)
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    function maskCnpj(value) {
        return onlyDigits(value).slice(0, 14)
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }

    function maskPhone(value) {
        const digits = onlyDigits(value).slice(0, 11);
        if (digits.length <= 10) {
            return digits.replace(/^(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d{1,4})$/, '$1-$2');
        }
        return digits.replace(/^(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d{1,4})$/, '$1-$2');
    }

    function formatKey() {
        if (typeSelect.value === 'cpf') keyInput.value = maskCpf(keyInput.value);
        if (typeSelect.value === 'cnpj') keyInput.value = maskCnpj(keyInput.value);
        if (typeSelect.value === 'phone') keyInput.value = maskPhone(keyInput.value);
    }

    function configureInput(clearValue) {
        const config = settings[typeSelect.value];
        if (clearValue) keyInput.value = '';
        keyInput.maxLength = config?.maxLength ?? 255;
        keyInput.inputMode = config?.inputMode ?? 'text';
        keyInput.placeholder = config?.placeholder ?? '';
        hint.textContent = config?.hint ?? 'Selecione o tipo da chave para aplicar a formatação correta.';
        formatKey();
    }

    typeSelect.addEventListener('change', function () { configureInput(true); });
    keyInput.addEventListener('input', formatKey);
    keyInput.addEventListener('keydown', function (event) {
        if (['cpf', 'cnpj', 'phone'].includes(typeSelect.value) && event.key.length === 1 && !/\d/.test(event.key)) {
            event.preventDefault();
        }
    });

    configureInput(false);
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
