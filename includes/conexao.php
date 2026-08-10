<?php

$servidor = "projintegrador.mysql.dbaas.com.br";
$usuario = "projintegrador";
$senha = "Pr0j3t01nt3gr@";
$banco = "projintegrador";

$conexao = mysqli_connect($servidor, $usuario, $senha, $banco);

if (!$conexao) {
    die("Erro ao conectar com o banco de dados: " . mysqli_connect_error());
}

    mysqli_set_charset($conexao, "utf8");

    return $conexao;

    ?>