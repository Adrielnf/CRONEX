<?php

$base = "../";

require_once "../includes/autenticar.php";

$conexao = require "../includes/conexao.php";

if (!isset($_GET["id"])) {
    header("Location: listar.php");
    exit;
}

$id = $_GET["id"];

$sql = "UPDATE terceirizados SET status = 'Inativo' WHERE id = ?";

$stmt = mysqli_prepare($conexao, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: listar.php?sucesso=inativado");
    exit;
} else {
    echo "Erro ao inativar terceirizado: " . mysqli_error($conexao);
}

?>