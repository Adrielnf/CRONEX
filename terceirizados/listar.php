<?php

$base = "../";

require_once "../includes/autenticar.php";

$conexao = require "../includes/conexao.php";

$sql = "SELECT * FROM terceirizados ORDER BY id DESC";
$resultado = mysqli_query($conexao, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronex - Terceirizados</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="app">

    <?php
    $base = "../";
    include "../includes/menu.php";
    ?>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Terceirizados</h1>
                <p>Gerencie todos os terceirizados cadastrados.</p>
            </div>

            <div class="user-box">
                <span>Administrador</span>
            </div>
        </header>

        <section class="panel">

            <?php if (isset($_GET["sucesso"])) { ?>

                <?php if ($_GET["sucesso"] == "cadastrado") { ?>
                    <div class="alert-success">Terceirizado cadastrado com sucesso!</div>
                <?php } ?>

                <?php if ($_GET["sucesso"] == "atualizado") { ?>
                    <div class="alert-success">Terceirizado atualizado com sucesso!</div>
                <?php } ?>

                <?php if ($_GET["sucesso"] == "inativado") { ?>
                    <div class="alert-success">Terceirizado inativado com sucesso!</div>
                <?php } ?>

            <?php } ?>

            <div class="panel-header">
                <h2>Lista de Terceirizados</h2>

                <a href="cadastrar.php" class="btn-primary">
                    + Novo Terceirizado
                </a>
            </div>

            <div class="table-toolbar">
                <input type="text" placeholder="Pesquisar terceirizado..." class="search-input">

                <select class="filter-select">
                    <option>Todos</option>
                    <option>Ativos</option>
                    <option>Inativos</option>
                </select>
            </div>

            <table class="cronex-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Terceirizado</th>
                        <th>Responsável</th>
                        <th>Cidade</th>
                        <th>Funcionários</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($linha = mysqli_fetch_assoc($resultado)) { ?>

                        <tr>
                            <td><?= $linha["codigo"] ?></td>
                            <td><?= $linha["razao_social"] ?></td>
                            <td><?= $linha["responsavel"] ?></td>
                            <td><?= $linha["cidade"] ?></td>
                            <td><?= $linha["funcionarios"] ?></td>

                            <td>
                                <?php if ($linha["status"] == "Ativo") { ?>
                                    <span class="status concluido">Ativo</span>
                                <?php } else { ?>
                                    <span class="status atraso">Inativo</span>
                                <?php } ?>
                            </td>

                            <td>
                                <a href="editar.php?id=<?= $linha["id"] ?>" class="btn-action edit">Editar</a>

                                <?php if ($linha["status"] == "Ativo") { ?>
                                    <a href="excluir.php?id=<?= $linha["id"] ?>" class="btn-action delete">Inativar</a>
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