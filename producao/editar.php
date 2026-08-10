<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("Location: listar.php");
    exit;
}

$id = intval($_GET["id"]);

if ($id <= 0) {
    header("Location: listar.php");
    exit;
}

$sql = "
    SELECT
        pr.*,

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
                WHERE
                    pc.producao_id = pr.id
                    AND pc.status = 'Coletado'
            ),
            0
        ) AS quantidade_coletada

    FROM producao pr

    WHERE pr.id = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    header("Location: listar.php?erro=1");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) === 0) {

    mysqli_stmt_close($stmt);

    header("Location: listar.php");
    exit;
}

$producao = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

$quantidadeTotal =
    intval($producao["quantidade"]);

$quantidadeLiberada =
    intval($producao["quantidade_liberada"]);

$quantidadeColetada =
    intval($producao["quantidade_coletada"]);

$possuiLiberacao =
    $quantidadeLiberada > 0;

$finalizada =
    $quantidadeTotal > 0 &&
    $quantidadeColetada >= $quantidadeTotal;

$sqlProdutos = "
    SELECT
        id,
        codigo,
        nome,
        status
    FROM produtos
    WHERE status = 'Ativo'
       OR id = ?
    ORDER BY nome ASC
";

$stmtProdutos = mysqli_prepare(
    $conexao,
    $sqlProdutos
);

mysqli_stmt_bind_param(
    $stmtProdutos,
    "i",
    $producao["produto_id"]
);

mysqli_stmt_execute($stmtProdutos);

$resultadoProdutos =
    mysqli_stmt_get_result($stmtProdutos);

$sqlTerceirizados = "
    SELECT
        id,
        codigo,
        razao_social,
        status
    FROM terceirizados
    WHERE status = 'Ativo'
       OR id = ?
    ORDER BY razao_social ASC
";

$stmtTerceirizados = mysqli_prepare(
    $conexao,
    $sqlTerceirizados
);

mysqli_stmt_bind_param(
    $stmtTerceirizados,
    "i",
    $producao["terceirizado_id"]
);

mysqli_stmt_execute($stmtTerceirizados);

$resultadoTerceirizados =
    mysqli_stmt_get_result($stmtTerceirizados);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cronex - Editar Produção</title>

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

            <h1>Produção</h1>

            <p>
                Edite as informações da ordem de produção.
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
                    Editar Produção
                </h2>

                <p>
                    <?= htmlspecialchars(
                        $producao["codigo"]
                    ) ?>
                </p>

            </div>

            <a
                href="listar.php"
                class="btn-secondary">

                <i class="fa-solid fa-arrow-left"></i>

                Voltar

            </a>

        </div>

        <?php if (isset($_GET["erro"])) { ?>

            <div class="alert-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php if ($_GET["erro"] === "bloqueado") { ?>

                    Produto, terceirizada e quantidade não podem ser alterados porque esta produção já possui peças liberadas.

                <?php } elseif ($_GET["erro"] === "data") { ?>

                    A previsão de entrega não pode ser anterior à data de envio.

                <?php } else { ?>

                    Não foi possível atualizar a produção. Verifique os dados informados.

                <?php } ?>

            </div>

        <?php } ?>

        <?php if ($finalizada) { ?>

            <div class="alert-success">

                <i class="fa-solid fa-circle-check"></i>

                Esta produção está finalizada.
                <?= $quantidadeColetada ?> de
                <?= $quantidadeTotal ?> peças foram coletadas.

            </div>

        <?php } elseif ($possuiLiberacao) { ?>

            <div class="alert-success">

                <i class="fa-solid fa-lock"></i>

                Esta produção já possui peças liberadas.
                Produto, terceirizada e quantidade não podem mais ser alterados.

            </div>

        <?php } ?>

        <form
            action="atualizar.php"
            method="POST"
            class="form-cronex">

            <input
                type="hidden"
                name="id"
                value="<?= intval(
                    $producao["id"]
                ) ?>">

            <div class="form-group">

                <label>
                    Código da Produção
                </label>

                <input
                    type="text"
                    value="<?= htmlspecialchars(
                        $producao["codigo"]
                    ) ?>"
                    disabled>

            </div>

            <div class="form-group">

                <label for="produto_id">
                    Produto *
                </label>

                <?php if ($possuiLiberacao) { ?>

                    <?php while (
                        $produto =
                            mysqli_fetch_assoc(
                                $resultadoProdutos
                            )
                    ) { ?>

                        <?php if (
                            intval($produto["id"])
                            === intval(
                                $producao["produto_id"]
                            )
                        ) { ?>

                            <input
                                type="text"
                                value="<?= htmlspecialchars(
                                    $produto["codigo"]
                                    . " - "
                                    . $produto["nome"]
                                ) ?>"
                                disabled>

                            <input
                                type="hidden"
                                name="produto_id"
                                value="<?= intval(
                                    $produto["id"]
                                ) ?>">

                        <?php } ?>

                    <?php } ?>

                <?php } else { ?>

                    <select
                        name="produto_id"
                        id="produto_id"
                        required>

                        <option value="">
                            Selecione o produto
                        </option>

                        <?php while (
                            $produto =
                                mysqli_fetch_assoc(
                                    $resultadoProdutos
                                )
                        ) { ?>

                            <option
                                value="<?= intval(
                                    $produto["id"]
                                ) ?>"

                                <?= intval(
                                    $produto["id"]
                                ) === intval(
                                    $producao["produto_id"]
                                )
                                    ? "selected"
                                    : ""
                                ?>>

                                <?= htmlspecialchars(
                                    $produto["codigo"]
                                ) ?>

                                -

                                <?= htmlspecialchars(
                                    $produto["nome"]
                                ) ?>

                                <?php if (
                                    $produto["status"]
                                    !== "Ativo"
                                ) { ?>

                                    (Inativo)

                                <?php } ?>

                            </option>

                        <?php } ?>

                    </select>

                <?php } ?>

            </div>

            <div class="form-group">

                <label for="terceirizado_id">
                    Terceirizada *
                </label>

                <?php if ($possuiLiberacao) { ?>

                    <?php while (
                        $terceirizado =
                            mysqli_fetch_assoc(
                                $resultadoTerceirizados
                            )
                    ) { ?>

                        <?php if (
                            intval(
                                $terceirizado["id"]
                            )
                            === intval(
                                $producao[
                                    "terceirizado_id"
                                ]
                            )
                        ) { ?>

                            <input
                                type="text"
                                value="<?= htmlspecialchars(
                                    $terceirizado["codigo"]
                                    . " - "
                                    . $terceirizado[
                                        "razao_social"
                                    ]
                                ) ?>"
                                disabled>

                            <input
                                type="hidden"
                                name="terceirizado_id"
                                value="<?= intval(
                                    $terceirizado["id"]
                                ) ?>">

                        <?php } ?>

                    <?php } ?>

                <?php } else { ?>

                    <select
                        name="terceirizado_id"
                        id="terceirizado_id"
                        required>

                        <option value="">
                            Selecione a terceirizada
                        </option>

                        <?php while (
                            $terceirizado =
                                mysqli_fetch_assoc(
                                    $resultadoTerceirizados
                                )
                        ) { ?>

                            <option
                                value="<?= intval(
                                    $terceirizado["id"]
                                ) ?>"

                                <?= intval(
                                    $terceirizado["id"]
                                ) === intval(
                                    $producao[
                                        "terceirizado_id"
                                    ]
                                )
                                    ? "selected"
                                    : ""
                                ?>>

                                <?= htmlspecialchars(
                                    $terceirizado["codigo"]
                                ) ?>

                                -

                                <?= htmlspecialchars(
                                    $terceirizado[
                                        "razao_social"
                                    ]
                                ) ?>

                                <?php if (
                                    $terceirizado["status"]
                                    !== "Ativo"
                                ) { ?>

                                    (Inativo)

                                <?php } ?>

                            </option>

                        <?php } ?>

                    </select>

                <?php } ?>

            </div>

            <div class="form-group">

                <label for="quantidade">
                    Quantidade de Peças *
                </label>

                <input
                    type="number"
                    name="quantidade"
                    id="quantidade"
                    min="1"
                    value="<?= $quantidadeTotal ?>"
                    <?= $possuiLiberacao
                        ? "readonly"
                        : ""
                    ?>
                    required>

            </div>

            <?php if ($possuiLiberacao) { ?>

                <div class="form-group">

                    <label>
                        Peças Liberadas
                    </label>

                    <input
                        type="text"
                        value="<?= $quantidadeLiberada ?> de <?= $quantidadeTotal ?>"
                        disabled>

                </div>

                <div class="form-group">

                    <label>
                        Peças Coletadas
                    </label>

                    <input
                        type="text"
                        value="<?= $quantidadeColetada ?> de <?= $quantidadeTotal ?>"
                        disabled>

                </div>

            <?php } ?>

            <div class="form-group">

                <label for="data_envio">
                    Data de Envio *
                </label>

                <input
                    type="date"
                    name="data_envio"
                    id="data_envio"
                    value="<?= htmlspecialchars(
                        $producao["data_envio"]
                    ) ?>"
                    required>

            </div>

            <div class="form-group">

                <label for="previsao_entrega">
                    Previsão de Entrega *
                </label>

                <input
                    type="date"
                    name="previsao_entrega"
                    id="previsao_entrega"
                    value="<?= htmlspecialchars(
                        $producao["previsao_entrega"]
                    ) ?>"
                    required>

            </div>

            <div class="form-group">

                <label>
                    Situação Atual
                </label>

                <?php if ($finalizada) { ?>

                    <input
                        type="text"
                        value="Finalizada"
                        disabled>

                <?php } elseif ($possuiLiberacao) { ?>

                    <input
                        type="text"
                        value="Em produção / coleta parcial"
                        disabled>

                <?php } elseif (
                    empty(
                        $producao[
                            "confirmacao_prazo"
                        ]
                    )
                ) { ?>

                    <input
                        type="text"
                        value="Aguardando confirmação"
                        disabled>

                <?php } elseif (
                    $producao[
                        "confirmacao_prazo"
                    ] === "Atraso"
                ) { ?>

                    <input
                        type="text"
                        value="Atraso informado"
                        disabled>

                <?php } else { ?>

                    <input
                        type="text"
                        value="Em produção"
                        disabled>

                <?php } ?>

            </div>

            <div class="form-group">

                <label for="observacoes">
                    Observações
                </label>

                <textarea
                    name="observacoes"
                    id="observacoes"
                    rows="4"
                    placeholder="Informações adicionais sobre esta produção..."><?= htmlspecialchars(
                        $producao["observacoes"]
                        ?? ""
                    ) ?></textarea>

            </div>

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

                    Salvar Alterações

                </button>

            </div>

        </form>

    </section>

</main>

</div>

<?php

mysqli_stmt_close($stmtProdutos);

mysqli_stmt_close($stmtTerceirizados);

?>

</body>

</html>