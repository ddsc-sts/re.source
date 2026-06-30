// ── Init Lucide Icons ──
lucide.createIcons();

// ── Radio labels — highlight ao selecionar ──
document.querySelectorAll('.radio-label').forEach(label => {
  const input = label.querySelector('input[type="radio"]');

  if (input.checked) label.classList.add('active');

  input.addEventListener('change', () => {
    document.querySelectorAll('.radio-label').forEach(l => l.classList.remove('active'));
    label.classList.add('active');
  });
});

// ── Estados via API IBGE ──
const stateSelect    = document.getElementById('state');
const selectedState  = stateSelect?.getAttribute('data-selected') ?? '';

if (stateSelect) {
  fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome')
    .then(r => r.json())
    .then(states => {
      states.forEach(s => {
        const opt = document.createElement('option');
        opt.value       = s.sigla;
        opt.textContent = s.nome;
        if (s.sigla === selectedState) opt.selected = true;
        stateSelect.appendChild(opt);
      });
    })
    .catch(() => {});
}