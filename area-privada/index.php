<?php require __DIR__ . '/procedimentos.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Área Privada | SMB Services</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css?v=2">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="../index.html" class="logo">SMB<span class="dot"></span>SERVICES</a>
    <nav class="main-nav">
      <a href="../faq.html">← Voltar à FAQ pública</a>
      <a href="editar.php" class="btn btn-primary">Editar conteúdo</a>
    </nav>
  </div>
</header>

<section class="section-dark">
  <div class="container">
    <div class="eyebrow">Acesso restrito</div>
    <h1>Área privada</h1>
    <p style="max-width:680px; margin-top:16px;">Procedimentos internos, runbooks e documentação técnica para uso exclusivo da equipa SMB Services.</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:720px;">

    <?php
    $procedimentos = listar_procedimentos();
    if (empty($procedimentos)) {
        echo '<p><em>Ainda não há procedimentos cadastrados.</em></p>';
    }
    $grupos = agrupar_por_categoria($procedimentos);
    $primeiro = true;
    foreach (CATEGORIAS as $chave => $nome) {
        if (empty($grupos[$chave])) {
            continue;
        }
        echo '<h2 style="margin:' . ($primeiro ? '0' : '32px') . ' 0 12px; font-size:20px;">' . htmlspecialchars($nome) . '</h2>';
        foreach ($grupos[$chave] as $p) {
            $aberto = $primeiro ? ' open' : '';
            echo '<details class="faq-item"' . $aberto . '>';
            echo '<summary>' . htmlspecialchars($p['titulo']) . '</summary>';
            echo '<div style="white-space:pre-wrap;">' . htmlspecialchars($p['conteudo']) . '</div>';
            echo '</details>';
            $primeiro = false;
        }
    }
    ?>

    <p style="font-size:13px; color:var(--text-muted-dark); font-style:italic; margin-top:24px;">Esta página está protegida por autenticação (utilizador e senha) a nível do servidor. Use o botão "Editar conteúdo" acima para adicionar, alterar ou remover procedimentos.</p>
  </div>
</section>

</body>
</html>
