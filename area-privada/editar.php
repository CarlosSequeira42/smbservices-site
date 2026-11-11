<?php
require __DIR__ . '/procedimentos.php';

$mensagem = '';
$editando = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';
    $procedimentos = listar_procedimentos();

    if ($acao === 'adicionar') {
        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        if (!isset(CATEGORIAS[$categoria])) {
            $categoria = 'outros';
        }
        if ($titulo !== '') {
            $procedimentos[] = [
                'id' => novo_id($procedimentos),
                'categoria' => $categoria,
                'titulo' => $titulo,
                'conteudo' => $conteudo,
            ];
            guardar_procedimentos($procedimentos);
            $mensagem = 'Procedimento adicionado.';
        } else {
            $mensagem = 'O título é obrigatório.';
        }
    } elseif ($acao === 'atualizar') {
        $id = $_POST['id'] ?? '';
        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        if (!isset(CATEGORIAS[$categoria])) {
            $categoria = 'outros';
        }
        foreach ($procedimentos as &$p) {
            if ($p['id'] === $id) {
                $p['titulo'] = $titulo;
                $p['conteudo'] = $conteudo;
                $p['categoria'] = $categoria;
                break;
            }
        }
        unset($p);
        guardar_procedimentos($procedimentos);
        $mensagem = 'Procedimento atualizado.';
    } elseif ($acao === 'remover') {
        $id = $_POST['id'] ?? '';
        $procedimentos = array_values(array_filter($procedimentos, function ($p) use ($id) {
            return $p['id'] !== $id;
        }));
        guardar_procedimentos($procedimentos);
        $mensagem = 'Procedimento removido.';
    }

    header('Location: editar.php?msg=' . urlencode($mensagem));
    exit;
}

$procedimentos = listar_procedimentos();
$mensagem = isset($_GET['msg']) ? $_GET['msg'] : '';
$idEditar = isset($_GET['editar']) ? $_GET['editar'] : null;
foreach ($procedimentos as $p) {
    if ($idEditar !== null && $p['id'] === $idEditar) {
        $editando = $p;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Área Privada | SMB Services</title>
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
      <a href="index.php">← Voltar à área privada</a>
    </nav>
  </div>
</header>

<section class="section-dark">
  <div class="container">
    <div class="eyebrow">Acesso restrito</div>
    <h1>Editar procedimentos</h1>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:720px;">

    <?php if ($mensagem): ?>
      <div class="card" style="border-color:var(--teal); margin-bottom:24px;">
        <p style="margin-bottom:0;"><?php echo htmlspecialchars($mensagem); ?></p>
      </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:32px;">
      <h3><?php echo $editando ? 'Editar procedimento' : 'Adicionar novo procedimento'; ?></h3>
      <form method="post" action="editar.php">
        <input type="hidden" name="acao" value="<?php echo $editando ? 'atualizar' : 'adicionar'; ?>">
        <?php if ($editando): ?>
          <input type="hidden" name="id" value="<?php echo htmlspecialchars($editando['id']); ?>">
        <?php endif; ?>
        <div class="form-field">
          <label for="titulo">Título</label>
          <input type="text" id="titulo" name="titulo" required value="<?php echo $editando ? htmlspecialchars($editando['titulo']) : ''; ?>">
        </div>
        <div class="form-field">
          <label for="categoria">Serviço / Categoria</label>
          <select id="categoria" name="categoria" required>
            <?php foreach (CATEGORIAS as $chave => $nome): ?>
              <option value="<?php echo htmlspecialchars($chave); ?>" <?php echo ($editando && $editando['categoria'] === $chave) ? 'selected' : ''; ?>><?php echo htmlspecialchars($nome); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="conteudo">Conteúdo</label>
          <textarea id="conteudo" name="conteudo" rows="8"><?php echo $editando ? htmlspecialchars($editando['conteudo']) : ''; ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="border:none; cursor:pointer;"><?php echo $editando ? 'Guardar alterações' : 'Adicionar'; ?> →</button>
        <?php if ($editando): ?>
          <a href="editar.php" class="btn btn-ghost" style="border-color:var(--border-light); color:var(--text-dark); margin-left:8px;">Cancelar</a>
        <?php endif; ?>
      </form>
    </div>

    <h3>Procedimentos existentes</h3>
    <?php if (empty($procedimentos)): ?>
      <p><em>Ainda não há procedimentos cadastrados.</em></p>
    <?php endif; ?>
    <?php $grupos = agrupar_por_categoria($procedimentos); ?>
    <?php foreach (CATEGORIAS as $chave => $nome): ?>
      <?php if (empty($grupos[$chave])) continue; ?>
      <h4 style="margin:24px 0 10px; color:var(--text-muted-dark);"><?php echo htmlspecialchars($nome); ?></h4>
      <?php foreach ($grupos[$chave] as $p): ?>
        <div class="card" style="margin-bottom:16px;">
          <h3 style="margin-bottom:6px;"><?php echo htmlspecialchars($p['titulo']); ?></h3>
          <p style="white-space:pre-wrap; font-size:14px; color:#334155;"><?php echo htmlspecialchars($p['conteudo']); ?></p>
          <div style="display:flex; gap:10px; margin-top:12px;">
            <a href="editar.php?editar=<?php echo urlencode($p['id']); ?>" class="btn btn-ghost" style="border-color:var(--border-light); color:var(--text-dark); font-size:13px; padding:8px 14px;">Editar</a>
            <form method="post" action="editar.php" onsubmit="return confirm('Remover este procedimento?');" style="margin:0;">
              <input type="hidden" name="acao" value="remover">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id']); ?>">
              <button type="submit" style="border:none; background:none; color:#dc2626; font-size:13px; font-weight:600; cursor:pointer; padding:8px 14px;">Remover</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>

  </div>
</section>

</body>
</html>
