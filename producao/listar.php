<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

$sql = "
    SELECT
        producao.id,
        producao.codigo,
        producao.quantidade,
        producao.data_envio,
        producao.previsao_entrega,
        producao.data_entrega,
        producao.status,
        producao.observacoes,

        produtos.codigo AS produto_codigo,
        produtos.nome AS produto_nome,

        terceirizados.codigo AS terceirizado_codigo,
        terceirizados.razao_social AS terceirizado_nome

    FROM producao

    INNER JOIN produtos
        ON producao.produto_id = produtos.id

    INNER JOIN terceirizados
        ON producao.terceirizado_id = terceirizados.id

    ORDER BY producao.id DESC
";

$resultado = mysqli_query($conexao, $sql);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cronex - Produção</title>

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
                Acompanhe todas as ordens de produção.
            </p>

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

                    Produção cadastrada com sucesso!

                </div>

            <?php } ?>


            <?php if ($_GET["sucesso"] == "atualizado") { ?>

                <div class="alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    Produção atualizada com sucesso!

                </div>

            <?php } ?>


            <?php if ($_GET["sucesso"] == "concluido") { ?>

                <div class="alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    Produção concluída com sucesso!

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

            <h2>
                Ordens de Produção
            </h2>

            <a
                href="cadastrar.php"
                class="btn-primary">

                <i class="fa-solid fa-plus"></i>

                Nova Produção

            </a>

        </div>


        <div class="table-toolbar">

            <input
                type="text"
                id="pesquisaProducao"
                placeholder="Pesquisar produção..."
                class="search-input">

            <select
                id="filtroStatus"
                class="filter-select">

                <option value="">
                    Todos
                </option>

                <option value="Aguardando confirmação">
                    Aguardando confirmação
                </option>

                <option value="Em produção">
                    Em produção
                </option>

                <option value="Finalizada">
                    Finalizada
                </option>

                <option value="Atrasado">
                    Atrasado
                </option>

            </select>

        </div>


        <table
            class="cronex-table"
            id="tabelaProducoes">

            <thead>

                <tr>

                    <th>Código</th>

                    <th>Produto</th>

                    <th>Terceirizada</th>

                    <th>Quantidade</th>

                    <th>Envio</th>

                    <th>Previsão</th>

                    <th>Status</th>

                    <th>Ações</th>

                </tr>

            </thead>


            <tbody>

            <?php if (
                $resultado &&
                mysqli_num_rows($resultado) > 0
            ) { ?>

                <?php while (
                    $linha = mysqli_fetch_assoc($resultado)
                ) { ?>

                    <?php

                    $status = trim($linha["status"]);

                    if (
                        $status != "Concluído" &&
                        $status != "Finalizada" &&
                        !empty($linha["previsao_entrega"]) &&
                        $linha["previsao_entrega"] < date("Y-m-d")
                    ) {

                        $statusExibicao = "Atrasado";

                        $classeStatus = "atraso";

                    } elseif (
                        $status == "Concluído" ||
                        $status == "Finalizada"
                    ) {

                        $statusExibicao = "Finalizada";

                        $classeStatus = "concluido";

                    } elseif (
                        $status == "Em produção"
                    ) {

                        $statusExibicao = "Em produção";

                        $classeStatus = "andamento";

                    } elseif (
                        $status == "Aguardando confirmação"
                    ) {

                        $statusExibicao =
                            "Aguardando confirmação";

                        $classeStatus = "pendente";

                    } else {

                        $statusExibicao = $status;

                        $classeStatus = "pendente";

                    }

                    ?>


                    <tr
                        data-status="<?= htmlspecialchars($statusExibicao) ?>">


                        <td>

                            <?= htmlspecialchars(
                                $linha["codigo"]
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $linha["produto_codigo"]
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $linha["produto_nome"]
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $linha["terceirizado_codigo"]
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $linha["terceirizado_nome"]
                            ) ?>

                        </td>


                        <td>

                            <?= intval(
                                $linha["quantidade"]
                            ) ?>

                        </td>


                        <td>

                            <?php if (
                                !empty($linha["data_envio"])
                            ) { ?>

                                <?= date(
                                    "d/m/Y",
                                    strtotime(
                                        $linha["data_envio"]
                                    )
                                ) ?>

                            <?php } else { ?>

                                -

                            <?php } ?>

                        </td>


                        <td>

                            <?php if (
                                !empty(
                                    $linha["previsao_entrega"]
                                )
                            ) { ?>

                                <?= date(
                                    "d/m/Y",
                                    strtotime(
                                        $linha["previsao_entrega"]
                                    )
                                ) ?>

                            <?php } else { ?>

                                -

                            <?php } ?>

                        </td>


                        <td>

                            <span
                                class="status <?= $classeStatus ?>">

                                <?= htmlspecialchars(
                                    $statusExibicao
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <a
                                href="editar.php?id=<?= intval(
                                    $linha["id"]
                                ) ?>"
                                class="btn-action edit">

                                Editar

                            </a>

                        </td>

                    </tr>

                <?php } ?>

            <?php } else { ?>

                <tr>

                    <td
                        colspan="8"
                        style="text-align: center;">

                        Nenhuma produção cadastrada.

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </section>

</main>

</div>


<script>

const campoPesquisa =
    document.getElementById("pesquisaProducao");

const filtroStatus =
    document.getElementById("filtroStatus");

const tabela =
    document.getElementById("tabelaProducoes");


function filtrarProducoes() {

    const pesquisa =
        campoPesquisa.value
            .toLowerCase()
            .trim();

    const statusSelecionado =
        filtroStatus.value
            .toLowerCase()
            .trim();

    const linhas =
        tabela.querySelectorAll(
            "tbody tr[data-status]"
        );


    linhas.forEach(function (linha) {

        const texto =
            linha.textContent
                .toLowerCase();

        const status =
            linha.dataset.status
                .toLowerCase()
                .trim();


        const correspondePesquisa =
            pesquisa === "" ||
            texto.includes(pesquisa);


        const correspondeStatus =
            statusSelecionado === "" ||
            status === statusSelecionado;


        if (
            correspondePesquisa &&
            correspondeStatus
        ) {

            linha.style.display = "";

        } else {

            linha.style.display = "none";

        }

    });

}


campoPesquisa.addEventListener(
    "input",
    filtrarProducoes
);


filtroStatus.addEventListener(
    "change",
    filtrarProducoes
);

</script>

</body>

</html>