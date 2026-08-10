<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

/*
|--------------------------------------------------------------------------
| BUSCAR PRODUTOS ATIVOS
|--------------------------------------------------------------------------
*/

$sqlProdutos = "
    SELECT
        id,
        codigo,
        nome,
        linha,
        categoria,
        tamanhos,
        tempo_medio,
        tempo_embalagem,
        consumo_tecido
    FROM produtos
    WHERE status = 'Ativo'
    ORDER BY nome ASC
";

$resultadoProdutos = mysqli_query(
    $conexao,
    $sqlProdutos
);

/*
|--------------------------------------------------------------------------
| BUSCAR TERCEIRIZADOS ATIVOS
|--------------------------------------------------------------------------
*/

$sqlTerceirizados = "
    SELECT
        id,
        codigo,
        razao_social,
        funcionarios,
        dias_trabalho,
        jornada_minutos,
        produtividade,
        tempo_entrega
    FROM terceirizados
    WHERE status = 'Ativo'
    ORDER BY razao_social ASC
";

$resultadoTerceirizados = mysqli_query(
    $conexao,
    $sqlTerceirizados
);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cronex - Nova Produção</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../css/style.css">

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

            <h1>Produção</h1>

            <p>
                Cadastre uma nova ordem de produção.
            </p>

        </div>

        <div class="user-box">

            <span>Administrador</span>

        </div>

    </header>

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Nova Produção
                </h2>

            </div>

            <a
                href="listar.php"
                class="btn-secondary">

                <i class="fa-solid fa-arrow-left"></i>

                Voltar

            </a>

        </div>

        <!-- MENSAGEM DE ERRO -->

        <?php if (isset($_GET["erro"])) { ?>

            <div class="alert-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php if ($_GET["erro"] == "produto") { ?>

                    O produto selecionado não foi encontrado ou está inativo.

                <?php } elseif ($_GET["erro"] == "terceirizado") { ?>

                    A terceirizada selecionada não foi encontrada ou está inativa.

                <?php } elseif ($_GET["erro"] == "calculo") { ?>

                    Não foi possível calcular a previsão da produção.

                <?php } elseif ($_GET["erro"] == "banco") { ?>

                    Ocorreu um erro ao salvar a produção.

                <?php } else { ?>

                    Não foi possível cadastrar a produção.
                    Verifique os dados informados.

                <?php } ?>

            </div>

        <?php } ?>

        <!-- INFORMAÇÃO SOBRE O CÁLCULO -->

        <div class="alert-info">

            <i class="fa-solid fa-calculator"></i>

            A previsão de entrega será calculada automaticamente
            pelo CRONEX com base no produto, quantidade e capacidade
            produtiva da terceirizada.

        </div>

        <!-- FORMULÁRIO -->

        <form
            action="salvar.php"
            method="POST"
            class="form-cronex">

            <!-- PRODUTO -->

            <div class="form-group">

                <label for="produto_id">
                    Produto *
                </label>

                <select
                    name="produto_id"
                    id="produto_id"
                    required>

                    <option value="">
                        Selecione o produto
                    </option>

                    <?php while ($produto = mysqli_fetch_assoc($resultadoProdutos)) { ?>

                        <option
                            value="<?= $produto["id"] ?>">

                            <?= htmlspecialchars($produto["codigo"]) ?>

                            -

                            <?= htmlspecialchars($produto["nome"]) ?>

                        </option>

                    <?php } ?>

                </select>

            </div>

            <!-- TERCEIRIZADA -->

            <div class="form-group">

                <label for="terceirizado_id">
                    Terceirizada *
                </label>

                <select
                    name="terceirizado_id"
                    id="terceirizado_id"
                    required>

                    <option value="">
                        Selecione a terceirizada
                    </option>

                    <?php while ($terceirizado = mysqli_fetch_assoc($resultadoTerceirizados)) { ?>

                        <option
                            value="<?= $terceirizado["id"] ?>">

                            <?= htmlspecialchars($terceirizado["codigo"]) ?>

                            -

                            <?= htmlspecialchars($terceirizado["razao_social"]) ?>

                        </option>

                    <?php } ?>

                </select>

            </div>

            <!-- QUANTIDADE -->

            <div class="form-group">

                <label for="quantidade">
                    Quantidade de Peças *
                </label>

                <input
                    type="number"
                    name="quantidade"
                    id="quantidade"
                    min="1"
                    placeholder="Ex: 500"
                    required>

            </div>

            <!-- DATA DE ENVIO -->

            <div class="form-group">

                <label for="data_envio">
                    Data de Envio *
                </label>

                <input
                    type="date"
                    name="data_envio"
                    id="data_envio"
                    value="<?= date("Y-m-d") ?>"
                    required>

            </div>

            <!-- PREVISÃO AUTOMÁTICA -->

            <div class="form-group">

                <label>
                    Previsão de Entrega
                </label>

                <input
                    type="text"
                    value="Calculada automaticamente pelo CRONEX"
                    disabled>

                <small>
                    O prazo será calculado após o cadastro da produção.
                </small>

            </div>

            <!-- STATUS -->

            <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    name="status"
                    id="status">

                    <option
                        value="Em produção"
                        selected>

                        Em produção

                    </option>

                    <option value="Aguardando">

                        Aguardando

                    </option>

                </select>

            </div>

            <!-- OBSERVAÇÕES -->

            <div class="form-group">

                <label for="observacoes">
                    Observações
                </label>

                <textarea
                    name="observacoes"
                    id="observacoes"
                    rows="4"
                    placeholder="Informações adicionais sobre esta produção..."></textarea>

            </div>

            <!-- BOTÕES -->

            <div class="form-actions">

                <a
                    href="listar.php"
                    class="btn-secondary">

                    Cancelar

                </a>

                <button
                    type="submit"
                    class="btn-primary">

                    <i class="fa-solid fa-floppy-disk"></i>

                    Cadastrar Produção

                </button>

            </div>

        </form>

    </section>

</main>

</div>

</body>

</html>