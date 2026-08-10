<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$id = isset($_POST["id"])
    ? intval($_POST["id"])
    : 0;

$produto_id = isset($_POST["produto_id"])
    ? intval($_POST["produto_id"])
    : 0;

$terceirizado_id = isset($_POST["terceirizado_id"])
    ? intval($_POST["terceirizado_id"])
    : 0;

$quantidade = isset($_POST["quantidade"])
    ? intval($_POST["quantidade"])
    : 0;

$data_envio = isset($_POST["data_envio"])
    ? trim($_POST["data_envio"])
    : "";

$previsao_entrega = isset($_POST["previsao_entrega"])
    ? trim($_POST["previsao_entrega"])
    : "";

$observacoes = isset($_POST["observacoes"])
    ? trim($_POST["observacoes"])
    : "";

if ($id <= 0) {
    header("Location: listar.php?erro=1");
    exit;
}

if (
    $produto_id <= 0 ||
    $terceirizado_id <= 0 ||
    $quantidade <= 0 ||
    empty($data_envio) ||
    empty($previsao_entrega)
) {

    header(
        "Location: editar.php?id="
        . $id
        . "&erro=campos"
    );

    exit;
}

if ($previsao_entrega < $data_envio) {

    header(
        "Location: editar.php?id="
        . $id
        . "&erro=data"
    );

    exit;
}

$sqlProducao = "
    SELECT
        id,
        produto_id,
        terceirizado_id,
        quantidade
    FROM producao
    WHERE id = ?
    LIMIT 1
";

$stmtProducao = mysqli_prepare(
    $conexao,
    $sqlProducao
);

if (!$stmtProducao) {
    header("Location: listar.php?erro=1");
    exit;
}

mysqli_stmt_bind_param(
    $stmtProducao,
    "i",
    $id
);

mysqli_stmt_execute($stmtProducao);

$resultadoProducao =
    mysqli_stmt_get_result(
        $stmtProducao
    );

if (
    mysqli_num_rows(
        $resultadoProducao
    ) !== 1
) {

    mysqli_stmt_close(
        $stmtProducao
    );

    header("Location: listar.php?erro=1");
    exit;
}

$producaoAtual =
    mysqli_fetch_assoc(
        $resultadoProducao
    );

mysqli_stmt_close(
    $stmtProducao
);

$sqlLiberacoes = "
    SELECT
        COUNT(*) AS total_registros,
        COALESCE(
            SUM(quantidade),
            0
        ) AS total_liberado
    FROM producao_coletas
    WHERE producao_id = ?
";

$stmtLiberacoes = mysqli_prepare(
    $conexao,
    $sqlLiberacoes
);

if (!$stmtLiberacoes) {

    header(
        "Location: editar.php?id="
        . $id
        . "&erro=banco"
    );

    exit;
}

mysqli_stmt_bind_param(
    $stmtLiberacoes,
    "i",
    $id
);

mysqli_stmt_execute(
    $stmtLiberacoes
);

$resultadoLiberacoes =
    mysqli_stmt_get_result(
        $stmtLiberacoes
    );

$dadosLiberacoes =
    mysqli_fetch_assoc(
        $resultadoLiberacoes
    );

mysqli_stmt_close(
    $stmtLiberacoes
);

$possuiLiberacao =
    intval(
        $dadosLiberacoes[
            "total_registros"
        ]
    ) > 0;

if ($possuiLiberacao) {

    if (
        $produto_id !== intval(
            $producaoAtual["produto_id"]
        )
        ||
        $terceirizado_id !== intval(
            $producaoAtual[
                "terceirizado_id"
            ]
        )
        ||
        $quantidade !== intval(
            $producaoAtual["quantidade"]
        )
    ) {

        header(
            "Location: editar.php?id="
            . $id
            . "&erro=bloqueado"
        );

        exit;
    }
}

$sqlProduto = "
    SELECT id
    FROM produtos
    WHERE id = ?
    LIMIT 1
";

$stmtProduto = mysqli_prepare(
    $conexao,
    $sqlProduto
);

if (!$stmtProduto) {

    header(
        "Location: editar.php?id="
        . $id
        . "&erro=produto"
    );

    exit;
}

mysqli_stmt_bind_param(
    $stmtProduto,
    "i",
    $produto_id
);

mysqli_stmt_execute(
    $stmtProduto
);

$resultadoProduto =
    mysqli_stmt_get_result(
        $stmtProduto
    );

if (
    mysqli_num_rows(
        $resultadoProduto
    ) !== 1
) {

    mysqli_stmt_close(
        $stmtProduto
    );

    header(
        "Location: editar.php?id="
        . $id
        . "&erro=produto"
    );

    exit;
}

mysqli_stmt_close(
    $stmtProduto
);

$sqlTerceirizado = "
    SELECT id
    FROM terceirizados
    WHERE id = ?
    LIMIT 1
";

$stmtTerceirizado = mysqli_prepare(
    $conexao,
    $sqlTerceirizado
);

if (!$stmtTerceirizado) {

    header(
        "Location: editar.php?id="
        . $id
        . "&erro=terceirizado"
    );

    exit;
}

mysqli_stmt_bind_param(
    $stmtTerceirizado,
    "i",
    $terceirizado_id
);

mysqli_stmt_execute(
    $stmtTerceirizado
);

$resultadoTerceirizado =
    mysqli_stmt_get_result(
        $stmtTerceirizado
    );

if (
    mysqli_num_rows(
        $resultadoTerceirizado
    ) !== 1
) {

    mysqli_stmt_close(
        $stmtTerceirizado
    );

    header(
        "Location: editar.php?id="
        . $id
        . "&erro=terceirizado"
    );

    exit;
}

mysqli_stmt_close(
    $stmtTerceirizado
);

$sql = "
    UPDATE producao
    SET
        produto_id = ?,
        terceirizado_id = ?,
        quantidade = ?,
        data_envio = ?,
        previsao_entrega = ?,
        observacoes = ?
    WHERE id = ?
";

$stmt = mysqli_prepare(
    $conexao,
    $sql
);

if (!$stmt) {

    header(
        "Location: editar.php?id="
        . $id
        . "&erro=banco"
    );

    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "iiisssi",
    $produto_id,
    $terceirizado_id,
    $quantidade,
    $data_envio,
    $previsao_entrega,
    $observacoes,
    $id
);

if (
    mysqli_stmt_execute(
        $stmt
    )
) {

    mysqli_stmt_close(
        $stmt
    );

    header(
        "Location: listar.php?sucesso=atualizado"
    );

    exit;
}

mysqli_stmt_close(
    $stmt
);

header(
    "Location: editar.php?id="
    . $id
    . "&erro=banco"
);

exit;
?>