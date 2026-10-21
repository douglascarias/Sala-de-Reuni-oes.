<?php
require_once __DIR__ . '/app/auth.php';

if (usuario_logado()) {
    redirect(base_url('index.php'));
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar($_POST['csrf'] ?? null);
    $erro = tentar_login($_POST['email'] ?? '', $_POST['senha'] ?? '');
    if ($erro === null) {
        redirect(base_url('index.php'));
    }
}
$titulo = 'Entrar';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar · <?= e(APP_NOME) ?></title>
<link rel="stylesheet" href="<?= e(base_url('assets/style.css')) ?>">
</head>
<body class="tela-login">
  <form class="card-login" method="post" autocomplete="on">
    <div class="login-marca">
      <span class="brand-mark grande">C</span>
      <h1>Condex</h1>
      <p>Reserva de Salas de Reunião</p>
    </div>

    <?php if ($erro): ?>
      <div class="alert alert-erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <label>E-mail
      <input type="email" name="email" required autofocus
             value="<?= e($_POST['email'] ?? '') ?>" placeholder="seu.nome@condex.com.br">
    </label>

    <label>Senha
      <input type="password" name="senha" required placeholder="••••••••">
    </label>

    <?= csrf_input() ?>
    <button class="btn btn-primario btn-bloco" type="submit">Entrar</button>

    <p class="login-rodape">Não tem acesso? Solicite ao administrador do sistema.</p>
  </form>
</body>
</html>
