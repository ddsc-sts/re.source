<?php
$old = $_SESSION['saque_old'] ?? [];
$formError = $_SESSION['saque_form_error'] ?? null;
unset($_SESSION['saque_old'], $_SESSION['saque_form_error']);
$companyName = $company['nome_fantasia'] ?: ($company['razao_social'] ?? 'Sua empresa');
$document = preg_replace('/\D/', '', (string) ($company['cnpj'] ?? ''));
require_once __DIR__ . '/../components/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/saque.css'), ENT_QUOTES, 'UTF-8') ?>">

<main class="internal-page-shell">
  <div class="withdraw-page">
    <a href="/re.source/estatisticas" class="withdraw-back"><i data-lucide="arrow-left"></i> Voltar ao painel</a>
    <header class="withdraw-heading">
        <h1>Solicitar retirada</h1>
        <p>Escolha PIX ou TED. A solicitação será reservada no saldo e enviada para aprovação manual.</p>
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
                <input type="hidden" name="request_token" value="<?= htmlspecialchars($requestToken, ENT_QUOTES, 'UTF-8') ?>">

                <h2 class="form-section-title">Dados da retirada</h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="valor_saque">Valor solicitado</label>
                        <input type="number" id="valor_saque" name="valor_saque" min="10" max="<?= htmlspecialchars((string) $availableBalance) ?>" step="0.01" value="<?= htmlspecialchars((string) ($old['valor_saque'] ?? '')) ?>" required>
                        <small>Valor mínimo de R$ 10,00.</small>
                    </div>
                </div>
                <div class="payment-options">
                    <label class="payment-option"><input type="radio" name="method" value="pix" <?= ($old['method'] ?? 'pix') === 'pix' ? 'checked' : '' ?>> PIX</label>
                    <label class="payment-option"><input type="radio" name="method" value="ted" <?= ($old['method'] ?? '') === 'ted' ? 'checked' : '' ?>> TED</label>
                </div>
                <div class="method-fields" id="pixFields">
                  <div class="form-grid">
                    <div class="field">
                        <label for="pix_key_type">Tipo da chave PIX</label>
                        <select id="pix_key_type" name="pix_key_type">
                            <option value="">Selecione</option>
                            <?php foreach (['cnpj' => 'CNPJ', 'cpf' => 'CPF', 'email' => 'E-mail', 'phone' => 'Celular', 'random' => 'Chave aleatória'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($old['pix_key_type'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="chave_pix">Chave PIX</label>
                        <input type="text" id="chave_pix" name="chave_pix" maxlength="255" value="<?= htmlspecialchars((string) ($old['chave_pix'] ?? '')) ?>" autocomplete="off">
                        <small id="pixKeyHint">Selecione o tipo da chave para aplicar a formatação correta.</small>
                    </div>
                  </div>
                </div>

                <div class="method-fields" id="tedFields" hidden>
                  <div class="form-grid">
                    <div class="field"><label for="bank_code">Código do banco</label><input id="bank_code" name="bank_code" maxlength="10" value="<?= htmlspecialchars((string)($old['bank_code'] ?? '')) ?>" placeholder="Ex.: 001"></div>
                    <div class="field"><label for="bank_name">Banco</label><input id="bank_name" name="bank_name" maxlength="100" value="<?= htmlspecialchars((string)($old['bank_name'] ?? '')) ?>" placeholder="Nome do banco"></div>
                    <div class="field"><label for="agency">Agência</label><input id="agency" name="agency" maxlength="20" value="<?= htmlspecialchars((string)($old['agency'] ?? '')) ?>"></div>
                    <div class="field"><label for="account_number">Conta</label><input id="account_number" name="account_number" maxlength="30" value="<?= htmlspecialchars((string)($old['account_number'] ?? '')) ?>"></div>
                    <div class="field"><label for="account_digit">Dígito</label><input id="account_digit" name="account_digit" maxlength="10" value="<?= htmlspecialchars((string)($old['account_digit'] ?? '')) ?>"></div>
                    <div class="field"><label for="account_type">Tipo de conta</label><select id="account_type" name="account_type"><option value="">Selecione</option><option value="checking" <?= ($old['account_type'] ?? '') === 'checking' ? 'selected' : '' ?>>Conta corrente</option><option value="savings" <?= ($old['account_type'] ?? '') === 'savings' ? 'selected' : '' ?>>Poupança</option></select></div>
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
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('pix_key_type');
    const keyInput = document.getElementById('chave_pix');
    const hint = document.getElementById('pixKeyHint');
    const methodInputs = document.querySelectorAll('input[name="method"]');
    const pixFields = document.getElementById('pixFields');
    const tedFields = document.getElementById('tedFields');

    function updateMethod() {
        const method = document.querySelector('input[name="method"]:checked')?.value || 'pix';
        pixFields.hidden = method !== 'pix';
        tedFields.hidden = method !== 'ted';
        pixFields.querySelectorAll('input,select').forEach(el => el.required = method === 'pix');
        tedFields.querySelectorAll('input,select').forEach(el => el.required = method === 'ted');
    }
    methodInputs.forEach(input => input.addEventListener('change', updateMethod));
    updateMethod();

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
