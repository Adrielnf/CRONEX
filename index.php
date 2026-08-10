<?php

$base = "";

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>CRONEX - Login</title>

    <link
        rel="stylesheet"
        href="css/style.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body class="login-body">

    <div class="login-container">

        <div class="login-left">

            <h1>
                CRONEX
            </h1>

            <p>
                Sistema inteligente para gestão da produção terceirizada.
            </p>

        </div>


        <div class="login-card">

            <h2>
                Acesso ao Sistema
            </h2>

            <p class="login-subtitle">
                Entre para gerenciar sua produção
            </p>


            <?php if (isset($_GET["erro"])): ?>

                <div class="login-error">

                    <?php

                    switch ($_GET["erro"]) {

                        case "campos":
                            echo "Preencha todos os campos.";
                            break;

                        case "login":
                            echo "Usuário ou senha incorretos.";
                            break;

                        case "inativo":
                            echo "Usuário inativo.";
                            break;

                        case "perfil":
                            echo "Perfil de usuário inválido.";
                            break;

                        case "vinculo":
                            echo "Usuário terceirizado sem vínculo.";
                            break;

                        case "banco":
                            echo "Erro ao conectar ao banco de dados.";
                            break;

                        case "acesso":
                            echo "Faça login para acessar o sistema.";
                            break;

                        case "permissao":
                            echo "Você não possui permissão para acessar esta página.";
                            break;

                        default:
                            echo "Não foi possível realizar o acesso.";

                    }

                    ?>

                </div>

            <?php endif; ?>


            <form
                action="login.php"
                method="post">


                <div class="form-group">

                    <label for="usuario">
                        Usuário
                    </label>

                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        placeholder="Digite seu usuário"
                        required>

                </div>


                <div class="form-group">

                    <label for="senha">
                        Senha
                    </label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        required>

                </div>


                <button
                    type="submit"
                    class="btn-login">

                    Entrar

                </button>


            </form>


            <p class="login-footer">
                Projeto Integrador - SENAI
            </p>

        </div>

    </div>

</body>

</html>