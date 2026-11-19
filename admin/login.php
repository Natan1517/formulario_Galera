
<?php
require_once dirname(__DIR__) . '/config.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: painel.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();

    $email = trim((string)($_POST['email'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');

    $q = db()->prepare('SELECT id_usuario, nome, senha_hash FROM usuarios_admin WHERE email = ?');
    $q->execute([$email]);
    $u = $q->fetch();

    if ($u && password_verify($senha, $u['senha_hash'])) {
        session_regenerate_id(true);

        $_SESSION['admin_id'] = $u['id_usuario'];
        $_SESSION['admin_nome'] = $u['nome'];

        header('Location: painel.php');
        exit;
    }

    $erro = 'E-mail ou senha inválidos.';
}
?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Acesso administrativo | Galera Tech</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="../assets/login.css">
</head>

<body>

    <main class="container py-5">

        <div class="card shadow-sm border-0">

            <div class="card-body">

                <div class="identidade">
                    <h1>Galera Tech</h1>
                    <span>ADMINISTRAÇÃO</span>
                </div>

                <p class="descricao">Acesso restrito ao painel administrativo.</p>

                <?php if ($erro): ?>
                    <div class="alert alert-danger">
                        <?= e($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="post">

                    <input type="hidden" name="csrf" value="<?= e(token()) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="email">E-mail</label>

                        <input
                            required
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="Digite seu e-mail"
                            autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="senha">Senha</label>

                        <input
                            required
                            type="password"
                            id="senha"
                            name="senha"
                            class="form-control"
                            placeholder="Digite sua senha"
                            autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Entrar no painel
                    </button>

                </form>

                <div class="rodape">
                    Galera Tech © 2026
                </div>

            </div>
        </div>

    </main>

</body>
</html>
