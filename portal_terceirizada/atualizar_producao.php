<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Terceirizada");

$conexao = require "../includes/conexao.php";

$terceirizado_id = isset($_SESSION["terceirizado_id"])
    ? intval($_SESSION["terceirizado_id"])
    : 0;

$producao_id = isset($_GET["id"])
    ? intval($_GET["id"])
    : 0;

if ($terceirizado_id <= 0) {
    header("Location: ../logout.php");
    exit;
}

if ($producao_id <= 0) {
    header("Location: producoes.php?erro=producao");
    exit;
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
        pr.data_confirmacao_prazo,
        pr.pecas_prontas,
        pr.data_pecas_prontas,
        pr.coletado,
        pr.data_coleta,
        pr.status,
        pr.observacoes,

        p.codigo AS produto_codigo,
        p.nome AS produto_nome,
        p.linha,
        p.categoria,
        p.tamanhos,

        t.codigo AS terceirizado_codigo,
        t.razao_social

    FROM producao pr

    INNER JOIN produtos p
        ON p.id = pr.produto_id

    INNER JOIN terceirizados t
        ON t.id = pr.terceirizado_id

    WHERE pr.id = ?
    AND pr.terceirizado_id = ?

    LIMIT 1
";

$stmt = mysqli_prepare(
    $conexao,
    $sql
);

if (!$stmt) {
    header("Location: producoes.php?erro=banco");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $producao_id,
    $terceirizado_id
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) !== 1) {

    mysqli_stmt_close($stmt);

    header("Location: producoes.php?erro=producao");
    exit;
}

$producao = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

if ($producao["coletado"] === "Sim") {
    header("Location: producoes.php?erro=finalizada");
    exit;
}

function formatarDataAtualizacao($data)
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

$previsaoAtual = !empty($producao["nova_previsao"])
    ? $producao["nova_previsao"]
    : $producao["previsao_entrega"];

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Cronex - Atualizar Produção
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

 <body>

<div class="app">

<header class="portal-mobile-header">

    <div class="portal-mobile-brand">

        <strong>CRONEX</strong>

        <span>
            Portal da Terceirizada
        </span>

    </div>

    <a
        href="../logout.php"
        class="portal-mobile-logout">

        <i class="fa-solid fa-right-from-bracket"></i>

        <span>Sair</span>

    </a>

</header>

    <aside class="sidebar">

        <div class="logo">

            <h2>CRONEX</h2>

            <span>
                Portal da Terceirizada
            </span>

        </div>

        <nav class="menu">

            <a href="producoes.php">

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

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>
                    Atualizar Produção
                </h1>

                <p>
                    Informe a situação atual deste pedido.
                </p>

            </div>

            <div class="user-box">

                <span>
                    <?= htmlspecialchars(
                        $producao["razao_social"]
                    ) ?>
                </span>

            </div>

        </header>

        <?php if (isset($_GET["erro"])) { ?>

            <div class="alert-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php if ($_GET["erro"] === "campos") { ?>

                    Verifique os campos informados.

                <?php } elseif ($_GET["erro"] === "data") { ?>

                    A nova previsão deve ser posterior à previsão atual.

                <?php } elseif ($_GET["erro"] === "motivo") { ?>

                    Informe o motivo do atraso.

                <?php } else { ?>

                    Não foi possível atualizar a produção.

                <?php } ?>

            </div>

        <?php } ?>

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        <?= htmlspecialchars(
                            $producao["codigo"]
                        ) ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars(
                            $producao["produto_codigo"]
                        ) ?>

                        -

                        <?= htmlspecialchars(
                            $producao["produto_nome"]
                        ) ?>
                    </p>

                </div>

                <a
                    href="producoes.php"
                    class="btn-secondary">

                    <i class="fa-solid fa-arrow-left"></i>

                    Voltar

                </a>

            </div>

            <div class="cards">

                <div class="card">

                    <p>
                        Quantidade
                    </p>

                    <h2>
                        <?= number_format(
                            intval($producao["quantidade"]),
                            0,
                            ",",
                            "."
                        ) ?>
                    </h2>

                    <span>
                        peças
                    </span>

                </div>

                <div class="card">

                    <p>
                        Data de Envio
                    </p>

                    <h2>
                        <?= formatarDataAtualizacao(
                            $producao["data_envio"]
                        ) ?>
                    </h2>

                    <span>
                        Início do pedido
                    </span>

                </div>

                <div class="card">

                    <p>
                        Previsão Original
                    </p>

                    <h2>
                        <?= formatarDataAtualizacao(
                            $producao["previsao_entrega"]
                        ) ?>
                    </h2>

                    <span>
                        Calculada pelo CRONEX
                    </span>

                </div>

                <div class="card">

                    <p>
                        Previsão Atual
                    </p>

                    <h2>
                        <?= formatarDataAtualizacao(
                            $previsaoAtual
                        ) ?>
                    </h2>

                    <span>

                        <?php if (
                            !empty($producao["nova_previsao"])
                        ) { ?>

                            Atualizada pela terceirizada

                        <?php } else { ?>

                            Previsão original

                        <?php } ?>

                    </span>

                </div>

            </div>

            <form
                action="salvar_atualizacao.php"
                method="POST"
                class="form-cronex"
                id="formAtualizacao">

                <input
                    type="hidden"
                    name="producao_id"
                    value="<?= intval(
                        $producao["id"]
                    ) ?>">

                <div class="form-group">

                    <label for="confirmacao_prazo">
                        O pedido ficará pronto na previsão informada? *
                    </label>

                    <select
                        name="confirmacao_prazo"
                        id="confirmacao_prazo"
                        required>

                        <option value="">
                            Selecione
                        </option>

                        <option
                            value="No prazo"
                            <?= $producao["confirmacao_prazo"] === "No prazo"
                                ? "selected"
                                : "" ?>>

                            Sim, ficará pronto no prazo

                        </option>

                        <option
                            value="Atraso"
                            <?= $producao["confirmacao_prazo"] === "Atraso"
                                ? "selected"
                                : "" ?>>

                            Não, haverá atraso

                        </option>

                    </select>

                </div>

                <div
                    id="camposAtraso"
                    style="display: none;">

                    <div class="form-group">

                        <label for="motivo_atraso">
                            Motivo do atraso *
                        </label>

                        <textarea
                            name="motivo_atraso"
                            id="motivo_atraso"
                            rows="4"
                            placeholder="Informe o motivo pelo qual o pedido não ficará pronto no prazo..."><?= htmlspecialchars(
                                $producao["motivo_atraso"] ?? ""
                            ) ?></textarea>

                    </div>

                    <div class="form-group">

                        <label for="nova_previsao">
                            Nova previsão de entrega *
                        </label>

                        <input
                            type="date"
                            name="nova_previsao"
                            id="nova_previsao"
                            min="<?= htmlspecialchars(
                                $previsaoAtual
                            ) ?>"
                            value="<?= htmlspecialchars(
                                $producao["nova_previsao"] ?? ""
                            ) ?>">

                    </div>

                </div>

                <div class="form-group">

                    <label for="pecas_prontas">
                        Situação das peças *
                    </label>

                    <select
                        name="pecas_prontas"
                        id="pecas_prontas"
                        required>

                        <option
                            value="Nao"
                            <?= $producao["pecas_prontas"] !== "Sim"
                                ? "selected"
                                : "" ?>>

                            Ainda estão em produção

                        </option>

                        <option
                            value="Sim"
                            <?= $producao["pecas_prontas"] === "Sim"
                                ? "selected"
                                : "" ?>>

                            Peças prontas para coleta

                        </option>

                    </select>

                </div>

                <?php if (
                    $producao["pecas_prontas"] === "Sim" &&
                    !empty($producao["data_pecas_prontas"])
                ) { ?>

                    <div class="alert-success">

                        <i class="fa-solid fa-circle-check"></i>

                        Peças informadas como prontas em

                        <strong>
                            <?= formatarDataAtualizacao(
                                $producao["data_pecas_prontas"]
                            ) ?>
                        </strong>.

                    </div>

                <?php } ?>

                <div class="form-actions">

                    <a
                        href="producoes.php"
                        class="btn-secondary">

                        Cancelar

                    </a>

                    <button
                        type="submit"
                        class="btn-primary">

                        <i class="fa-solid fa-floppy-disk"></i>

                        Salvar Atualização

                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

<script>

const confirmacaoPrazo =
    document.getElementById("confirmacao_prazo");

const camposAtraso =
    document.getElementById("camposAtraso");

const motivoAtraso =
    document.getElementById("motivo_atraso");

const novaPrevisao =
    document.getElementById("nova_previsao");

function atualizarCamposAtraso() {

    if (confirmacaoPrazo.value === "Atraso") {

        camposAtraso.style.display = "block";

        motivoAtraso.required = true;
        novaPrevisao.required = true;

    } else {

        camposAtraso.style.display = "none";

        motivoAtraso.required = false;
        novaPrevisao.required = false;
    }
}

confirmacaoPrazo.addEventListener(
    "change",
    atualizarCamposAtraso
);

atualizarCamposAtraso();

</script>

</body>

</html>