<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastrar.php");
    exit;
}

function campo($nome) {
    return isset($_POST[$nome]) ? trim($_POST[$nome]) : "";
}

$codigo = campo("codigo");

$data_cadastro = campo("data_cadastro");
$nome = campo("nome");
$observacoes = campo("observacoes");

$linha = campo("linha");
$categoria = campo("categoria");
$status = campo("status");

$cores = campo("cores");
$tamanhos = campo("tamanhos");

$tecido_principal = campo("tecido_principal");
$consumo_tecido = campo("consumo_tecido");
$linha_costura = campo("linha_costura");
$elastico = campo("elastico");
$bojo = campo("bojo");
$alca = campo("alca");
$fecho = campo("fecho");
$forro = campo("forro");
$etiqueta = campo("etiqueta");
$embalagem = campo("embalagem");

$tempo_medio = campo("tempo_medio");
$complexidade = campo("complexidade");
$etapas_producao = campo("etapas_producao");
$observacoes_processo = campo("observacoes_processo");

if (
    empty($data_cadastro) ||
    empty($nome) ||
    empty($linha) ||
    empty($categoria) ||
    empty($status)
) {
    header("Location: cadastrar.php?erro=campos");
    exit;
}

$sql = "
    INSERT INTO produtos (
        codigo,
        data_cadastro,
        nome,
        observacoes,
        linha,
        categoria,
        status,
        cores,
        tamanhos,
        tecido_principal,
        consumo_tecido,
        linha_costura,
        elastico,
        bojo,
        alca,
        fecho,
        forro,
        etiqueta,
        embalagem,
        tempo_medio,
        complexidade,
        etapas_producao,
        observacoes_processo
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?
    )
";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    echo "Erro ao preparar cadastro: " . mysqli_error($conexao);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "sssssssssssssssssssssss",
    $codigo,
    $data_cadastro,
    $nome,
    $observacoes,
    $linha,
    $categoria,
    $status,
    $cores,
    $tamanhos,
    $tecido_principal,
    $consumo_tecido,
    $linha_costura,
    $elastico,
    $bojo,
    $alca,
    $fecho,
    $forro,
    $etiqueta,
    $embalagem,
    $tempo_medio,
    $complexidade,
    $etapas_producao,
    $observacoes_processo
);

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header("Location: listar.php?sucesso=cadastrado");
    exit;
}

echo "Erro ao cadastrar produto: " . mysqli_stmt_error($stmt);

mysqli_stmt_close($stmt);

?>