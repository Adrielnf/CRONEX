<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronex - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

    <div class="login-container">
        <div class="login-left">
            <h1>CRONEX</h1>
            <p>Sistema inteligente para gestão da produção terceirizada.</p>
        </div>

        <div class="login-card">
            <h2>Acesso ao Sistema</h2>
            <p class="login-subtitle">Entre para gerenciar sua produção</p>

            <form action="dashboard.php" method="post">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Digite seu e-mail"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        placeholder="Digite sua senha"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>

            <p class="login-footer">Projeto Integrador - SENAI</p>
        </div>
    </div>

</body>
</html>