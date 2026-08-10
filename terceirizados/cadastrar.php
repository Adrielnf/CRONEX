<?php

$base = "../";

require_once "../includes/autenticar.php";

$acaoFormulario = "salvar.php";
$textoBotao = "Salvar Terceirizado";

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronex - Cadastro de Terceirizados</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="app">

<?php
$base = "../";
include "../includes/menu.php";
?>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Cadastro de Terceirizados</h1>
                <p>Registre os parceiros responsáveis pela produção.</p>
            </div>

            <div class="user-box">
                <span>Administrador</span>
            </div>
        </header>

        <section class="panel">

            <div class="panel-header">
                <h2>Novo Terceirizado</h2>
                <a href="listar.php">Ver terceirizados</a>
            </div>

            <?php include "formulario.php"; ?>

        </section>

    </main>

</div>

<script src="../js/script.js"></script>

</body>

</html>