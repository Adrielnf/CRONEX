<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Coletor");

$conexao = require "../includes/conexao.php";


/* =========================================================
   PRODUÇÕES
   ========================================================= */

$sql = "
    SELECT
        pr.id,
        pr.codigo,
        pr.quantidade,
        pr.data_envio,
        pr.previsao_entrega,
        pr.nova_previsao,
        pr.confirmacao_prazo,
        pr.coletado,
        pr.data_coleta,
        pr.status,

        p.codigo AS produto_codigo,
        p.nome AS produto_nome,

        t.codigo AS terceirizado_codigo,
        t.razao_social,
        t.responsavel,
        t.telefone,
        t.whatsapp,
        t.cep,
        t.logradouro,
        t.numero,
        t.complemento,
        t.bairro,
        t.cidade,
        t.uf,

        COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE pc.producao_id = pr.id
            ),
            0
        ) AS quantidade_liberada,

        COALESCE(
            (
                SELECT SUM(pc.quantidade)
                FROM producao_coletas pc
                WHERE pc.producao_id = pr.id
                AND pc.status = 'Coletado'
            ),
            0
        ) AS quantidade_coletada

    FROM producao pr

    INNER JOIN produtos p
        ON p.id = pr.produto_id

    INNER JOIN terceirizados t
        ON t.id = pr.terceirizado_id

    ORDER BY
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
    die("Erro ao carregar as produções: "
        . mysqli_error($conexao));
}


$producoes = [];

while ($producao = mysqli_fetch_assoc($resultado)) {

    $producao["quantidade"] =
        intval($producao["quantidade"]);

    $producao["quantidade_liberada"] =
        intval($producao["quantidade_liberada"]);

    $producao["quantidade_coletada"] =
        intval($producao["quantidade_coletada"]);

    $producoes[] = $producao;
}


/* =========================================================
   LIBERAÇÕES AGUARDANDO COLETA
   ========================================================= */

$sqlColetas = "
    SELECT
        pc.id AS coleta_id,
        pc.producao_id,
        pc.quantidade AS quantidade_coleta,
        pc.status AS coleta_status,
        pc.data_liberacao,
        pc.data_coleta,

        pr.codigo,
        pr.quantidade AS quantidade_total,
        pr.previsao_entrega,
        pr.nova_previsao,

        p.codigo AS produto_codigo,
        p.nome AS produto_nome,

        t.codigo AS terceirizado_codigo,
        t.razao_social,
        t.responsavel,
        t.telefone,
        t.whatsapp,
        t.cep,
        t.logradouro,
        t.numero,
        t.complemento,
        t.bairro,
        t.cidade,
        t.uf,

        COALESCE(
            (
                SELECT SUM(pc2.quantidade)
                FROM producao_coletas pc2
                WHERE pc2.producao_id = pr.id
                AND pc2.status = 'Coletado'
            ),
            0
        ) AS quantidade_coletada

    FROM producao_coletas pc

    INNER JOIN producao pr
        ON pr.id = pc.producao_id

    INNER JOIN produtos p
        ON p.id = pr.produto_id

    INNER JOIN terceirizados t
        ON t.id = pr.terceirizado_id

    ORDER BY

        CASE
            WHEN pc.status = 'Aguardando'
            THEN 1

            WHEN pc.status = 'Coletado'
            THEN 2

            ELSE 3
        END,

        pc.data_liberacao ASC,
        pc.id ASC
";

$resultadoColetas = mysqli_query(
    $conexao,
    $sqlColetas
);

if (!$resultadoColetas) {
    die("Erro ao carregar as coletas: "
        . mysqli_error($conexao));
}


$prontas = 0;
$coletadas = 0;
$atrasadasColeta = 0;

while (
    $coleta = mysqli_fetch_assoc(
        $resultadoColetas
    )
) {

    $coleta["coleta_id"] =
        intval($coleta["coleta_id"]);

    $coleta["producao_id"] =
        intval($coleta["producao_id"]);

    $coleta["quantidade_coleta"] =
        intval($coleta["quantidade_coleta"]);

    $coleta["quantidade_total"] =
        intval($coleta["quantidade_total"]);

    $coleta["quantidade_coletada"] =
        intval($coleta["quantidade_coletada"]);


    /*
     * Verifica se a coleta está atrasada.
     *
     * A coleta deve ser realizada no mesmo dia
     * em que as peças foram liberadas.
     */
    $coletaAtrasada = false;
    $diasAtrasoColeta = 0;

    if (
        $coleta["coleta_status"] === "Aguardando" &&
        !empty($coleta["data_liberacao"])
    ) {

        $dataLiberacao = date(
            "Y-m-d",
            strtotime($coleta["data_liberacao"])
        );

        $dataHoje = date("Y-m-d");

        if ($dataLiberacao < $dataHoje) {

            $coletaAtrasada = true;

            $dataLiberacaoObj = new DateTime(
                $dataLiberacao
            );

            $dataHojeObj = new DateTime(
                $dataHoje
            );

            $intervalo = $dataLiberacaoObj->diff(
                $dataHojeObj
            );

            $diasAtrasoColeta =
                $intervalo->days;
        }
    }


    /*
     * Guarda as informações da situação
     * junto com os dados da coleta.
     */
    $coleta["coleta_atrasada"] =
        $coletaAtrasada;

    $coleta["dias_atraso"] =
        $diasAtrasoColeta;


    /*
     * Conta quantas coletas estão atrasadas.
     */
    if ($coleta["coleta_atrasada"]) {

        $atrasadasColeta++;
    }


    /*
     * Guarda a coleta no array principal.
     */
    $coletas[] = $coleta;


    /*
     * Conta coletas que ainda aguardam retirada.
     */
    if ($coleta["coleta_status"] === "Aguardando") {

        $prontas++;
    }


    /*
     * Conta coletas já realizadas.
     */
    if ($coleta["coleta_status"] === "Coletado") {

        $coletadas++;
    }
}


/* =========================================================
   CONTAR PRÓXIMAS PRODUÇÕES

   Uma produção continua sendo "próxima" enquanto ainda
   existir quantidade que a terceirizada não liberou.
   ========================================================= */

$previstas = 0;

foreach ($producoes as $producao) {

    $restanteLiberar =
        $producao["quantidade"]
        -
        $producao["quantidade_liberada"];

    if (
        $restanteLiberar > 0 &&
        $producao["coletado"] !== "Sim"
    ) {
        $previstas++;
    }
}


/* =========================================================
   FUNÇÕES
   ========================================================= */

function formatarDataColeta($data)
{
    if (
        empty($data) ||
        $data === "0000-00-00" ||
        $data === "0000-00-00 00:00:00"
    ) {
        return "-";
    }

    return date(
        "d/m/Y",
        strtotime($data)
    );
}


function formatarDataHoraColeta($data)
{
    if (
        empty($data) ||
        $data === "0000-00-00 00:00:00"
    ) {
        return "-";
    }

    return date(
        "d/m/Y \à\s H:i",
        strtotime($data)
    );
}


/* =========================================================
   MONTAR ENDEREÇO
   ========================================================= */

function montarEnderecoColeta($producao)
{
    $partes = [];

    if (!empty($producao["logradouro"])) {

        $logradouro =
            $producao["logradouro"];

        if (!empty($producao["numero"])) {

            $logradouro .=
                ", " .
                $producao["numero"];
        }

        $partes[] = $logradouro;
    }

    if (!empty($producao["complemento"])) {

        $partes[] =
            $producao["complemento"];
    }

    if (!empty($producao["bairro"])) {

        $partes[] =
            $producao["bairro"];
    }

    $cidadeUf = "";

    if (!empty($producao["cidade"])) {

        $cidadeUf =
            $producao["cidade"];
    }

    if (!empty($producao["uf"])) {

        if ($cidadeUf !== "") {
            $cidadeUf .= " - ";
        }

        $cidadeUf .=
            $producao["uf"];
    }

    if ($cidadeUf !== "") {

        $partes[] =
            $cidadeUf;
    }

    if (!empty($producao["cep"])) {

        $partes[] =
            "CEP " .
            $producao["cep"];
    }

    if (empty($partes)) {

        return "Endereço não informado";
    }

    return implode(
        " • ",
        $partes
    );
}


/* =========================================================
   ENDEREÇO GOOGLE MAPS
   ========================================================= */

function montarEnderecoMaps($producao)
{
    $partes = [];

    if (!empty($producao["logradouro"])) {

        $partes[] =
            $producao["logradouro"];
    }

    if (!empty($producao["numero"])) {

        $partes[] =
            $producao["numero"];
    }

    if (!empty($producao["bairro"])) {

        $partes[] =
            $producao["bairro"];
    }

    if (!empty($producao["cidade"])) {

        $partes[] =
            $producao["cidade"];
    }

    if (!empty($producao["uf"])) {

        $partes[] =
            $producao["uf"];
    }

    if (!empty($producao["cep"])) {

        $partes[] =
            $producao["cep"];
    }

    $partes[] = "Brasil";

    return implode(
        ", ",
        $partes
    );
}


/* =========================================================
   LINK GOOGLE MAPS
   ========================================================= */

function gerarLinkMaps($producao)
{
    if (
        empty($producao["logradouro"]) &&
        empty($producao["cep"])
    ) {
        return "";
    }

    $endereco =
        montarEnderecoMaps(
            $producao
        );

    return
        "https://www.google.com/maps/dir/?api=1&destination="
        . rawurlencode($endereco);
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

    <title>
        Cronex - Portal do Coletor
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


        <!-- =====================================================
         CABEÇALHO MOBILE
         ===================================================== -->

        <header class="portal-mobile-header">

            <div class="portal-mobile-brand">

                <strong>
                    CRONEX
                </strong>

                <span>
                    Portal do Coletor
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
                    Portal do Coletor
                </span>

            </div>

            <nav class="menu">

                <a
                    href="coletas.php"
                    class="active">

                    <i class="fa-solid fa-truck"></i>

                    <span>
                        Coletas
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
                        Coletas
                    </h1>

                    <p>
                        Veja rapidamente onde existem peças para coletar.
                    </p>

                </div>

                <div class="user-box">

                    <span>

                        <?= htmlspecialchars(
                            $_SESSION["nome"] ?? "Coletor"
                        ) ?>

                    </span>

                </div>

            </header>


            <!-- =================================================
             MENSAGENS
             ================================================= -->

            <?php if ($sucesso === "coletado") { ?>

                <div class="alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    Coleta registrada com sucesso.

                </div>

            <?php } ?>


            <?php if ($erro === "nao_pronto") { ?>

                <div class="alert-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    Esta liberação ainda não está disponível para coleta.

                </div>

            <?php } ?>


            <?php if ($erro === "ja_coletado") { ?>

                <div class="alert-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    Esta coleta já foi registrada.

                </div>

            <?php } ?>


            <?php if (
                $erro !== "" &&
                $erro !== "nao_pronto" &&
                $erro !== "ja_coletado"
            ) { ?>

                <div class="alert-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    Não foi possível realizar a operação.

                </div>

            <?php } ?>


            <!-- =================================================
             RESUMO
             ================================================= -->

            <section class="portal-resumo">

                <div class="portal-resumo-card destaque">

                    <div class="portal-resumo-icon">

                        <i class="fa-solid fa-box-open"></i>

                    </div>

                    <div>

                        <?php if ($atrasadasColeta > 0) { ?>

                            <span>
                                Coletas atrasadas
                            </span>

                            <strong>
                                <?= $atrasadasColeta ?>
                            </strong>

                            <small>
                                <?= $atrasadasColeta === 1
                                    ? "coleta precisa ser realizada"
                                    : "coletas precisam ser realizadas" ?>
                            </small>

                        <?php } else { ?>

                            <span>
                                Prontas para coleta
                            </span>

                            <strong>
                                <?= $prontas ?>
                            </strong>

                            <small>

                                <?= $prontas === 1
                                    ? "coleta disponível agora"
                                    : "coletas disponíveis agora" ?>

                            </small>

                        <?php } ?>

                    </div>

                </div>


                <div class="portal-resumo-card">

                    <div class="portal-resumo-icon">

                        <i class="fa-solid fa-calendar-days"></i>

                    </div>

                    <div>

                        <span>
                            Próximas
                        </span>

                        <strong>
                            <?= $previstas ?>
                        </strong>

                        <small>
                            Produções ainda em andamento
                        </small>

                    </div>

                </div>


                <div class="portal-resumo-card">

                    <div class="portal-resumo-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div>

                        <span>
                            Coletadas
                        </span>

                        <strong>
                            <?= $coletadas ?>
                        </strong>

                        <small>
                            Coletas já registradas
                        </small>

                    </div>

                </div>

            </section>


            <!-- =================================================
             PRONTAS PARA COLETA
             ================================================= -->

            <section class="portal-section">

                <div class="portal-section-title">

                    <div>

                        <h2>

                            <?php if ($atrasadasColeta > 0) { ?>

                                <i class="fa-solid fa-triangle-exclamation"></i>

                                Coletas com atenção

                            <?php } else { ?>

                                <i class="fa-solid fa-box-open"></i>

                                Prontas para coleta

                            <?php } ?>

                        </h2>

                        <p>

                            <?php if ($atrasadasColeta > 0) { ?>

                                Existem coletas atrasadas. Priorize estas retiradas.

                            <?php } else { ?>

                                Priorize estas peças.

                            <?php } ?>

                        </p>

                    </div>

                </div>


                <div class="portal-producao-grid">

                    <?php

                    $temProntas = false;

                    foreach ($coletas as $coleta) {

                        if (
                            $coleta["coleta_status"]
                            !== "Aguardando"
                        ) {

                            continue;
                        }

                        $temProntas = true;

                        $endereco =
                            montarEnderecoColeta(
                                $coleta
                            );

                        $linkMaps =
                            gerarLinkMaps(
                                $coleta
                            );

                        $quantidadeTotal =
                            intval(
                                $coleta["quantidade_total"]
                            );

                        $quantidadeColeta =
                            intval(
                                $coleta["quantidade_coleta"]
                            );

                        $quantidadeColetada =
                            intval(
                                $coleta["quantidade_coletada"]
                            );

                        $restanteDepoisColeta =
                            max(
                                0,
                                $quantidadeTotal
                                    -
                                    $quantidadeColetada
                                    -
                                    $quantidadeColeta
                            );

                    ?>

                        <div
                            class="portal-producao-card destaque-coleta">


                            <?php if ($coleta["coleta_atrasada"]) { ?>

                                <div class="alerta-atraso-coleta">

                                    <i class="fa-solid fa-triangle-exclamation"></i>

                                    <div>

                                        <strong>
                                            Coleta atrasada
                                        </strong>

                                        <span>
                                            Esta coleta foi liberada em
                                            <?= formatarDataColeta(
                                                $coleta["data_liberacao"]
                                            ) ?>
                                            e ainda não foi realizada.
                                        </span>

                                        <strong>

                                            <?= $coleta["dias_atraso"] ?>

                                            <?= $coleta["dias_atraso"] == 1
                                                ? "dia de atraso"
                                                : "dias de atraso" ?>

                                        </strong>

                                    </div>

                                </div>

                            <?php } ?>


                            <div class="portal-producao-topo">

                                <div>

                                    <span class="portal-codigo">

                                        <?= htmlspecialchars(
                                            $coleta["codigo"]
                                        ) ?>

                                    </span>

                                    <h3>

                                        <?= htmlspecialchars(
                                            $coleta["razao_social"]
                                        ) ?>

                                    </h3>

                                </div>


                                <?php if ($coleta["coleta_atrasada"]) { ?>

                                    <span class="portal-badge atraso">

                                        <i class="fa-solid fa-triangle-exclamation"></i>

                                        Prioridade

                                    </span>

                                <?php } else { ?>

                                    <span class="portal-badge sucesso">

                                        <i class="fa-solid fa-box-open"></i>

                                        <?= number_format(
                                            $quantidadeColeta,
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                        peças prontas

                                    </span>

                                <?php } ?>

                            </div>


                            <!-- INFORMAÇÕES -->

                            <div class="portal-info-grid">

                                <div>

                                    <span>
                                        Produto
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $coleta["produto_nome"]
                                        ) ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Disponíveis nesta coleta
                                    </span>

                                    <strong>

                                        <?= number_format(
                                            $quantidadeColeta,
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                        peças

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Produção total
                                    </span>

                                    <strong>

                                        <?= number_format(
                                            $quantidadeTotal,
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                        peças

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Já coletadas anteriormente
                                    </span>

                                    <strong>

                                        <?= number_format(
                                            $quantidadeColetada,
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                        peças

                                    </strong>

                                </div>

                            </div>


                            <!-- ENDEREÇO -->

                            <div class="coletor-endereco">

                                <i class="fa-solid fa-location-dot"></i>

                                <div>

                                    <span>
                                        Local da coleta
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $endereco
                                        ) ?>

                                    </strong>

                                </div>

                            </div>


                            <!-- GOOGLE MAPS -->

                            <?php if ($linkMaps !== "") { ?>

                                <a
                                    href="<?= htmlspecialchars(
                                                $linkMaps,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="coletor-btn-rota">

                                    <i class="fa-solid fa-route"></i>

                                    <span>
                                        Abrir rota no Google Maps
                                    </span>

                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>

                                </a>

                            <?php } ?>


                            <!-- RESPONSÁVEL -->

                            <?php if (
                                !empty($coleta["responsavel"])
                            ) { ?>

                                <div class="coletor-contato">

                                    <i class="fa-solid fa-user"></i>

                                    <span>

                                        Responsável:

                                        <?= htmlspecialchars(
                                            $coleta["responsavel"]
                                        ) ?>

                                    </span>

                                </div>

                            <?php } ?>


                            <!-- TELEFONE -->

                            <?php if (
                                !empty($coleta["telefone"])
                            ) { ?>

                                <div class="coletor-contato">

                                    <i class="fa-solid fa-phone"></i>

                                    <span>

                                        Telefone:

                                        <?= htmlspecialchars(
                                            $coleta["telefone"]
                                        ) ?>

                                    </span>

                                </div>

                            <?php } ?>


                            <!-- DATA DA LIBERAÇÃO -->

                            <div class="coletor-previsao">

                                <i class="fa-solid fa-clock"></i>

                                <div>

                                    <span>
                                        Peças liberadas em
                                    </span>

                                    <strong>

                                        <?= formatarDataHoraColeta(
                                            $coleta["data_liberacao"]
                                        ) ?>

                                    </strong>

                                </div>

                            </div>


                            <!-- SITUAÇÃO APÓS ESTA COLETA -->

                            <div class="coletor-previsao">

                                <i class="fa-solid fa-boxes-stacked"></i>

                                <div>

                                    <span>
                                        Restarão na produção após esta coleta
                                    </span>

                                    <strong>

                                        <?= number_format(
                                            $restanteDepoisColeta,
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                        peças

                                    </strong>

                                </div>

                            </div>


                            <!-- AÇÕES -->

                            <div class="portal-acoes">

                                <button
                                    type="button"
                                    class="portal-btn pronto btn-abrir-coleta"

                                    data-id="<?= intval(
                                                    $coleta["coleta_id"]
                                                ) ?>"

                                    data-codigo="<?= htmlspecialchars(
                                                        $coleta["codigo"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"

                                    data-empresa="<?= htmlspecialchars(
                                                        $coleta["razao_social"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"

                                    data-produto="<?= htmlspecialchars(
                                                        $coleta["produto_nome"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"

                                    data-quantidade="<?= intval(
                                                            $quantidadeColeta
                                                        ) ?>">

                                    <i class="fa-solid fa-truck"></i>

                                    Registrar coleta de

                                    <?= number_format(
                                        $quantidadeColeta,
                                        0,
                                        ",",
                                        "."
                                    ) ?>

                                    peças

                                </button>

                            </div>

                        </div>

                    <?php } ?>


                    <?php if (!$temProntas) { ?>

                        <div class="portal-vazio">

                            <i class="fa-solid fa-circle-check"></i>

                            <strong>
                                Nenhuma coleta disponível agora.
                            </strong>

                            <span>
                                Novas coletas aparecerão aqui quando a terceirizada liberar peças.
                            </span>

                        </div>

                    <?php } ?>

                </div>

            </section>


            <!-- =================================================
             PRÓXIMAS COLETAS
             ================================================= -->

            <?php if ($previstas > 0) { ?>

                <section class="portal-section">

                    <div class="portal-section-title">

                        <div>

                            <h2>

                                <i class="fa-solid fa-calendar-days"></i>

                                Próximas coletas

                            </h2>

                            <p>
                                Produções que ainda possuem peças em fabricação.
                            </p>

                        </div>

                    </div>


                    <div class="portal-producao-grid">

                        <?php foreach (
                            $producoes as $producao
                        ) { ?>

                            <?php

                            $quantidadeTotal =
                                intval(
                                    $producao["quantidade"]
                                );

                            $quantidadeLiberada =
                                intval(
                                    $producao["quantidade_liberada"]
                                );

                            $quantidadeColetada =
                                intval(
                                    $producao["quantidade_coletada"]
                                );

                            $restanteLiberar =
                                max(
                                    0,
                                    $quantidadeTotal
                                        -
                                        $quantidadeLiberada
                                );

                            if (
                                $restanteLiberar <= 0 ||
                                $producao["coletado"] === "Sim"
                            ) {
                                continue;
                            }


                            $previsaoAtual =
                                !empty($producao["nova_previsao"])
                                ? $producao["nova_previsao"]
                                : $producao["previsao_entrega"];


                            $endereco =
                                montarEnderecoColeta(
                                    $producao
                                );

                            $linkMaps =
                                gerarLinkMaps(
                                    $producao
                                );

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
                                                $producao["razao_social"]
                                            ) ?>

                                        </h3>

                                    </div>


                                    <span class="portal-badge info">

                                        <i class="fa-solid fa-shirt"></i>

                                        Em produção

                                    </span>

                                </div>


                                <div class="portal-info-grid">

                                    <div>

                                        <span>
                                            Produto
                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $producao["produto_nome"]
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Produção total
                                        </span>

                                        <strong>

                                            <?= number_format(
                                                $quantidadeTotal,
                                                0,
                                                ",",
                                                "."
                                            ) ?>

                                            peças

                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Já liberadas
                                        </span>

                                        <strong>

                                            <?= number_format(
                                                $quantidadeLiberada,
                                                0,
                                                ",",
                                                "."
                                            ) ?>

                                            peças

                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Ainda em produção
                                        </span>

                                        <strong>

                                            <?= number_format(
                                                $restanteLiberar,
                                                0,
                                                ",",
                                                "."
                                            ) ?>

                                            peças

                                        </strong>

                                    </div>

                                </div>


                                <div class="coletor-previsao">

                                    <i class="fa-solid fa-calendar-check"></i>

                                    <div>

                                        <span>
                                            Previsão atual
                                        </span>

                                        <strong>

                                            <?= formatarDataColeta(
                                                $previsaoAtual
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>


                                <div class="coletor-endereco">

                                    <i class="fa-solid fa-location-dot"></i>

                                    <div>

                                        <span>
                                            Local previsto para coleta
                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $endereco
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>


                                <?php if (
                                    $linkMaps !== ""
                                ) { ?>

                                    <a
                                        href="<?= htmlspecialchars(
                                                    $linkMaps,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="coletor-btn-rota">

                                        <i class="fa-solid fa-route"></i>

                                        <span>
                                            Ver local no Google Maps
                                        </span>

                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>

                                    </a>

                                <?php } ?>

                            </div>

                        <?php } ?>

                    </div>

                </section>

            <?php } ?>


            <!-- =================================================
             COLETAS REALIZADAS
             ================================================= -->

            <?php if ($coletadas > 0) { ?>

                <section class="portal-section">

                    <div class="portal-section-title">

                        <div>

                            <h2>

                                <i class="fa-solid fa-circle-check"></i>

                                Coletas realizadas

                            </h2>

                            <p>
                                Retiradas já registradas pelo coletor.
                            </p>

                        </div>

                    </div>


                    <div class="portal-producao-grid">

                        <?php foreach (
                            $coletas as $coleta
                        ) { ?>

                            <?php

                            if (
                                $coleta["coleta_status"]
                                !== "Coletado"
                            ) {
                                continue;
                            }

                            ?>


                            <div class="portal-producao-card finalizado">

                                <div class="portal-producao-topo">

                                    <div>

                                        <span class="portal-codigo">

                                            <?= htmlspecialchars(
                                                $coleta["codigo"]
                                            ) ?>

                                        </span>

                                        <h3>

                                            <?= htmlspecialchars(
                                                $coleta["razao_social"]
                                            ) ?>

                                        </h3>

                                    </div>


                                    <span class="portal-badge sucesso">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Coletado

                                    </span>

                                </div>


                                <div class="portal-info-grid">

                                    <div>

                                        <span>
                                            Produto
                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $coleta["produto_nome"]
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Quantidade coletada
                                        </span>

                                        <strong>

                                            <?= number_format(
                                                intval(
                                                    $coleta["quantidade_coleta"]
                                                ),
                                                0,
                                                ",",
                                                "."
                                            ) ?>

                                            peças

                                        </strong>

                                    </div>

                                </div>


                                <div class="portal-status-final">

                                    <i class="fa-solid fa-truck-fast"></i>

                                    <div>

                                        <strong>
                                            Coleta realizada
                                        </strong>

                                        <span>

                                            <?= !empty($coleta["data_coleta"])
                                                ? "Registrada em " .
                                                formatarDataHoraColeta(
                                                    $coleta["data_coleta"]
                                                )
                                                : "Coleta registrada" ?>

                                        </span>

                                    </div>

                                </div>


                                <div class="coletor-endereco">

                                    <i class="fa-solid fa-location-dot"></i>

                                    <div>

                                        <span>
                                            Local da coleta
                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                montarEnderecoColeta(
                                                    $coleta
                                                )
                                            ) ?>

                                        </strong>

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
     MODAL - CONFIRMAR COLETA
     ========================================================= -->

    <div
        class="modal-overlay"
        id="modalColeta"
        aria-hidden="true">

        <div
            class="modal-confirmacao"
            role="dialog"
            aria-modal="true">

            <button
                type="button"
                class="modal-fechar"
                id="fecharModalColeta">

                <i class="fa-solid fa-xmark"></i>

            </button>


            <div class="modal-confirmacao-icon">

                <i class="fa-solid fa-truck"></i>

            </div>


            <h2>
                Registrar coleta
            </h2>


            <p>
                Confirme a retirada das peças liberadas pela terceirizada.
            </p>


            <div class="portal-info-grid">

                <div>

                    <span>
                        Produção
                    </span>

                    <strong id="modalCodigo">
                        -
                    </strong>

                </div>


                <div>

                    <span>
                        Terceirizada
                    </span>

                    <strong id="modalEmpresa">
                        -
                    </strong>

                </div>


                <div>

                    <span>
                        Produto
                    </span>

                    <strong id="modalProduto">
                        -
                    </strong>

                </div>


                <div>

                    <span>
                        Quantidade desta coleta
                    </span>

                    <strong id="modalQuantidade">
                        -
                    </strong>

                </div>

            </div>


            <form
                action="registrar_coleta.php"
                method="POST"
                id="formConfirmarColeta">

                <!--
                IMPORTANTE:
                Agora este campo contém o ID da tabela
                producao_coletas, e não o ID da produção.
            -->

                <input
                    type="hidden"
                    name="coleta_id"
                    id="modalProducaoId"
                    value="">


                <div class="modal-acoes">

                    <button
                        type="button"
                        class="portal-btn alterar"
                        id="cancelarModalColeta">

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="portal-btn pronto">

                        <i class="fa-solid fa-circle-check"></i>

                        Confirmar coleta

                    </button>

                </div>

            </form>

        </div>

    </div>


    <script src="../js/script.js?v=<?= time() ?>"></script>

</body>

</html>