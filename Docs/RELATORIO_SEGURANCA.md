# Revisão básica de segurança

## Proteções existentes

- PDO com prepared statements e emulação desativada reduz SQL injection.
- Senhas usam `password_hash` e `password_verify`.
- Operações mutáveis verificam método HTTP e token CSRF.
- Middlewares separam visitante, empresa aprovada e administrador.
- Saques e mudanças financeiras usam transações e tokens contra duplicação.
- Código de entrega é armazenado como hash, expira, tem uso único e limite de tentativas.
- Views usam `htmlspecialchars` na maior parte das saídas dinâmicas.

## Riscos e ações necessárias

| Prioridade | Risco | Tratamento |
|---|---|---|
| Alta | Upload local e validação distribuída | Validar MIME real, tamanho e extensão; renomear; armazenar fora da raiz pública/object storage. |
| Alta | Sessão sem política central visível | Cookies `Secure`, `HttpOnly`, `SameSite=Lax`, rotação no login e expiração por inatividade. |
| Alta | URLs fixas residuais | Substituir por `app_url()`/`asset_url()` e bloquear regressão em teste. |
| Média | APIs públicas sem limite | Rate limit para login, CNPJ, reset, chat e código de entrega. |
| Média | Dependências via CDN | Hospedar dependências ou usar SRI e CSP estrita. |
| Média | XSS em views antigas | Auditoria contextual e helper único de escape; CSP sem `unsafe-inline`. |
| Média | Dados pessoais/LGPD | Base legal, consentimento, minimização, retenção, exportação e exclusão auditável. |
| Baixa | Mensagens de erro em debug | `APP_DEBUG=false` em produção e página de erro genérica. |

## Checklist operacional

Executar análise de dependências, SAST, teste de autorização entre empresas, varredura de upload,
backup/restore, rotação de segredos e resposta a incidentes antes de publicação real.
