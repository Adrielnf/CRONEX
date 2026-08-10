<?php

$base = "";

require_once "includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "includes/conexao.php";


$sqlTerceirizados = "
    SELECT COUNT(*) AS total
    FROM terceirizados
    WHERE status = 'Ativo'
";

$resultadoTerceirizados = mysqli_query(
    $conexao,
    $sqlTerceirizados
);

$dadosTerceirizados = mysqli_fetch_assoc(
    $resultadoTerceirizados
);

$totalTerceirizados = intval(
    $dadosTerceirizados["total"]
);


$sqlProdutos = "
    SELECT COUNT(*) AS total
    FROM produtos
";

$resultadoProdutos = mysqli_query(
    $conexao,
    $sqlProdutos
);

$dadosProdutos = mysqli_fetch_assoc(
    $resultadoProdutos
);

$totalProdutos = intval(
    $dadosProdutos["total"]
);


$sqlProducoesAtivas = "
    SELECT COUNT(*) AS total
    FROM producao pr

    WHERE
        COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE
                    pc.producao_id = pr.id
                    AND pc.status = 'Coletado'
            ),
            0
        ) < pr.quantidade
";

$resultadoProducoesAtivas = mysqli_query(
    $conexao,
    $sqlProducoesAtivas
);

$dadosProducoesAtivas = mysqli_fetch_assoc(
    $resultadoProducoesAtivas
);

$totalProducoesAtivas = intval(
    $dadosProducoesAtivas["total"]
);


$sqlAguardandoColeta = "
    SELECT
        COALESCE(SUM(pc.quantidade), 0) AS total

    FROM producao_coletas pc

    WHERE pc.status = 'Aguardando'
";

$resultadoAguardandoColeta = mysqli_query(
    $conexao,
    $sqlAguardandoColeta
);

$dadosAguardandoColeta = mysqli_fetch_assoc(
    $resultadoAguardandoColeta
);

$totalAguardandoColeta = intval(
    $dadosAguardandoColeta["total"]
);


$sqlAtrasos = "
    SELECT COUNT(*) AS total

    FROM producao pr

    WHERE
        pr.confirmacao_prazo = 'Atraso'

        AND COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE
                    pc.producao_id = pr.id
                    AND pc.status = 'Coletado'
            ),
            0
        ) < pr.quantidade
";

$resultadoAtrasos = mysqli_query(
    $conexao,
    $sqlAtrasos
);

$dadosAtrasos = mysqli_fetch_assoc(
    $resultadoAtrasos
);

$totalAtrasos = intval(
    $dadosAtrasos["total"]
);


$sqlFinalizadas = "
    SELECT COUNT(*) AS total

    FROM producao pr

    WHERE
        COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE
                    pc.producao_id = pr.id
                    AND pc.status = 'Coletado'
            ),
            0
        ) >= pr.quantidade
";

$resultadoFinalizadas = mysqli_query(
    $conexao,
    $sqlFinalizadas
);

$dadosFinalizadas = mysqli_fetch_assoc(
    $resultadoFinalizadas
);

$totalFinalizadas = intval(
    $dadosFinalizadas["total"]
);


$sqlRecentes = "
    SELECT
        pr.id,
        pr.codigo,
        pr.quantidade,
        pr.confirmacao_prazo,

        p.nome AS produto_nome,

        t.razao_social AS terceirizado_nome,

        COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE
                    pc.producao_id = pr.id
                    AND pc.status = 'Coletado'
            ),
            0
        ) AS quantidade_coletada,

        COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE
                    pc.producao_id = pr.id
                    AND pc.status = 'Aguardando'
            ),
            0
        ) AS quantidade_aguardando

    FROM producao pr

    INNER JOIN produtos p
        ON p.id = pr.produto_id

    INNER JOIN terceirizados t
        ON t.id = pr.terceirizado_id

    ORDER BY pr.id DESC

    LIMIT 5
";

$resultadoRecentes = mysqli_query(
    $conexao,
    $sqlRecentes
);

$producoesRecentes = [];

if ($resultadoRecentes) {

    while (
        $linha = mysqli_fetch_assoc(
            $resultadoRecentes
        )
    ) {

        $producoesRecentes[] = $linha;
    }
}


$sqlColetas = "
    SELECT
        pc.id AS coleta_id,
        pc.quantidade AS quantidade_coleta,
        pc.data_liberacao,

        pr.id AS producao_id,
        pr.codigo,

        p.nome AS produto_nome,

        t.razao_social AS terceirizado_nome

    FROM producao_coletas pc

    INNER JOIN producao pr
        ON pr.id = pc.producao_id

    INNER JOIN produtos p
        ON p.id = pr.produto_id

    INNER JOIN terceirizados t
        ON t.id = pr.terceirizado_id

    WHERE pc.status = 'Aguardando'

    ORDER BY
        pc.data_liberacao ASC,
        pc.id ASC

    LIMIT 5
";

$resultadoColetas = mysqli_query(
    $conexao,
    $sqlColetas
);

$proximasColetas = [];

if ($resultadoColetas) {

    while (
        $linha = mysqli_fetch_assoc(
            $resultadoColetas
        )
    ) {

        $proximasColetas[] = $linha;
    }
}


function statusDashboard($producao)
{
    $quantidadeTotal = intval(
        $producao["quantidade"]
    );

    $quantidadeColetada = intval(
        $producao["quantidade_coletada"]
    );

    $quantidadeAguardando = intval(
        $producao["quantidade_aguardando"]
    );


    if (
        $quantidadeTotal > 0 &&
        $quantidadeColetada >= $quantidadeTotal
    ) {

        return [
            "texto" => "Finalizada",
            "classe" => "concluido",
            "icone" => "fa-circle-check"
        ];
    }


    if ($quantidadeAguardando > 0) {

        return [
            "texto" => "Aguardando coleta",
            "classe" => "andamento",
            "icone" => "fa-truck"
        ];
    }


    if (
        empty($producao["confirmacao_prazo"])
    ) {

        return [
            "texto" => "Aguardando confirmação",
            "classe" => "andamento",
            "icone" => "fa-clock"
        ];
    }


    if (
        $producao["confirmacao_prazo"]
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
        Cronex - Dashboard
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="css/style.css?v=<?= time() ?>">

</head>

<body>

    <div class="app">

        <?php
        include "includes/menu.php";
        ?>


        <main class="main-content">

            <header class="topbar">

                <div>

                    <h1>
                        Dashboard
                    </h1>

                    <p>
                        Visão geral da produção terceirizada
                    </p>

                </div>


                <div class="user-box">

                    <span>
                        Administrador
                    </span>

                </div>

            </header>


            <section class="cards">

                <div class="card">

                    <p>
                        Terceirizados
                    </p>

                    <h2>
                        <?= $totalTerceirizados ?>
                    </h2>

                    <span>
                        Parceiros ativos
                    </span>

                </div>


                <div class="card">

                    <p>
                        Produtos
                    </p>

                    <h2>
                        <?= $totalProdutos ?>
                    </h2>

                    <span>
                        Modelos cadastrados
                    </span>

                </div>


                <div class="card">

                    <p>
                        Produções Ativas
                    </p>

                    <h2>
                        <?= $totalProducoesAtivas ?>
                    </h2>

                    <span>
                        Ordens não finalizadas
                    </span>

                </div>


                <div class="card">

                    <p>
                        Aguardando Coleta
                    </p>

                    <h2>
                        <?= $totalAguardandoColeta ?>
                    </h2>

                    <span>
                        Peças prontas para retirada
                    </span>

                </div>

            </section>


            <section class="dashboard-alertas">

                <a
                    href="previsao/listar.php"
                    class="dashboard-alerta">

                    <div class="dashboard-alerta-icon">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>

                    <div>

                        <span>
                            Atrasos informados
                        </span>

                        <strong>
                            <?= $totalAtrasos ?>
                        </strong>

                    </div>

                </a>


                <a
                    href="previsao/listar.php"
                    class="dashboard-alerta">

                    <div class="dashboard-alerta-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div>

                        <span>
                            Produções finalizadas
                        </span>

                        <strong>
                            <?= $totalFinalizadas ?>
                        </strong>

                    </div>

                </a>

            </section>


            <section class="dashboard-grid">

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h2>
                                Produções Recentes
                            </h2>

                            <p>
                                Últimas ordens cadastradas
                            </p>

                        </div>


                        <a href="producao/listar.php">

                            Ver todas

                        </a>

                    </div>


                    <div class="table-responsive">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Produção
                                    </th>

                                    <th>
                                        Terceirizada
                                    </th>

                                    <th>
                                        Produto
                                    </th>

                                    <th>
                                        Situação
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php if (
                                    count(
                                        $producoesRecentes
                                    ) > 0
                                ) { ?>


                                    <?php foreach (
                                        $producoesRecentes
                                        as $producao
                                    ) { ?>


                                        <?php

                                        $status = statusDashboard(
                                            $producao
                                        );

                                        ?>


                                        <tr>

                                            <td>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $producao["codigo"]
                                                    ) ?>

                                                </strong>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $producao["terceirizado_nome"]
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $producao["produto_nome"]
                                                ) ?>

                                            </td>


                                            <td>

                                                <span
                                                    class="status <?= htmlspecialchars(
                                                                        $status["classe"]
                                                                    ) ?>">

                                                    <i
                                                        class="fa-solid <?= htmlspecialchars(
                                                                            $status["icone"]
                                                                        ) ?>">
                                                    </i>

                                                    <?= htmlspecialchars(
                                                        $status["texto"]
                                                    ) ?>

                                                </span>

                                            </td>

                                        </tr>


                                    <?php } ?>


                                <?php } else { ?>


                                    <tr>

                                        <td
                                            colspan="4"
                                            style="text-align:center;">

                                            Nenhuma produção cadastrada.

                                        </td>

                                    </tr>


                                <?php } ?>


                            </tbody>

                        </table>

                    </div>

                </div>


                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h2>
                                Aguardando Coleta
                            </h2>

                            <p>
                                Peças liberadas pelas terceirizadas
                            </p>

                        </div>


                        <a href="previsao/listar.php">

                            Ver todas

                        </a>

                    </div>


                    <div class="collection-list">


                        <?php if (
                            count(
                                $proximasColetas
                            ) > 0
                        ) { ?>


                            <?php foreach (
                                $proximasColetas
                                as $coleta
                            ) { ?>


                                <div class="collection-item">

                                    <div>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $coleta["terceirizado_nome"]
                                            ) ?>

                                        </strong>


                                        <p>

                                            <?= htmlspecialchars(
                                                $coleta["codigo"]
                                            ) ?>

                                            ·

                                            <?= htmlspecialchars(
                                                $coleta["produto_nome"]
                                            ) ?>

                                        </p>


                                        <small>

                                            <?= intval(
                                                $coleta["quantidade_coleta"]
                                            ) ?>

                                            peças

                                        </small>

                                    </div>


                                    <span class="status andamento">

                                        <i class="fa-solid fa-truck"></i>

                                        Aguardando

                                    </span>

                                </div>


                            <?php } ?>


                        <?php } else { ?>


                            <div class="dashboard-vazio">

                                <i class="fa-solid fa-circle-check"></i>

                                <strong>
                                    Nenhuma coleta pendente
                                </strong>

                                <span>
                                    Não existem peças aguardando retirada.
                                </span>

                            </div>


                        <?php } ?>


                    </div>

                </div>

            </section>

        </main>

    </div>

</body>

</html>