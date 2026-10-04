<p align="center">
  <img src="public/img/logos/logo.png" alt="Re.Source" width="120" />
</p>

<h1 align="center">Re.Source</h1>

<p align="center">
  Marketplace B2B para empresas negociarem resíduos industriais.<br/>
  Do anúncio à entrega, com chat, proposta, frete e saldo interno.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/testes-106_passando-2EA44F?style=flat-square" alt="Testes" />
  <img src="https://img.shields.io/badge/status-MVP_acadêmico-lightgrey?style=flat-square" alt="Status" />
</p>

<p align="center">
  <img src="Docs/screenshots/dashboard.png" alt="Dashboard do Re.Source" width="860" />
</p>

---

## O que é

Muita empresa descarta material que outra poderia usar: sobra de metal, madeira, plástico, tecido. O Re.Source é a tentativa de colocar essas duas pontas na mesma tela.

Um vendedor anuncia o resíduo, um comprador encontra, os dois conversam, fecham uma proposta, o frete é combinado, e o dinheiro só é liberado quando a entrega é confirmada com um código.

Foi feito em equipe como MVP de curso, e o objetivo era fazer **o fluxo inteiro funcionar de ponta a ponta**, não só telas soltas.

## Como uma negociação acontece

```text
cadastro → confirmação por e-mail → aprovação do admin
   → anúncio → chat → proposta → aceite dos dois lados
   → frete → código de entrega → saldo liberado → saque
```

Toda empresa nova entra como pendente e só ganha acesso completo depois que um administrador aprova. O admin também pode pedir correção, rejeitar, suspender e reativar.

## Telas

<table>
  <tr>
    <td width="50%"><img src="Docs/screenshots/pagina-inicial.png" alt="Página inicial" /><br/><sub>Página inicial</sub></td>
    <td width="50%"><img src="Docs/screenshots/anuncios.png" alt="Busca e anúncios" /><br/><sub>Busca e anúncios</sub></td>
  </tr>
  <tr>
    <td width="50%"><img src="Docs/screenshots/negociacao-chat.png" alt="Chat e negociação" /><br/><sub>Chat e negociação</sub></td>
    <td width="50%"><img src="Docs/screenshots/entregas.png" alt="Acompanhamento de entrega" /><br/><sub>Acompanhamento de entrega</sub></td>
  </tr>
</table>

## Detalhes que vale mencionar

- **Código de entrega:** seis dígitos, guardado com hash, com validade, limite de tentativas e uso único. Só depois dele o saldo do vendedor é liberado.
- **Chat:** atualiza por polling, com contador de mensagens não lidas. Não usa WebSocket.
- **Arquitetura:** MVC em PHP puro, com controllers, middlewares de autenticação e autorização, e uma camada de serviços para as regras de negócio.
- **Testes:** 106 testes e 326 asserções com PHPUnit.
- **Painel admin:** empresas, anúncios, negociações, logística, saques, suporte e uma área de impacto ESG.

## Limitações

Por ser um MVP acadêmico, algumas partes são simuladas:

- O frete é simulado. As opções ficam salvas no banco, mas não há transportadora real.
- O saldo é interno. Não existe gateway de pagamento, e o saque (PIX/TED) é aprovado ou recusado manualmente pelo admin.
- Os dados de demonstração são fictícios.

## Rodando na sua máquina

Você precisa de PHP 8+, MySQL/MariaDB e Composer. O jeito mais simples é o XAMPP.

**No Windows, com XAMPP em `C:\xampp` e Git no `PATH`:** execute `iniciar_ambiente.bat`. Ele clona o projeto, cria o `.env`, sobe Apache e MySQL, importa o banco e abre o site.

**Manualmente:**

```bash
git clone https://github.com/ddsc-sts/re.source.git
cd re.source
cp .env.example .env
```

Ajuste o `.env` se o seu MySQL não for o padrão:

```env
APP_URL=http://localhost/re.source
APP_BASE_PATH=/re.source

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=resource
DB_USERNAME=root
DB_PASSWORD=
```

Depois importe os arquivos SQL no phpMyAdmin, nesta ordem:

```text
database/seeders/re.sourcebanco.sql
database/inserts/create_admin.sql
database/inserts/empresa_demo.sql    (opcional)
database/inserts/produto.sql         (opcional, depende do anterior)
database/inserts/saldo_demo.sql      (opcional, depende dos dois acima)
```

E acesse `http://localhost/re.source`.

Para rodar os testes:

```bash
composer install
vendor/bin/phpunit
```

<details>
<summary>Contas de demonstração</summary>
<br/>

Todas fictícias, só para testar.

| Perfil | E-mail | Senha |
|---|---|---|
| Admin (`/admin`) | admin@resource.com.br | `Admin@2026!` |
| Empresa | carlos@metaljoin.com.br | `Resource@2026` |
| Empresa | ana@madeirasul.com.br | `Resource@2026` |
| Empresa | roberto@plasticonord.com.br | `Resource@2026` |
| Empresa | fernanda@textilcat.com.br | `Resource@2026` |
| Empresa pendente | marina@empresapendente.com.br | `Resource@2026` |

</details>

## Estrutura

```text
app/
  Controllers/   Middleware/   Services/   Views/
config/          envio de e-mail e afins
database/        seeders/ (schema)  inserts/ (demo)
public/          css/  js/  img/  index.php
routes/
tests/
Docs/            documentação e screenshots
```

## Equipe

| | Principais frentes |
|---|---|
| **Leonardo Becker** | Integração do MVP e fluxo principal: anúncios, busca, negociação, chat, propostas, frete, entrega por código, saldo e saques. Revisão final e preparação do repositório. |
| **Daniel dos Santos** | Estrutura inicial, banco de dados, cadastro, login, verificação por e-mail e recuperação de senha. Migração para MVC, redesign das telas, base de frete e entrega, e a suíte de testes. |
| **Geder** ([@Gedinn07](https://github.com/Gedinn07)) | Padronização de CSS, telas do painel admin (empresas, anúncios, negociações), área de impacto ESG e revisão geral de interface. |

## Licença

Projeto acadêmico, ainda sem licença definida. Antes de reutilizar fora desse contexto, fale com a equipe.
