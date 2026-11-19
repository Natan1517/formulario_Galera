
<?php
require_once dirname(__DIR__) . '/config.php';
auth();

$nome = trim((string)($_GET['nome'] ?? ''));
$escola = (string)($_GET['escola'] ?? '');
$serie = (string)($_GET['serie'] ?? '');
$cidade = trim((string)($_GET['cidade'] ?? ''));
$ano = (string)($_GET['ano'] ?? '');
$sem = (string)($_GET['semestre'] ?? '');

$where = [];
$par = [];

if ($nome !== '') {
    $where[] = 'a.nome LIKE ?';
    $par[] = '%' . $nome . '%';
}

if ($escola !== '') {
    $where[] = 'a.nome_escola = ?';
    $par[] = $escola;
}

if ($serie !== '') {
    $where[] = 'a.serie_escolar = ?';
    $par[] = $serie;
}

if ($cidade !== '') {
    $where[] = 'a.cidade LIKE ?';
    $par[] = '%' . $cidade . '%';
}

if ($ano !== '' && ctype_digit($ano)) {
    $where[] = 'i.ano = ?';
    $par[] = (int)$ano;
}

if (in_array($sem, ['1', '2'], true)) {
    $where[] = 'i.semestre = ?';
    $par[] = (int)$sem;
}

$sql = ' FROM inscricoes i JOIN alunos a ON a.id_aluno = i.aluno_id ';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$q = db()->prepare('SELECT COUNT(*)' . $sql);
$q->execute($par);
$totalFiltrado = (int)$q->fetchColumn();

$totalGeral = (int)db()->query(
    'SELECT COUNT(*) FROM inscricoes'
)->fetchColumn();

$totalEscolas = (int)db()->query(
    'SELECT COUNT(DISTINCT nome_escola) FROM alunos'
)->fetchColumn();

$consulta = 'SELECT
    i.id_inscricao,
    i.ano,
    i.semestre,
    i.data_inscricao,
    i.situacao,
    a.nome,
    a.cidade,
    a.serie_escolar,
    a.nome_escola'
    . $sql .
    ' ORDER BY i.data_inscricao DESC, i.id_inscricao DESC';

if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    $q = db()->prepare($consulta);
    $q->execute($par);
    $dados = $q->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="inscricoes_galera_tech.csv"');

    echo "\xEF\xBB\xBF";

    $arquivo = fopen('php://output', 'w');

    fputcsv($arquivo, [
        'Nome',
        'Escola',
        'Série',
        'Cidade',
        'Ano',
        'Semestre',
        'Data da inscrição',
        'Situação'
    ], ';');

    foreach ($dados as $r) {
        fputcsv($arquivo, [
            $r['nome'],
            $r['nome_escola'],
            $r['serie_escolar'],
            $r['cidade'],
            $r['ano'],
            $r['semestre'],
            $r['data_inscricao'],
            $r['situacao']
        ], ';');
    }

    fclose($arquivo);
    exit;
}

$q = db()->prepare($consulta . ' LIMIT 200');
$q->execute($par);
$rows = $q->fetchAll();

$escolas = db()->query(
    'SELECT DISTINCT nome_escola FROM alunos ORDER BY nome_escola'
)->fetchAll();

$series = db()->query(
    'SELECT DISTINCT serie_escolar FROM alunos ORDER BY serie_escolar'
)->fetchAll();

function option(string $v, string $t, string $current): void {
    echo '<option value="' . e($v) . '"'
        . ($v === $current ? ' selected' : '')
        . '>' . e($t) . '</option>';
}
?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Painel Administrativo | Galera Tech</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/painel.css">

    <style>
        body {
            background: #f2f6fb;
            color: #18324b;
            transition: background 0.3s, color 0.3s;
        }

        .painel-titulo {
            font-weight: 700;
        }

        .cartao-info {
            background: white;
            border: 1px solid #dce5f0;
            border-radius: 15px;
            padding: 22px;
            height: 100%;
            box-shadow: 0 4px 12px #183a7210;
            transition: 0.3s;
        }

        .cartao-info:hover {
            transform: translateY(-4px);
        }

        .cartao-info h3 {
            font-size: 15px;
            color: #64748b;
        }

        .cartao-info strong {
            display: block;
            font-size: 32px;
            color: #005a96;
        }

        .secao-painel {
            background: white;
            border: 1px solid #dce5f0;
            border-radius: 15px;
            padding: 22px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px #183a7210;
        }

        .form-control,
        .form-select {
            min-height: 44px;
            border-radius: 8px;
        }

        .btn {
            border-radius: 8px;
        }

        .tabela-painel th {
            background: #eaf3fb;
            color: #003b79;
            white-space: nowrap;
        }

        .tabela-painel td {
            vertical-align: middle;
        }

        .tabela-painel tbody tr:hover {
            background: #f1f8fd;
        }

        .botoes-tema {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .botao-tema {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            background: #ffffff;
            color: #003b79;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        #temaAutomatico {
            font-size: 13px;
            font-weight: bold;
        }

        .botao-tema.ativo {
            background: #003b79;
            color: white;
        }

        body.modo-escuro {
            background: #101b2a;
            color: #e5edf7;
        }

        body.modo-escuro .cartao-info,
        body.modo-escuro .secao-painel {
            background: #1b2b40;
            border-color: #34475e;
            color: #e5edf7;
        }

        body.modo-escuro .cartao-info h3 {
            color: #b5c4d8;
        }

        body.modo-escuro .cartao-info strong {
            color: #8bc9ed;
        }

        body.modo-escuro .form-control,
        body.modo-escuro .form-select {
            background-color: #20334a;
            color: white;
            border-color: #465a70;
        }

        body.modo-escuro .form-control::placeholder {
            color: #aab8c9;
        }

        body.modo-escuro .tabela-painel {
            color: #e5edf7;
            border-color: #34475e;
        }

        body.modo-escuro .tabela-painel th {
            background: #263d56;
            color: #d9eaff;
        }

        body.modo-escuro .tabela-painel tbody tr:hover {
            background: #263d56;
            color: white;
        }

        body.modo-escuro .text-secondary {
            color: #b5c4d8 !important;
        }

        @media (max-width: 576px) {
            .cartao-info {
                padding: 17px;
            }

            .cartao-info strong {
                font-size: 26px;
            }

            .cabecalho .container {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 18px;
            }
        }
    </style>
</head>

<body>

    <header class="cabecalho py-4">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>
                <h1 class="h3 mb-1 painel-titulo">Painel de Inscrições</h1>
                <div>Galera Tech • Administração</div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="botoes-tema">
                    <button type="button" id="botaoTema" class="botao-tema" title="Alternar tema">🌙</button>
                    <button type="button" id="temaAutomatico" class="botao-tema" title="Tema automático">AUTO</button>
                </div>

                <a class="btn btn-light" href="sair.php">Sair</a>
            </div>

        </div>
    </header>

    <main class="container py-4">

        <div class="mb-4">
            <h2 class="h4 fw-bold">Visão geral</h2>
            <p class="text-secondary">Acompanhe e organize as inscrições do Galera Tech.</p>
        </div>

        <div class="row g-3 mb-4">

            <div class="col-md-4">
                <div class="cartao-info">
                    <h3>Total de inscrições</h3>
                    <strong><?= $totalGeral ?></strong>
                    <small class="text-secondary">Inscrições cadastradas</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cartao-info">
                    <h3>Resultados encontrados</h3>
                    <strong><?= $totalFiltrado ?></strong>
                    <small class="text-secondary">De acordo com os filtros</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cartao-info">
                    <h3>Escolas cadastradas</h3>
                    <strong><?= $totalEscolas ?></strong>
                    <small class="text-secondary">Escolas diferentes</small>
                </div>
            </div>

        </div>

        <section class="secao-painel">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Pesquisar inscrições</h2>
                    <p class="text-secondary mb-0">Utilize os filtros para encontrar estudantes.</p>
                </div>
            </div>

            <form method="get" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Nome do estudante</label>
                    <input
                        class="form-control"
                        name="nome"
                        value="<?= e($nome) ?>"
                        placeholder="Digite o nome">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Escola</label>
                    <select class="form-select" name="escola">
                        <?php
                        option('', 'Todas as escolas', $escola);
                        foreach ($escolas as $v) {
                            option($v['nome_escola'], $v['nome_escola'], $escola);
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Série</label>
                    <select class="form-select" name="serie">
                        <?php
                        option('', 'Todas as séries', $serie);
                        foreach ($series as $v) {
                            option($v['serie_escolar'], $v['serie_escolar'], $serie);
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Cidade</label>
                    <input
                        class="form-control"
                        name="cidade"
                        value="<?= e($cidade) ?>"
                        placeholder="Digite a cidade">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ano</label>
                    <select name="ano" class="form-select">
                        <?php
                        option('', 'Todos os anos', $ano);
                        foreach (range(2026, 2030) as $v) {
                            option((string)$v, (string)$v, $ano);
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Semestre</label>
                    <select name="semestre" class="form-select">
                        <?php
                        option('', 'Todos os semestres', $sem);
                        option('1', '1º semestre', $sem);
                        option('2', '2º semestre', $sem);
                        ?>
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">

                    <button type="submit" class="btn btn-primary px-4">
                        Pesquisar
                    </button>

                    <a href="painel.php" class="btn btn-outline-secondary px-4">
                        Limpar filtros
                    </a>

                    <button
                        type="submit"
                        name="exportar"
                        value="csv"
                        class="btn btn-success px-4">
                        Exportar Excel
                    </button>

                </div>

            </form>

        </section>

        <section class="secao-painel">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                <div>
                    <h2 class="h5 fw-bold mb-1">Lista de estudantes</h2>
                    <p class="text-secondary mb-0">
                        <?= $totalFiltrado ?> inscrição(ões) encontrada(s).
                    </p>
                </div>

                <span class="badge text-bg-primary p-2">
                    <?= count($rows) ?> exibidas
                </span>

            </div>

            <?php if ($totalFiltrado > 200): ?>
                <div class="alert alert-info">
                    Estão sendo exibidas as 200 inscrições mais recentes. A exportação inclui todos os resultados filtrados.
                </div>
            <?php endif; ?>

            <div class="table-responsive">

                <table class="table table-striped tabela-painel align-middle">

                    <thead>
                        <tr>
                            <th>Estudante</th>
                            <th>Escola</th>
                            <th>Série</th>
                            <th>Cidade</th>
                            <th>Período</th>
                            <th>Data</th>
                            <th>Situação</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td class="fw-semibold">
                                    <?= e($r['nome']) ?>
                                </td>

                                <td>
                                    <?= e($r['nome_escola']) ?>
                                </td>

                                <td>
                                    <?= e($r['serie_escolar']) ?>
                                </td>

                                <td>
                                    <?= e($r['cidade']) ?>
                                </td>

                                <td>
                                    <?= e($r['semestre'] . 'º/' . $r['ano']) ?>
                                </td>

                                <td>
                                    <?= e(date('d/m/Y', strtotime($r['data_inscricao']))) ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= e($r['situacao']) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        class="btn btn-sm btn-outline-primary"
                                        href="detalhes.php?id=<?= (int)$r['id_inscricao'] ?>">
                                        Ver detalhes
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (!$rows): ?>

                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <strong>Nenhuma inscrição encontrada.</strong>
                                    <p class="text-secondary mb-0">Tente alterar os filtros da pesquisa.</p>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <footer class="text-center text-secondary py-3">
            Galera Tech • Painel Administrativo
        </footer>

    </main>

    <script>
        const botao = document.getElementById("botaoTema");
        const automatico = document.getElementById("temaAutomatico");
        const sistema = window.matchMedia("(prefers-color-scheme: dark)");

        let tema = localStorage.getItem("tema") || "automatico";

        function aplicarTema() {
            const escuro = tema === "automatico" ? sistema.matches : tema === "escuro";

            document.body.classList.toggle("modo-escuro", escuro);

            botao.textContent = escuro ? "☀️" : "🌙";

            botao.classList.toggle("ativo", tema !== "automatico");
            automatico.classList.toggle("ativo", tema === "automatico");
        }

        botao.addEventListener("click", function () {
            tema = document.body.classList.contains("modo-escuro") ? "claro" : "escuro";
            localStorage.setItem("tema", tema);
            aplicarTema();
        });

        automatico.addEventListener("click", function () {
            tema = "automatico";
            localStorage.removeItem("tema");
            aplicarTema();
        });

        sistema.addEventListener("change", function () {
            if (tema === "automatico") {
                aplicarTema();
            }
        });

        aplicarTema();
    </script>

</body>
</html>
