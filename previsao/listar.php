<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";
require_once "../includes/funcoes.php";

$sql = "
    SELECT
        pr.id,
        pr.codigo,
        pr.quantidade,
        pr.data_envio,
        pr.previsao_entrega,
        pr.confirmacao_prazo,
        pr.motivo_atraso,
        pr.nova_previsao,
        pr.data_confirmacao_prazo,
        pr.status,

        p.codigo AS produto_codigo,
        p.nome AS produto_nome,

        t.codigo AS terceirizado_codigo,
        t.razao_social AS terceirizado_nome,

        COALESCE(pc.quantidade_liberada, 0) AS quantidade_liberada,
        COALESCE(pc.quantidade_coletada, 0) AS quantidade_coletada,
        COALESCE(pc.quantidade_aguardando, 0) AS quantidade_aguardando,

        pc.ultima_liberacao,
        pc.ultima_coleta

    FROM producao pr

    INNER JOIN produtos p
        ON p.id = pr.produto_id

    INNER JOIN terceirizados t
        ON t.id = pr.terceirizado_id

    LEFT JOIN (
        SELECT
            producao_id,

            SUM(quantidade) AS quantidade_liberada,

            SUM(
                CASE
                    WHEN status = 'Coletado'
                    THEN quantidade
                    ELSE 0
                END
            ) AS quantidade_coletada,

            SUM(
                CASE
                    WHEN status <> 'Coletado'
                    THEN quantidade
                    ELSE 0
                END
            ) AS quantidade_aguardando,

            MAX(data_liberacao) AS ultima_liberacao,

            MAX(
                CASE
                    WHEN status = 'Coletado'
                    THEN data_coleta
                    ELSE NULL
                END
            ) AS ultima_coleta

        FROM producao_coletas

        GROUP BY producao_id

    ) pc
        ON pc.producao_id = pr.id

    ORDER BY
        CASE
            WHEN COALESCE(
                pc.quantidade_coletada,
                0
            ) >= pr.quantidade
            THEN 4

            WHEN COALESCE(
                pc.quantidade_aguardando,
                0
            ) > 0
            THEN 1

            WHEN pr.confirmacao_prazo = 'Atraso'
            THEN 2

            ELSE 3
        END ASC,

        COALESCE(
            pr.nova_previsao,
            pr.previsao_entrega
        ) ASC,

        pr.id DESC
";

$resultado = mysqli_query(
    $conexao,
    $sql
);

if (!$resultado) {
    die("Erro ao carregar previsões: "
        . mysqli_error($conexao));
}

$hoje = date("Y-m-d");

$totalProducoes = 0;
$aguardandoConfirmacao = 0;
$atrasosInformados = 0;
$prontasColeta = 0;
$coletadas = 0;

$producoes = [];

while ($linha = mysqli_fetch_assoc($resultado)) {

    $linha["quantidade"] =
        intval($linha["quantidade"]);

    $linha["quantidade_liberada"] =
        intval($linha["quantidade_liberada"]);

    $linha["quantidade_coletada"] =
        intval($linha["quantidade_coletada"]);

    $linha["quantidade_aguardando"] =
        intval($linha["quantidade_aguardando"]);

    $linha["quantidade_restante"] =
        max(
            0,
            $linha["quantidade"]
                -
                $linha["quantidade_liberada"]
        );

    $linha["finalizada"] =
        (
            $linha["quantidade"] > 0 &&
            $linha["quantidade_coletada"]
            >= $linha["quantidade"]
        );

    $linha["tem_coleta_pendente"] =
        (
            $linha["quantidade_aguardando"] > 0
        );

    $totalProducoes++;

    if (
        empty($linha["confirmacao_prazo"]) &&
        !$linha["finalizada"]
    ) {
        $aguardandoConfirmacao++;
    }

    if (
        $linha["confirmacao_prazo"] === "Atraso" &&
        !$linha["finalizada"]
    ) {
        $atrasosInformados++;
    }

    if ($linha["tem_coleta_pendente"]) {
        $prontasColeta++;
    }

    if ($linha["finalizada"]) {
        $coletadas++;
    }

    $producoes[] = $linha;
}

function formatarDataCronex($data)
{
    if (
        empty($data) ||
        $data === "0000-00-00"
    ) {
        return "-";
    }

    return date(
        "d/m/Y",
        strtotime($data)
    );
}

function formatarDataHoraCronex($data)
{
    if (
        empty($data) ||
        $data === "0000-00-00 00:00:00"
    ) {
        return "-";
    }

    return date(
        "d/m/Y H:i",
        strtotime($data)
    );
}

function obterStatusVisualProducao($linha)
{
    if ($linha["finalizada"]) {
        return "Finalizada";
    }

    if ($linha["tem_coleta_pendente"]) {
        return "Aguardando coleta";
    }

    if (empty($linha["confirmacao_prazo"])) {
        return "Aguardando confirmação";
    }

    return "Em produção";
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Cronex - Previsão e Coleta
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../css/style.css?v=<?= time() ?>">

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

                    <h1>
                        Previsão e Coleta
                    </h1>

                    <p>
                        Acompanhe os prazos das terceirizadas e o andamento das coletas.
                    </p>

                </div>

                <div class="user-box">

                    <span>
                        Administrador
                    </span>

                </div>

            </header>


            <section class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Acompanhamento de Produção
                        </h2>

                        <p>
                            Previsões, confirmações e coletas das ordens de produção.
                        </p>

                    </div>

                </div>


                <div class="stats-grid">

                    <div class="stat-card">

                        <div class="stat-icon">
                            <i class="fa-solid fa-industry"></i>
                        </div>

                        <div class="stat-info">

                            <span>
                                Total de Produções
                            </span>

                            <strong>
                                <?= $totalProducoes ?>
                            </strong>

                            <small>
                                Ordens cadastradas
                            </small>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">
                            <i class="fa-solid fa-clock"></i>
                        </div>

                        <div class="stat-info">

                            <span>
                                Aguardando Confirmação
                            </span>

                            <strong>
                                <?= $aguardandoConfirmacao ?>
                            </strong>

                            <small>
                                Aguardando terceirizada
                            </small>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div class="stat-info">

                            <span>
                                Atrasos Informados
                            </span>

                            <strong>
                                <?= $atrasosInformados ?>
                            </strong>

                            <small>
                                Prazos alterados
                            </small>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">
                            <i class="fa-solid fa-box-open"></i>
                        </div>

                        <div class="stat-info">

                            <span>
                                Prontas para Coleta
                            </span>

                            <strong>
                                <?= $prontasColeta ?>
                            </strong>

                            <small>
                                Aguardando retirada
                            </small>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">
                            <i class="fa-solid fa-truck"></i>
                        </div>

                        <div class="stat-info">

                            <span>
                                Produções Finalizadas
                            </span>

                            <strong>
                                <?= $coletadas ?>
                            </strong>

                            <small>
                                Coletas concluídas
                            </small>

                        </div>

                    </div>

                    <div class="table-toolbar">

                        <input
                            type="text"
                            id="pesquisaPrevisao"
                            placeholder="Pesquisar produção, produto ou terceirizada..."
                            class="search-input">

                        <select
                            id="filtroSituacao"
                            class="filter-select">

                            <option value="">
                                Todas as situações
                            </option>

                            <option value="aguardando">
                                Aguardando confirmação
                            </option>

                            <option value="prazo">
                                Dentro do prazo
                            </option>

                            <option value="atraso">
                                Atraso informado
                            </option>

                            <option value="pronta">
                                Pronta para coleta
                            </option>

                            <option value="coletado">
                                Coletado
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table
                            class="cronex-table previsoes-table"
                            id="tabelaPrevisoes">

                            <thead>

                                <tr>

                                    <th>
                                        Produção
                                    </th>

                                    <th>
                                        Produto
                                    </th>

                                    <th>
                                        Terceirizada
                                    </th>

                                    <th>
                                        Qtd.
                                    </th>

                                    <th>
                                        Envio
                                    </th>

                                    <th>
                                        Previsão Original
                                    </th>

                                    <th>
                                        Previsão Atual
                                    </th>

                                    <th>
                                        Prazo
                                    </th>

                                    <th>
                                        Peças
                                    </th>

                                    <th>
                                        Coleta
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (count($producoes) > 0) { ?>

                                    <?php foreach ($producoes as $linha) { ?>

                                        <?php

                                        $previsaoAtual =
                                            !empty($linha["nova_previsao"])
                                            ? $linha["nova_previsao"]
                                            : $linha["previsao_entrega"];

                                        $situacaoFiltro = "aguardando";

                                        if ($linha["finalizada"]) {

                                            $situacaoFiltro = "coletado";
                                        } elseif ($linha["tem_coleta_pendente"]) {

                                            $situacaoFiltro = "pronta";
                                        } elseif (
                                            $linha["confirmacao_prazo"]
                                            === "Atraso"
                                        ) {

                                            $situacaoFiltro = "atraso";
                                        } elseif (
                                            $linha["confirmacao_prazo"]
                                            === "No prazo"
                                        ) {

                                            $situacaoFiltro = "prazo";
                                        }

                                        $statusVisual =
                                            obterStatusVisualProducao(
                                                $linha
                                            );

                                        ?>


                                        <tr
                                            data-situacao="<?= htmlspecialchars(
                                                                $situacaoFiltro
                                                            ) ?>">

                                            <td class="col-producao">

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $linha["codigo"]
                                                    ) ?>
                                                </strong>

                                                <br>

                                                <small>

                                                    <?php if (
                                                        $statusVisual === "Finalizada"
                                                    ) { ?>

                                                        <i class="fa-solid fa-circle-check"></i>

                                                    <?php } elseif (
                                                        $statusVisual === "Aguardando coleta"
                                                    ) { ?>

                                                        <i class="fa-solid fa-truck"></i>

                                                    <?php } elseif (
                                                        $statusVisual === "Aguardando confirmação"
                                                    ) { ?>

                                                        <i class="fa-solid fa-clock"></i>

                                                    <?php } else { ?>

                                                        <i class="fa-solid fa-shirt"></i>

                                                    <?php } ?>

                                                    <?= htmlspecialchars(
                                                        $statusVisual
                                                    ) ?>

                                                </small>

                                            </td>


                                            <td class="col-produto">

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $linha["produto_nome"]
                                                    ) ?>
                                                </strong>

                                                <br>

                                                <small>
                                                    <?= htmlspecialchars(
                                                        $linha["produto_codigo"]
                                                    ) ?>
                                                </small>

                                            </td>


                                            <td class="col-terceirizada">

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $linha["terceirizado_nome"]
                                                    ) ?>
                                                </strong>

                                                <br>

                                                <small>
                                                    <?= htmlspecialchars(
                                                        $linha["terceirizado_codigo"]
                                                    ) ?>
                                                </small>

                                            </td>


                                            <td class="col-quantidade">

                                                <span class="mobile-info-label">
                                                    Quantidade
                                                </span>

                                                <span class="mobile-info-value">

                                                    <?= number_format(
                                                        $linha["quantidade"],
                                                        0,
                                                        ",",
                                                        "."
                                                    ) ?>

                                                    peças

                                                </span>

                                            </td>


                                            <td class="col-envio">

                                                <span class="mobile-info-label">
                                                    Data de envio
                                                </span>

                                                <span class="mobile-info-value">

                                                    <?= formatarDataCronex(
                                                        $linha["data_envio"]
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td class="col-previsao-original">

                                                <span class="mobile-info-label">
                                                    Previsão original
                                                </span>

                                                <span class="mobile-info-value">

                                                    <?= formatarDataCronex(
                                                        $linha["previsao_entrega"]
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td class="col-previsao-atual">

                                                <span class="mobile-info-label">
                                                    Previsão atual
                                                </span>

                                                <span class="mobile-info-value">

                                                    <?php if (
                                                        !empty($linha["nova_previsao"])
                                                    ) { ?>

                                                        <strong>

                                                            <?= formatarDataCronex(
                                                                $linha["nova_previsao"]
                                                            ) ?>

                                                        </strong>

                                                        <br>

                                                        <small>
                                                            Alterada pela terceirizada
                                                        </small>

                                                    <?php } else { ?>

                                                        <?= formatarDataCronex(
                                                            $linha["previsao_entrega"]
                                                        ) ?>

                                                    <?php } ?>

                                                </span>

                                            </td>


                                            <td class="col-prazo">

                                                <?php if (
                                                    $linha["confirmacao_prazo"]
                                                    === "No prazo"
                                                ) { ?>

                                                    <span class="status concluido">
                                                        No prazo
                                                    </span>

                                                <?php } elseif (
                                                    $linha["confirmacao_prazo"]
                                                    === "Atraso"
                                                ) { ?>

                                                    <span class="status atraso">
                                                        Atraso informado
                                                    </span>

                                                    <?php if (
                                                        !empty($linha["motivo_atraso"])
                                                    ) { ?>

                                                        <br>

                                                        <small
                                                            title="<?= htmlspecialchars(
                                                                        $linha["motivo_atraso"]
                                                                    ) ?>">

                                                            <?= htmlspecialchars(
                                                                $linha["motivo_atraso"]
                                                            ) ?>

                                                        </small>

                                                    <?php } ?>

                                                <?php } else { ?>

                                                    <?php if (
                                                        !empty($previsaoAtual) &&
                                                        $previsaoAtual < $hoje
                                                    ) { ?>

                                                        <span class="status atraso">
                                                            Sem confirmação
                                                        </span>

                                                    <?php } else { ?>

                                                        <span class="status pendente">
                                                            Aguardando
                                                        </span>

                                                    <?php } ?>

                                                <?php } ?>

                                            </td>


                                            <td class="col-pecas">

                                                <span class="mobile-info-label">
                                                    Peças
                                                </span>

                                                <?php if (
                                                    $linha["finalizada"]
                                                ) { ?>

                                                    <span class="status concluido">
                                                        100% concluídas
                                                    </span>

                                                    <br>

                                                    <small>

                                                        <?= number_format(
                                                            $linha["quantidade"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        de

                                                        <?= number_format(
                                                            $linha["quantidade"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        peças

                                                    </small>

                                                <?php } elseif (
                                                    $linha["quantidade_liberada"] > 0
                                                ) { ?>

                                                    <span class="status pendente">

                                                        <?= number_format(
                                                            $linha["quantidade_liberada"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        liberadas

                                                    </span>

                                                    <br>

                                                    <small>

                                                        <?= number_format(
                                                            $linha["quantidade_restante"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        ainda em produção

                                                    </small>

                                                <?php } else { ?>

                                                    <span class="status pendente">
                                                        Em produção
                                                    </span>

                                                    <br>

                                                    <small>

                                                        <?= number_format(
                                                            $linha["quantidade"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        peças restantes

                                                    </small>

                                                <?php } ?>

                                            </td>


                                            <td class="col-coleta">

                                                <span class="mobile-info-label">
                                                    Coleta
                                                </span>

                                                <?php if (
                                                    $linha["finalizada"]
                                                ) { ?>

                                                    <span class="status concluido">
                                                        Coletado
                                                    </span>

                                                    <br>

                                                    <small>

                                                        <?= number_format(
                                                            $linha["quantidade_coletada"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        de

                                                        <?= number_format(
                                                            $linha["quantidade"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        peças

                                                    </small>

                                                    <?php if (
                                                        !empty($linha["ultima_coleta"])
                                                    ) { ?>

                                                        <br>

                                                        <small>

                                                            <?= formatarDataHoraCronex(
                                                                $linha["ultima_coleta"]
                                                            ) ?>

                                                        </small>

                                                    <?php } ?>

                                                <?php } elseif (
                                                    $linha["quantidade_aguardando"] > 0
                                                ) { ?>

                                                    <span class="status pendente">
                                                        Aguardando coleta
                                                    </span>

                                                    <br>

                                                    <small>

                                                        <?= number_format(
                                                            $linha["quantidade_aguardando"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        peças disponíveis

                                                    </small>

                                                    <?php if (
                                                        $linha["quantidade_coletada"] > 0
                                                    ) { ?>

                                                        <br>

                                                        <small>

                                                            <?= number_format(
                                                                $linha["quantidade_coletada"],
                                                                0,
                                                                ",",
                                                                "."
                                                            ) ?>

                                                            já coletadas

                                                        </small>

                                                    <?php } ?>

                                                <?php } elseif (
                                                    $linha["quantidade_coletada"] > 0
                                                ) { ?>

                                                    <span class="status pendente">
                                                        Coleta parcial
                                                    </span>

                                                    <br>

                                                    <small>

                                                        <?= number_format(
                                                            $linha["quantidade_coletada"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        de

                                                        <?= number_format(
                                                            $linha["quantidade"],
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        coletadas

                                                    </small>

                                                <?php } else { ?>

                                                    <span class="status pendente">
                                                        Pendente
                                                    </span>

                                                <?php } ?>

                                            </td>

                                        </tr>

                                    <?php } ?>

                                <?php } else { ?>

                                    <tr>

                                        <td
                                            colspan="10"
                                            style="text-align: center;">

                                            Nenhuma produção encontrada.

                                        </td>

                                    </tr>

                                <?php } ?>

                            </tbody>

                        </table>

                    </div>

            </section>

        </main>

    </div>


    <script>
        const pesquisaPrevisao =
            document.getElementById(
                "pesquisaPrevisao"
            );


        const filtroSituacao =
            document.getElementById(
                "filtroSituacao"
            );


        const linhasPrevisao =
            document.querySelectorAll(
                "#tabelaPrevisoes tbody tr[data-situacao]"
            );


        function filtrarPrevisoes() {

            const pesquisa =
                pesquisaPrevisao.value
                .toLowerCase()
                .trim();


            const situacao =
                filtroSituacao.value;


            linhasPrevisao.forEach(
                function(linha) {

                    const texto =
                        linha.innerText
                        .toLowerCase();


                    const situacaoLinha =
                        linha.dataset.situacao;


                    const correspondePesquisa =
                        pesquisa === "" ||
                        texto.includes(
                            pesquisa
                        );


                    const correspondeSituacao =
                        situacao === "" ||
                        situacaoLinha === situacao;


                    if (
                        correspondePesquisa &&
                        correspondeSituacao
                    ) {

                        linha.style.display = "";

                    } else {

                        linha.style.display = "none";

                    }

                }
            );

        }


        pesquisaPrevisao.addEventListener(
            "input",
            filtrarPrevisoes
        );


        filtroSituacao.addEventListener(
            "change",
            filtrarPrevisoes
        );
    </script>

    <script src="../js/script.js"></script>


</body>

</html>