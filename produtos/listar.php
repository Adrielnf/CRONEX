<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

$sql = "SELECT * FROM produtos ORDER BY id DESC";
$resultado = mysqli_query($conexao, $sql);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cronex - Produtos</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="app">

<?php include "../includes/menu.php"; ?>
<body>

<div class="app">

<?php
$base = "../";
include "../includes/menu.php";
?>

<main class="main-content">

    <header class="topbar">

        <div>
            <h1>Produtos</h1>
            <p>Gerencie todos os produtos cadastrados.</p>
        </div>

        <div class="user-box">
            <span>Administrador</span>
        </div>

    </header>

    <section class="panel">

        <?php if (isset($_GET["sucesso"])) { ?>

            <?php if ($_GET["sucesso"] == "cadastrado") { ?>
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    Produto cadastrado com sucesso!
                </div>
            <?php } ?>

            <?php if ($_GET["sucesso"] == "atualizado") { ?>
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    Produto atualizado com sucesso!
                </div>
            <?php } ?>

            <?php if ($_GET["sucesso"] == "inativado") { ?>
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    Produto inativado com sucesso!
                </div>
            <?php } ?>

        <?php } ?>

        <?php if (isset($_GET["erro"])) { ?>

            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                Ocorreu um erro ao realizar a operação.
            </div>

        <?php } ?>

        <div class="panel-header">

            <h2>Lista de Produtos</h2>

            <a href="cadastrar.php" class="btn-primary">
                + Novo Produto
            </a>

        </div>

        <div class="table-toolbar">

            <input
                type="text"
                placeholder="Pesquisar produto..."
                class="search-input">

            <select class="filter-select">
                <option>Todos</option>
                <option>Lingerie</option>
                <option>Fitness</option>
                <option>Ativos</option>
                <option>Inativos</option>
            </select>

        </div>

        <table class="cronex-table">

            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Linha</th>
                    <th>Categoria</th>
                    <th>Tamanhos</th>
                    <th>Tempo Médio</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($linha = mysqli_fetch_assoc($resultado)) { ?>

                    <tr>

                        <td><?= htmlspecialchars($linha["codigo"]) ?></td>

                        <td><?= htmlspecialchars($linha["nome"]) ?></td>

                        <td><?= htmlspecialchars($linha["linha"]) ?></td>

                        <td><?= htmlspecialchars($linha["categoria"]) ?></td>

                        <td><?= nl2br(htmlspecialchars($linha["tamanhos"])) ?></td>

                        <td><?= htmlspecialchars($linha["tempo_medio"]) ?> min</td>

                        <td>

                            <?php if ($linha["status"] == "Ativo") { ?>

                                <span class="status concluido">
                                    Ativo
                                </span>

                            <?php } else { ?>

                                <span class="status atraso">
                                    Inativo
                                </span>

                            <?php } ?>

                        </td>

                        <td>

                            <a
                                href="editar.php?id=<?= $linha["id"] ?>"
                                class="btn-action edit">
                                Editar
                            </a>

                            <?php if ($linha["status"] == "Ativo") { ?>

                                <a
                                    href="excluir.php?id=<?= $linha["id"] ?>"
                                    class="btn-action delete"
                                    onclick="return confirm('Deseja realmente inativar este produto?');">
                                    Inativar
                                </a>

                            <?php } ?>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </section>

</main>

</div>

</body>

</html>