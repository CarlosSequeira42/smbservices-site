<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro = '';
$next = $_GET['next'] ?? 'index.php';
if ($next === '' || strpos($next, 'login.php') !== false) {
    $next = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utilizador = trim($_POST['utilizador'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $next = $_POST['next'] ?? 'index.php';

    $configPath = __DIR__ . '/auth-secret.php';
    if (!file_exists($configPath)) {
        $erro = 'Configuração de acesso ainda não foi definida (falta o ficheiro auth-secret.php).';
    } else {
        $config = include $configPath;
        if ($utilizador !== '' && hash_equals($config['utilizador'], $utilizador) && password_verify($senha, $config['senha_hash'])) {
            session_regenerate_id(true);
            $_SESSION['autenticado'] = true;
            $_SESSION['ultima_atividade'] = time();
            header('Location: ' . ($next !== '' ? $next : 'index.php'));
            exit;
        }
        $erro = 'Utilizador ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar | Área Privada | SMB Services</title>
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
  </div>
</header>

<section class="section-dark">
  <div class="container">
    <div class="eyebrow">Acesso restrito</div>
    <h1>Área privada</h1>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:420px;">

    <?php if (isset($_GET['expirado'])): ?>
      <div class="card" style="border-color:#d97706; margin-bottom:24px;">
        <p style="margin-bottom:0;">A sessão expirou por inatividade. Entre novamente.</p>
      </div>
    <?php endif; ?>

    <?php if ($erro): ?>
      <div class="card" style="border-color:#dc2626; margin-bottom:24px;">
        <p style="margin-bottom:0;"><?php echo htmlspecialchars($erro); ?></p>
      </div>
    <?php endif; ?>

    <div class="card">
      <form method="post" action="login.php">
        <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
        <div class="form-field">
          <label for="utilizador">Utilizador</label>
          <input type="text" id="utilizador" name="utilizador" required autofocus>
        </div>
        <div class="form-field">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn btn-primary" style="border:none; cursor:pointer; width:100%;">Entrar →</button>
      </form>
    </div>

  </div>
</section>

</body>
</html>
