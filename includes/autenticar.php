<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["perfil"])
) {
    header("Location: " . $base . "index.php?erro=acesso");
    exit;
}

if (
    empty($_SESSION["usuario_id"]) ||
    empty($_SESSION["perfil"])
) {
    session_unset();
    session_destroy();

    header("Location: " . $base . "index.php?erro=acesso");
    exit;
}

function exigirPerfil($perfisPermitidos)
{
    global $base;

    if (!is_array($perfisPermitidos)) {
        $perfisPermitidos = [$perfisPermitidos];
    }

    if (
        !isset($_SESSION["perfil"]) ||
        !in_array(
            $_SESSION["perfil"],
            $perfisPermitidos,
            true
        )
    ) {
        header("Location: " . $base . "index.php?erro=permissao");
        exit;
    }
}