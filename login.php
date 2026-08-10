<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$conexao = require "includes/conexao.php";

$usuario = isset($_POST["usuario"])
    ? trim($_POST["usuario"])
    : "";

$senha = isset($_POST["senha"])
    ? $_POST["senha"]
    : "";

if ($usuario === "" || $senha === "") {
    header("Location: index.php?erro=campos");
    exit;
}

$sql = "
    SELECT
        id,
        nome,
        email,
        usuario,
        senha,
        perfil,
        terceirizado_id,
        status
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    header("Location: index.php?erro=banco");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $usuario
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) !== 1) {

    mysqli_stmt_close($stmt);

    header("Location: index.php?erro=login");
    exit;
}

$dadosUsuario = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

if ($dadosUsuario["status"] !== "Ativo") {
    header("Location: index.php?erro=inativo");
    exit;
}

if (!password_verify($senha, $dadosUsuario["senha"])) {
    header("Location: index.php?erro=login");
    exit;
}

if (
    !in_array(
        $dadosUsuario["perfil"],
        [
            "Administrador",
            "Terceirizada",
            "Coletor"
        ],
        true
    )
) {
    header("Location: index.php?erro=perfil");
    exit;
}

if (
    $dadosUsuario["perfil"] === "Terceirizada" &&
    empty($dadosUsuario["terceirizado_id"])
) {
    header("Location: index.php?erro=vinculo");
    exit;
}

session_regenerate_id(true);

$_SESSION["usuario_id"] =
    intval($dadosUsuario["id"]);

$_SESSION["nome"] =
    $dadosUsuario["nome"];

$_SESSION["usuario"] =
    $dadosUsuario["usuario"];

$_SESSION["perfil"] =
    $dadosUsuario["perfil"];

$_SESSION["terceirizado_id"] =
    !empty($dadosUsuario["terceirizado_id"])
        ? intval($dadosUsuario["terceirizado_id"])
        : null;

$sqlAcesso = "
    UPDATE usuarios
    SET ultimo_acesso = NOW()
    WHERE id = ?
";

$stmtAcesso = mysqli_prepare(
    $conexao,
    $sqlAcesso
);

if ($stmtAcesso) {

    mysqli_stmt_bind_param(
        $stmtAcesso,
        "i",
        $_SESSION["usuario_id"]
    );

    mysqli_stmt_execute($stmtAcesso);

    mysqli_stmt_close($stmtAcesso);
}

if ($_SESSION["perfil"] === "Administrador") {

    header("Location: dashboard.php");
    exit;
}

if ($_SESSION["perfil"] === "Terceirizada") {

    header("Location: portal_terceirizada/producoes.php");
    exit;
}

if ($_SESSION["perfil"] === "Coletor") {

    header("Location: coletor/coletas.php");
    exit;
}

session_unset();
session_destroy();

header("Location: index.php?erro=perfil");
exit;
?>