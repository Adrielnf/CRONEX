<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Terceirizada");

$conexao = require "../includes/conexao.php";

$terceirizado_id = isset($_SESSION["terceirizado_id"])
    ? intval($_SESSION["terceirizado_id"])
    : 0;

if ($terceirizado_id <= 0) {
    header("Location: ../logout.php");
    exit;
}


/* =========================================================
   DADOS DA TERCEIRIZADA
   ========================================================= */

$sqlTerceirizado = "
    SELECT
        id,
        codigo,
        razao_social
    FROM terceirizados
    WHERE id = ?
    LIMIT 1
";

$stmtTerceirizado = mysqli_prepare(
    $conexao,
    $sqlTerceirizado
);

mysqli_stmt_bind_param(
    $stmtTerceirizado,
    "i",
    $terceirizado_id
);

mysqli_stmt_execute($stmtTerceirizado);

$resultadoTerceirizado =
    mysqli_stmt_get_result($stmtTerceirizado);

$terceirizado =
    mysqli_fetch_assoc($resultadoTerceirizado);

mysqli_stmt_close($stmtTerceirizado);

if (!$terceirizado) {
    header("Location: ../logout.php");
    exit;
}


/* =========================================================
   PRODUÇÕES DA TERCEIRIZADA

   Também calculamos:
   - quantidade já liberada para coleta
   - quantidade já coletada
   - quantidade restante
   ========================================================= */

$sql = "
    SELECT
        p.id,
        p.codigo,
        p.quantidade,
        p.data_envio,
        p.previsao_entrega,
        p.confirmacao_prazo,
        p.motivo_atraso,
        p.nova_previsao,
        p.pecas_prontas,
        p.data_pecas_prontas,
        p.coletado,
        p.data_coleta,
        p.status,

        pr.codigo AS produto_codigo,
        pr.nome AS produto_nome,

        COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE pc.producao_id = p.id
            ),
            0
        ) AS quantidade_liberada,

        COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE pc.producao_id = p.id
                AND pc.status = 'Coletado'
            ),
            0
        ) AS quantidade_coletada

    FROM producao p

    INNER JOIN produtos pr
        ON pr.id = p.produto_id

    WHERE p.terceirizado_id = ?

    ORDER BY

        CASE

            WHEN
                (
                    p.confirmacao_prazo IS NULL
                    OR p.confirmacao_prazo = ''
                )
                AND (
                    p.coletado IS NULL
                    OR p.coletado <> 'Sim'
                )
            THEN 1

            WHEN
                p.coletado = 'Sim'
            THEN 4

            ELSE 2

        END,

        p.id DESC
";

$stmt = mysqli_prepare(
    $conexao,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $terceirizado_id
);

mysqli_stmt_execute($stmt);

$resultado =
    mysqli_stmt_get_result($stmt);

$producoes = [];

while ($linha = mysqli_fetch_assoc($resultado)) {

    $linha["quantidade_liberada"] =
        intval($linha["quantidade_liberada"]);

    $linha["quantidade_coletada"] =
        intval($linha["quantidade_coletada"]);

    $linha["quantidade_restante"] =
        max(
            0,
            intval($linha["quantidade"])
                - $linha["quantidade_liberada"]
        );

    $producoes[] = $linha;
}

mysqli_stmt_close($stmt);


/* =========================================================
   CONTADORES
   ========================================================= */

$precisaAcao = 0;
$emProducao = 0;
$aguardandoColeta = 0;
$finalizados = 0;

foreach ($producoes as $producao) {

    /*
     * Produção já coletada
     */
    if ($producao["coletado"] === "Sim") {

        $finalizados++;

        continue;
    }


    /*
     * Verifica se a produção está atrasada.
     *
     * Se existir uma nova previsão, usamos ela.
     * Caso contrário, usamos a previsão original.
     */
    $previsaoAtual =
        !empty($producao["nova_previsao"])
        ? $producao["nova_previsao"]
        : $producao["previsao_entrega"];


    $atrasada = false;


    if (
        !empty($previsaoAtual) &&
        $previsaoAtual < date("Y-m-d") &&
        $producao["quantidade_restante"] > 0
    ) {

        $atrasada = true;
    }


    /*
     * Precisa da atenção:
     *
     * 1. Ainda não confirmou o prazo
     * OU
     * 2. A produção está atrasada
     */
    if (
        empty($producao["confirmacao_prazo"]) ||
        $atrasada
    ) {

        $precisaAcao++;
    }


    /*
     * Enquanto ainda existir saldo não liberado,
     * a produção continua aparecendo como "Em produção".
     */
    if ($producao["quantidade_restante"] > 0) {

        $emProducao++;
    }


    /*
     * Existe coleta aguardando quando a quantidade
     * liberada é maior que a quantidade coletada.
     */
    if (
        $producao["quantidade_liberada"]
        > $producao["quantidade_coletada"]
    ) {

        $aguardandoColeta +=
            $producao["quantidade_liberada"]
            - $producao["quantidade_coletada"];
    }
}

/* =========================================================
   MENSAGENS
   ========================================================= */

$sucesso = isset($_GET["sucesso"])
    ? $_GET["sucesso"]
    : "";

$erro = isset($_GET["erro"])
    ? $_GET["erro"]
    : "";

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cronex - Minhas Produções</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../css/style.css?v=<?= time() ?>">

</head>

<body>

    <div class="app">


        <!-- =====================================================
         CABEÇALHO MOBILE
         ===================================================== -->

        <header class="portal-mobile-header">

            <div class="portal-mobile-brand">

                <strong>
                    CRONEX
                </strong>

                <span>
                    Portal da Terceirizada
                </span>

            </div>

            <a
                href="../logout.php"
                class="portal-mobile-logout">

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>
                    Sair
                </span>

            </a>

        </header>


        <!-- =====================================================
         MENU
         ===================================================== -->

        <aside class="sidebar">

            <div class="logo">

                <h2>
                    CRONEX
                </h2>

                <span>
                    Portal da Terceirizada
                </span>

            </div>

            <nav class="menu">

                <a
                    href="producoes.php"
                    class="active">

                    <i class="fa-solid fa-industry"></i>

                    <span>
                        Minhas Produções
                    </span>

                </a>

                <hr>

                <a href="../logout.php">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    <span>
                        Sair
                    </span>

                </a>

            </nav>

        </aside>


        <!-- =====================================================
         CONTEÚDO
         ===================================================== -->

        <main class="main-content">

            <header class="topbar">

                <div>

                    <h1>
                        Minhas Produções
                    </h1>

                    <p>
                        Veja rapidamente o que precisa da sua atenção.
                    </p>

                </div>

                <div class="user-box">

                    <?= htmlspecialchars(
                        $terceirizado["razao_social"]
                    ) ?>

                </div>

            </header>


            <!-- =================================================
             MENSAGENS
             ================================================= -->

            <?php if ($sucesso === "atualizado") { ?>

                <div class="alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    Produção atualizada com sucesso.

                </div>

            <?php } ?>


            <?php if ($sucesso === "liberado") { ?>

                <div class="alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    Peças liberadas para coleta com sucesso.

                </div>

            <?php } ?>


            <?php if ($erro !== "") { ?>

                <div class="alert-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?php if ($erro === "quantidade") { ?>

                        Informe uma quantidade válida de peças.

                    <?php } elseif ($erro === "saldo") { ?>

                        A quantidade informada é maior que a quantidade restante.

                    <?php } else { ?>

                        Não foi possível realizar a operação.

                    <?php } ?>

                </div>

            <?php } ?>


            <!-- =================================================
             RESUMO
             ================================================= -->

            <section class="portal-resumo">

                <div class="portal-resumo-card destaque">

                    <div class="portal-resumo-icon">

                        <i class="fa-solid fa-bell"></i>

                    </div>

                    <div>

                        <span>
                            Precisa da sua atenção
                        </span>

                        <strong>
                            <?= $precisaAcao ?>
                        </strong>

                        <small>

                            <?= $precisaAcao === 1
                                ? "item precisa de atenção"
                                : "itens precisam de atenção" ?>

                        </small>
                    </div>

                </div>


                <div class="portal-resumo-card">

                    <div class="portal-resumo-icon">

                        <i class="fa-solid fa-shirt"></i>

                    </div>

                    <div>

                        <span>
                            Em produção
                        </span>

                        <strong>
                            <?= $emProducao ?>
                        </strong>

                        <small>
                            Pedidos ainda em execução
                        </small>

                    </div>

                </div>


                <div class="portal-resumo-card">

                    <div class="portal-resumo-icon">

                        <i class="fa-solid fa-truck"></i>

                    </div>

                    <div>

                        <span>
                            Aguardando coleta
                        </span>

                        <strong>
                            <?= $aguardandoColeta ?>
                        </strong>

                        <small>
                            Peças disponíveis para retirada
                        </small>

                    </div>

                </div>


                <div class="portal-resumo-card">

                    <div class="portal-resumo-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div>

                        <span>
                            Finalizados
                        </span>

                        <strong>
                            <?= $finalizados ?>
                        </strong>

                        <small>

                            <?= $finalizados === 1
                                ? "pedido concluído e coletado"
                                : "pedidos concluídos e coletados" ?>

                        </small>

                    </div>

                </div>

            </section>


            <!-- =================================================
             PRECISA DE ATENÇÃO
             ================================================= -->

            <?php if ($precisaAcao > 0) { ?>

                <section class="portal-section">

                    <div class="portal-section-title">

                        <div>

                            <h2>

                                <i class="fa-solid fa-bell"></i>

                                Precisa da sua atenção

                            </h2>

                            <p>
                                Responda estes pedidos primeiro.
                            </p>

                        </div>

                    </div>

                    <div class="portal-producao-grid">

                        <?php foreach ($producoes as $producao) { ?>

                            <?php

                            $previsaoAtencao =
                                !empty($producao["nova_previsao"])
                                ? $producao["nova_previsao"]
                                : $producao["previsao_entrega"];

                            $atrasadaAtencao =
                                !empty($previsaoAtencao) &&
                                $previsaoAtencao < date("Y-m-d") &&
                                $producao["quantidade_restante"] > 0;


                            if (
                                $producao["coletado"] === "Sim" ||
                                (
                                    !empty($producao["confirmacao_prazo"]) &&
                                    !$atrasadaAtencao
                                )
                            ) {
                                continue;
                            }

                            ?>

                            <?php

                            $previsaoAtencao =
                                !empty($producao["nova_previsao"])
                                ? $producao["nova_previsao"]
                                : $producao["previsao_entrega"];

                            $atrasadaAtencao = false;
                            $diasAtraso = 0;

                            if (
                                !empty($previsaoAtencao) &&
                                $previsaoAtencao < date("Y-m-d") &&
                                $producao["quantidade_restante"] > 0
                            ) {

                                $atrasadaAtencao = true;

                                $dataPrevista = new DateTime($previsaoAtencao);
                                $dataAtual = new DateTime(date("Y-m-d"));

                                $intervalo = $dataPrevista->diff($dataAtual);

                                $diasAtraso = $intervalo->days;
                            }

                            ?>

                            <div class="portal-producao-card <?= $atrasadaAtencao ? 'atrasada' : 'atencao' ?>">

                                <div class="portal-producao-topo">

                                    <div>

                                        <span class="portal-codigo">

                                            <?= htmlspecialchars(
                                                $producao["codigo"]
                                            ) ?>

                                        </span>

                                        <h3>

                                            <?= htmlspecialchars(
                                                $producao["produto_nome"]
                                            ) ?>

                                        </h3>

                                    </div>


                                    <?php if ($atrasadaAtencao) { ?>

                                        <span class="portal-badge atraso">

                                            <i class="fa-solid fa-triangle-exclamation"></i>

                                            Produção atrasada

                                        </span>

                                    <?php } else { ?>

                                        <span class="portal-badge atencao">

                                            <i class="fa-solid fa-clock"></i>

                                            Resposta necessária

                                        </span>

                                    <?php } ?>

                                </div>


                                <div class="portal-info-grid">

                                    <div>

                                        <span>
                                            Quantidade
                                        </span>

                                        <strong>

                                            <?= intval(
                                                $producao["quantidade"]
                                            ) ?>

                                            peças

                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Previsão
                                        </span>

                                        <strong>

                                            <?= !empty($previsaoAtencao)
                                                ? date(
                                                    "d/m/Y",
                                                    strtotime($previsaoAtencao)
                                                )
                                                : "-" ?>

                                        </strong>

                                    </div>

                                </div>


                                <?php if ($atrasadaAtencao) { ?>

                                    <div class="alerta-atraso-producao">

                                        <i class="fa-solid fa-triangle-exclamation"></i>

                                        <div>

                                            <strong>
                                                Produção atrasada
                                            </strong>

                                            <p>

                                                O prazo previsto para
                                                <?= date(
                                                    "d/m/Y",
                                                    strtotime($previsaoAtencao)
                                                ) ?>
                                                foi ultrapassado.

                                            </p>

                                            <span>

                                                <?= $diasAtraso ?>

                                                <?= $diasAtraso == 1
                                                    ? "dia de atraso"
                                                    : "dias de atraso" ?>

                                            </span>

                                        </div>

                                    </div>


                                    <div class="portal-pergunta simples">

                                        <strong>
                                            É necessário atualizar o prazo.
                                        </strong>

                                        <p>
                                            Informe uma nova previsão de entrega para manter o pedido atualizado.
                                        </p>

                                    </div>


                                <?php } else { ?>

                                    <div class="portal-pergunta">

                                        <strong>
                                            Você consegue entregar até essa data?
                                        </strong>

                                        <p>
                                            Confirme o prazo ou informe uma nova previsão.
                                        </p>

                                    </div>

                                <?php } ?>


                                <div class="portal-acoes">


                                    <?php if ($atrasadaAtencao) { ?>

                                        <a
                                            href="atualizar_producao.php?id=<?= intval(
                                                                                $producao["id"]
                                                                            ) ?>"
                                            class="portal-btn alterar">

                                            <i class="fa-solid fa-calendar-days"></i>

                                            Atualizar prazo

                                        </a>


                                        <button
                                            type="button"
                                            class="portal-btn pronto btn-liberar-pecas"
                                            data-id="<?= intval(
                                                            $producao["id"]
                                                        ) ?>"
                                            data-codigo="<?= htmlspecialchars(
                                                                $producao["codigo"]
                                                            ) ?>"
                                            data-produto="<?= htmlspecialchars(
                                                                $producao["produto_nome"]
                                                            ) ?>"
                                            data-total="<?= intval(
                                                            $producao["quantidade"]
                                                        ) ?>"
                                            data-liberadas="<?= intval(
                                                                $producao["quantidade_liberada"]
                                                            ) ?>"
                                            data-restante="<?= intval(
                                                                $producao["quantidade_restante"]
                                                            ) ?>">

                                            <i class="fa-solid fa-box-open"></i>

                                            Informar peças prontas

                                        </button>


                                    <?php } else { ?>


                                        <form
                                            action="salvar_atualizacao.php"
                                            method="POST">

                                            <input
                                                type="hidden"
                                                name="producao_id"
                                                value="<?= intval(
                                                            $producao["id"]
                                                        ) ?>">

                                            <input
                                                type="hidden"
                                                name="confirmacao_prazo"
                                                value="No prazo">

                                            <input
                                                type="hidden"
                                                name="pecas_prontas"
                                                value="Nao">


                                            <button
                                                type="submit"
                                                class="portal-btn confirmar">

                                                <i class="fa-solid fa-check"></i>

                                                Sim, confirmo

                                            </button>

                                        </form>


                                        <a
                                            href="atualizar_producao.php?id=<?= intval(
                                                                                $producao["id"]
                                                                            ) ?>"
                                            class="portal-btn alterar">

                                            <i class="fa-solid fa-triangle-exclamation"></i>

                                            Não, preciso alterar

                                        </a>


                                    <?php } ?>


                                </div>

                            </div>

                        <?php } ?>

                    </div>

                </section>

            <?php } ?>


            <!-- =================================================
             EM PRODUÇÃO
             ================================================= -->

            <section class="portal-section">

                <div class="portal-section-title">

                    <div>

                        <h2>

                            <i class="fa-solid fa-shirt"></i>

                            Em produção

                        </h2>

                        <p>
                            Informe quando houver peças disponíveis para coleta.
                        </p>

                    </div>

                </div>


                <div class="portal-producao-grid">

                    <?php

                    $temEmProducao = false;

                    foreach ($producoes as $producao) {

                        if (
                            empty($producao["confirmacao_prazo"]) ||
                            $producao["coletado"] === "Sim" ||
                            $producao["quantidade_restante"] <= 0
                        ) {
                            continue;
                        }

                        $temEmProducao = true;

                        $previsaoAtual =
                            !empty($producao["nova_previsao"])
                            ? $producao["nova_previsao"]
                            : $producao["previsao_entrega"];

                    ?>

                        <div class="portal-producao-card">

                            <div class="portal-producao-topo">

                                <div>

                                    <span class="portal-codigo">

                                        <?= htmlspecialchars(
                                            $producao["codigo"]
                                        ) ?>

                                    </span>

                                    <h3>

                                        <?= htmlspecialchars(
                                            $producao["produto_nome"]
                                        ) ?>

                                    </h3>

                                </div>


                                <?php if (
                                    $producao["confirmacao_prazo"]
                                    === "Atraso"
                                ) { ?>

                                    <span class="portal-badge atraso">

                                        <i class="fa-solid fa-triangle-exclamation"></i>

                                        Prazo alterado

                                    </span>

                                <?php } else { ?>

                                    <span class="portal-badge confirmado">

                                        <i class="fa-solid fa-check"></i>

                                        Prazo confirmado

                                    </span>

                                <?php } ?>

                            </div>


                            <div class="portal-info-grid">

                                <div>

                                    <span>
                                        Quantidade total
                                    </span>

                                    <strong>

                                        <?= intval(
                                            $producao["quantidade"]
                                        ) ?>

                                        peças

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Entrega prevista
                                    </span>

                                    <strong>

                                        <?= date(
                                            "d/m/Y",
                                            strtotime(
                                                $previsaoAtual
                                            )
                                        ) ?>

                                    </strong>

                                </div>

                            </div>


                            <!-- =====================================
                             RESUMO DAS QUANTIDADES
                             ===================================== -->

                            <div class="portal-info-grid">

                                <div>

                                    <span>
                                        Já liberadas
                                    </span>

                                    <strong>

                                        <?= intval(
                                            $producao["quantidade_liberada"]
                                        ) ?>

                                        peças

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Restantes
                                    </span>

                                    <strong>

                                        <?= intval(
                                            $producao["quantidade_restante"]
                                        ) ?>

                                        peças

                                    </strong>

                                </div>

                            </div>


                            <div class="portal-pergunta simples">

                                <strong>
                                    Possui peças prontas?
                                </strong>

                                <p>
                                    Informe quantas peças estão disponíveis para coleta.
                                </p>

                            </div>


                            <div class="portal-acoes">

                                <button
                                    type="button"
                                    class="portal-btn pronto btn-liberar-pecas"
                                    data-id="<?= intval(
                                                    $producao["id"]
                                                ) ?>"
                                    data-codigo="<?= htmlspecialchars(
                                                        $producao["codigo"]
                                                    ) ?>"
                                    data-produto="<?= htmlspecialchars(
                                                        $producao["produto_nome"]
                                                    ) ?>"
                                    data-total="<?= intval(
                                                    $producao["quantidade"]
                                                ) ?>"
                                    data-liberadas="<?= intval(
                                                        $producao["quantidade_liberada"]
                                                    ) ?>"
                                    data-restante="<?= intval(
                                                        $producao["quantidade_restante"]
                                                    ) ?>">

                                    <i class="fa-solid fa-box-open"></i>

                                    Informar peças prontas

                                </button>


                                <a
                                    href="atualizar_producao.php?id=<?= intval(
                                                                        $producao["id"]
                                                                    ) ?>"
                                    class="portal-link">

                                    <i class="fa-solid fa-calendar-days"></i>

                                    Preciso alterar o prazo

                                </a>

                            </div>

                        </div>

                    <?php } ?>


                    <?php if (!$temEmProducao) { ?>

                        <div class="portal-vazio">

                            <i class="fa-solid fa-circle-check"></i>

                            <strong>
                                Nenhum pedido em produção.
                            </strong>

                            <span>
                                Você não precisa fazer nada aqui agora.
                            </span>

                        </div>

                    <?php } ?>

                </div>

            </section>


            <!-- =================================================
             AGUARDANDO COLETA
             ================================================= -->

            <?php if ($aguardandoColeta > 0) { ?>

                <section class="portal-section">

                    <div class="portal-section-title">

                        <div>

                            <h2>

                                <i class="fa-solid fa-truck"></i>

                                Aguardando coleta

                            </h2>

                            <p>
                                Peças que você já liberou e aguardam retirada.
                            </p>

                        </div>

                    </div>


                    <div class="portal-producao-grid">

                        <?php foreach ($producoes as $producao) { ?>

                            <?php

                            $quantidadeAguardando =
                                intval(
                                    $producao["quantidade_liberada"]
                                )
                                -
                                intval(
                                    $producao["quantidade_coletada"]
                                );

                            if (
                                $producao["coletado"] === "Sim" ||
                                $quantidadeAguardando <= 0
                            ) {
                                continue;
                            }

                            $previsaoAtual =
                                !empty($producao["nova_previsao"])
                                ? $producao["nova_previsao"]
                                : $producao["previsao_entrega"];

                            ?>

                            <div class="portal-producao-card concluido">

                                <div class="portal-producao-topo">

                                    <div>

                                        <span class="portal-codigo">

                                            <?= htmlspecialchars(
                                                $producao["codigo"]
                                            ) ?>

                                        </span>

                                        <h3>

                                            <?= htmlspecialchars(
                                                $producao["produto_nome"]
                                            ) ?>

                                        </h3>

                                    </div>


                                    <span class="portal-badge pronto">

                                        <i class="fa-solid fa-box-open"></i>

                                        <?= $quantidadeAguardando ?>
                                        peças prontas

                                    </span>

                                </div>


                                <div class="portal-info-grid">

                                    <div>

                                        <span>
                                            Quantidade disponível
                                        </span>

                                        <strong>

                                            <?= $quantidadeAguardando ?>

                                            peças

                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Previsão
                                        </span>

                                        <strong>

                                            <?= date(
                                                "d/m/Y",
                                                strtotime(
                                                    $previsaoAtual
                                                )
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>


                                <div class="portal-status-final">

                                    <i class="fa-solid fa-truck-fast"></i>

                                    <div>

                                        <strong>
                                            Aguardando coleta
                                        </strong>

                                        <span>

                                            <?= $quantidadeAguardando ?>

                                            peças estão disponíveis para retirada.

                                        </span>

                                    </div>

                                </div>

                            </div>

                        <?php } ?>

                    </div>

                </section>

            <?php } ?>


            <!-- =================================================
             FINALIZADOS
             ================================================= -->

            <?php if ($finalizados > 0) { ?>

                <section class="portal-section">

                    <div class="portal-section-title">

                        <div>

                            <h2>

                                <i class="fa-solid fa-circle-check"></i>

                                Finalizados

                            </h2>

                            <p>
                                Pedidos concluídos e já retirados pelo coletor.
                            </p>

                        </div>

                    </div>


                    <div class="portal-producao-grid">

                        <?php foreach ($producoes as $producao) { ?>

                            <?php

                            if (
                                $producao["coletado"] !== "Sim"
                            ) {
                                continue;
                            }

                            ?>

                            <div class="portal-producao-card concluido">

                                <div class="portal-producao-topo">

                                    <div>

                                        <span class="portal-codigo">

                                            <?= htmlspecialchars(
                                                $producao["codigo"]
                                            ) ?>

                                        </span>

                                        <h3>

                                            <?= htmlspecialchars(
                                                $producao["produto_nome"]
                                            ) ?>

                                        </h3>

                                    </div>


                                    <span class="portal-badge pronto">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Coleta realizada

                                    </span>

                                </div>


                                <div class="portal-info-grid">

                                    <div>

                                        <span>
                                            Quantidade
                                        </span>

                                        <strong>

                                            <?= intval(
                                                $producao["quantidade"]
                                            ) ?>

                                            peças

                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Data da coleta
                                        </span>

                                        <strong>

                                            <?php if (
                                                !empty($producao["data_coleta"])
                                            ) { ?>

                                                <?= date(
                                                    "d/m/Y \à\s H:i",
                                                    strtotime(
                                                        $producao["data_coleta"]
                                                    )
                                                ) ?>

                                            <?php } else { ?>

                                                Coleta registrada

                                            <?php } ?>

                                        </strong>

                                    </div>

                                </div>


                                <div class="portal-status-final">

                                    <i class="fa-solid fa-circle-check"></i>

                                    <div>

                                        <strong>
                                            Processo finalizado
                                        </strong>

                                        <span>
                                            A coleta deste pedido já foi realizada. Nenhuma ação necessária.
                                        </span>

                                    </div>

                                </div>

                            </div>

                        <?php } ?>

                    </div>

                </section>

            <?php } ?>

        </main>

    </div>


    <!-- =========================================================
     MODAL - INFORMAR PEÇAS PRONTAS
     ========================================================= -->

    <div
        class="modal-overlay"
        id="modalPecasProntas"
        aria-hidden="true">

        <div
            class="modal-confirmacao"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalPecasProntas">

            <div class="modal-confirmacao-icon">

                <i class="fa-solid fa-box-open"></i>

            </div>


            <h2 id="tituloModalPecasProntas">
                Informar peças prontas
            </h2>


            <p id="modalPecasDescricao">
                Informe quantas peças estão disponíveis para coleta.
            </p>


            <form
                action="salvar_atualizacao.php"
                method="POST"
                id="formLiberarPecas">

                <input
                    type="hidden"
                    name="acao"
                    value="liberar_parcial">

                <input
                    type="hidden"
                    name="producao_id"
                    id="modalProducaoId"
                    value="">


                <div class="portal-info-grid">

                    <div>

                        <span>
                            Produção total
                        </span>

                        <strong id="modalQuantidadeTotal">
                            0 peças
                        </strong>

                    </div>


                    <div>

                        <span>
                            Já liberadas
                        </span>

                        <strong id="modalQuantidadeLiberada">
                            0 peças
                        </strong>

                    </div>

                </div>


                <div class="portal-pergunta simples">

                    <strong>
                        Quantas peças estão prontas?
                    </strong>

                    <p id="modalQuantidadeRestante">
                        Restam 0 peças para liberar.
                    </p>

                </div>


                <div class="campo">

                    <label for="quantidade_pronta">
                        Quantidade pronta
                    </label>

                    <input
                        type="number"
                        name="quantidade_pronta"
                        id="quantidade_pronta"
                        min="1"
                        step="1"
                        required>

                </div>


                <div class="modal-confirmacao-acoes">

                    <button
                        type="button"
                        class="modal-btn cancelar"
                        id="cancelarPecasProntas">

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="modal-btn confirmar">

                        <i class="fa-solid fa-check"></i>

                        Liberar para coleta

                    </button>

                </div>

            </form>

        </div>

    </div>


    <script src="../js/script.js?v=<?= time() ?>"></script>

</body>

</html>