<?php

$base = "../";

require_once "../includes/autenticar.php";

$conexao = require "../includes/conexao.php";

if (!isset($_GET["id"])) {
    header("Location: listar.php");
    exit;
}

$id = $_GET["id"];

$sql = "SELECT * FROM terceirizados WHERE id = $id";
$resultado = mysqli_query($conexao, $sql);

if (mysqli_num_rows($resultado) == 0) {
    header("Location: listar.php");
    exit;
}

$terceirizado = mysqli_fetch_assoc($resultado);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronex - Editar Terceirizado</title>

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
        <h1>Editar Terceirizado</h1>
        <p>Atualize os dados do parceiro selecionado.</p>
    </div>

    <div class="user-box">
        <span>Administrador</span>
    </div>
</header>

<section class="panel">

<div class="panel-header">
    <h2>Editar Cadastro</h2>
    <a href="listar.php">Ver terceirizados</a>
</div>

<div class="tabs">
    <button class="tab-btn active" onclick="abrirAba(event, 'dados')">Dados gerais</button>
    <button class="tab-btn" onclick="abrirAba(event, 'capacidade')">Capacidade produtiva</button>
    <button class="tab-btn" onclick="abrirAba(event, 'financeiro')">Financeiro</button>
</div>

<form action="atualizar.php" method="post" class="form-cronex">

<input type="hidden" name="id" value="<?= $terceirizado['id'] ?>">

<div id="dados" class="tab-content active">

    <div class="form-row">
        <div class="form-group">
            <label>Código</label>
            <input type="text" value="<?= $terceirizado['codigo'] ?>" readonly>
        </div>

        <div class="form-group">
            <label>Razão social / Nome</label>
            <input type="text" name="razao_social" value="<?= $terceirizado['razao_social'] ?>" required>
        </div>

        <div class="form-group">
            <label>CPF/CNPJ</label>
            <input type="text" name="cnpj" value="<?= $terceirizado['cnpj'] ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Inscrição estadual</label>
            <input type="text" name="inscricao_estadual" value="<?= $terceirizado['inscricao_estadual'] ?>">
        </div>

        <div class="form-group">
            <label>Responsável</label>
            <input type="text" name="responsavel" value="<?= $terceirizado['responsavel'] ?>" required>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="Ativo" <?= $terceirizado['status'] == "Ativo" ? "selected" : "" ?>>Ativo</option>
                <option value="Inativo" <?= $terceirizado['status'] == "Inativo" ? "selected" : "" ?>>Inativo</option>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Telefone</label>
            <input type="text" name="telefone" value="<?= $terceirizado['telefone'] ?>">
        </div>

        <div class="form-group">
            <label>WhatsApp</label>
            <input type="text" name="whatsapp" value="<?= $terceirizado['whatsapp'] ?>">
        </div>

        <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" value="<?= $terceirizado['email'] ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>CEP</label>
            <input type="text" name="cep" value="<?= $terceirizado['cep'] ?>">
        </div>

        <div class="form-group">
            <label>Endereço</label>
            <input type="text" name="logradouro" value="<?= $terceirizado['logradouro'] ?>">
        </div>

        <div class="form-group">
            <label>Número</label>
            <input type="text" name="numero" value="<?= $terceirizado['numero'] ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Complemento</label>
            <input type="text" name="complemento" value="<?= $terceirizado['complemento'] ?>">
        </div>

        <div class="form-group">
            <label>Bairro</label>
            <input type="text" name="bairro" value="<?= $terceirizado['bairro'] ?>">
        </div>

        <div class="form-group">
            <label>Cidade</label>
            <input type="text" name="cidade" value="<?= $terceirizado['cidade'] ?>">
        </div>

        <div class="form-group">
            <label>UF</label>
            <select name="uf">
                <option value="RJ" <?= $terceirizado['uf'] == "RJ" ? "selected" : "" ?>>RJ</option>
                <option value="SP" <?= $terceirizado['uf'] == "SP" ? "selected" : "" ?>>SP</option>
                <option value="MG" <?= $terceirizado['uf'] == "MG" ? "selected" : "" ?>>MG</option>
                <option value="ES" <?= $terceirizado['uf'] == "ES" ? "selected" : "" ?>>ES</option>
                <option value="PR" <?= $terceirizado['uf'] == "PR" ? "selected" : "" ?>>PR</option>
                <option value="SC" <?= $terceirizado['uf'] == "SC" ? "selected" : "" ?>>SC</option>
                <option value="RS" <?= $terceirizado['uf'] == "RS" ? "selected" : "" ?>>RS</option>
            </select>
        </div>
    </div>

</div>

<div id="capacidade" class="tab-content">

    <div class="form-row">
        <div class="form-group">
            <label>Quantidade de funcionários</label>
            <input type="number" name="funcionarios" value="<?= $terceirizado['funcionarios'] ?>" min="1">
        </div>

        <div class="form-group">
            <label>Dias trabalhados por semana</label>
            <input type="number" name="dias_trabalho" value="<?= $terceirizado['dias_trabalho'] ?>" min="1" max="7">
        </div>

        <div class="form-group">
            <label>Jornada diária (minutos)</label>
            <input type="number" name="jornada_minutos" value="<?= $terceirizado['jornada_minutos'] ?>" min="1">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Produtividade (%)</label>
            <input type="number" name="produtividade" value="<?= $terceirizado['produtividade'] ?>" min="1" max="100">
        </div>

        <div class="form-group">
            <label>Capacidade máxima por dia (peças)</label>
            <input type="number" name="capacidade_dia" value="<?= $terceirizado['capacidade_dia'] ?>" min="1">
        </div>

        <div class="form-group">
            <label>Tempo médio de entrega (dias)</label>
            <input type="number" name="tempo_entrega" value="<?= $terceirizado['tempo_entrega'] ?>" min="1">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Aceita urgência?</label>
            <select name="urgencia">
                <option value="Não" <?= $terceirizado['aceita_urgencia'] == "Não" ? "selected" : "" ?>>Não</option>
                <option value="Sim" <?= $terceirizado['aceita_urgencia'] == "Sim" ? "selected" : "" ?>>Sim</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label>Processos realizados</label>
        <textarea name="processos"><?= $terceirizado['processos'] ?></textarea>
    </div>

    <div class="form-group">
        <label>Máquinas disponíveis</label>
        <textarea name="maquinas"><?= $terceirizado['maquinas'] ?></textarea>
    </div>

</div>

<div id="financeiro" class="tab-content">

    <div class="form-row">
        <div class="form-group">
            <label>Forma de pagamento</label>
            <select name="forma_pagamento">
                <option value="PIX" <?= $terceirizado['forma_pagamento'] == "PIX" ? "selected" : "" ?>>PIX</option>
                <option value="Transferência" <?= $terceirizado['forma_pagamento'] == "Transferência" ? "selected" : "" ?>>Transferência</option>
                <option value="Dinheiro" <?= $terceirizado['forma_pagamento'] == "Dinheiro" ? "selected" : "" ?>>Dinheiro</option>
                <option value="Boleto" <?= $terceirizado['forma_pagamento'] == "Boleto" ? "selected" : "" ?>>Boleto</option>
            </select>
        </div>

        <div class="form-group">
            <label>Condição de pagamento</label>
            <select name="condicao_pagamento">
                <option value="Por produção" <?= $terceirizado['condicao_pagamento'] == "Por produção" ? "selected" : "" ?>>Por produção</option>
                <option value="Semanal" <?= $terceirizado['condicao_pagamento'] == "Semanal" ? "selected" : "" ?>>Semanal</option>
                <option value="Quinzenal" <?= $terceirizado['condicao_pagamento'] == "Quinzenal" ? "selected" : "" ?>>Quinzenal</option>
                <option value="Mensal" <?= $terceirizado['condicao_pagamento'] == "Mensal" ? "selected" : "" ?>>Mensal</option>
            </select>
        </div>

        <div class="form-group">
            <label>Valor por minuto (R$)</label>
            <input type="number" name="valor_minuto" value="<?= $terceirizado['valor_minuto'] ?>" step="0.01" min="0">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Banco</label>
            <input type="text" name="banco" value="<?= $terceirizado['banco'] ?>">
        </div>

        <div class="form-group">
            <label>Agência</label>
            <input type="text" name="agencia" value="<?= $terceirizado['agencia'] ?>">
        </div>

        <div class="form-group">
            <label>Conta</label>
            <input type="text" name="conta" value="<?= $terceirizado['conta'] ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Chave PIX</label>
            <input type="text" name="pix" value="<?= $terceirizado['pix'] ?>">
        </div>

        <div class="form-group">
            <label>Favorecido</label>
            <input type="text" name="favorecido" value="<?= $terceirizado['favorecido'] ?>">
        </div>
    </div>

</div>

<div class="form-actions">
    <button type="submit" class="btn-primary">Salvar Alterações</button>
    <a href="listar.php" class="btn-secondary">Cancelar</a>
</div>

</form>

</section>

</main>

</div>

<script>
function abrirAba(event, abaId) {
    const conteudos = document.querySelectorAll('.tab-content');
    const botoes = document.querySelectorAll('.tab-btn');

    conteudos.forEach(function(conteudo) {
        conteudo.classList.remove('active');
    });

    botoes.forEach(function(botao) {
        botao.classList.remove('active');
    });

    document.getElementById(abaId).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>

</body>
</html>