<?php
require __DIR__ . '/sessao.php';
exigir_autenticacao();
require __DIR__ . '/procedimentos.php';
?>
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
<style>
  .area-privada-layout {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(260px, 340px);
    gap: 32px;
    align-items: start;
  }
  @media (max-width: 860px) {
    .area-privada-layout {
      grid-template-columns: 1fr;
    }
  }
</style>
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="../index.html" class="logo">SMB<span class="dot"></span>SERVICES</a>
    <nav class="main-nav">
      <a href="../faq.html">← Voltar à FAQ pública</a>
      <a href="editar.php" class="btn btn-primary">Editar conteúdo</a>
      <a href="logout.php" class="btn btn-ghost">Sair</a>
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
  <div class="container" style="max-width:1100px;">

    <?php
    $erroConfig = null;
    try {
        $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
        $todos = listar_procedimentos($busca !== '' ? $busca : null);
        $verId = isset($_GET['ver']) ? $_GET['ver'] : null;
        $destaque = $verId !== null ? obter_procedimento($verId) : ultimo_procedimento();
    } catch (Throwable $e) {
        $erroConfig = $e->getMessage();
        $todos = [];
        $destaque = null;
        $busca = '';
        $verId = null;
    }
    $sufixoBusca = $busca !== '' ? '&busca=' . urlencode($busca) : '';
    ?>

    <?php if ($erroConfig): ?>
      <div class="card" style="border-color:#dc2626; margin-bottom:24px;">
        <p style="margin-bottom:0;"><strong>Configuração pendente:</strong> <?php echo htmlspecialchars($erroConfig); ?></p>
      </div>
    <?php else: ?>

    <div class="area-privada-layout">

      <!-- Painel principal: apenas o procedimento em foco -->
      <div style="min-width:0;">
        <?php if ($destaque): ?>
          <?php if ($verId !== null): ?>
            <p style="margin-bottom:12px; font-size:13px;"><a href="index.php<?php echo $busca !== '' ? '?busca=' . urlencode($busca) : ''; ?>">← Ver o mais recente</a></p>
          <?php else: ?>
            <div class="eyebrow" style="margin-bottom:10px;">Mais recente</div>
          <?php endif; ?>
          <div class="card">
            <div style="font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted-dark); margin-bottom:8px;"><?php echo htmlspecialchars(CATEGORIAS[$destaque['categoria']]); ?></div>
            <h2 style="margin-bottom:14px;"><?php echo htmlspecialchars($destaque['titulo']); ?></h2>
            <div style="white-space:pre-wrap; font-size:15px; line-height:1.6;"><?php echo htmlspecialchars($destaque['conteudo']); ?></div>
          </div>
        <?php else: ?>
          <p><em><?php echo $busca !== '' ? 'Nenhum procedimento encontrado para esta pesquisa.' : 'Ainda não há procedimentos cadastrados.'; ?></em></p>
        <?php endif; ?>
      </div>

      <!-- Barra lateral: pesquisa e categorias -->
      <div style="min-width:0;">
        <form method="get" action="index.php" style="display:flex; gap:8px; margin-bottom:20px;">
          <input type="text" name="busca" placeholder="Pesquisar…" value="<?php echo htmlspecialchars($busca); ?>" style="flex:1; padding:8px 12px; border:1px solid var(--border-light); border-radius:8px; font-size:14px;">
          <button type="submit" class="btn btn-ghost" style="border-color:var(--border-light); color:var(--text-dark); font-size:13px; padding:8px 12px;">Ir</button>
        </form>
        <?php if ($busca !== ''): ?>
          <p style="margin:-12px 0 16px;"><a href="index.php" style="font-size:12px;">Limpar pesquisa</a></p>
        <?php endif; ?>

        <?php $grupos = agrupar_por_categoria($todos); ?>
        <?php foreach (CATEGORIAS as $chave => $nome): ?>
          <?php if (empty($grupos[$chave])) continue; ?>
          <div class="card" style="margin-bottom:14px; padding:16px;">
            <h4 style="margin-bottom:10px; font-size:14px;"><?php echo htmlspecialchars($nome); ?> <span style="color:var(--text-muted-dark); font-weight:400;">(<?php echo count($grupos[$chave]); ?>)</span></h4>
            <div style="max-height:180px; overflow-y:auto;">
              <ul style="list-style:none; margin:0; padding:0;">
                <?php foreach ($grupos[$chave] as $p): ?>
                  <?php $ativo = $destaque && $destaque['id'] === $p['id']; ?>
                  <li style="margin-bottom:8px;">
                    <a href="index.php?ver=<?php echo urlencode($p['id']) . $sufixoBusca; ?>" style="font-size:13px; display:block; <?php echo $ativo ? 'font-weight:700; color:var(--navy);' : 'color:var(--text-dark);'; ?>">
                      <?php echo htmlspecialchars($p['titulo']); ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>

    <p style="font-size:13px; color:var(--text-muted-dark); font-style:italic; margin-top:32px;">Esta página exige login e a sessão encerra automaticamente após um período de inatividade. Use o botão "Editar conteúdo" acima para adicionar, alterar ou remover procedimentos.</p>
    <?php endif; ?>
  </div>
</section>

</body>
</html>
