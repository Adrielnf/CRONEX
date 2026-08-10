<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";
require_once "../includes/funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastrar.php");
    exit;
}

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

$status = isset($_POST["status"])
    ? trim($_POST["status"])
    : "Em produção";

$observacoes = isset($_POST["observacoes"])
    ? trim($_POST["observacoes"])
    : "";

if (
    $produto_id <= 0 ||
    $terceirizado_id <= 0 ||
    $quantidade <= 0 ||
    empty($data_envio)
) {
    header("Location: cadastrar.php?erro=campos");
    exit;
}

$statusPermitidos = [
    "Aguardando",
    "Em produção"
];

if (!in_array($status, $statusPermitidos)) {
    $status = "Em produção";
}

$sqlProduto = "
    SELECT
        id,
        tempo_medio,
        tempo_embalagem,
        consumo_tecido
    FROM produtos
    WHERE id = ?
    AND status = 'Ativo'
";

$stmtProduto = mysqli_prepare($conexao, $sqlProduto);

if (!$stmtProduto) {
    header("Location: cadastrar.php?erro=banco");
    exit;
}

mysqli_stmt_bind_param(
    $stmtProduto,
    "i",
    $produto_id
);

mysqli_stmt_execute($stmtProduto);

$resultadoProduto = mysqli_stmt_get_result($stmtProduto);

if (mysqli_num_rows($resultadoProduto) === 0) {
    mysqli_stmt_close($stmtProduto);

    header("Location: cadastrar.php?erro=produto");
    exit;
}

$produto = mysqli_fetch_assoc($resultadoProduto);

mysqli_stmt_close($stmtProduto);

$sqlTerceirizado = "
    SELECT
        id,
        funcionarios,
        dias_trabalho,
        jornada_minutos,
        produtividade,
        tempo_entrega
    FROM terceirizados
    WHERE id = ?
    AND status = 'Ativo'
";

$stmtTerceirizado = mysqli_prepare(
    $conexao,
    $sqlTerceirizado
);

if (!$stmtTerceirizado) {
    header("Location: cadastrar.php?erro=banco");
    exit;
}

mysqli_stmt_bind_param(
    $stmtTerceirizado,
    "i",
    $terceirizado_id
);

mysqli_stmt_execute($stmtTerceirizado);

$resultadoTerceirizado = mysqli_stmt_get_result(
    $stmtTerceirizado
);

if (mysqli_num_rows($resultadoTerceirizado) === 0) {
    mysqli_stmt_close($stmtTerceirizado);

    header("Location: cadastrar.php?erro=terceirizado");
    exit;
}

$terceirizado = mysqli_fetch_assoc(
    $resultadoTerceirizado
);

mysqli_stmt_close($stmtTerceirizado);

$tempoMedio = !empty($produto["tempo_medio"])
    ? floatval($produto["tempo_medio"])
    : 0;

$tempoEmbalagem = !empty($produto["tempo_embalagem"])
    ? floatval($produto["tempo_embalagem"])
    : 0;

$funcionarios = !empty($terceirizado["funcionarios"])
    ? intval($terceirizado["funcionarios"])
    : 1;

$diasTrabalho = !empty($terceirizado["dias_trabalho"])
    ? intval($terceirizado["dias_trabalho"])
    : 5;

$jornadaMinutos = !empty($terceirizado["jornada_minutos"])
    ? intval($terceirizado["jornada_minutos"])
    : 480;

$produtividade = !empty($terceirizado["produtividade"])
    ? floatval($terceirizado["produtividade"])
    : 100;

$tempoEntrega = !empty($terceirizado["tempo_entrega"])
    ? intval($terceirizado["tempo_entrega"])
    : 0;

if ($tempoMedio <= 0) {
    header("Location: cadastrar.php?erro=calculo");
    exit;
}

$calculo = calcularPrevisaoComFila(
    $conexao,
    $terceirizado_id,
    $quantidade,
    $tempoMedio,
    $tempoEmbalagem,
    $funcionarios,
    $jornadaMinutos,
    $produtividade,
    $diasTrabalho,
    $data_envio,
    $tempoEntrega
);

$previsao_entrega = $calculo["previsao_entrega"];

if (empty($previsao_entrega)) {
    header("Location: cadastrar.php?erro=calculo");
    exit;
}

$codigo = gerarCodigo(
    $conexao,
    "producao",
    "PRO"
);

$sql = "
    INSERT INTO producao (
        codigo,
        produto_id,
        terceirizado_id,
        quantidade,
        data_envio,
        previsao_entrega,
        status,
        observacoes
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    header("Location: cadastrar.php?erro=banco");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "siiissss",
    $codigo,
    $produto_id,
    $terceirizado_id,
    $quantidade,
    $data_envio,
    $previsao_entrega,
    $status,
    $observacoes
);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: listar.php?sucesso=cadastrado");
    exit;
}

mysqli_stmt_close($stmt);

header("Location: cadastrar.php?erro=banco");
exit;

?>