<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php";

$titulo_pagina = "Contato — Re.Source";
include 'header.php';
?>

<style>
body { font-family: var(--font-body); background: var(--bg); color: var(--dark); min-height: 100vh; }

.contact-section {
    max-width: 800px;
    margin: 4rem auto;
    padding: 0 1.5rem;
}

.form-container {
    background: var(--white);
    border-radius: 1rem;
    padding: 3rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}

.contact-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.contact-header h1 {
    font-family: var(--font-main);
    color: var(--dark);
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.contact-header p {
    color: var(--muted);
    font-size: 1.1rem;
    line-height: 1.6;
}

.form-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }

.input-box { display: flex; flex-direction: column; gap: 0.4rem; }
.input-box label { font-family: var(--font-main); font-size: 0.875rem; font-weight: 600; color: var(--dark); }

.input-box input[type="text"],
.input-box textarea {
    width: 100%; padding: 0.875rem 1rem; border: 1px solid var(--border-color); border-radius: 0.5rem;
    background-color: var(--bg); color: var(--dark); font-size: 1rem; font-family: var(--font-body);
    transition: all 0.2s ease;
}
.input-box input:focus, .input-box textarea:focus {
    border-color: var(--green); background-color: var(--white); box-shadow: 0 0 0 4px rgba(21,115,71,0.15); outline: none;
}

.btn-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    width: 100%;
    padding: 1rem;
    border-radius: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    background: #25D366; /* WhatsApp Green */
    cursor: pointer;
    border: none;
    transition: background 0.2s, transform 0.1s;
    margin-top: 1rem;
}
.btn-submit:hover { background: #128C7E; transform: translateY(-1px); }

@media (max-width: 768px) {
    .form-container { padding: 2rem 1.5rem; }
    .contact-header h1 { font-size: 2rem; }
}
</style>

<main class="contact-section">
    <div class="form-container">
        <div class="contact-header">
            <h1>Fale Conosco</h1>
            <p>Tem dúvidas, sugestões ou precisa de suporte?<br>Preencha os dados abaixo e fale diretamente com nossa equipe no WhatsApp!</p>
        </div>

        <form id="formContato">
            <div class="form-grid">
                <div class="input-box">
                    <label for="nome">Seu Nome / Empresa</label>
                    <input type="text" id="nome" name="nome" placeholder="Digite seu nome ou da sua empresa" required>
                </div>

                <div class="input-box">
                    <label for="assunto">Assunto</label>
                    <input type="text" id="assunto" name="assunto" placeholder="Ex: Suporte com Anúncio, Dúvidas, Comercial..." required>
                </div>

                <div class="input-box">
                    <label for="mensagem">Mensagem</label>
                    <textarea id="mensagem" name="mensagem" rows="5" placeholder="Digite sua mensagem aqui..." required style="resize:vertical;"></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                    Enviar pelo WhatsApp
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const formContato = document.getElementById('formContato');

    formContato.addEventListener('submit', function(e) {
        e.preventDefault();

        const nome = document.getElementById('nome').value.trim();
        const assunto = document.getElementById('assunto').value.trim();
        const msg = document.getElementById('mensagem').value.trim();

        if (!nome || !assunto || !msg) return;

        // Montar a mensagem
        let textoFinal = `Olá, me chamo *${nome}*.\n`;
        textoFinal += `*Assunto:* ${assunto}\n\n`;
        textoFinal += `${msg}`;

        // Codificar para URL
        const textoEncoded = encodeURIComponent(textoFinal);

        // O número usado no footer é 5547999999999
        const numeroWhatsApp = "5547999999999";
        
        // Redirecionar
        window.open(`https://wa.me/${numeroWhatsApp}?text=${textoEncoded}`, '_blank');
    });
});
</script>

<?php include 'footer.php'; ?>
