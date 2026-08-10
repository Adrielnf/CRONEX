<?php

$base = "../";

require_once "../includes/autenticar.php";

$conexao = require "../includes/conexao.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: listar.php");
    exit;
}

function campo($nome) {
    return isset($_POST[$nome]) ? trim($_POST[$nome]) : "";
}

$id = campo("id");

$razao_social = campo("razao_social");
$cnpj = campo("cnpj");
$inscricao_estadual = campo("inscricao_estadual");
$responsavel = campo("responsavel");

$telefone = campo("telefone");
$whatsapp = campo("whatsapp");
$email = campo("email");

$cep = campo("cep");
$logradouro = campo("logradouro");
$numero = campo("numero");
$complemento = campo("complemento");
$bairro = campo("bairro");
$cidade = campo("cidade");
$uf = campo("uf");

$funcionarios = campo("funcionarios");
$dias_trabalho = campo("dias_trabalho");
$jornada_minutos = campo("jornada_minutos");
$produtividade = campo("produtividade");
$capacidade_dia = campo("capacidade_dia");
$tempo_entrega = campo("tempo_entrega");
$aceita_urgencia = campo("urgencia");

$processos = campo("processos");
$maquinas = campo("maquinas");

$forma_pagamento = campo("forma_pagamento");
$condicao_pagamento = campo("condicao_pagamento");
$banco = campo("banco");
$agencia = campo("agencia");
$conta = campo("conta");
$pix = campo("pix");
$favorecido = campo("favorecido");
$valor_minuto = campo("valor_minuto");

$status = campo("status");

$sql = "UPDATE terceirizados SET
    razao_social = ?,
    cnpj = ?,
    inscricao_estadual = ?,
    responsavel = ?,
    telefone = ?,
    whatsapp = ?,
    email = ?,
    cep = ?,
    logradouro = ?,
    numero = ?,
    complemento = ?,
    bairro = ?,
    cidade = ?,
    uf = ?,
    funcionarios = ?,
    dias_trabalho = ?,
    jornada_minutos = ?,
    produtividade = ?,
    capacidade_dia = ?,
    tempo_entrega = ?,
    aceita_urgencia = ?,
    processos = ?,
    maquinas = ?,
    forma_pagamento = ?,
    condicao_pagamento = ?,
    banco = ?,
    agencia = ?,
    conta = ?,
    pix = ?,
    favorecido = ?,
    valor_minuto = ?,
    status = ?
WHERE id = ?";

$stmt = mysqli_prepare($conexao, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssssssssssssssssssssssssi",
    $razao_social,
    $cnpj,
    $inscricao_estadual,
    $responsavel,
    $telefone,
    $whatsapp,
    $email,
    $cep,
    $logradouro,
    $numero,
    $complemento,
    $bairro,
    $cidade,
    $uf,
    $funcionarios,
    $dias_trabalho,
    $jornada_minutos,
    $produtividade,
    $capacidade_dia,
    $tempo_entrega,
    $aceita_urgencia,
    $processos,
    $maquinas,
    $forma_pagamento,
    $condicao_pagamento,
    $banco,
    $agencia,
    $conta,
    $pix,
    $favorecido,
    $valor_minuto,
    $status,
    $id
);

if (mysqli_stmt_execute($stmt)) {
    header("Location: listar.php?sucesso=atualizado");
    exit;
} else {
    echo "Erro ao atualizar terceirizado: " . mysqli_error($conexao);
}

?>