# Proposta de arquitetura em nuvem

```text
Usuário / navegador
        |
     HTTPS + DNS
        |
CDN / Web Application Firewall
        |
Container PHP + Apache  ----> Object Storage (imagens e documentos)
        |                         |
        +---- MariaDB gerenciado -+
        |
Logs, métricas, alertas e auditoria
```

## Componentes

- **DNS, HTTPS e WAF:** endereço público, criptografia em trânsito e filtragem inicial.
- **Aplicação em container:** imagem imutável criada pelo `Dockerfile`, escalável horizontalmente.
- **Banco gerenciado:** rede privada, backup automático, restauração testada e alta disponibilidade.
- **Object Storage:** uploads fora do container; URLs assinadas para arquivos privados e CDN para públicos.
- **Observabilidade:** logs estruturados sem dados sensíveis, métricas, rastreamento de erros e alertas.
- **Segredos:** cofre de segredos e identidades gerenciadas; nunca imagens Docker ou Git.

## Caminho sugerido no Azure

Azure Front Door/WAF -> Azure Container Apps -> Azure Database for MySQL, com Blob Storage,
Key Vault e Azure Monitor. Ambientes de homologação e produção devem ser separados, e o pipeline
somente promove uma imagem que passou pelos testes.

## Produção

Antes de usuários reais: domínio e TLS, consentimento/LGPD, política de retenção, storage externo,
backup e restore, migrações versionadas, rate limiting, e-mail transacional, monitoramento 24x7,
gestão de incidentes e testes de carga e segurança.
