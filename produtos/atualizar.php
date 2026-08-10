<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

/*
|--------------------------------------------------------------------------
| VERIFICAR MÉTODO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = isset($_POST["id"])
    ? intval($_POST["id"])
    : 0;

if ($id <= 0) {
    header("Location: listar.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| VERIFICAR SE O PRODUTO EXISTE
|--------------------------------------------------------------------------
*/

$sqlProduto = "
    SELECT id
    FROM produtos
    WHERE id = ?
";

$stmtProduto = mysqli_prepare(
    $conexao,
    $sqlProduto
);

if (!$stmtProduto) {
    header("Location: listar.php?erro=1");
    exit;
}

mysqli_stmt_bind_param(
    $stmtProduto,
    "i",
    $id
);

mysqli_stmt_execute($stmtProduto);

$resultadoProduto =
    mysqli_stmt_get_result($stmtProduto);

if (mysqli_num_rows($resultadoProduto) === 0) {

    mysqli_stmt_close($stmtProduto);

    header("Location: listar.php");
    exit;
}

mysqli_stmt_close($stmtProduto);

/*
|--------------------------------------------------------------------------
| CAMPOS PERMITIDOS
|--------------------------------------------------------------------------
|
| Somente estes campos podem ser alterados pelo formulário.
| O código do produto não é alterado.
|
*/

$camposPermitidos = [

    // Dados gerais
    "data_cadastro",
    "nome",
    "observacoes",

    // Classificação
    "linha",
    "categoria",
    "status",

    // Grade
    "cores",
    "tamanhos",

    // Materiais
    "tecido_principal",
    "consumo_tecido",
    "linha_costura",
    "elastico",
    "bojo",
    "alca",
    "fecho",
    "forro",
    "etiqueta",
    "embalagem",

    // Produção
    "tempo_medio",
    "complexidade",
    "etapas_producao",
    "observacoes_processo"

];

/*
|--------------------------------------------------------------------------
| PREPARAR CAMPOS PARA ATUALIZAÇÃO
|--------------------------------------------------------------------------
*/

$camposAtualizar = [];
$valores = [];

foreach ($camposPermitidos as $campo) {

    if (!isset($_POST[$campo])) {
        continue;
    }

    $valor = $_POST[$campo];

    if (is_array($valor)) {
        $valor = implode(", ", $valor);
    }

    $valor = trim($valor);

    $camposAtualizar[] = "$campo = ?";
    $valores[] = $valor;
}

/*
|--------------------------------------------------------------------------
| VERIFICAR SE EXISTEM CAMPOS
|--------------------------------------------------------------------------
*/

if (empty($camposAtualizar)) {

    header(
        "Location: editar.php?id=" .
        $id .
        "&erro=campos"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| MONTAR UPDATE
|--------------------------------------------------------------------------
*/

$sql = "
    UPDATE produtos
    SET " . implode(", ", $camposAtualizar) . "
    WHERE id = ?
";

$stmt = mysqli_prepare(
    $conexao,
    $sql
);

if (!$stmt) {

    header(
        "Location: editar.php?id=" .
        $id .
        "&erro=banco"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ADICIONAR ID
|--------------------------------------------------------------------------
*/

$valores[] = $id;

/*
|--------------------------------------------------------------------------
| TIPOS DOS PARÂMETROS
|--------------------------------------------------------------------------
|
| Os campos do formulário são tratados como string.
| O último parâmetro é o ID (inteiro).
|
*/

$tipos =
    str_repeat(
        "s",
        count($valores) - 1
    ) . "i";

/*
|--------------------------------------------------------------------------
| VINCULAR PARÂMETROS
|--------------------------------------------------------------------------
*/

mysqli_stmt_bind_param(
    $stmt,
    $tipos,
    ...$valores
);

/*
|--------------------------------------------------------------------------
| EXECUTAR
|--------------------------------------------------------------------------
*/

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header(
        "Location: listar.php?sucesso=atualizado"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($stmt);

header(
    "Location: editar.php?id=" .
    $id .
    "&erro=banco"
);

exit;

?>