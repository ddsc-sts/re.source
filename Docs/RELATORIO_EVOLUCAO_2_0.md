# Re.Source 2.0 - Relatório de evolução

## 1. Problema identificado

O MVP entregava o fluxo principal, mas dependia de configuração manual, continha caminhos locais,
oferecia pouca orientação inicial e ainda não evidenciava um diferencial além dos classificados.

## 2. Solução proposta

Onboarding persistente, Central de Ajuda, Match explicável, passaporte rastreável com QR Code,
indicadores transparentes, ambiente Docker, inicialização automática do banco, CI e documentação
de cloud, segurança e acessibilidade.

## 3. Justificativa

As mudanças cobrem os quatro pilares definidos: experiência, inovação, engenharia e cloud, sem
substituir os fluxos estáveis de negociação do MVP.

## 4. Tecnologias

PHP 8.3, Apache, MariaDB, PDO, JavaScript, CSS, Docker Compose, PHPUnit e GitHub Actions.

## 5. Desenvolvimento

- O onboarding usa o estado `companies.onboarding_completed` e pode ser reaberto pela Ajuda.
- O Match pontua sinais explicáveis e mostra cada parcela da compatibilidade.
- O passaporte cria código `RS-ANO-ID`, token público não enumerável e QR para consulta.
- A migration `001_product_innovation.sql` guarda fatores ambientais e passaportes.
- Compose cria aplicação, banco e dados demonstrativos; phpMyAdmin é perfil opcional.

## 6. Evidências

As novas rotas `/ajuda`, `/match` e `/passaporte` demonstram os fluxos. O diagrama está em
`Docs/ARQUITETURA_CLOUD.md`; infraestrutura em `Dockerfile`, `compose.yaml` e `.github/workflows/ci.yml`.

## 7. Testes

A validação automatizada roda no GitHub Actions com PHP 8.3 e MariaDB. Devem ser acrescentados
testes específicos de pontuação, autorização do passaporte, teclado e responsividade.

## 8. Dificuldades

O legado mistura resposta HTTP, domínio e SQL em controllers extensos e ainda possui URLs fixas.
O cálculo ESG original utiliza fator acadêmico único, insuficiente para alegações ambientais reais.

## 9. Aprendizados

Portabilidade, feedback e explicabilidade são partes do produto. Inovação útil não exige ML no
primeiro ciclo: regras auditáveis criam uma base melhor para dados futuros.

## 10. Próximos passos

Eliminar caminhos residuais, externalizar uploads, adotar fatores ESG publicados por material e
transporte, ampliar testes, instrumentar observabilidade, executar avaliação LGPD e pentest.

## Prontidão para usuários reais

Ainda seriam necessários hardening de sessão e upload, object storage, HTTPS/WAF, banco gerenciado,
backup restaurável, rate limiting, gestão de segredos, monitoramento e alertas, suporte operacional,
políticas LGPD, testes de carga e segurança e validação científica dos indicadores ambientais.
