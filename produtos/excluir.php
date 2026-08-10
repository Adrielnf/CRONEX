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

$sql = "UPDATE produtos SET status = 'Inativo' WHERE id = ?";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    header("Location: listar.php?erro=1");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header("Location: listar.php?sucesso=inativado");
    exit;
}

mysqli_stmt_close($stmt);

header("Location: listar.php?erro=1");
exit;
?>