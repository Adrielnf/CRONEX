<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

$dataInicial = isset($_GET["data_inicial"])
    ? trim($_GET["data_inicial"])
    : "";

$dataFinal = isset($_GET["data_final"])
    ? trim($_GET["data_final"])
    : "";

$terceirizadoFiltro = isset($_GET["terceirizado"])
    ? intval($_GET["terceirizado"])
    : 0;

$situacaoFiltro = isset($_GET["situacao"])
    ? trim($_GET["situacao"])
    : "";

$sqlTerceirizados = "
    SELECT
        id,
        codigo,
        razao_social
    FROM terceirizados
    ORDER BY razao_social ASC
";

$resultadoTerceirizados = mysqli_query(
    $conexao,
    $sqlTerceirizados
);

$terceirizados = [];

if ($resultadoTerceirizados) {

    while (
        $terceirizado = mysqli_fetch_assoc(
            $resultadoTerceirizados
        )
    ) {

        $terceirizados[] = $terceirizado;
    }
}

$condicoes = [];
$tipos = "";
$parametros = [];

if ($dataInicial !== "") {

    $condicoes[] = "pr.data_envio >= ?";

    $tipos .= "s";

    $parametros[] = $dataInicial;
}

if ($dataFinal !== "") {

    $condicoes[] = "pr.data_envio <= ?";

    $tipos .= "s";

    $parametros[] = $dataFinal;
}

if ($terceirizadoFiltro > 0) {

    $condicoes[] = "pr.terceirizado_id = ?";

    $tipos .= "i";

    $parametros[] = $terceirizadoFiltro;
}

if ($situacaoFiltro === "aguardando") {

    $condicoes[] = "
        (
            pr.confirmacao_prazo IS NULL
            OR pr.confirmacao_prazo = ''
        )
        AND COALESCE(pc.total_coletado, 0) < pr.quantidade
    ";
} elseif ($situacaoFiltro === "producao") {

    $condicoes[] = "
        pr.confirmacao_prazo = 'No prazo'
        AND COALESCE(pc.total_coletado, 0) < pr.quantidade
        AND COALESCE(pc.lotes_pendentes, 0) = 0
    ";
} elseif ($situacaoFiltro === "atraso") {

    $condicoes[] = "
        pr.confirmacao_prazo = 'Atraso'
        AND COALESCE(pc.total_coletado, 0) < pr.quantidade
    ";
} elseif ($situacaoFiltro === "coleta") {

    $condicoes[] = "
        COALESCE(pc.lotes_pendentes, 0) > 0
    ";
} elseif ($situacaoFiltro === "finalizada") {

    $condicoes[] = "
        COALESCE(pc.total_coletado, 0) >= pr.quantidade
    ";
}

$where = "";

if (count($condicoes) > 0) {

    $where = "
        WHERE
        " . implode(
        " AND ",
        $condicoes
    );
}

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
        pr.status,

        p.codigo AS produto_codigo,
        p.nome AS produto_nome,

        t.codigo AS terceirizado_codigo,
        t.razao_social AS terceirizado_nome,

        COALESCE(
            pc.total_liberado,
            0
        ) AS total_liberado,

        COALESCE(
            pc.total_coletado,
            0
        ) AS total_coletado,

        COALESCE(
            pc.lotes_pendentes,
            0
        ) AS lotes_pendentes,

        COALESCE(
            pc.quantidade_pendente,
            0
        ) AS quantidade_pendente,

        COALESCE(
            pc.total_coletas,
            0
        ) AS total_coletas,

        pc.ultima_coleta

    FROM producao pr

    INNER JOIN produtos p
        ON p.id = pr.produto_id

    INNER JOIN terceirizados t
        ON t.id = pr.terceirizado_id

    LEFT JOIN (

        SELECT

            producao_id,

            SUM(quantidade)
                AS total_liberado,

            SUM(
                CASE
                    WHEN status = 'Coletado'
                    THEN quantidade
                    ELSE 0
                END
            ) AS total_coletado,

            SUM(
                CASE
                    WHEN status = 'Aguardando'
                    THEN 1
                    ELSE 0
                END
            ) AS lotes_pendentes,

            SUM(
                CASE
                    WHEN status = 'Aguardando'
                    THEN quantidade
                    ELSE 0
                END
            ) AS quantidade_pendente,

            SUM(
                CASE
                    WHEN status = 'Coletado'
                    THEN 1
                    ELSE 0
                END
            ) AS total_coletas,

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

    $where

    ORDER BY
        pr.data_envio DESC,
        pr.id DESC
";

$stmt = mysqli_prepare(
    $conexao,
    $sql
);

if (!$stmt) {

    die("Erro ao preparar relatório: "
        . mysqli_error($conexao));
}

if (count($parametros) > 0) {

    $referencias = [];

    $referencias[] = &$tipos;

    foreach (
        $parametros as $chave => $valor
    ) {

        $referencias[] =
            &$parametros[$chave];
    }

    call_user_func_array(
        "mysqli_stmt_bind_param",
        $referencias
    );
}

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result(
    $stmt
);

$totalProducoes = 0;
$totalPecas = 0;
$totalFinalizadas = 0;
$totalAtrasos = 0;
$totalColetas = 0;

$producoes = [];

while (
    $linha = mysqli_fetch_assoc(
        $resultado
    )
) {

    $totalProducoes++;

    $quantidadeTotal =
        intval($linha["quantidade"]);

    $quantidadeColetada =
        intval($linha["total_coletado"]);

    $totalPecas += $quantidadeTotal;

    $totalColetas += intval(
        $linha["total_coletas"]
    );

    if (
        $quantidadeTotal > 0
        &&
        $quantidadeColetada >= $quantidadeTotal
    ) {

        $totalFinalizadas++;
    }

    if (
        $linha["confirmacao_prazo"]
        === "Atraso"
    ) {

        $totalAtrasos++;
    }

    $producoes[] = $linha;
}

mysqli_stmt_close($stmt);

$desempenhoTerceirizadas = [];

foreach ($producoes as $producao) {

    $codigoTerceirizado =
        $producao["terceirizado_codigo"];

    if (
        !isset(
            $desempenhoTerceirizadas[$codigoTerceirizado]
        )
    ) {

        $desempenhoTerceirizadas[$codigoTerceirizado] = [

            "codigo" =>
            $producao["terceirizado_codigo"],

            "nome" =>
            $producao["terceirizado_nome"],

            "producoes" => 0,

            "pecas" => 0,

            "pecas_coletadas" => 0,

            "finalizadas" => 0,

            "atrasos" => 0,

            "no_prazo" => 0,

            "coletas" => 0

        ];
    }

    $desempenhoTerceirizadas[$codigoTerceirizado]["producoes"]++;

    $desempenhoTerceirizadas[$codigoTerceirizado]["pecas"] += intval(
        $producao["quantidade"]
    );

    $desempenhoTerceirizadas[$codigoTerceirizado]["pecas_coletadas"] += intval(
        $producao["total_coletado"]
    );

    $desempenhoTerceirizadas[$codigoTerceirizado]["coletas"] += intval(
        $producao["total_coletas"]
    );

    if (
        intval($producao["quantidade"]) > 0
        &&
        intval($producao["total_coletado"])
        >= intval($producao["quantidade"])
    ) {

        $desempenhoTerceirizadas[$codigoTerceirizado]["finalizadas"]++;
    }

    if (
        $producao["confirmacao_prazo"]
        === "Atraso"
    ) {

        $desempenhoTerceirizadas[$codigoTerceirizado]["atrasos"]++;
    }

    if (
        $producao["confirmacao_prazo"]
        === "No prazo"
    ) {

        $desempenhoTerceirizadas[$codigoTerceirizado]["no_prazo"]++;
    }
}

$desempenhoTerceirizadas =
    array_values(
        $desempenhoTerceirizadas
    );

usort(
    $desempenhoTerceirizadas,
    function ($a, $b) {

        return
            $b["producoes"]
            <=>
            $a["producoes"];
    }
);

function formatarDataRelatorio($data)
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

function formatarDataHoraRelatorio($data)
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

function obterSituacaoRelatorio($linha)
{
    $quantidadeTotal =
        intval($linha["quantidade"]);

    $quantidadeColetada =
        intval($linha["total_coletado"]);

    $lotesPendentes =
        intval($linha["lotes_pendentes"]);

    if (
        $quantidadeTotal > 0
        &&
        $quantidadeColetada >= $quantidadeTotal
    ) {

        return [
            "texto" => "Finalizada",
            "classe" => "concluido",
            "icone" => "fa-circle-check"
        ];
    }

    if ($lotesPendentes > 0) {

        return [
            "texto" => "Aguardando coleta",
            "classe" => "andamento",
            "icone" => "fa-truck"
        ];
    }

    if (
        empty($linha["confirmacao_prazo"])
    ) {

        return [
            "texto" => "Aguardando confirmação",
            "classe" => "andamento",
            "icone" => "fa-clock"
        ];
    }

    if (
        $linha["confirmacao_prazo"]
        === "Atraso"
    ) {

        return [
            "texto" => "Atraso informado",
            "classe" => "atraso",
            "icone" => "fa-triangle-exclamation"
        ];
    }

    return [
        "texto" => "Em produção",
        "classe" => "andamento",
        "icone" => "fa-shirt"
    ];
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
        Cronex - Relatórios
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
                        Relatórios
                    </h1>

                    <p>
                        Acompanhe os resultados da produção terceirizada.
                    </p>

                </div>

                <div class="user-box">

                    <span>
                        Administrador
                    </span>

                </div>

            </header>

            <section class="panel relatorio-filtros">

                <div class="panel-header">

                    <div>

                        <h2>
                            Filtros do Relatório
                        </h2>

                        <p>
                            Selecione os dados que deseja analisar.
                        </p>

                    </div>

                </div>

                <form
                    method="GET"
                    action="listar.php"
                    class="relatorio-form">

                    <div class="form-group">

                        <label for="data_inicial">
                            Data inicial
                        </label>

                        <input
                            type="date"
                            id="data_inicial"
                            name="data_inicial"
                            value="<?= htmlspecialchars(
                                        $dataInicial
                                    ) ?>">

                    </div>

                    <div class="form-group">

                        <label for="data_final">
                            Data final
                        </label>

                        <input
                            type="date"
                            id="data_final"
                            name="data_final"
                            value="<?= htmlspecialchars(
                                        $dataFinal
                                    ) ?>">

                    </div>

                    <div class="form-group">

                        <label for="terceirizado">
                            Terceirizada
                        </label>

                        <select
                            id="terceirizado"
                            name="terceirizado">

                            <option value="0">
                                Todas
                            </option>

                            <?php foreach (
                                $terceirizados
                                as $terceirizado
                            ) { ?>

                                <option
                                    value="<?= intval(
                                                $terceirizado["id"]
                                            ) ?>"

                                    <?= (
                                        $terceirizadoFiltro
                                        === intval(
                                            $terceirizado["id"]
                                        )
                                    )
                                        ? "selected"
                                        : ""
                                    ?>>

                                    <?= htmlspecialchars(
                                        $terceirizado["razao_social"]
                                    ) ?>

                                </option>

                            <?php } ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="situacao">
                            Situação
                        </label>

                        <select
                            id="situacao"
                            name="situacao">

                            <option
                                value=""
                                <?= $situacaoFiltro === ""
                                    ? "selected"
                                    : ""
                                ?>>

                                Todas

                            </option>

                            <option
                                value="aguardando"
                                <?= $situacaoFiltro === "aguardando"
                                    ? "selected"
                                    : ""
                                ?>>

                                Aguardando confirmação

                            </option>

                            <option
                                value="producao"
                                <?= $situacaoFiltro === "producao"
                                    ? "selected"
                                    : ""
                                ?>>

                                Em produção

                            </option>

                            <option
                                value="atraso"
                                <?= $situacaoFiltro === "atraso"
                                    ? "selected"
                                    : ""
                                ?>>

                                Atraso informado

                            </option>

                            <option
                                value="coleta"
                                <?= $situacaoFiltro === "coleta"
                                    ? "selected"
                                    : ""
                                ?>>

                                Aguardando coleta

                            </option>

                            <option
                                value="finalizada"
                                <?= $situacaoFiltro === "finalizada"
                                    ? "selected"
                                    : ""
                                ?>>

                                Finalizada

                            </option>

                        </select>

                    </div>

                    <div class="relatorio-form-acoes">

                        <button
                            type="submit"
                            class="btn-primary">

                            <i class="fa-solid fa-filter"></i>

                            Filtrar

                        </button>

                        <a
                            href="listar.php"
                            class="btn-secondary">

                            <i class="fa-solid fa-rotate-left"></i>

                            Limpar

                        </a>

                    </div>

                </form>

            </section>

            <section class="relatorio-cards">

                <div class="relatorio-card">

                    <div class="relatorio-card-icon">

                        <i class="fa-solid fa-industry"></i>

                    </div>

                    <div>

                        <span>
                            Produções
                        </span>

                        <strong>
                            <?= $totalProducoes ?>
                        </strong>

                        <small>
                            Ordens no resultado
                        </small>

                    </div>

                </div>

                <div class="relatorio-card">

                    <div class="relatorio-card-icon">

                        <i class="fa-solid fa-shirt"></i>

                    </div>

                    <div>

                        <span>
                            Peças
                        </span>

                        <strong>

                            <?= number_format(
                                $totalPecas,
                                0,
                                ",",
                                "."
                            ) ?>

                        </strong>

                        <small>
                            Peças programadas
                        </small>

                    </div>

                </div>

                <div class="relatorio-card">

                    <div class="relatorio-card-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div>

                        <span>
                            Finalizadas
                        </span>

                        <strong>
                            <?= $totalFinalizadas ?>
                        </strong>

                        <small>
                            Produções concluídas
                        </small>

                    </div>

                </div>

                <div class="relatorio-card">

                    <div class="relatorio-card-icon">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>

                    <div>

                        <span>
                            Atrasos
                        </span>

                        <strong>
                            <?= $totalAtrasos ?>
                        </strong>

                        <small>
                            Ocorrências de atraso
                        </small>

                    </div>

                </div>

                <div class="relatorio-card">

                    <div class="relatorio-card-icon">

                        <i class="fa-solid fa-truck"></i>

                    </div>

                    <div>

                        <span>
                            Coletas
                        </span>

                        <strong>
                            <?= $totalColetas ?>
                        </strong>

                        <small>
                            Retiradas realizadas
                        </small>

                    </div>

                </div>

            </section>

            <section class="panel relatorio-resultados">

                <div class="panel-header">

                    <div>

                        <h2>
                            Resultado
                        </h2>

                        <p>

                            <?= $totalProducoes ?>

                            <?= $totalProducoes === 1
                                ? "produção encontrada"
                                : "produções encontradas"
                            ?>

                        </p>

                    </div>

                    <button
                        type="button"
                        class="btn-secondary"
                        onclick="window.print()">

                        <i class="fa-solid fa-print"></i>

                        Imprimir

                    </button>

                </div>

                <div class="table-responsive">

                    <table class="cronex-table">

                        <thead>

                            <tr>

                                <th>Produção</th>

                                <th>Produto</th>

                                <th>Terceirizada</th>

                                <th>Quantidade</th>

                                <th>Envio</th>

                                <th>Previsão</th>

                                <th>Situação</th>

                                <th>Coleta</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (
                                count($producoes) > 0
                            ) { ?>

                                <?php foreach (
                                    $producoes as $linha
                                ) { ?>

                                    <?php

                                    $situacao =
                                        obterSituacaoRelatorio(
                                            $linha
                                        );

                                    $previsaoAtual =
                                        !empty($linha["nova_previsao"])
                                        ? $linha["nova_previsao"]
                                        : $linha["previsao_entrega"];

                                    $quantidadeTotal =
                                        intval(
                                            $linha["quantidade"]
                                        );

                                    $quantidadeColetada =
                                        intval(
                                            $linha["total_coletado"]
                                        );

                                    $quantidadePendente =
                                        intval(
                                            $linha["quantidade_pendente"]
                                        );

                                    ?>

                                    <tr>

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $linha["codigo"]
                                                ) ?>

                                            </strong>

                                        </td>

                                        <td>

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

                                        <td>

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

                                        <td>

                                            <?= number_format(
                                                $quantidadeTotal,
                                                0,
                                                ",",
                                                "."
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= formatarDataRelatorio(
                                                $linha["data_envio"]
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= formatarDataRelatorio(
                                                $previsaoAtual
                                            ) ?>

                                            <?php if (
                                                !empty($linha["nova_previsao"])
                                            ) { ?>

                                                <br>

                                                <small>
                                                    Previsão alterada
                                                </small>

                                            <?php } ?>

                                        </td>

                                        <td>

                                            <span
                                                class="status <?= htmlspecialchars(
                                                                    $situacao["classe"]
                                                                ) ?>">

                                                <i
                                                    class="fa-solid <?= htmlspecialchars(
                                                                        $situacao["icone"]
                                                                    ) ?>">
                                                </i>

                                                <?= htmlspecialchars(
                                                    $situacao["texto"]
                                                ) ?>

                                            </span>

                                            <?php if (
                                                $linha["confirmacao_prazo"] === "Atraso"
                                                &&
                                                !empty($linha["motivo_atraso"])
                                            ) { ?>

                                                <br>

                                                <small>

                                                    <?= htmlspecialchars(
                                                        $linha["motivo_atraso"]
                                                    ) ?>

                                                </small>

                                            <?php } ?>

                                        </td>

                                        <td>

                                            <?php if (
                                                $quantidadeTotal > 0
                                                &&
                                                $quantidadeColetada
                                                >= $quantidadeTotal
                                            ) { ?>

                                                <span class="status concluido">

                                                    <i class="fa-solid fa-check"></i>

                                                    Coletado

                                                </span>

                                                <br>

                                                <small>

                                                    <?= number_format(
                                                        $quantidadeColetada,
                                                        0,
                                                        ",",
                                                        "."
                                                    ) ?>

                                                    de

                                                    <?= number_format(
                                                        $quantidadeTotal,
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

                                                        <?= formatarDataHoraRelatorio(
                                                            $linha["ultima_coleta"]
                                                        ) ?>

                                                    </small>

                                                <?php } ?>

                                            <?php } elseif (
                                                $quantidadePendente > 0
                                            ) { ?>

                                                <span class="status andamento">

                                                    Aguardando coleta

                                                </span>

                                                <br>

                                                <small>

                                                    <?= number_format(
                                                        $quantidadePendente,
                                                        0,
                                                        ",",
                                                        "."
                                                    ) ?>

                                                    peças disponíveis

                                                </small>

                                                <?php if (
                                                    $quantidadeColetada > 0
                                                ) { ?>

                                                    <br>

                                                    <small>

                                                        <?= number_format(
                                                            $quantidadeColetada,
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        de

                                                        <?= number_format(
                                                            $quantidadeTotal,
                                                            0,
                                                            ",",
                                                            "."
                                                        ) ?>

                                                        já coletadas

                                                    </small>

                                                <?php } ?>

                                            <?php } elseif (
                                                $quantidadeColetada > 0
                                            ) { ?>

                                                <span class="status andamento">

                                                    Coleta parcial

                                                </span>

                                                <br>

                                                <small>

                                                    <?= number_format(
                                                        $quantidadeColetada,
                                                        0,
                                                        ",",
                                                        "."
                                                    ) ?>

                                                    de

                                                    <?= number_format(
                                                        $quantidadeTotal,
                                                        0,
                                                        ",",
                                                        "."
                                                    ) ?>

                                                    peças coletadas

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
                                        colspan="8"
                                        class="relatorio-sem-dados">

                                        <i class="fa-solid fa-magnifying-glass"></i>

                                        <strong>
                                            Nenhum resultado encontrado
                                        </strong>

                                        <span>
                                            Altere os filtros e tente novamente.
                                        </span>

                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </section>

            <section class="panel relatorio-desempenho">

                <div class="panel-header">

                    <div>

                        <h2>
                            Desempenho por Terceirizada
                        </h2>

                        <p>
                            Resumo das produções de cada parceiro no período selecionado.
                        </p>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="cronex-table">

                        <thead>

                            <tr>

                                <th>
                                    Terceirizada
                                </th>

                                <th>
                                    Produções
                                </th>

                                <th>
                                    Peças
                                </th>

                                <th>
                                    Finalizadas
                                </th>

                                <th>
                                    Atrasos
                                </th>

                                <th>
                                    Prazo confirmado
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (
                                count(
                                    $desempenhoTerceirizadas
                                ) > 0
                            ) { ?>

                                <?php foreach (
                                    $desempenhoTerceirizadas
                                    as $desempenho
                                ) { ?>

                                    <tr>

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $desempenho["nome"]
                                                ) ?>

                                            </strong>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $desempenho["codigo"]
                                                ) ?>

                                            </small>

                                        </td>

                                        <td>

                                            <strong>

                                                <?= intval(
                                                    $desempenho["producoes"]
                                                ) ?>

                                            </strong>

                                        </td>

                                        <td>

                                            <strong>

                                                <?= number_format(
                                                    $desempenho["pecas"],
                                                    0,
                                                    ",",
                                                    "."
                                                ) ?>

                                            </strong>

                                            <?php if (
                                                $desempenho["pecas_coletadas"] > 0
                                            ) { ?>

                                                <br>

                                                <small>

                                                    <?= number_format(
                                                        $desempenho["pecas_coletadas"],
                                                        0,
                                                        ",",
                                                        "."
                                                    ) ?>

                                                    coletadas

                                                </small>

                                            <?php } ?>

                                        </td>

                                        <td>

                                            <?php if (
                                                $desempenho["finalizadas"] > 0
                                            ) { ?>

                                                <span class="status concluido">

                                                    <i class="fa-solid fa-circle-check"></i>

                                                    <?= intval(
                                                        $desempenho["finalizadas"]
                                                    ) ?>

                                                </span>

                                            <?php } else { ?>

                                                <span class="relatorio-zero">
                                                    0
                                                </span>

                                            <?php } ?>

                                        </td>

                                        <td>

                                            <?php if (
                                                $desempenho["atrasos"] > 0
                                            ) { ?>

                                                <span class="status atraso">

                                                    <i class="fa-solid fa-triangle-exclamation"></i>

                                                    <?= intval(
                                                        $desempenho["atrasos"]
                                                    ) ?>

                                                </span>

                                            <?php } else { ?>

                                                <span class="relatorio-zero">
                                                    0
                                                </span>

                                            <?php } ?>

                                        </td>

                                        <td>

                                            <?php if (
                                                $desempenho["no_prazo"] > 0
                                            ) { ?>

                                                <span class="status concluido">

                                                    <i class="fa-solid fa-check"></i>

                                                    <?= intval(
                                                        $desempenho["no_prazo"]
                                                    ) ?>

                                                </span>

                                            <?php } else { ?>

                                                <span class="relatorio-zero">
                                                    0
                                                </span>

                                            <?php } ?>

                                        </td>

                                    </tr>

                                <?php } ?>

                            <?php } else { ?>

                                <tr>

                                    <td
                                        colspan="6"
                                        class="relatorio-sem-dados">

                                        <i class="fa-solid fa-chart-column"></i>

                                        <strong>
                                            Nenhum dado disponível
                                        </strong>

                                        <span>
                                            Não existem produções para os filtros selecionados.
                                        </span>

                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>

    </div>

</body>

</html>