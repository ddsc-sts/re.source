# Congelamento de escopo do MVP

Data do congelamento: 04/07/2026.

Até a apresentação, somente são permitidos:

- correções que impeçam o roteiro principal;
- ajustes de segurança, dados demonstrativos e responsividade;
- melhoria de mensagens de erro ou feedback visual;
- testes, documentação e restauração do ambiente.

Não entram no caminho crítico:

- integração real com transportadora ou Melhor Envio;
- gateway bancário ou pagamento real;
- WebSocket para o chat;
- perfil externo de entregador;
- recursos novos que não apareçam no roteiro de apresentação.

Decisões do MVP:

- chat por polling HTTP;
- frete com três cotações simuladas persistidas;
- confirmação de entrega por código de seis dígitos;
- saldo em ledger interno;
- saque PIX/TED com aprovação manual do administrador;
- métricas ESG acadêmicas, identificadas como estimativas.
