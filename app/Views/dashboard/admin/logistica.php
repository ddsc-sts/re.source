<?php
// Variáveis injetadas pelo AdminController:
// $user, $metrics, $recentCompanies, $recentActivity, $esgIndicators, $volumeChart, $chartStats, $heroStats

$userName     = htmlspecialchars($user['name'] ?? 'Administrador');
$userInitials = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', $userName), 0, 2)));
$hour         = (int)(new DateTime())->format('H');
$greeting     = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');

function companyColor(string $name): string {
    $colors = ['#157347','#1d4ed8','#7c3aed','#0d9488','#ea580c','#be185d','#0369a1'];
    return $colors[abs(crc32($name)) % count($colors)];
}

// Helper para renderizar o badge de delta
function renderDelta(?array $delta): string {
    if (!$delta) return '';
    $dir   = $delta['direcao'];
    $val   = htmlspecialchars($delta['valor']);
    $icon  = $dir === 'up' ? 'trending-up' : ($dir === 'down' ? 'trending-down' : 'minus');
    $class = $dir === 'up' ? 'up' : ($dir === 'down' ? 'down' : 'flat');
    return "<div class=\"metric-delta {$class}\"><i data-lucide=\"{$icon}\"></i>{$val}</div>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Painel Administrativo — Re.Source</title>
  <link rel="icon" href="<?= htmlspecialchars(asset_url('/img/logos/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/admin-dashboard.css" />
</head>

<body>

<!-- ═══════════════════════
     HEADER
═══════════════════════ -->
<header class="site-header">

<?php require_once __DIR__ . '/../../components/topbar.php'; ?>

<?php require_once __DIR__ . '/../../components/navbar.php'; ?>

</header>

<script>
    lucide.createIcons();
</script>

</body>
