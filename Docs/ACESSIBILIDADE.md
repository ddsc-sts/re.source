# Acessibilidade

Meta: WCAG 2.2 nível AA.

- Navegação integral por teclado, ordem lógica de TAB e foco sempre visível.
- “Pular para o conteúdo”, landmarks e títulos hierárquicos nas telas principais.
- Labels persistentes, instruções associadas e erros anunciados com `role="alert"`.
- Texto alternativo descritivo; imagens decorativas com `alt=""`.
- Contraste mínimo de 4,5:1 para texto normal e 3:1 para texto grande/componentes.
- Alvos interativos de pelo menos 24 x 24 CSS px e interface responsiva a 320 px.
- Respeito a `prefers-reduced-motion` e ausência de informação transmitida apenas por cor.

Validação: teclado, zoom de 200%, Lighthouse/axe e ao menos NVDA + Firefox no Windows.
