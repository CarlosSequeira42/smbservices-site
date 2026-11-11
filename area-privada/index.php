<?php
require __DIR__ . '/sessao.php';
exigir_autenticacao();
require __DIR__ . '/procedimentos.php';

$erroConfig = null;
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$verId = isset($_GET['ver']) ? $_GET['ver'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = $_POST['id'] ?? '';
    $buscaVolta = $_POST['busca'] ?? '';
    $verVolta = $_POST['ver'] ?? '';

    try {
        if ($acao === 'favoritar') {
            alternar_favorito($id);
        } elseif ($acao === 'remover') {
            remover_procedimento($id);
            if ($verVolta === $id) {
                $verVolta = '';
            }
        }
    } catch (Throwable $e) {
        // Se a base de dados ainda não estiver configurada, a página
        // seguinte (sem POST) mostra a mensagem de configuração pendente.
    }

    $query = [];
    if ($buscaVolta !== '') {
        $query['busca'] = $buscaVolta;
    }
    if ($verVolta !== '') {
        $query['ver'] = $verVolta;
    }
    header('Location: index.php' . (count($query) ? '?' . http_build_query($query) : ''));
    exit;
}

/**
 * Imprime os botões de ação (favoritar / remover) de um procedimento.
 */
function botoes_acao($p, $busca, $verAtual) {
    $camposContexto = '<input type="hidden" name="busca" value="' . htmlspecialchars($busca) . '">'
        . '<input type="hidden" name="ver" value="' . htmlspecialchars($verAtual) . '">';
    $estrela = $p['favorito'] ? '★' : '☆';
    $corEstrela = $p['favorito'] ? '#d97706' : 'var(--text-muted-dark)';
    echo '<div style="display:flex; gap:4px; flex-shrink:0;">';
    echo '<form method="post" action="index.php" style="margin:0;">';
    echo '<input type="hidden" name="acao" value="favoritar">';
    echo '<input type="hidden" name="id" value="' . htmlspecialchars($p['id']) . '">';
    echo $camposContexto;
    echo '<button type="submit" title="Marcar/desmarcar como favorito" style="border:none; background:none; cursor:pointer; font-size:16px; line-height:1; padding:2px 4px; color:' . $corEstrela . ';">' . $estrela . '</button>';
    echo '</form>';
    echo '<form method="post" action="index.php" onsubmit="return confirm(\'Remover este procedimento? Esta ação não pode ser desfeita.\');" style="margin:0;">';
    echo '<input type="hidden" name="acao" value="remover">';
    echo '<input type="hidden" name="id" value="' . htmlspecialchars($p['id']) . '">';
    echo $camposContexto;
    echo '<button type="submit" title="Remover" style="border:none; background:none; cursor:pointer; font-size:14px; line-height:1; padding:2px 4px; color:#dc2626;">🗑</button>';
    echo '</form>';
    echo '</div>';
}
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
    $todos = [];
    $destaque = null;
    $favoritos = [];
    try {
        $todos = listar_procedimentos($busca !== '' ? $busca : null);
        $favoritos = listar_favoritos();
        if ($verId !== null) {
            $destaque = obter_procedimento($verId);
        }
    } catch (Throwable $e) {
        $erroConfig = $e->getMessage();
    }
    $sufixoBusca = $busca !== '' ? '&busca=' . urlencode($busca) : '';
    ?>

    <?php if ($erroConfig): ?>
      <div class="card" style="border-color:#dc2626; margin-bottom:24px;">
        <p style="margin-bottom:0;"><strong>Configuração pendente:</strong> <?php echo htmlspecialchars($erroConfig); ?></p>
      </div>
    <?php else: ?>

    <div class="area-privada-layout">

      <!-- Painel principal -->
      <div style="min-width:0;">
        <?php if ($verId !== null): ?>
          <p style="margin-bottom:12px; font-size:13px;"><a href="index.php<?php echo $busca !== '' ? '?busca=' . urlencode($busca) : ''; ?>">← Ver favoritos</a></p>
          <?php if ($destaque): ?>
            <div class="card">
              <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                <div>
                  <div style="font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted-dark); margin-bottom:8px;"><?php echo htmlspecialchars(CATEGORIAS[$destaque['categoria']]); ?></div>
                  <h2 style="margin-bottom:14px;"><?php echo htmlspecialchars($destaque['titulo']); ?></h2>
                </div>
                <?php botoes_acao($destaque, $busca, $destaque['id']); ?>
              </div>
              <div style="white-space:pre-wrap; font-size:15px; line-height:1.6;"><?php echo htmlspecialchars($destaque['conteudo']); ?></div>
            </div>
          <?php else: ?>
            <p><em>Este procedimento já não existe.</em></p>
          <?php endif; ?>
        <?php else: ?>
          <div class="eyebrow" style="margin-bottom:10px;">Favoritos</div>
          <?php if (!empty($favoritos)): ?>
            <?php foreach ($favoritos as $p): ?>
              <div class="card" style="margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                  <div>
                    <div style="font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted-dark); margin-bottom:8px;"><?php echo htmlspecialchars(CATEGORIAS[$p['categoria']]); ?></div>
                    <h2 style="margin-bottom:14px;"><?php echo htmlspecialchars($p['titulo']); ?></h2>
                  </div>
                  <?php botoes_acao($p, $busca, ''); ?>
                </div>
                <div style="white-space:pre-wrap; font-size:15px; line-height:1.6;"><?php echo htmlspecialchars($p['conteudo']); ?></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p><em>Ainda não há favoritos. Marque um procedimento com a estrela (☆) na lista ao lado para vê-lo aqui.</em></p>
            <?php $ultimo = null; try { $ultimo = ultimo_procedimento(); } catch (Throwable $e) {} ?>
            <?php if ($ultimo): ?>
              <div class="eyebrow" style="margin:24px 0 10px;">Mais recente</div>
              <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                  <div>
                    <div style="font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted-dark); margin-bottom:8px;"><?php echo htmlspecialchars(CATEGORIAS[$ultimo['categoria']]); ?></div>
                    <h2 style="margin-bottom:14px;"><?php echo htmlspecialchars($ultimo['titulo']); ?></h2>
                  </div>
                  <?php botoes_acao($ultimo, $busca, ''); ?>
                </div>
                <div style="white-space:pre-wrap; font-size:15px; line-height:1.6;"><?php echo htmlspecialchars($ultimo['conteudo']); ?></div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
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
        <?php if (empty($todos)): ?>
          <p><em><?php echo $busca !== '' ? 'Nenhum procedimento encontrado para esta pesquisa.' : 'Ainda não há procedimentos cadastrados.'; ?></em></p>
        <?php endif; ?>
        <?php foreach (CATEGORIAS as $chave => $nome): ?>
          <?php if (empty($grupos[$chave])) continue; ?>
          <div class="card" style="margin-bottom:14px; padding:16px;">
            <h4 style="margin-bottom:10px; font-size:14px;"><?php echo htmlspecialchars($nome); ?> <span style="color:var(--text-muted-dark); font-weight:400;">(<?php echo count($grupos[$chave]); ?>)</span></h4>
            <div style="max-height:200px; overflow-y:auto;">
              <ul style="list-style:none; margin:0; padding:0;">
                <?php foreach ($grupos[$chave] as $p): ?>
                  <?php $ativo = $verId !== null && $destaque && $destaque['id'] === $p['id']; ?>
                  <li style="display:flex; align-items:center; gap:4px; margin-bottom:6px;">
                    <a href="index.php?ver=<?php echo urlencode($p['id']) . $sufixoBusca; ?>" style="flex:1; min-width:0; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; <?php echo $ativo ? 'font-weight:700; color:var(--navy);' : 'color:var(--text-dark);'; ?>">
                      <?php echo ($p['favorito'] ? '★ ' : '') . htmlspecialchars($p['titulo']); ?>
                    </a>
                    <?php botoes_acao($p, $busca, $verId !== null ? $verId : ''); ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>

    <p style="font-size:13px; color:var(--text-muted-dark); font-style:italic; margin-top:32px;">Esta página exige login e a sessão encerra automaticamente após um período de inatividade. Use o botão "Editar conteúdo" acima para adicionar procedimentos, ou a estrela/lixeira em cada item para favoritar ou remover.</p>
    <?php endif; ?>
  </div>
</section>

</body>
</html>
