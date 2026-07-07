# Re.Source

<p align="center">
  <img src="public/img/logos/logo.png" alt="Logo Re.Source" width="260">
</p>

<p align="center">
  Marketplace B2B acadêmico para negociação de resíduos industriais, com cadastro de empresas, aprovação administrativa, anúncios, chat, proposta, frete simulado, confirmação de entrega e saldo interno.
</p>

## Sobre o projeto

O Re.Source é um sistema desenvolvido como MVP acadêmico para apresentação de curso. A proposta é simular uma plataforma B2B onde empresas podem anunciar, buscar e negociar resíduos industriais, incentivando economia circular e reaproveitamento de materiais.

O projeto não executa operações financeiras reais, não integra transportadoras reais e não deve ser tratado como sistema pronto para produção. O foco atual é demonstrar o fluxo principal de ponta a ponta de forma funcional e apresentável.

## Funcionalidades principais

- Cadastro de empresa com validação, confirmação por e-mail e status pendente.
- Login de empresa pendente com navegação limitada.
- Aprovação, solicitação de correção, rejeição, suspensão e reativação pelo administrador.
- Dashboard empresarial com métricas, categorias e anúncios.
- CRUD de anúncios de resíduos.
- Busca pública por texto, categoria e filtros básicos.
- Chat entre empresas com contador de mensagens não lidas e atualização por polling.
- Propostas comerciais com quantidade, valor, prazo e responsabilidade pelo frete.
- Aceite mútuo entre comprador e vendedor.
- Frete simulado com opções persistidas no banco.
- Código de entrega de seis dígitos com hash, validade, limite de tentativas e uso único.
- Liberação interna de saldo após confirmação de entrega.
- Solicitação de saque PIX/TED com aprovação ou recusa manual pelo administrador.
- Painel administrativo com empresas, anúncios, negociações, logística, saques, suporte, impacto e configurações.
- Termos de Uso e Política de Privacidade.
- Testes automatizados com PHPUnit.

## Tecnologias

- PHP 8+
- MariaDB/MySQL
- PDO
- HTML, CSS e JavaScript
- PHPUnit
- XAMPP para ambiente local

## Estrutura do projeto

```text
app/
  Controllers/      Controllers da aplicação
  Middleware/       Middlewares de autenticação e autorização
  Services/         Serviços de domínio
  Views/            Telas e componentes PHP

config/             Configurações auxiliares, incluindo envio de e-mail
database/
  seeders/          Schema consolidado do banco
  inserts/          Dados acadêmicos/demonstração
  migrations/       Migrations históricas para bancos antigos

public/
  css/              Estilos
  img/              Logos e imagens da interface
  js/               Scripts do frontend
  index.php         Entrada pública

routes/             Mapa de rotas
tests/              Testes automatizados
Docs/               Documentação acadêmica do projeto
```

## Como rodar localmente

### 1. Clonar o projeto

```bash
git clone <url-do-repositorio>
cd re.source
```

### 2. Configurar o ambiente

Copie o arquivo de exemplo:

```bash
cp .env.example .env
```

Em Windows, você também pode copiar manualmente o `.env.example` e renomear para `.env`.

Configuração local esperada:

```env
APP_URL=http://localhost/re.source
APP_BASE_PATH=/re.source

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=resource
DB_USERNAME=root
DB_PASSWORD=
```

Para testar envio de e-mails, preencha também as variáveis SMTP no `.env`.

### 3. Preparar o banco de dados

No phpMyAdmin:

1. Crie um banco vazio chamado `resource`.
2. Use collation `utf8mb4_unicode_ci`.
3. Selecione o banco.
4. Importe os arquivos SQL nesta ordem:

```text
1. database/seeders/re.sourcebanco.sql
2. database/inserts/create_admin.sql
3. database/inserts/empresa_demo.sql   (opcional, recomendado para demonstração)
4. database/inserts/produto.sql        (opcional, depende do item 3)
5. database/inserts/saldo_demo.sql     (opcional, depende dos itens 3 e 4)
```

Em banco novo, não é necessário executar as migrations. Elas existem para atualização de bancos antigos.

### 4. Acessar o sistema

```text
http://localhost/re.source
```

## Contas acadêmicas de demonstração

Administrador:

```text
URL:   http://localhost/re.source/admin
E-mail: admin@resource.com.br
Senha: Admin@2026!
```

Empresas demonstrativas:

```text
carlos@metaljoin.com.br
ana@madeirasul.com.br
roberto@plasticonord.com.br
fernanda@textilcat.com.br
marina@empresapendente.com.br

Senha comum: Resource@2026
```

Essas credenciais são fictícias e existem apenas para demonstração acadêmica.

## Fluxo principal da apresentação

```text
Cadastro
-> Confirmação por e-mail
-> Empresa pendente
-> Aprovação administrativa
-> Dashboard liberado
-> Criação/busca de anúncio
-> Chat entre empresas
-> Proposta comercial
-> Aceite mútuo
-> Frete simulado
-> Código de entrega
-> Liberação de saldo
-> Solicitação de saque
-> Aprovação/recusa manual pelo administrador
```

## Testes

O projeto possui suíte de testes com PHPUnit.

Instale as dependências:

```bash
composer install
```

Execute:

```bash
vendor/bin/phpunit
```

No Windows, dependendo do terminal:

```bash
vendor\bin\phpunit
```

O último relatório registrado no projeto indica:

```text
OK (106 tests, 326 assertions)
```

## Licença

Projeto acadêmico. Defina uma licença formal antes de reutilizar ou distribuir em contexto não acadêmico.
